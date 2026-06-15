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
            <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">PPN Keluaran</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--fi-color-success-600,#16a34a);">{{ $this->rupiah($r['output']) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">PPN Masukan</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--fi-color-danger-600,#dc2626);">{{ $this->rupiah($r['input']) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">PPN Bersih</div>
            <div style="font-size:1.25rem;font-weight:800;">{{ $this->rupiah($r['net']) }}</div>
            <div style="font-size:.75rem;color:var(--fi-color-gray-500,#6b7280);margin-top:.25rem;">{{ $r['status'] }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Rincian Transaksi Pajak</x-slot>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <th style="padding:.5rem 0;">Tanggal</th>
                    <th style="padding:.5rem 0;">Nomor</th>
                    <th style="padding:.5rem 0;">Keterangan</th>
                    <th style="padding:.5rem 0;text-align:right;">PPN Keluaran</th>
                    <th style="padding:.5rem 0;text-align:right;">PPN Masukan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($r['rows'] as $row)
                    <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                        <td style="padding:.4rem 0;">{{ $row['date'] }}</td>
                        <td style="padding:.4rem 0;color:var(--fi-color-gray-500,#6b7280);">{{ $row['number'] }}</td>
                        <td style="padding:.4rem 0;">{{ $row['description'] }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $row['output'] > 0 ? $this->rupiah($row['output']) : '-' }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $row['input'] > 0 ? $this->rupiah($row['input']) : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Belum ada transaksi pajak.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>
