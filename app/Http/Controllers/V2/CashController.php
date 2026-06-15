<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashController extends Controller
{
    public const TYPE_LABELS = [
        'in' => 'Kas Masuk',
        'out' => 'Kas Keluar',
        'transfer' => 'Transfer',
    ];

    public function transactions(Request $request): View
    {
        $company = Company::query()->first();

        $records = CashTransaction::query()
            ->with(['account', 'toAccount'])
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('type')->value(), fn ($q, $type) => $q->where('type', $type))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('description', 'like', $like));
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('v2.cash.transactions', [
            'records' => $records,
            'typeLabels' => self::TYPE_LABELS,
        ]);
    }
}
