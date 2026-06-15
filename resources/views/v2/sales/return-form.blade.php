@extends('v2.layouts.app')
@section('title', 'Retur Penjualan')
@section('heading', 'Retur Penjualan')

@section('content')
    <x-v2.return-form
        :action="route('v2.sales.invoices.return.store', $invoice)"
        :back-url="route('v2.sales.invoices.show', $invoice)"
        :number="$invoice->number"
        :items="$invoice->items"
    />
@endsection
