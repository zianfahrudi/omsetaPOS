<x-filament-panels::page>
    @php($r = $this->report())

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
                <label style="display:block;font-weight:500;font-size:.875rem;margin-bottom:.5rem;">Dari Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="dateFrom" />
                </x-filament::input.wrapper>
            </div>
            <div>
                <label style="display:block;font-weight:500;font-size:.875rem;margin-bottom:.5rem;">Sampai Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="dateTo" />
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
        <x-filament::section>
            <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">Saldo Awal</div>
            <div style="font-size:1.25rem;font-weight:800;">{{ $this->rupiah($r['opening']) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">Kas Masuk</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--fi-color-success-600,#16a34a);">{{ $this->rupiah($r['total_in']) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">Kas Keluar</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--fi-color-danger-600,#dc2626);">{{ $this->rupiah($r['total_out']) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">Saldo Akhir</div>
            <div style="font-size:1.25rem;font-weight:800;">{{ $this->rupiah($r['closing']) }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Rincian per Aktivitas</x-slot>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <th style="padding:.5rem 0;">Aktivitas</th>
                    <th style="padding:.5rem 0;text-align:right;">Masuk</th>
                    <th style="padding:.5rem 0;text-align:right;">Keluar</th>
                    <th style="padding:.5rem 0;text-align:right;">Bersih</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($r['groups'] as $g)
                    <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                        <td style="padding:.4rem 0;">{{ $g['label'] }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $g['in'] > 0 ? $this->rupiah($g['in']) : '-' }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $g['out'] > 0 ? $this->rupiah($g['out']) : '-' }}</td>
                        <td style="padding:.4rem 0;text-align:right;font-weight:600;">{{ $this->rupiah($g['net']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Belum ada pergerakan kas.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="font-weight:800;border-top:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <td style="padding:.5rem 0;">Arus Kas Bersih</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($r['total_in']) }}</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($r['total_out']) }}</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($r['net']) }}</td>
                </tr>
            </tfoot>
        </table>
    </x-filament::section>
</x-filament-panels::page>
