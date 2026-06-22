<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir - omsetaPOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('cashier/cashier.css') }}">
</head>

<body>
    <main class="shell cart-empty" data-cashier data-products-url="{{ route('cashier.products') }}"
        data-transactions-url="{{ route('cashier.transactions') }}"
        data-mark-paid-url="{{ route('cashier.transactions.mark-paid', ['sale' => 0]) }}"
        data-refunds-url="{{ route('cashier.refunds.store') }}" data-customers-url="{{ route('cashier.customers') }}"
        data-vehicles-url="{{ route('cashier.vehicles') }}"
        data-vehicles-store-url="{{ route('cashier.vehicles.store') }}"
        data-customers-store-url="{{ route('cashier.customers.store') }}"
        data-customer-check-url="{{ route('cashier.customers.check') }}"
        data-employees-url="{{ route('cashier.employees') }}"
        data-pricing-url="{{ route('cashier.pricing') }}" data-checkout-url="{{ route('cashier.checkout') }}"
        data-csrf="{{ csrf_token() }}">
        @include('cashier.partials.product-panel')

        @include('cashier.partials.cart-panel')

        @include('cashier.partials.summary-panel')
    </main>

    <div class="toast hidden" id="toast"></div>

    @include('cashier.partials.modal-receipt')

    @include('cashier.partials.modal-transaction')

    @include('cashier.partials.modal-refund')

    @include('cashier.partials.modal-refund-product')

    @include('cashier.partials.modal-customer')

    @include('cashier.partials.modal-vehicle')

    <script src="{{ asset('cashier/cashier.js') }}"></script>
</body>

</html>
