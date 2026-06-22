<?php

namespace App\Services\Accounting;

use App\Models\Company;
use App\Models\Product;

/**
 * Memecah nilai penjualan menjadi kredit per akun pendapatan berdasarkan
 * kategori produk (Category->revenue_account_id). Produk tanpa pemetaan
 * kategori dikreditkan ke akun "Penjualan" default ($fallbackAccountId).
 */
class RevenueAccountResolver
{
    /**
     * @param  array<int, array{product_id:int|null, amount:float}>  $rows
     * @param  float|null  $reconcileTo  Pastikan total kredit tepat = nilai ini (perbaiki drift pembulatan ke akun default).
     * @return array<int, float> [account_id => amount]
     */
    public function split(Company $company, array $rows, int $fallbackAccountId, ?float $reconcileTo = null): array
    {
        // Petakan product_id → revenue_account_id kategori (bila ada & valid).
        $productIds = collect($rows)->pluck('product_id')->filter()->unique()->values();

        $accountByProduct = [];
        if ($productIds->isNotEmpty()) {
            $validAccountIds = $company->accounts()
                ->where('type', 'revenue')
                ->where('is_postable', true)
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            Product::query()
                ->with('category:id,revenue_account_id')
                ->whereIn('id', $productIds)
                ->get(['id', 'category_id'])
                ->each(function (Product $p) use (&$accountByProduct, $validAccountIds) {
                    $accId = $p->category?->revenue_account_id;
                    if ($accId !== null && in_array($accId, $validAccountIds, true)) {
                        $accountByProduct[$p->id] = $accId;
                    }
                });
        }

        $split = [];
        foreach ($rows as $row) {
            $amount = round((float) $row['amount'], 2);
            if ($amount === 0.0) {
                continue;
            }
            $accId = ($row['product_id'] !== null && isset($accountByProduct[$row['product_id']]))
                ? $accountByProduct[$row['product_id']]
                : $fallbackAccountId;

            $split[$accId] = round(($split[$accId] ?? 0.0) + $amount, 2);
        }

        // Rekonsiliasi drift pembulatan agar total persis = $reconcileTo.
        if ($reconcileTo !== null) {
            $sum = round(array_sum($split), 2);
            $drift = round($reconcileTo - $sum, 2);
            if (abs($drift) >= 0.01) {
                $split[$fallbackAccountId] = round(($split[$fallbackAccountId] ?? 0.0) + $drift, 2);
            }
        }

        // Buang entri nol hasil rekonsiliasi.
        return array_filter($split, fn ($amt) => abs($amt) >= 0.01);
    }
}
