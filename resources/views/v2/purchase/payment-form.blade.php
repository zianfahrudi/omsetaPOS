@extends('v2.layouts.app')
@section('title', 'Pembayaran Hutang')
@section('heading', 'Pembayaran Hutang')

@section('content')
    <x-v2.payment-form
        :action="route('v2.purchase.invoices.payment.store', $invoice)"
        :back-url="route('v2.purchase.invoices.show', $invoice)"
        :number="$invoice->number"
        partner-label="Pemasok"
        :partner-name="$invoice->supplier?->name ?? '—'"
        :grand-total="$invoice->grand_total"
        :paid="$invoice->paid_amount"
        :outstanding="$invoice->outstanding_amount"
        :accounts="$cashAccounts"
    />
@endsection
