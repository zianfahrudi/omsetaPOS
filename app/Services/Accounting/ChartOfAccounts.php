<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;

/**
 * Installs a standard Indonesian SME chart of accounts for a company.
 * Header accounts are non-postable; leaf accounts carry a `subtype` so the
 * rest of the system can resolve "the inventory account", "the AR account", etc.
 */
class ChartOfAccounts
{
    /**
     * Nested definition: [code, name, type, subtype|null, children[]].
     *
     * @var array<int, array{0:string,1:string,2:string,3:?string,4?:array}>
     */
    private const TREE = [
        ['1-0000', 'ASET', 'asset', null, [
            ['1-1000', 'Aset Lancar', 'asset', null, [
                ['1-1100', 'Kas', 'asset', 'cash'],
                ['1-1200', 'Bank', 'asset', 'bank'],
                ['1-1300', 'Piutang Usaha', 'asset', 'accounts_receivable'],
                ['1-1400', 'Persediaan Barang', 'asset', 'inventory'],
                ['1-1500', 'PPN Masukan', 'asset', 'tax_input'],
            ]],
            ['1-2000', 'Aset Tetap', 'asset', null, [
                ['1-2100', 'Peralatan', 'asset', 'fixed_asset'],
                ['1-2200', 'Akumulasi Penyusutan', 'asset', 'accumulated_depreciation'],
            ]],
        ]],
        ['2-0000', 'LIABILITAS', 'liability', null, [
            ['2-1000', 'Liabilitas Jangka Pendek', 'liability', null, [
                ['2-1100', 'Hutang Usaha', 'liability', 'accounts_payable'],
                ['2-1200', 'PPN Keluaran', 'liability', 'tax_output'],
                ['2-1300', 'Hutang Lain-lain', 'liability', null],
            ]],
        ]],
        ['3-0000', 'EKUITAS', 'equity', null, [
            ['3-1000', 'Modal Pemilik', 'equity', 'equity'],
            ['3-2000', 'Laba Ditahan', 'equity', 'retained_earnings'],
            ['3-3000', 'Ikhtisar Laba Rugi', 'equity', 'income_summary'],
        ]],
        ['4-0000', 'PENDAPATAN', 'revenue', null, [
            ['4-1000', 'Penjualan', 'revenue', 'sales'],
            ['4-2000', 'Retur Penjualan', 'revenue', 'sales_return'],
            ['4-3000', 'Diskon Penjualan', 'revenue', 'sales_discount'],
            ['4-9000', 'Pendapatan Lain-lain', 'revenue', 'other_income'],
        ]],
        ['5-0000', 'HARGA POKOK PENJUALAN', 'expense', null, [
            ['5-1000', 'Harga Pokok Penjualan', 'expense', 'cogs'],
        ]],
        ['6-0000', 'BEBAN', 'expense', null, [
            ['6-1000', 'Beban Operasional', 'expense', 'operating_expense'],
            ['6-2000', 'Beban Gaji', 'expense', null],
            ['6-3000', 'Beban Sewa', 'expense', null],
            ['6-4000', 'Beban Utilitas', 'expense', null],
            ['6-9000', 'Beban Lain-lain', 'expense', 'other_expense'],
        ]],
    ];

    /** Subtypes that the application relies on; protected from deletion. */
    private const SYSTEM_SUBTYPES = [
        'cash', 'bank', 'accounts_receivable', 'inventory', 'tax_input',
        'accounts_payable', 'tax_output', 'equity', 'retained_earnings',
        'income_summary', 'sales', 'sales_return', 'sales_discount', 'cogs',
    ];

    public function install(Company $company): void
    {
        foreach (self::TREE as $node) {
            $this->createNode($company, $node, null);
        }
    }

    /**
     * @param  array{0:string,1:string,2:string,3:?string,4?:array}  $node
     */
    private function createNode(Company $company, array $node, ?int $parentId): void
    {
        [$code, $name, $type, $subtype] = $node;
        $children = $node[4] ?? [];
        $hasChildren = $children !== [];

        $account = Account::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => $code],
            [
                'parent_id' => $parentId,
                'name' => $name,
                'type' => $type,
                'subtype' => $subtype,
                'normal_balance' => Account::normalBalanceFor($type),
                'is_postable' => ! $hasChildren,
                'is_system' => $subtype !== null && in_array($subtype, self::SYSTEM_SUBTYPES, true),
                'is_active' => true,
            ],
        );

        foreach ($children as $child) {
            $this->createNode($company, $child, $account->id);
        }
    }
}
