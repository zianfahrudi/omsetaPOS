<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FixedAsset;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Fixed asset register with straight-line depreciation.
 *
 * Depreciation: Dr Beban Penyusutan   Cr Akumulasi Penyusutan
 */
class FixedAssetService
{
    public function __construct(private readonly PostingService $posting) {}

    /**
     * Post one month of straight-line depreciation for an asset.
     */
    public function depreciate(FixedAsset $asset, Carbon|string|null $date = null): ?FixedAsset
    {
        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($asset, $date) {
            $asset = FixedAsset::query()->whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $company = $asset->company;

            $amount = min($asset->monthlyDepreciation(), $asset->remainingDepreciable());
            $amount = round($amount, 2);

            if ($amount <= 0) {
                throw new InvalidArgumentException('Aset sudah tersusut penuh.');
            }

            $expense = $asset->expense_account_id ?? $company->account('operating_expense')?->id;
            $accumulated = $asset->accumulated_account_id ?? $company->account('accumulated_depreciation')?->id;

            if (! $expense || ! $accumulated) {
                throw new InvalidArgumentException('Akun beban/akumulasi penyusutan belum dikonfigurasi.');
            }

            $this->posting->post(
                company: $company,
                date: $date,
                lines: [
                    ['account_id' => $expense, 'debit' => $amount, 'memo' => "Penyusutan {$asset->name}"],
                    ['account_id' => $accumulated, 'credit' => $amount, 'memo' => "Akumulasi penyusutan {$asset->name}"],
                ],
                type: 'adjustment',
                description: "Penyusutan {$asset->name}",
                reference: $asset->code ?? $asset->name,
                source: $asset,
                createdBy: $asset->created_by,
            );

            $asset->increment('accumulated_depreciation', $amount);
            $asset->forceFill([
                'last_depreciated_at' => $date,
                'status' => $asset->remainingDepreciable() - $amount <= 0 ? 'fully_depreciated' : 'active',
            ])->save();

            ActivityLogger::log('asset.depreciated', "Penyusutan {$asset->name}", null, $asset, [
                'amount' => $amount,
            ]);

            return $asset->fresh();
        });
    }
}
