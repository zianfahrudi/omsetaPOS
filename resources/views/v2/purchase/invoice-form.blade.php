@extends('v2.layouts.app')
@section('title', 'Faktur Pembelian Baru')
@section('heading', 'Faktur Pembelian Baru')

@section('content')
    <x-v2.doc-form
        :action="route('v2.purchase.invoices.store')"
        :back-url="route('v2.purchase.invoices')"
        partner-label="Pemasok" partner-field="contact_id" :partners="$suppliers"
        ref-label="No. Faktur Pemasok" ref-field="supplier_invoice_no"
        secondary-label="Jatuh Tempo" secondary-field="due_date"
        :show-warehouse="true"
        price-label="Harga Beli" price-field="unit_cost"
        :products="$products" :warehouses="$warehouses"
        submit-label="Simpan & Posting"
    />
@endsection
