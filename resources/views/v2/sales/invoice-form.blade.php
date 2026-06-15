@extends('v2.layouts.app')
@section('title', 'Faktur Penjualan Baru')
@section('heading', 'Faktur Penjualan Baru')

@section('content')
    <x-v2.doc-form
        :action="route('v2.sales.invoices.store')"
        :back-url="route('v2.sales.invoices')"
        partner-label="Pelanggan" partner-field="contact_id" :partners="$customers"
        ref-label="Ref. Pelanggan" ref-field="customer_ref"
        secondary-label="Jatuh Tempo" secondary-field="due_date"
        :show-warehouse="true"
        price-label="Harga Jual" price-field="unit_price"
        :products="$products" :warehouses="$warehouses"
        submit-label="Simpan & Posting"
    />
@endsection
