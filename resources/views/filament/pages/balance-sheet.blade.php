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
                <label style="display:block;font-weight:500;font-size:.875rem;margin-bottom:.5rem;">Per Tanggal</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="asOf" />
                </x-filament::input.wrapper>
            </div>
        </div>
        @unless ($r['balanced'])
            <p style="margin-top:1rem;color:var(--fi-color-danger-600,#dc2626);font-weight:600;">
                ⚠ Neraca tidak seimbang. Periksa jurnal.
            </p>
        @endunless
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1.5rem;">
        <x-filament::section>
            <x-slot name="heading">Aset</x-slot>
            <table style="width:100%;border-collapse:collapse;">
                @foreach ($r['assets'] as $row)
                    <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                        <td style="padding:.4rem 0;color:var(--fi-color-gray-500,#6b7280);width:6rem;">{{ $row['code'] }}</td>
                        <td style="padding:.4rem 0;">{{ $row['name'] }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $this->rupiah($row['amount']) }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight:700;border-top:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <td colspan="2" style="padding:.5rem 0;">Total Aset</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($r['total_assets']) }}</td>
                </tr>
            </table>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Liabilitas &amp; Ekuitas</x-slot>
            <table style="width:100%;border-collapse:collapse;">
                <tr><td colspan="3" style="padding:.4rem 0;font-weight:600;color:var(--fi-color-gray-500,#6b7280);">Liabilitas</td></tr>
                @foreach ($r['liabilities'] as $row)
                    <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                        <td style="padding:.4rem 0;color:var(--fi-color-gray-500,#6b7280);width:6rem;">{{ $row['code'] }}</td>
                        <td style="padding:.4rem 0;">{{ $row['name'] }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $this->rupiah($row['amount']) }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight:600;"><td colspan="2" style="padding:.4rem 0;">Total Liabilitas</td><td style="padding:.4rem 0;text-align:right;">{{ $this->rupiah($r['total_liabilities']) }}</td></tr>

                <tr><td colspan="3" style="padding:.6rem 0 .4rem;font-weight:600;color:var(--fi-color-gray-500,#6b7280);">Ekuitas</td></tr>
                @foreach ($r['equity'] as $row)
                    <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                        <td style="padding:.4rem 0;color:var(--fi-color-gray-500,#6b7280);width:6rem;">{{ $row['code'] }}</td>
                        <td style="padding:.4rem 0;">{{ $row['name'] }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $this->rupiah($row['amount']) }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight:600;"><td colspan="2" style="padding:.4rem 0;">Total Ekuitas</td><td style="padding:.4rem 0;text-align:right;">{{ $this->rupiah($r['total_equity']) }}</td></tr>

                <tr style="font-weight:800;border-top:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <td colspan="2" style="padding:.5rem 0;">Total Liabilitas + Ekuitas</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($r['total_liabilities'] + $r['total_equity']) }}</td>
                </tr>
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
