<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $company = Company::query()->first();

        $accounts = Account::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('type')->value(), fn ($q, $type) => $q->where('type', $type))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like));
            })
            ->orderBy('code')
            ->get();

        $grouped = $accounts->groupBy('type');

        return view('v2.accounting.accounts', [
            'grouped' => $grouped,
            'types' => Account::TYPES,
            'typeLabels' => self::TYPE_LABELS,
        ]);
    }

    public const TYPE_LABELS = [
        'asset' => 'Aset',
        'liability' => 'Liabilitas',
        'equity' => 'Ekuitas',
        'revenue' => 'Pendapatan',
        'expense' => 'Beban',
    ];
}
