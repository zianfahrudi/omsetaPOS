@extends('v2.layouts.app')
@section('title', 'Retur Pembelian')
@section('heading', 'Retur Pembelian')

@section('content')
    <x-v2.return-form
        :action="route('v2.purchase.invoices.return.store', $invoice)"
        :back-url="route('v2.purchase.invoices.show', $invoice)"
        :number="$invoice->number"
        :items="$invoice->items"
    />
@endsection
