<x-filament-panels::page>
    @php($r = $this->report())

    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items:end;">
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
                <label style="display:block;font-weight:500;font-size:.875rem;margin-bottom:.5rem;">Kategori</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="categoryId">
                        <option value="">Semua kategori</option>
                        @foreach ($this->categories() as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <div>
                <label style="display:flex;align-items:center;gap:.5rem;font-weight:500;font-size:.875rem;">
                    <input type="checkbox" wire:model.live="lowStockOnly" /> Hanya stok rendah
                </label>
            </div>
        </div>
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
        <x-filament::section>
            <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">Jumlah Produk</div>
            <div style="font-size:1.25rem;font-weight:800;">{{ number_format($r['total_items'], 0, ',', '.') }}</div>
        </x-filament::section>
        <x-filament::section>
            <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">Total Nilai Persediaan</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--fi-color-primary-600,#4f46e5);">{{ $this->rupiah($r['total_value']) }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <th style="padding:.5rem 0;">Produk</th>
                    <th style="padding:.5rem 0;">Kategori</th>
                    <th style="padding:.5rem 0;text-align:right;">Stok</th>
                    <th style="padding:.5rem 0;text-align:right;">Harga Pokok</th>
                    <th style="padding:.5rem 0;text-align:right;">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($r['rows'] as $row)
                    <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                        <td style="padding:.4rem 0;">
                            {{ $row['name'] }}
                            <span style="color:var(--fi-color-gray-400,#9ca3af);font-size:.75rem;">{{ $row['sku'] }}</span>
                        </td>
                        <td style="padding:.4rem 0;color:var(--fi-color-gray-500,#6b7280);">{{ $row['category'] ?? '-' }}</td>
                        <td style="padding:.4rem 0;text-align:right;color:{{ $row['low'] ? 'var(--fi-color-danger-600,#dc2626)' : 'inherit' }};">
                            {{ $row['stock'] }} {{ $row['unit'] }}{{ $row['low'] ? ' ⚠' : '' }}
                        </td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $this->rupiah($row['cost_price']) }}</td>
                        <td style="padding:.4rem 0;text-align:right;font-weight:600;">{{ $this->rupiah($row['value']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Tidak ada produk.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="font-weight:800;border-top:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <td colspan="4" style="padding:.5rem 0;">Total Nilai Persediaan</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($r['total_value']) }}</td>
                </tr>
            </tfoot>
        </table>
    </x-filament::section>
</x-filament-panels::page>
