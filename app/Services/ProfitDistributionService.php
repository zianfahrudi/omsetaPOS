<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Journal;
use App\Models\ProfitDistribution;
use App\Services\Accounting\PostingService;
use App\Services\Accounting\ReportService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Bagi hasil laba (profit sharing). Menghitung laba bersih periode dari laporan
 * laba rugi, membagi ke beberapa pihak sesuai persentase, lalu menjurnal:
 *   Dr Laba Ditahan (3-2000)   Cr Hutang Bagi Hasil (2-1400)
 * Pembayaran ke pihak penerima dilakukan terpisah lewat Kas Keluar terhadap
 * akun Hutang Bagi Hasil.
 */
class ProfitDistributionService
{
    public function __construct(
        private readonly PostingService $posting,
        private readonly ReportService $reports,
    ) {}

    /**
     * Laba bersih periode (acuan pembagian).
     */
    public function netIncomeFor(Company $company, Carbon|string $from, Carbon|string $to): float
    {
        return (float) $this->reports->incomeStatement($company, $from, $to)['net_income'];
    }

    /**
     * @param  array<int, array{name:string, percent:float|int|string}>  $shares
     */
    public function create(
        Company $company,
        Carbon|string $from,
        Carbon|string $to,
        float $baseAmount,
        array $shares,
        Carbon|string|null $date = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): ProfitDistribution {
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);
        $date = $date ? Carbon::parse($date) : now();

        if ($baseAmount <= 0) {
            throw new InvalidArgumentException('Laba yang dibagikan harus lebih dari nol.');
        }

        $shares = array_values(array_filter(
            $shares,
            fn ($s) => filled($s['name'] ?? null) && (float) ($s['percent'] ?? 0) > 0,
        ));
        if ($shares === []) {
            throw new InvalidArgumentException('Isi minimal satu pihak penerima dengan persentase.');
        }

        $totalPercent = round(array_sum(array_map(fn ($s) => (float) $s['percent'], $shares)), 2);
        if ($totalPercent > 100.0) {
            throw new InvalidArgumentException("Total persentase ({$totalPercent}%) tidak boleh melebihi 100%.");
        }

        $retained = $company->account('retained_earnings');
        $payable = $company->account('profit_sharing_payable');
        if (! $retained || ! $payable) {
            throw new InvalidArgumentException('Akun Laba Ditahan atau Hutang Bagi Hasil belum tersedia.');
        }

        return DB::transaction(function () use ($company, $from, $to, $date, $baseAmount, $shares, $notes, $createdBy, $retained, $payable) {
            $netIncome = $this->netIncomeFor($company, $from, $to);

            $distribution = ProfitDistribution::create([
                'company_id' => $company->id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'period_from' => $from,
                'period_to' => $to,
                'net_income' => $netIncome,
                'base_amount' => round($baseAmount, 2),
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            // Alokasi: pembulatan dijaga agar total sama persis dengan base_amount.
            $allocated = 0.0;
            $count = count($shares);
            foreach ($shares as $i => $share) {
                $percent = (float) $share['percent'];
                $amount = $i === $count - 1
                    ? round($baseAmount - $allocated, 2)
                    : round($baseAmount * $percent / 100, 2);
                $allocated = round($allocated + $amount, 2);

                $distribution->shares()->create([
                    'sort_order' => $i + 1,
                    'name' => $share['name'],
                    'percent' => $percent,
                    'amount' => $amount,
                ]);
            }

            // Jurnal: Dr Laba Ditahan, Cr Hutang Bagi Hasil.
            $this->posting->post(
                company: $company,
                date: $date,
                lines: [
                    ['account_id' => $retained->id, 'debit' => round($baseAmount, 2), 'memo' => "Bagi hasil {$distribution->number}"],
                    ['account_id' => $payable->id, 'credit' => round($baseAmount, 2), 'memo' => "Hutang bagi hasil {$distribution->number}"],
                ],
                type: 'general',
                description: "Bagi hasil laba {$distribution->number} ({$from->format('d/m/Y')}–{$to->format('d/m/Y')})",
                reference: $distribution->number,
                source: $distribution,
                createdBy: $createdBy,
            );

            ActivityLogger::log('profit_distribution.created', "Bagi hasil {$distribution->number}", null, $distribution, [
                'base_amount' => round($baseAmount, 2),
                'shares' => $distribution->shares->map(fn ($s) => [$s->name => (float) $s->amount])->all(),
            ]);

            return $distribution->load('shares');
        });
    }

    /**
     * Batalkan bagi hasil: hapus jurnal terkait + record.
     */
    public function delete(ProfitDistribution $distribution): void
    {
        DB::transaction(function () use ($distribution) {
            Journal::query()
                ->where('source_type', $distribution->getMorphClass())
                ->where('source_id', $distribution->id)
                ->get()
                ->each(function (Journal $journal) {
                    $journal->lines()->delete();
                    $journal->delete();
                });

            $number = $distribution->number;
            $distribution->shares()->delete();
            $distribution->delete();

            ActivityLogger::log('profit_distribution.deleted', "Bagi hasil {$number} dibatalkan");
        });
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = ProfitDistribution::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('BH/%s/%04d', $period, $sequence);
            $sequence++;
        } while (ProfitDistribution::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
