@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $qtyFmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $subtotal = $project->penawaranSubtotal();
    $regionLine = collect([$project->district?->name, $project->regency?->name, $project->province?->name])->filter()->implode(', ');
@endphp

<div style="font-family: Arial, Helvetica, sans-serif; color:#111; font-size:13px;">
    <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
        <tr>
            <td style="vertical-align:top;">
                <div style="font-size:18px; font-weight:bold;">{{ $company?->name ?: 'Perusahaan' }}</div>
                <div>{{ $company?->address }}</div>
                <div>{{ collect([$company?->phone, $company?->email])->filter()->implode(' · ') }}</div>
            </td>
            <td style="vertical-align:top; text-align:right;">
                <div style="font-size:16px; font-weight:bold;">PENAWARAN HARGA (RAB)</div>
                <div>No. Proyek: {{ $project->code ?: $project->id }}</div>
                <div>Tanggal: {{ now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:13px;">
        <tr>
            <td style="width:130px; color:#555;">Nama Proyek</td><td>: <strong>{{ $project->name }}</strong></td>
            <td style="width:110px; color:#555;">Status</td><td>: {{ $statusLabels[$project->status] ?? $project->status }}</td>
        </tr>
        <tr>
            <td style="color:#555;">Pelanggan</td><td>: {{ $project->customer?->name ?: '—' }}</td>
            <td style="color:#555;">Tanggal Mulai</td><td>: {{ $project->start_date?->format('d/m/Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td style="color:#555;">Lokasi</td><td>: {{ $regionLine ?: '—' }}</td>
            <td style="color:#555;">Tanggal Selesai</td><td>: {{ $project->end_date?->format('d/m/Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td style="color:#555; vertical-align:top;">Alamat</td><td colspan="3">: {{ $project->location ?: '—' }}</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; font-size:13px;" border="1" cellpadding="6">
        <thead>
            <tr style="background:#f1f5f9;">
                <th style="border:1px solid #cbd5e1; text-align:left;">No</th>
                <th style="border:1px solid #cbd5e1; text-align:left;">Uraian</th>
                <th style="border:1px solid #cbd5e1; text-align:right;">Qty</th>
                <th style="border:1px solid #cbd5e1; text-align:left;">Satuan</th>
                <th style="border:1px solid #cbd5e1; text-align:right;">Harga Satuan</th>
                <th style="border:1px solid #cbd5e1; text-align:right;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($project->costs->sortBy('type') as $i => $cost)
                <tr>
                    <td style="border:1px solid #cbd5e1;">{{ $loop->iteration }}</td>
                    <td style="border:1px solid #cbd5e1;">{{ $cost->product?->name ?: ($cost->description ?: '—') }} ({{ $costLabels[$cost->type] ?? $cost->type }})</td>
                    <td style="border:1px solid #cbd5e1; text-align:right;">{{ $qtyFmt($cost->quantity) }}</td>
                    <td style="border:1px solid #cbd5e1;">{{ $cost->unit ?: '—' }}</td>
                    <td style="border:1px solid #cbd5e1; text-align:right;">{{ $rp($cost->unit_cost) }}</td>
                    <td style="border:1px solid #cbd5e1; text-align:right;">{{ $rp($cost->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="border:1px solid #cbd5e1; text-align:center; color:#888;">Belum ada bahan / biaya.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="border:1px solid #cbd5e1; text-align:right; font-weight:bold;">Subtotal</td>
                <td style="border:1px solid #cbd5e1; text-align:right; font-weight:bold;">{{ $rp($subtotal) }}</td>
            </tr>
            <tr>
                <td colspan="5" style="border:1px solid #cbd5e1; text-align:right;">Overhead ({{ rtrim(rtrim(number_format((float) $project->overhead_percent, 2, ',', '.'), '0'), ',') }}%)</td>
                <td style="border:1px solid #cbd5e1; text-align:right;">{{ $rp($project->overheadAmount()) }}</td>
            </tr>
            <tr>
                <td colspan="5" style="border:1px solid #cbd5e1; text-align:right;">Profit ({{ rtrim(rtrim(number_format((float) $project->profit_percent, 2, ',', '.'), '0'), ',') }}%)</td>
                <td style="border:1px solid #cbd5e1; text-align:right;">{{ $rp($project->profitAmount()) }}</td>
            </tr>
            <tr style="background:#eef2ff;">
                <td colspan="5" style="border:1px solid #cbd5e1; text-align:right; font-weight:bold;">TOTAL PENAWARAN</td>
                <td style="border:1px solid #cbd5e1; text-align:right; font-weight:bold;">{{ $rp($project->totalPenawaran()) }}</td>
            </tr>
            @if ((float) $project->down_payment > 0 || $project->status === 'paid')
                <tr>
                    <td colspan="5" style="border:1px solid #cbd5e1; text-align:right;">DP Diterima</td>
                    <td style="border:1px solid #cbd5e1; text-align:right;">{{ $rp($project->down_payment) }}</td>
                </tr>
                <tr>
                    <td colspan="5" style="border:1px solid #cbd5e1; text-align:right; font-weight:bold;">Sisa Tagihan</td>
                    <td style="border:1px solid #cbd5e1; text-align:right; font-weight:bold;">{{ $rp($project->remainingBill()) }}</td>
                </tr>
            @endif
        </tfoot>
    </table>

    <table style="width:100%; margin-top:36px; font-size:13px;">
        <tr>
            <td style="text-align:center;">Hormat kami,<br><br><br><br>(_____________________)</td>
            <td style="text-align:center;">Menyetujui,<br><br><br><br>(_____________________)</td>
        </tr>
    </table>
</div>
