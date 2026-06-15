@extends('v2.layouts.app')
@section('title', 'Penawaran Baru')
@section('heading', 'Penawaran Harga Baru')

@section('content')
    <x-v2.doc-form
        :action="route('v2.sales.quotations.store')"
        :back-url="route('v2.sales.quotations')"
        partner-label="Pelanggan" partner-field="contact_id" :partners="$customers"
        secondary-label="Berlaku s/d" secondary-field="valid_until"
        price-label="Harga Jual" price-field="unit_price"
        :products="$products"
    />
@endsection
