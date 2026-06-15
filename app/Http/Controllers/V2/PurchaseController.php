<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function orders(Request $request): View
    {
        $records = $this->base(PurchaseOrder::query()->with('supplier'), $request)->paginate(20)->withQueryString();

        return view('v2.purchase.orders', compact('records'));
    }

    public function invoices(Request $request): View
    {
        $records = $this->base(Purchase::query()->with('supplier'), $request)->paginate(20)->withQueryString();

        return view('v2.purchase.invoices', compact('records'));
    }

    public function invoiceShow(Purchase $invoice): View
    {
        $invoice->load(['items.product', 'supplier', 'payments']);

        return view('v2.purchase.invoice-show', compact('invoice'));
    }

    private function base($query, Request $request)
    {
        $company = Company::query()->first();

        return $query
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where('number', 'like', $like);
            })
            ->orderByDesc('date')
            ->orderByDesc('id');
    }
}
