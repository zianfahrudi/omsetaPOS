<?php

namespace App\Services;

use App\Models\CashierSession;
use App\Models\Sale;
use App\Models\Store;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Cashier shift sessions: open with starting cash, close with counted cash;
 * the system computes expected cash (opening + cash sales) and the variance.
 */
class CashierSessionService
{
    public function open(Store $store, int $userId, float $openingCash, ?int $createdBy = null): CashierSession
    {
        $exists = CashierSession::query()
            ->where('store_id', $store->id)
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException('Masih ada sesi kasir yang terbuka. Tutup dulu sebelum membuka sesi baru.');
        }

        $session = CashierSession::create([
            'store_id' => $store->id,
            'user_id' => $userId,
            'number' => $this->number(),
            'opened_at' => now(),
            'opening_cash' => round($openingCash, 2),
            'status' => 'open',
        ]);

        ActivityLogger::log('cashier_session.opened', "Sesi kasir {$session->number} dibuka", $store->id, $session, [
            'opening_cash' => $session->opening_cash,
        ]);

        return $session;
    }

    public function close(CashierSession $session, float $countedCash, ?string $notes = null): CashierSession
    {
        if (! $session->isOpen()) {
            throw new InvalidArgumentException('Sesi kasir sudah ditutup.');
        }

        return DB::transaction(function () use ($session, $countedCash, $notes) {
            $closedAt = now();

            $cashSales = (float) Sale::query()
                ->where('store_id', $session->store_id)
                ->where('cashier_id', $session->user_id)
                ->where('payment_method', 'cash')
                ->where('is_debt', false)
                ->whereBetween('created_at', [$session->opened_at, $closedAt])
                ->sum('grand_total');

            $cashSales = round($cashSales, 2);
            $expected = round((float) $session->opening_cash + $cashSales, 2);
            $counted = round($countedCash, 2);

            $session->forceFill([
                'closed_at' => $closedAt,
                'cash_sales_total' => $cashSales,
                'expected_cash' => $expected,
                'closing_cash' => $counted,
                'cash_difference' => round($counted - $expected, 2),
                'status' => 'closed',
                'notes' => $notes,
            ])->save();

            ActivityLogger::log('cashier_session.closed', "Sesi kasir {$session->number} ditutup", $session->store_id, $session, [
                'expected_cash' => $expected,
                'closing_cash' => $counted,
                'difference' => $session->cash_difference,
            ]);

            return $session->fresh();
        });
    }

    private function number(): string
    {
        $period = now()->format('Ymd');
        $sequence = CashierSession::query()->whereDate('opened_at', today())->count() + 1;

        do {
            $number = sprintf('SES/%s/%04d', $period, $sequence);
            $sequence++;
        } while (CashierSession::query()->where('number', $number)->exists());

        return $number;
    }
}
