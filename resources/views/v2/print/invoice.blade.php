<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} {{ $number }}</title>
    @php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; color: #0f172a; background: #f1f5f9; padding: 24px; }
        .sheet { max-width: 760px; margin: 0 auto; background: #fff; padding: 36px; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 16px; }
        .head h1 { font-size: 18px; letter-spacing: .04em; }
        .company { font-size: 13px; color: #475569; line-height: 1.5; }
        .company .name { font-size: 16px; font-weight: 700; color: #0f172a; }
        .meta { margin-top: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 6px 24px; font-size: 13px; }
        .meta .label { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        th, td { padding: 9px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; color: #475569; font-weight: 600; }
        .r { text-align: right; }
        .totals { margin-top: 16px; margin-left: auto; width: 280px; font-size: 13px; }
        .totals .row { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .grand { border-top: 2px solid #0f172a; margin-top: 6px; padding-top: 8px; font-size: 15px; font-weight: 700; }
        .actions { max-width: 760px; margin: 16px auto 0; display: flex; gap: 8px; }
        .actions a, .actions button { flex: 0 0 auto; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; }
        .btn-print { background: #4f46e5; color: #fff; }
        .btn-back { background: #e2e8f0; color: #334155; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; max-width: 100%; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <div class="company">
                <div class="name">{{ $company?->name ?? 'Perusahaan' }}</div>
                @if ($company?->address)<div>{{ $company->address }}</div>@endif
                @if ($company?->phone)<div>{{ $company->phone }}</div>@endif
            </div>
            <div style="text-align:right;">
                <h1>{{ $title }}</h1>
                <div class="company" style="margin-top:6px;">{{ $number }}</div>
            </div>
        </div>

        <div class="meta">
            <div><span class="label">{{ $partnerLabel }}:</span> <strong>{{ $partnerName }}</strong></div>
            <div><span class="label">Tanggal:</span> {{ \Illuminate\Support\Carbon::parse($date)->format('d F Y') }}</div>
            @if ($ref)<div><span class="label">Referensi:</span> {{ $ref }}</div>@endif
            @if ($dueDate)<div><span class="label">Jatuh Tempo:</span> {{ \Illuminate\Support\Carbon::parse($dueDate)->format('d F Y') }}</div>@endif
        </div>

        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="r">Qty</th>
                    <th class="r">Harga</th>
                    <th class="r">Pajak</th>
                    <th class="r">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td class="r">{{ number_format($item['qty'], 0, ',', '.') }}</td>
                        <td class="r">{{ $rp($item['price']) }}</td>
                        <td class="r">{{ $rp($item['tax']) }}</td>
                        <td class="r">{{ $rp($item['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row"><span>Subtotal</span><span>{{ $rp($subtotal) }}</span></div>
            <div class="row"><span>Pajak</span><span>{{ $rp($taxTotal) }}</span></div>
            <div class="row grand"><span>Total</span><span>{{ $rp($grandTotal) }}</span></div>
            <div class="row"><span>Dibayar</span><span>{{ $rp($paid) }}</span></div>
            <div class="row"><span>Sisa</span><span>{{ $rp($outstanding) }}</span></div>
        </div>
    </div>

    <div class="actions">
        <a class="btn-back" href="{{ $backUrl }}">Kembali</a>
        <button class="btn-print" onclick="window.print()">Cetak</button>
    </div>
</body>
</html>
