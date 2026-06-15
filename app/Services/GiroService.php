<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Giro;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Incoming giro/cheque lifecycle. Treated as settlement of a customer
 * receivable with an instrument that clears later.
 *
 *   receive: Dr Piutang Giro   Cr Piutang Usaha
 *   clear:   Dr Bank           Cr Piutang Giro
 *   reject:  Dr Piutang Usaha  Cr Piutang Giro
 */
class GiroService
{
    public function __construct(private readonly PostingService $posting) {}

    public function receive(
        Company $company,
        int $contactId,
        float $amount,
        Carbon|string|null $date = null,
        ?string $giroNumber = null,
        ?string $bankName = null,
        Carbon|string|null $dueDate = null,
        ?int $createdBy = null,
    ): Giro {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal giro harus lebih dari nol.');
        }

        $customer = Contact::query()->where('company_id', $company->id)->whereKey($contactId)
            ->firstOr(fn () => throw new InvalidArgumentException('Pelanggan tidak ditemukan.'));

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $customer, $amount, $date, $giroNumber, $bankName, $dueDate, $createdBy) {
            $giro = Giro::create([
                'company_id' => $company->id,
                'contact_id' => $customer->id,
                'number' => $this->number($company, $date),
                'giro_number' => $giroNumber,
                'bank_name' => $bankName,
                'date' => $date,
                'due_date' => $dueDate ? Carbon::parse($dueDate) : null,
                'amount' => $amount,
                'status' => 'received',
                'created_by' => $createdBy,
            ]);

            $this->post($company, $date, [
                ['account_id' => $this->account($company, 'giro_receivable'), 'debit' => $amount, 'memo' => 'Giro diterima'],
                ['account_id' => $this->account($company, 'accounts_receivable'), 'credit' => $amount, 'contact_id' => $customer->id, 'memo' => 'Pelunasan piutang via giro'],
            ], 'cash_receipt', "Giro masuk {$giro->number}", $giro, $createdBy);

            $customer->forceFill([
                'receivable_balance' => max(0, round((float) $customer->receivable_balance - $amount, 2)),
            ])->save();

            ActivityLogger::log('giro.received', "Giro {$giro->number} diterima", null, $giro, ['amount' => $amount]);

            return $giro->load('customer');
        });
    }

    public function deposit(Giro $giro): Giro
    {
        if ($giro->status !== 'received') {
            throw new InvalidArgumentException('Hanya giro berstatus diterima yang bisa disetor.');
        }

        $giro->forceFill(['status' => 'deposited'])->save();
        ActivityLogger::log('giro.deposited', "Giro {$giro->number} disetor", null, $giro, []);

        return $giro;
    }

    public function clear(Giro $giro, int $bankAccountId, Carbon|string|null $date = null): Giro
    {
        if (! $giro->isOpen()) {
            throw new InvalidArgumentException('Giro sudah selesai diproses.');
        }

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($giro, $bankAccountId, $date) {
            $company = $giro->company;
            $amount = (float) $giro->amount;

            $this->post($company, $date, [
                ['account_id' => $bankAccountId, 'debit' => $amount, 'memo' => 'Pencairan giro '.$giro->number],
                ['account_id' => $this->account($company, 'giro_receivable'), 'credit' => $amount, 'memo' => 'Giro cair'],
            ], 'cash_receipt', "Pencairan giro {$giro->number}", $giro, $giro->created_by);

            $giro->forceFill(['status' => 'cleared', 'cleared_bank_account_id' => $bankAccountId])->save();
            ActivityLogger::log('giro.cleared', "Giro {$giro->number} cair", null, $giro, ['amount' => $amount]);

            return $giro->fresh();
        });
    }

    public function reject(Giro $giro): Giro
    {
        if (! $giro->isOpen()) {
            throw new InvalidArgumentException('Giro sudah selesai diproses.');
        }

        return DB::transaction(function () use ($giro) {
            $company = $giro->company;
            $amount = (float) $giro->amount;

            $this->post($company, now(), [
                ['account_id' => $this->account($company, 'accounts_receivable'), 'debit' => $amount, 'contact_id' => $giro->contact_id, 'memo' => 'Giro ditolak, piutang kembali'],
                ['account_id' => $this->account($company, 'giro_receivable'), 'credit' => $amount, 'memo' => 'Giro ditolak'],
            ], 'adjustment', "Giro ditolak {$giro->number}", $giro, $giro->created_by);

            $giro->customer?->increment('receivable_balance', $amount);
            $giro->forceFill(['status' => 'rejected'])->save();
            ActivityLogger::log('giro.rejected', "Giro {$giro->number} ditolak", null, $giro, ['amount' => $amount]);

            return $giro->fresh();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function post(Company $company, Carbon $date, array $lines, string $type, string $desc, Giro $giro, ?int $by): void
    {
        $this->posting->post(
            company: $company,
            date: $date,
            lines: $lines,
            type: $type,
            description: $desc,
            reference: $giro->number,
            source: $giro,
            createdBy: $by,
        );
    }

    private function account(Company $company, string $subtype): int
    {
        $account = $company->account($subtype);
        if (! $account) {
            throw new InvalidArgumentException("Akun sistem '{$subtype}' belum dikonfigurasi.");
        }

        return $account->id;
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = Giro::query()->where('company_id', $company->id)
            ->whereYear('date', $date->year)->whereMonth('date', $date->month)->count() + 1;

        do {
            $number = sprintf('GIRO/%s/%04d', $period, $sequence);
            $sequence++;
        } while (Giro::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
