<x-filament-panels::page>
    @php($rows = $this->rows())
    @php($totalDebit = $rows->sum('debit'))
    @php($totalCredit = $rows->sum('credit'))

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

    <x-filament::section>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <th style="padding:.5rem 0;width:6rem;">Kode</th>
                    <th style="padding:.5rem 0;">Nama Akun</th>
                    <th style="padding:.5rem 0;text-align:right;">Debit</th>
                    <th style="padding:.5rem 0;text-align:right;">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr style="border-bottom:1px solid var(--fi-color-gray-100,#f3f4f6);">
                        <td style="padding:.4rem 0;color:var(--fi-color-gray-500,#6b7280);">{{ $row['code'] }}</td>
                        <td style="padding:.4rem 0;">{{ $row['name'] }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $row['debit'] > 0 ? $this->rupiah($row['debit']) : '-' }}</td>
                        <td style="padding:.4rem 0;text-align:right;">{{ $row['credit'] > 0 ? $this->rupiah($row['credit']) : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="padding:.5rem 0;color:var(--fi-color-gray-400,#9ca3af);">Belum ada saldo.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="font-weight:800;border-top:2px solid var(--fi-color-gray-200,#e5e7eb);">
                    <td colspan="2" style="padding:.5rem 0;">Total</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($totalDebit) }}</td>
                    <td style="padding:.5rem 0;text-align:right;">{{ $this->rupiah($totalCredit) }}</td>
                </tr>
            </tfoot>
        </table>
    </x-filament::section>
</x-filament-panels::page>
