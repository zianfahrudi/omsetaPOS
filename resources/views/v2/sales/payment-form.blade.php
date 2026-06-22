@extends('v2.layouts.app')
@section('title', 'Pembayaran Piutang')
@section('heading', 'Pembayaran Piutang')

@section('content')
    <x-v2.payment-form
        :action="route('v2.sales.invoices.payment.store', $invoice)"
        :back-url="route('v2.sales.invoices.show', $invoice)"
        :number="$invoice->number"
        partner-label="Pelanggan"
        :partner-name="$invoice->customer?->name ?? '—'"
        :grand-total="$invoice->grand_total"
        :paid="$invoice->paid_amount"
        :outstanding="$invoice->outstanding_amount"
        :accounts="$cashAccounts"
    />
@endsection
