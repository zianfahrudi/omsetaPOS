@extends('v2.layouts.app')
@section('title', 'Daftar Hutang')
@section('heading', 'Daftar Hutang')

@section('content')
    <x-v2.aging :report="$report" :as-of="$asOf" :action="route('v2.purchase.payables')" partner-label="Pemasok" />
@endsection
