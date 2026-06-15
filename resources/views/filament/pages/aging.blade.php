<x-filament-panels::page>
    @php($r = $this->report())
    @php($b = $r['buckets'])

    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            <div>
                <label style="display:block;font-weight:500;font-size:.875rem;margin-bottom:.5rem;">Perusahaan</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="companyId">
                        @foreach ($this->companies() as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <div>
                <label style="display:block;font-weight:500;font-size:.875rem;margin-bottom:.5rem;">Per Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="asOf" />
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;">
        @php($labels = ['current' => 'Belum Jatuh Tempo', '1_30' => '1-30 Hari', '31_60' => '31-60 Hari', '61_90' => '61-90 Hari', 'over_90' => '> 90 Hari'])
        @foreach ($labels as $key => $label)
            <x-filament::section>
                <div style="font-size:.8rem;color:var(--fi-color-gray-500,#6b7280);">{{ $label }}</div>
                <div style="font-size:1.05rem;font-weight:800;">{{ $this->rupiah($b[$key] ?? 0) }}</div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section>
        <x-slot name="heading">Rincian ({{ $this->rupiah($r['total']) }})</x-slot>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <th style="padding:.5rem 0;">{{ $this->partyLabel() }}</th>
                    <th style="padding:.5rem 0;">Nomor</th>
                    <th style="padding:.5rem 0;">Jatuh Tempo</th>
                    <th style="padding:.5rem 0;text-align:right;">Telat (hari)</th>
                    <th style="padding:.5rem 0;text-align:right;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($r['rows'] as $row)
                    <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                        <td style="padding:.4rem 0;">{{ $row['party'] }}</td>
                        <td style="padding:.4rem 0;color:var(--fi-color-gray-500,#6b7280);">{{ $row['number'] }}</td>
                        <td style="padding:.4rem 0;">{{ $row['due'] }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $row['overdue_days'] > 0 ? $row['overdue_days'] : '-' }}</td>
                        <td style="padding:.4rem 0;text-align:right;font-weight:600;">{{ $this->rupiah($row['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Tidak ada saldo outstanding.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>
