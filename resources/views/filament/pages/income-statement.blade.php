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

    <x-filament::section>
        <x-slot name="heading">Pendapatan</x-slot>
        <table style="width:100%;border-collapse:collapse;">
            @forelse ($r['revenue'] as $row)
                <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                    <td style="padding:.5rem 0;color:var(--fi-color-gray-500,#6b7280);width:6rem;">{{ $row['code'] }}</td>
                    <td style="padding:.5rem 0;">{{ $row['name'] }}</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($row['amount']) }}</td>
                </tr>
            @empty
                <tr><td style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Belum ada pendapatan.</td></tr>
            @endforelse
            <tr style="font-weight:700;border-top:2px solid var(--fi-color-gray-200,#e5e7eb);">
                <td colspan="2" style="padding:.5rem 0;">Total Pendapatan</td>
                <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($r['total_revenue']) }}</td>
            </tr>
        </table>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Beban &amp; HPP</x-slot>
        <table style="width:100%;border-collapse:collapse;">
            @forelse ($r['expense'] as $row)
                <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                    <td style="padding:.5rem 0;color:var(--fi-color-gray-500,#6b7280);width:6rem;">{{ $row['code'] }}</td>
                    <td style="padding:.5rem 0;">{{ $row['name'] }}</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($row['amount']) }}</td>
                </tr>
            @empty
                <tr><td style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Belum ada beban.</td></tr>
            @endforelse
            <tr style="font-weight:700;border-top:2px solid var(--fi-color-gray-200,#e5e7eb);">
                <td colspan="2" style="padding:.5rem 0;">Total Beban</td>
                <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($r['total_expense']) }}</td>
            </tr>
        </table>
    </x-filament::section>

    <x-filament::section>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:1.125rem;font-weight:800;">
            <span>Laba (Rugi) Bersih</span>
            <span style="color:{{ $r['net_income'] >= 0 ? 'var(--fi-color-success-600,#16a34a)' : 'var(--fi-color-danger-600,#dc2626)' }};">
                {{ $this->rupiah($r['net_income']) }}
            </span>
        </div>
    </x-filament::section>
</x-filament-panels::page>
