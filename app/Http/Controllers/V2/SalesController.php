<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function quotations(Request $request): View
    {
        $records = $this->base(SalesQuotation::query()->with('customer'), $request)->paginate(20)->withQueryString();

        return view('v2.sales.quotations', compact('records'));
    }

    public function orders(Request $request): View
    {
        $records = $this->base(SalesOrder::query()->with('customer'), $request)->paginate(20)->withQueryString();

        return view('v2.sales.orders', compact('records'));
    }

    public function invoices(Request $request): View
    {
        $records = $this->base(SalesInvoice::query()->with('customer'), $request)->paginate(20)->withQueryString();

        return view('v2.sales.invoices', compact('records'));
    }

    public function invoiceShow(SalesInvoice $invoice): View
    {
        $invoice->load(['items.product', 'customer', 'payments']);

        return view('v2.sales.invoice-show', compact('invoice'));
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
