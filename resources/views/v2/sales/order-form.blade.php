@extends('v2.layouts.app')
@section('title', 'Pesanan Penjualan Baru')
@section('heading', 'Pesanan Penjualan Baru')

@section('content')
    <x-v2.doc-form
        :action="route('v2.sales.orders.store')"
        :back-url="route('v2.sales.orders')"
        partner-label="Pelanggan" partner-field="contact_id" :partners="$customers"
        secondary-label="Estimasi Kirim" secondary-field="expected_date"
        price-label="Harga Jual" price-field="unit_price"
        :products="$products"
    />
@endsection
