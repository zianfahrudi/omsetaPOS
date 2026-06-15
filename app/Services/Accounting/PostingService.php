<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Core double-entry posting engine. Every financial document funnels through
 * here so the ledger is always balanced (total debit === total credit).
 */
class PostingService
{
    private const PREFIXES = [
        'general' => 'JU',
        'sales' => 'JJ',
        'purchase' => 'JB',
        'cash_receipt' => 'KM',
        'cash_payment' => 'KK',
        'inventory' => 'JP',
        'opening' => 'OB',
        'adjustment' => 'JP',
    ];

    /**
     * Post a balanced journal.
     *
     * @param  array<int, array{account_id:int, debit?:float|int|string, credit?:float|int|string, memo?:string|null, contact_id?:int|null, store_id?:int|null}>  $lines
     */
    public function post(
        Company $company,
        Carbon|string $date,
        array $lines,
        string $type = 'general',
        ?string $description = null,
        ?string $reference = null,
        ?Model $source = null,
        ?int $createdBy = null,
    ): Journal {
        $normalized = $this->normalizeLines($lines);

        if (count($normalized) < 2) {
            throw new InvalidArgumentException('Jurnal minimal terdiri dari 2 baris.');
        }

        return DB::transaction(function () use ($company, $date, $normalized, $type, $description, $reference, $source, $createdBy) {
            $accounts = $this->resolveAccounts($company, $normalized);

            $totalDebit = 0.0;
            $totalCredit = 0.0;

            foreach ($normalized as $line) {
                $totalDebit += $line['debit'];
                $totalCredit += $line['credit'];
            }

            if (round($totalDebit, 2) <= 0.0) {
                throw new InvalidArgumentException('Nilai jurnal harus lebih dari nol.');
            }

            if (bccomp(number_format($totalDebit, 2, '.', ''), number_format($totalCredit, 2, '.', ''), 2) !== 0) {
                throw new InvalidArgumentException(sprintf(
                    'Jurnal tidak seimbang: debit %s != kredit %s.',
                    number_format($totalDebit, 2),
                    number_format($totalCredit, 2),
                ));
            }

            $journal = Journal::create([
                'company_id' => $company->id,
                'number' => $this->generateNumber($company, $type, $date),
                'date' => $date,
                'type' => $type,
                'reference' => $reference,
                'description' => $description,
                'status' => 'posted',
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'created_by' => $createdBy,
                'posted_at' => now(),
            ]);

            foreach ($normalized as $line) {
                $journal->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'memo' => $line['memo'],
                    'contact_id' => $line['contact_id'],
                    'store_id' => $line['store_id'],
                ]);
            }

            return $journal->load('lines');
        });
    }

    /**
     * Reverse a posted journal by creating a mirror journal (debit <-> credit).
     */
    public function reverse(Journal $journal, Carbon|string|null $date = null, ?string $description = null): Journal
    {
        $journal->loadMissing('lines', 'company');

        $lines = $journal->lines->map(fn ($line) => [
            'account_id' => $line->account_id,
            'debit' => (float) $line->credit,
            'credit' => (float) $line->debit,
            'memo' => $line->memo,
            'contact_id' => $line->contact_id,
            'store_id' => $line->store_id,
        ])->all();

        return $this->post(
            company: $journal->company,
            date: $date ?? now()->toDateString(),
            lines: $lines,
            type: $journal->type,
            description: $description ?? "Pembalikan jurnal {$journal->number}",
            reference: $journal->number,
            source: $journal,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array{account_id:int, debit:float, credit:float, memo:?string, contact_id:?int, store_id:?int}>
     */
    private function normalizeLines(array $lines): array
    {
        $normalized = [];

        foreach ($lines as $index => $line) {
            $accountId = (int) ($line['account_id'] ?? 0);
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($accountId <= 0) {
                throw new InvalidArgumentException("Baris jurnal #{$index} tidak memiliki akun.");
            }

            if ($debit < 0 || $credit < 0) {
                throw new InvalidArgumentException("Baris jurnal #{$index} tidak boleh bernilai negatif.");
            }

            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException("Baris jurnal #{$index} tidak boleh mengisi debit dan kredit sekaligus.");
            }

            if ($debit === 0.0 && $credit === 0.0) {
                continue; // skip empty lines
            }

            $normalized[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $line['memo'] ?? null,
                'contact_id' => isset($line['contact_id']) ? (int) $line['contact_id'] : null,
                'store_id' => isset($line['store_id']) ? (int) $line['store_id'] : null,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{account_id:int}>  $lines
     * @return \Illuminate\Support\Collection<int, Account>
     */
    private function resolveAccounts(Company $company, array $lines)
    {
        $ids = collect($lines)->pluck('account_id')->unique();

        $accounts = Account::query()
            ->where('company_id', $company->id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $id) {
            $account = $accounts->get($id);

            if (! $account) {
                throw new InvalidArgumentException("Akun #{$id} tidak ditemukan di perusahaan ini.");
            }

            if (! $account->is_postable) {
                throw new InvalidArgumentException("Akun {$account->code} - {$account->name} adalah akun induk dan tidak bisa dijurnal.");
            }

            if (! $account->is_active) {
                throw new InvalidArgumentException("Akun {$account->code} - {$account->name} nonaktif.");
            }
        }

        return $accounts;
    }

    private function generateNumber(Company $company, string $type, Carbon|string $date): string
    {
        $prefix = self::PREFIXES[$type] ?? 'JU';
        $period = ($date instanceof Carbon ? $date : Carbon::parse($date))->format('Ym');

        $sequence = Journal::query()
            ->where('company_id', $company->id)
            ->where('type', $type)
            ->whereYear('date', (int) substr($period, 0, 4))
            ->whereMonth('date', (int) substr($period, 4, 2))
            ->lockForUpdate()
            ->count() + 1;

        do {
            $number = sprintf('%s/%s/%04d', $prefix, $period, $sequence);
            $sequence++;
        } while (Journal::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
