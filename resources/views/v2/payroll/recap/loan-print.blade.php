<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Bon · {{ $periodLabel }}</title>
    @php
        $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
        $statusLabel = ['pending' => 'Belum Dipotong', 'deducted' => 'Dipotong Gaji', 'paid' => 'Lunas'];
    @endphp
    <style>
        body { margin:0; background:#f1f5f9; font-family:Arial, sans-serif; color:#0f172a; }
        .toolbar { position:sticky; top:0; display:flex; gap:8px; align-items:center; background:#fff; border-bottom:1px solid #e2e8f0; padding:12px 16px; }
        .toolbar button, .toolbar a { font:500 13px Arial; border-radius:8px; padding:8px 14px; cursor:pointer; text-decoration:none; border:1px solid transparent; }
        .btn-print { background:#4f46e5; color:#fff; }
        .btn-back { background:#f1f5f9; color:#475569; }
        .sheet { max-width:900px; margin:20px auto; background:#fff; padding:32px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
        h1 { font-size:18px; margin:0 0 2px; }
        .sub { color:#64748b; font-size:13px; margin:0 0 16px; }
        table { width:100%; border-collapse:collapse; font-size:12px; }
        th, td { border:1px solid #cbd5e1; padding:6px 8px; }
        thead th { background:#f1f5f9; text-align:left; }
        .num { text-align:right; }
        tfoot td { font-weight:bold; background:#f8fafc; }
        @media print { .toolbar { display:none; } body { background:#fff; } .sheet { box-shadow:none; margin:0; max-width:none; padding:0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak / PDF</button>
        <a class="btn-back" href="{{ route('v2.payrolls.recap.loan', ['month' => $month, 'employee_id' => $employeeId]) }}">← Kembali</a>
    </div>
    <div class="sheet">
        <h1>Rekap Bon / Kasbon Karyawan</h1>
        <p class="sub">{{ $company?->name }} — Periode {{ $periodLabel }}</p>
        <table>
            <thead>
                <tr><th>No</th><th>Tanggal</th><th>Karyawan</th><th>Keterangan</th><th class="num">Nominal</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse ($loans as $i => $l)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ optional($l->date)->format('d/m/Y') }}</td>
                        <td>{{ $l->employee?->name ?? '—' }} @if($l->employee?->code)({{ $l->employee->code }})@endif</td>
                        <td>{{ $l->description ?: '—' }}</td>
                        <td class="num">{{ $rp($l->amount) }}</td>
                        <td>{{ $statusLabel[$l->status] ?? ucfirst($l->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;">Tidak ada bon/kasbon untuk periode ini.</td></tr>
                @endforelse
            </tbody>
            @if ($loans->isNotEmpty())
                <tfoot>
                    <tr><td colspan="4">TOTAL</td><td class="num">{{ $rp($totals['amount']) }}</td><td></td></tr>
                </tfoot>
            @endif
        </table>
    </div>
</body>
</html>
