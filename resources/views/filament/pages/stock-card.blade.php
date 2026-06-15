<x-filament-panels::page>
    @php($movements = $this->movements())
    @php($product = $this->product())

    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            <div>
                <label style="display:block;font-weight:500;font-size:.875rem;margin-bottom:.5rem;">Produk</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="productId">
                        @foreach ($this->products() as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
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
        @if ($product)
            <p style="margin-top:1rem;">Stok saat ini: <strong>{{ $product->stock }} {{ $product->unit }}</strong></p>
        @endif
    </x-filament::section>

    <x-filament::section>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <th style="padding:.5rem 0;">Tanggal</th>
                    <th style="padding:.5rem 0;">Jenis</th>
                    <th style="padding:.5rem 0;">Keterangan</th>
                    <th style="padding:.5rem 0;text-align:right;">Masuk</th>
                    <th style="padding:.5rem 0;text-align:right;">Keluar</th>
                    <th style="padding:.5rem 0;text-align:right;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $m)
                    <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                        <td style="padding:.4rem 0;">{{ $m->created_at->format('d M Y H:i') }}</td>
                        <td style="padding:.4rem 0;">{{ $this->typeLabel($m->type) }}</td>
                        <td style="padding:.4rem 0;color:var(--fi-color-gray-500,#6b7280);">{{ $m->notes }}</td>
                        <td style="padding:.4rem 0;text-align:right;color:var(--fi-color-success-600,#16a34a);">{{ $m->quantity > 0 ? $m->quantity : '-' }}</td>
                        <td style="padding:.4rem 0;text-align:right;color:var(--fi-color-danger-600,#dc2626);">{{ $m->quantity < 0 ? abs($m->quantity) : '-' }}</td>
                        <td style="padding:.4rem 0;text-align:right;font-weight:600;">{{ $m->stock_after }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Belum ada pergerakan stok pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>
