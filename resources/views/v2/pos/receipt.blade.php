<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $sale->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', ui-monospace, monospace; background: #f1f5f9; color: #0f172a; padding: 20px; }
        .receipt { width: 320px; margin: 0 auto; background: #fff; padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        .center { text-align: center; }
        .muted { color: #64748b; }
        h1 { font-size: 16px; }
        .small { font-size: 12px; }
        hr { border: none; border-top: 1px dashed #cbd5e1; margin: 10px 0; }
        table { width: 100%; font-size: 12px; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .r { text-align: right; }
        .row { display: flex; justify-content: space-between; font-size: 12px; padding: 1px 0; }
        .row.total { font-weight: 700; font-size: 14px; margin-top: 4px; }
        .actions { width: 320px; margin: 14px auto 0; display: flex; gap: 8px; }
        .actions button, .actions a { flex: 1; text-align: center; padding: 10px; border-radius: 8px; font-family: sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; }
        .btn-print { background: #4f46e5; color: #fff; }
        .btn-back { background: #e2e8f0; color: #334155; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt { box-shadow: none; width: 100%; }
            .actions { display: none; }
        }
    </style>
</head>
@php($rp = fn ($v) => number_format((float) $v, 0, ',', '.'))
<body onload="window.matchMedia('print')">
    <div class="receipt">
        <div class="center">
            <h1>{{ $sale->store?->company?->name ?? $sale->store?->name }}</h1>
            <p class="small muted">{{ $sale->store?->name }}</p>
            @if ($sale->store?->address)<p class="small muted">{{ $sale->store->address }}</p>@endif
            @if ($sale->store?->phone)<p class="small muted">{{ $sale->store->phone }}</p>@endif
        </div>
        <hr>
        <div class="small">
            <div class="row"><span>No</span><span>{{ $sale->number }}</span></div>
            <div class="row"><span>Tanggal</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
            <div class="row"><span>Kasir</span><span>{{ $sale->cashier?->name }}</span></div>
            @if ($sale->customer_name)<div class="row"><span>Pelanggan</span><span>{{ $sale->customer_name }}</span></div>@endif
            @if ($sale->vehicle_plate_number)<div class="row"><span>Plat</span><span>{{ $sale->vehicle_plate_number }}</span></div>@endif
        </div>
        <hr>
        <table>
            @foreach ($sale->items as $item)
                <tr>
                    <td colspan="2">{{ $item->product_name }}</td>
                </tr>
                <tr>
                    <td class="muted">{{ (int) $item->quantity }} x {{ $rp($item->unit_price) }}</td>
                    <td class="r">{{ $rp($item->line_total) }}</td>
                </tr>
            @endforeach
        </table>
        <hr>
        <div class="row"><span>Subtotal</span><span>{{ $rp($sale->subtotal) }}</span></div>
        @if ((float) $sale->discount_total > 0)<div class="row"><span>Diskon</span><span>-{{ $rp($sale->discount_total) }}</span></div>@endif
        @if ((float) $sale->service_fee_total > 0)<div class="row"><span>Service</span><span>{{ $rp($sale->service_fee_total) }}</span></div>@endif
        @if ((float) $sale->tax_total > 0)<div class="row"><span>Pajak</span><span>{{ $rp($sale->tax_total) }}</span></div>@endif
        <div class="row total"><span>TOTAL</span><span>{{ $rp($sale->grand_total) }}</span></div>
        <div class="row"><span>Bayar ({{ strtoupper($sale->payment_method) }})</span><span>{{ $rp($sale->paid_amount) }}</span></div>
        <div class="row"><span>Kembali</span><span>{{ $rp($sale->change_amount) }}</span></div>
        @if ((float) $sale->debt_amount > 0)<div class="row"><span>Hutang</span><span>{{ $rp($sale->debt_amount) }}</span></div>@endif
        <hr>
        <p class="center small muted">Terima kasih atas kunjungan Anda 🙏</p>
        @if ($sale->status === 'void')<p class="center small" style="color:#ef4444;font-weight:700;">** TRANSAKSI DIBATALKAN **</p>@endif
    </div>
    <div class="actions">
        <a class="btn-back" href="{{ route('v2.pos.transactions.show', $sale) }}">Kembali</a>
        <button class="btn-print" onclick="window.print()">Cetak</button>
    </div>
</body>
</html>
