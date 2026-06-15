<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public const REASON_LABELS = [
        'opname' => 'Stok Opname',
        'damaged' => 'Rusak',
        'lost' => 'Hilang',
        'expired' => 'Kadaluarsa',
        'correction' => 'Koreksi',
    ];

    public function adjustments(Request $request): View
    {
        $company = Company::query()->first();

        $records = StockAdjustment::query()
            ->with(['product', 'warehouse'])
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where('number', 'like', $like);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('v2.inventory.adjustments', [
            'records' => $records,
            'reasonLabels' => self::REASON_LABELS,
        ]);
    }

    public function transfers(Request $request): View
    {
        $company = Company::query()->first();

        $records = StockTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'items'])
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where('number', 'like', $like);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('v2.inventory.transfers', compact('records'));
    }
}
