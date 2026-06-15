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
                <label style="display:block;font-weight:500;font-size:.875rem;margin-bottom:.5rem;">Gudang</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="warehouseId">
                        <option value="">Semua gudang</option>
                        @foreach ($this->warehouses() as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">Total Nilai Stok</div>
        <div style="font-size:1.5rem;font-weight:800;color:var(--fi-color-primary-600,#4f46e5);">{{ $this->rupiah($r['total_value']) }}</div>
    </x-filament::section>

    <x-filament::section>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <th style="padding:.5rem 0;">Gudang</th>
                    <th style="padding:.5rem 0;">Produk</th>
                    <th style="padding:.5rem 0;text-align:right;">Stok</th>
                    <th style="padding:.5rem 0;text-align:right;">Harga Pokok</th>
                    <th style="padding:.5rem 0;text-align:right;">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($r['rows'] as $row)
                    <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                        <td style="padding:.4rem 0;">{{ $row['warehouse'] }}</td>
                        <td style="padding:.4rem 0;">{{ $row['product'] }} <span style="color:var(--fi-color-gray-400,#9ca3af);font-size:.75rem;">{{ $row['sku'] }}</span></td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $row['quantity'] }} {{ $row['unit'] }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $this->rupiah($row['cost_price']) }}</td>
                        <td style="padding:.4rem 0;text-align:right;font-weight:600;">{{ $this->rupiah($row['value']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Belum ada stok.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="font-weight:800;border-top:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <td colspan="4" style="padding:.5rem 0;">Total Nilai</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($r['total_value']) }}</td>
                </tr>
            </tfoot>
        </table>
    </x-filament::section>
</x-filament-panels::page>
