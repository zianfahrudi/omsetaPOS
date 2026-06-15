<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FixedAsset;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\FixedAssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FixedAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_straight_line_depreciation_posts_journal(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        // Cost 12.000.000, salvage 0, life 12 months -> 1.000.000/month.
        $asset = FixedAsset::create([
            'company_id' => $company->id,
            'asset_account_id' => $company->account('fixed_asset')->id,
            'accumulated_account_id' => $company->account('accumulated_depreciation')->id,
            'expense_account_id' => $company->account('operating_expense')->id,
            'name' => 'Mesin',
            'acquisition_date' => now()->startOfYear(),
            'acquisition_cost' => 12000000,
            'salvage_value' => 0,
            'useful_life_months' => 12,
        ]);

        app(FixedAssetService::class)->depreciate($asset, now());

        $asset->refresh();
        $this->assertSame('1000000.00', (string) $asset->accumulated_depreciation);
        $this->assertSame(11000000.0, $asset->bookValue());

        $ledger = app(LedgerService::class);
        $this->assertSame(1000000.0, $ledger->balance($company->account('operating_expense')));
        // accumulated depreciation is an asset (contra) with debit-normal; credit posting -> negative balance
        $this->assertSame(-1000000.0, $ledger->balance($company->account('accumulated_depreciation')));
    }

    public function test_depreciation_stops_when_fully_depreciated(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $asset = FixedAsset::create([
            'company_id' => $company->id,
            'name' => 'Alat',
            'acquisition_date' => now(),
            'acquisition_cost' => 2000000,
            'salvage_value' => 0,
            'useful_life_months' => 2,
            'accumulated_depreciation' => 2000000,
            'status' => 'fully_depreciated',
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(FixedAssetService::class)->depreciate($asset, now());
    }
}
