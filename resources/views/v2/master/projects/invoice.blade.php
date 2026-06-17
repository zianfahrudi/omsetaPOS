<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Faktur · {{ $project->name }}</title>
    <style>
        body { margin:0; background:#f1f5f9; }
        .toolbar { position:sticky; top:0; display:flex; gap:8px; flex-wrap:wrap; align-items:center;
            background:#fff; border-bottom:1px solid #e2e8f0; padding:12px 16px; }
        .toolbar a, .toolbar button { font:500 13px Arial, sans-serif; border-radius:8px; padding:8px 14px;
            cursor:pointer; text-decoration:none; border:1px solid transparent; }
        .btn-print { background:#4f46e5; color:#fff; border:none; }
        .btn-back { background:#f1f5f9; color:#475569; }
        .sheet { max-width:820px; margin:20px auto; background:#fff; padding:32px;
            box-shadow:0 1px 4px rgba(0,0,0,.08); }
        @media print {
            .toolbar { display:none; }
            body { background:#fff; }
            .sheet { box-shadow:none; margin:0; max-width:none; padding:0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak / PDF</button>
        <a class="btn-back" href="{{ route('v2.projects.show', $project->id) }}">← Kembali</a>
    </div>
    <div class="sheet">
        @include('v2.master.projects.invoice-body')
    </div>
</body>
</html>
