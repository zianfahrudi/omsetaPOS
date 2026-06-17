@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $kontrak = $project->effectiveContractValue();
    $terms = $project->paymentTerms;
    $totalTerms = $project->totalTerms();
    $totalPaid = $project->totalPaidTerms();
    $sisa = $project->remainingBill();
    $regionLine = collect([$project->district?->name, $project->regency?->name, $project->province?->name])->filter()->implode(', ');
    $invoiceNo = 'INV/'.($project->code ?: $project->id).'/'.now()->format('Ymd');
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
                <div style="font-size:16px; font-weight:bold;">FAKTUR / INVOICE</div>
                <div>No. Faktur: {{ $invoiceNo }}</div>
                <div>Tanggal: {{ now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:13px;">
        <tr>
            <td style="width:130px; color:#555;">Kepada</td><td>: <strong>{{ $project->customer?->name ?: '—' }}</strong></td>
            <td style="width:110px; color:#555;">No. Proyek</td><td>: {{ $project->code ?: $project->id }}</td>
        </tr>
        <tr>
            <td style="color:#555;">Proyek</td><td>: {{ $project->name }}</td>
            <td style="color:#555;">Tanggal Mulai</td><td>: {{ $project->start_date?->format('d/m/Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td style="color:#555; vertical-align:top;">Lokasi</td><td colspan="3">: {{ collect([$project->location, $regionLine])->filter()->implode(' — ') ?: '—' }}</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; font-size:13px;" border="1" cellpadding="6">
        <thead>
            <tr style="background:#f1f5f9;">
                <th style="border:1px solid #cbd5e1; text-align:left;">No</th>
                <th style="border:1px solid #cbd5e1; text-align:left;">Uraian Tagihan</th>
                <th style="border:1px solid #cbd5e1; text-align:left;">Jatuh Tempo</th>
                <th style="border:1px solid #cbd5e1; text-align:center;">Status</th>
                <th style="border:1px solid #cbd5e1; text-align:right;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @if ($terms->isNotEmpty())
                @foreach ($terms as $i => $term)
                    <tr>
                        <td style="border:1px solid #cbd5e1;">{{ $i + 1 }}</td>
                        <td style="border:1px solid #cbd5e1;">{{ $term->name }}{{ $term->note ? ' — '.$term->note : '' }}</td>
                        <td style="border:1px solid #cbd5e1;">{{ $term->due_date?->format('d/m/Y') ?: '—' }}</td>
                        <td style="border:1px solid #cbd5e1; text-align:center;">{{ $term->is_paid ? 'Lunas' : 'Belum' }}</td>
                        <td style="border:1px solid #cbd5e1; text-align:right;">{{ $rp($term->amount) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td style="border:1px solid #cbd5e1;">1</td>
                    <td style="border:1px solid #cbd5e1;">Pekerjaan {{ $project->name }}</td>
                    <td style="border:1px solid #cbd5e1;">—</td>
                    <td style="border:1px solid #cbd5e1; text-align:center;">—</td>
                    <td style="border:1px solid #cbd5e1; text-align:right;">{{ $rp($kontrak) }}</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr style="background:#eef2ff;">
                <td colspan="4" style="border:1px solid #cbd5e1; text-align:right; font-weight:bold;">NILAI KONTRAK</td>
                <td style="border:1px solid #cbd5e1; text-align:right; font-weight:bold;">{{ $rp($kontrak) }}</td>
            </tr>
            @if ($terms->isNotEmpty())
            <tr>
                <td colspan="4" style="border:1px solid #cbd5e1; text-align:right;">Sudah Dibayar</td>
                <td style="border:1px solid #cbd5e1; text-align:right;">{{ $rp($totalPaid) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="4" style="border:1px solid #cbd5e1; text-align:right; font-weight:bold;">SISA TAGIHAN</td>
                <td style="border:1px solid #cbd5e1; text-align:right; font-weight:bold; color:#b91c1c;">{{ $rp($sisa) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top:18px; font-size:12px; color:#555;">
        <div style="font-weight:bold; color:#111;">Pembayaran ditujukan ke:</div>
        <div>{{ $company?->name ?: 'Perusahaan' }}{{ $company?->phone ? ' · '.$company->phone : '' }}</div>
    </div>

    <table style="width:100%; margin-top:36px; font-size:13px;">
        <tr>
            <td style="text-align:center;">Penerima,<br><br><br><br>(_____________________)</td>
            <td style="text-align:center;">Hormat kami,<br><br><br><br>(_____________________)</td>
        </tr>
    </table>
</div>
