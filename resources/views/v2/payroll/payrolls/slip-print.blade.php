<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slip Gaji · {{ $payroll->employee?->name }}</title>
    @php
        $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
        $piece = $payroll->employee?->isPiecework();
    @endphp
    <style>
        body { margin:0; background:#f1f5f9; font-family:Arial, sans-serif; color:#0f172a; }
        .toolbar { position:sticky; top:0; display:flex; gap:8px; align-items:center; background:#fff; border-bottom:1px solid #e2e8f0; padding:12px 16px; }
        .toolbar button, .toolbar a { font:500 13px Arial; border-radius:8px; padding:8px 14px; cursor:pointer; text-decoration:none; border:1px solid transparent; }
        .btn-print { background:#4f46e5; color:#fff; }
        .btn-back { background:#f1f5f9; color:#475569; }
        .sheet { max-width:560px; margin:20px auto; background:#fff; padding:32px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
        .head { text-align:center; border-bottom:2px solid #0f172a; padding-bottom:12px; margin-bottom:16px; }
        .head h1 { font-size:18px; margin:0; letter-spacing:1px; }
        .head .co { font-size:14px; font-weight:bold; margin:0 0 2px; }
        .head .sub { color:#64748b; font-size:12px; margin:2px 0 0; }
        .info { font-size:13px; margin-bottom:16px; }
        .info p { margin:3px 0; }
        .info .lbl { display:inline-block; width:110px; color:#64748b; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        td { padding:6px 0; border-bottom:1px solid #f1f5f9; }
        .num { text-align:right; }
        .plus { color:#059669; }
        .minus { color:#e11d48; }
        .total td { border-top:2px solid #0f172a; border-bottom:none; padding-top:12px; font-weight:bold; font-size:16px; }
        .total .thp { color:#4f46e5; }
        .foot { margin-top:28px; display:flex; justify-content:space-between; font-size:12px; color:#64748b; }
        .sign { text-align:center; }
        .sign .line { margin-top:48px; border-top:1px solid #94a3b8; width:140px; }
        @media print { .toolbar { display:none; } body { background:#fff; } .sheet { box-shadow:none; margin:0; max-width:none; padding:0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak / PDF</button>
        @php $waUrl = $payroll->whatsappUrl($company?->name); @endphp
        @if ($waUrl)
            <a class="btn-back" style="background:#dcfce7;color:#047857;" href="{{ $waUrl }}" target="_blank">Kirim WhatsApp</a>
        @endif
        <a class="btn-back" href="{{ route('v2.payrolls.show', $payroll) }}">← Kembali</a>
    </div>

    <div class="sheet">
        <div class="head">
            <p class="co">{{ $company?->name ?? 'Perusahaan' }}</p>
            <h1>SLIP GAJI</h1>
            <p class="sub">Periode {{ $payroll->period_start->format('d/m/Y') }} – {{ $payroll->period_end->format('d/m/Y') }}</p>
        </div>

        <div class="info">
            <p><span class="lbl">Nama</span>: <strong>{{ $payroll->employee?->name }}</strong></p>
            <p><span class="lbl">Jabatan</span>: {{ $payroll->employee?->position ?: '—' }}</p>
            <p><span class="lbl">Tipe Gaji</span>: {{ $piece ? 'Borongan / Proyek' : 'Per Jam ('.$rp($payroll->employee?->hourly_rate).'/jam)' }}</p>
        </div>

        <table>
            @unless ($piece)
                <tr><td>Total Jam Kerja</td><td class="num">{{ number_format($payroll->total_hours, 2) }} jam</td></tr>
            @endunless
            <tr><td>{{ $piece ? 'Gaji Borongan/Proyek' : 'Gaji Kotor' }}</td><td class="num">{{ $rp($payroll->gross_salary) }}</td></tr>
            <tr><td class="plus">+ Bonus</td><td class="num plus">{{ $rp($payroll->total_bonus) }}</td></tr>
            @if ((float) $payroll->carry_over != 0)
                <tr><td class="plus">+ Sisa Gaji Kemarin</td><td class="num plus">{{ $rp($payroll->carry_over) }}</td></tr>
            @endif
            <tr><td class="minus">− Bon / Kasbon</td><td class="num minus">{{ $rp($payroll->total_loan) }}</td></tr>
            <tr><td class="minus">− Potongan</td><td class="num minus">{{ $rp($payroll->total_deduction) }}</td></tr>
            <tr><td class="minus">− Arisan</td><td class="num minus">{{ $rp($payroll->total_arisan) }}</td></tr>
            <tr><td class="minus">− Tabungan</td><td class="num minus">{{ $rp($payroll->total_savings) }}</td></tr>
            <tr class="total"><td>Take Home Pay</td><td class="num thp">{{ $rp($payroll->take_home_pay) }}</td></tr>
        </table>

        <div class="foot">
            <div class="sign">Diterima oleh,<div class="line"></div>{{ $payroll->employee?->name }}</div>
            <div class="sign">Hormat kami,<div class="line"></div>{{ $company?->name }}</div>
        </div>
    </div>
</body>
</html>
