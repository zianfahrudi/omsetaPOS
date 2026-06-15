<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $company = Company::query()->first();

        $journals = Journal::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('reference', 'like', $like)->orWhere('description', 'like', $like));
            })
            ->when($request->string('type')->value(), fn ($q, $type) => $q->where('type', $type))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('v2.accounting.journals', compact('journals'));
    }

    public function show(Journal $journal): View
    {
        $journal->load(['lines.account', 'createdBy']);

        return view('v2.accounting.journal-show', compact('journal'));
    }
}
