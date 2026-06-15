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
                <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="dateFrom" /></x-filament::input.wrapper>
            </div>
            <div>
                <label style="display:block;font-weight:500;font-size:.875rem;margin-bottom:.5rem;">Sampai Tanggal</label>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="dateTo" /></x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <div style="font-size:.875rem;color:var(--fi-color-gray-500,#6b7280);">Total Pembelian Periode</div>
        <div style="font-size:1.5rem;font-weight:800;color:var(--fi-color-warning-600,#d97706);">{{ $this->rupiah($r['total']) }}</div>
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:1.5rem;">
        <x-filament::section>
            <x-slot name="heading">Per Produk</x-slot>
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr style="text-align:left;border-bottom:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <th style="padding:.4rem 0;">Produk</th><th style="padding:.4rem 0;text-align:right;">Qty</th><th style="padding:.4rem 0;text-align:right;">Total</th>
                </tr></thead>
                <tbody>
                    @forelse ($r['by_product'] as $row)
                        <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                            <td style="padding:.4rem 0;">{{ $row['label'] }}</td>
                            <td style="padding:.4rem 0;text-align:right;">{{ $row['quantity'] }}</td>
                            <td style="padding:.4rem 0;text-align:right;">{{ $this->rupiah($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Per Supplier</x-slot>
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr style="text-align:left;border-bottom:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <th style="padding:.4rem 0;">Supplier</th><th style="padding:.4rem 0;text-align:right;">Total</th>
                </tr></thead>
                <tbody>
                    @forelse ($r['by_supplier'] as $row)
                        <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                            <td style="padding:.4rem 0;">{{ $row['label'] }}</td>
                            <td style="padding:.4rem 0;text-align:right;">{{ $this->rupiah($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
