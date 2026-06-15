@props(['value' => null])

@php
    $v = (string) $value;
    $map = [
        // hijau — selesai/positif
        'posted' => 'emerald', 'invoiced' => 'emerald', 'received' => 'emerald',
        'ordered' => 'emerald', 'lunas' => 'emerald', 'paid' => 'emerald', 'completed' => 'emerald',
        // amber — proses/sebagian
        'draft' => 'amber', 'open' => 'amber', 'sebagian' => 'amber', 'partial' => 'amber', 'pending' => 'amber',
        // merah — batal/belum
        'cancelled' => 'rose', 'canceled' => 'rose', 'void' => 'rose', 'belum_lunas' => 'rose', 'overdue' => 'rose',
    ];
    $color = $map[$v] ?? 'slate';
    $labels = [
        'belum_lunas' => 'Belum Lunas', 'sebagian' => 'Sebagian', 'lunas' => 'Lunas',
        'posted' => 'Posted', 'draft' => 'Draft', 'invoiced' => 'Difakturkan',
        'ordered' => 'Dipesan', 'received' => 'Diterima', 'cancelled' => 'Batal', 'open' => 'Terbuka',
    ];
    $label = $labels[$v] ?? ($v === '' ? '—' : ucfirst($v));
    $classes = [
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'rose' => 'bg-rose-50 text-rose-700',
        'slate' => 'bg-slate-100 text-slate-600',
    ][$color];
@endphp

<span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $classes }}">{{ $label }}</span>
