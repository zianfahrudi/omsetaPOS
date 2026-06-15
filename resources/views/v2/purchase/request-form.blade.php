@extends('v2.layouts.app')
@section('title', 'Permintaan Pembelian Baru')
@section('heading', 'Permintaan Pembelian Baru')

@section('content')
    <x-v2.doc-form
        :action="route('v2.purchase.requests.store')"
        :back-url="route('v2.purchase.requests')"
        partner-label="Pemasok" partner-field="contact_id" :partners="$suppliers"
        secondary-label="Dibutuhkan" secondary-field="needed_date"
        price-label="Perkiraan Harga" price-field="unit_cost"
        :products="$products"
    />
@endsection
