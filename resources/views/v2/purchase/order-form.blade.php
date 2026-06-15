@extends('v2.layouts.app')
@section('title', 'Pesanan Pembelian Baru')
@section('heading', 'Pesanan Pembelian Baru')

@section('content')
    <x-v2.doc-form
        :action="route('v2.purchase.orders.store')"
        :back-url="route('v2.purchase.orders')"
        partner-label="Pemasok" partner-field="contact_id" :partners="$suppliers"
        secondary-label="Estimasi Terima" secondary-field="expected_date"
        price-label="Harga Beli" price-field="unit_cost"
        :products="$products"
    />
@endsection
