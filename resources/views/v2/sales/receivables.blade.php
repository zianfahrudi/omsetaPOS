@extends('v2.layouts.app')
@section('title', 'Daftar Piutang')
@section('heading', 'Daftar Piutang')

@section('content')
    <x-v2.aging :report="$report" :as-of="$asOf" :action="route('v2.sales.receivables')" partner-label="Pelanggan" />
@endsection
