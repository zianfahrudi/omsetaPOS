<x-filament-panels::page>
    @php($totals = $this->totals())

    <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- Filter Section -->
        <x-filament::section>
            <x-slot name="heading">Filter Laporan</x-slot>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div>
                    <label style="display: block; font-weight: 500; font-size: 0.875rem; margin-bottom: 0.5rem;">Dari Tanggal</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="dateFrom" />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label style="display: block; font-weight: 500; font-size: 0.875rem; margin-bottom: 0.5rem;">Sampai Tanggal</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="dateTo" />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label style="display: block; font-weight: 500; font-size: 0.875rem; margin-bottom: 0.5rem;">Toko</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="storeId">
                            <option value="">Semua toko</option>
                            @foreach ($this->stores() as $store)
                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label style="display: block; font-weight: 500; font-size: 0.875rem; margin-bottom: 0.5rem;">Pembayaran</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="paymentMethod">
                            <option value="">Semua</option>
                            <option value="cash">Tunai</option>
                            <option value="qris">QRIS / Transfer</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>

        <!-- Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            
            <div style="background-color: var(--fi-color-white, #fff); border-radius: 0.75rem; border: 1px solid var(--fi-color-gray-200, #e5e7eb); padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="padding: 0.5rem; border-radius: 0.5rem; background-color: rgba(34, 197, 94, 0.1);">
                        <x-filament::icon icon="heroicon-o-banknotes" style="width: 1.5rem; height: 1.5rem; color: #22c55e;" />
                    </div>
                    <div style="font-size: 0.875rem; color: var(--fi-color-gray-500, #6b7280); font-weight: 500;">Omzet Bersih</div>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--fi-color-gray-950, #030712);">{{ $this->rupiah($totals['revenue']) }}</div>
            </div>

            <div style="background-color: var(--fi-color-white, #fff); border-radius: 0.75rem; border: 1px solid var(--fi-color-gray-200, #e5e7eb); padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="padding: 0.5rem; border-radius: 0.5rem; background-color: rgba(239, 68, 68, 0.1);">
                        <x-filament::icon icon="heroicon-o-credit-card" style="width: 1.5rem; height: 1.5rem; color: #ef4444;" />
                    </div>
                    <div style="font-size: 0.875rem; color: var(--fi-color-gray-500, #6b7280); font-weight: 500;">Total Hutang</div>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--fi-color-gray-950, #030712);">{{ $this->rupiah($totals['debt']) }}</div>
            </div>

            <div style="background-color: var(--fi-color-white, #fff); border-radius: 0.75rem; border: 1px solid var(--fi-color-gray-200, #e5e7eb); padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="padding: 0.5rem; border-radius: 0.5rem; background-color: rgba(59, 130, 246, 0.1);">
                        <x-filament::icon icon="heroicon-o-shopping-bag" style="width: 1.5rem; height: 1.5rem; color: #3b82f6;" />
                    </div>
                    <div style="font-size: 0.875rem; color: var(--fi-color-gray-500, #6b7280); font-weight: 500;">Total Transaksi</div>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--fi-color-gray-950, #030712);">{{ number_format($totals['transactions']) }} <span style="font-size: 0.875rem; font-weight: normal; color: var(--fi-color-gray-500, #6b7280);">trx</span></div>
            </div>

            <div style="background-color: var(--fi-color-white, #fff); border-radius: 0.75rem; border: 1px solid var(--fi-color-gray-200, #e5e7eb); padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="padding: 0.5rem; border-radius: 0.5rem; background-color: rgba(16, 185, 129, 0.1);">
                        <x-filament::icon icon="heroicon-o-calculator" style="width: 1.5rem; height: 1.5rem; color: #10b981;" />
                    </div>
                    <div style="font-size: 0.875rem; color: var(--fi-color-gray-500, #6b7280); font-weight: 500;">Rata-rata Transaksi</div>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--fi-color-gray-950, #030712);">{{ $this->rupiah($totals['average']) }}</div>
            </div>

            <div style="background-color: var(--fi-color-white, #fff); border-radius: 0.75rem; border: 1px solid var(--fi-color-gray-200, #e5e7eb); padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="padding: 0.5rem; border-radius: 0.5rem; background-color: rgba(245, 158, 11, 0.1);">
                        <x-filament::icon icon="heroicon-o-arrow-path" style="width: 1.5rem; height: 1.5rem; color: #f59e0b;" />
                    </div>
                    <div style="font-size: 0.875rem; color: var(--fi-color-gray-500, #6b7280); font-weight: 500;">Jumlah Refund</div>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--fi-color-gray-950, #030712);">{{ number_format($totals['refunds']) }} <span style="font-size: 0.875rem; font-weight: normal; color: var(--fi-color-gray-500, #6b7280);">trx</span></div>
            </div>

        </div>

        <!-- Tables -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            
            <!-- Store Breakdown -->
            <x-filament::section>
                <x-slot name="heading">Performa Per Toko</x-slot>
                
                <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                    @forelse ($this->storeBreakdown() as $row)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px solid var(--fi-color-gray-200, #e5e7eb);">
                            <div>
                                <div style="font-weight: 600;">{{ $row->store?->name }}</div>
                                <div style="font-size: 0.875rem; color: var(--fi-color-gray-500, #6b7280);">{{ number_format($row->transactions) }} Transaksi</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700;">{{ $this->rupiah($row->revenue) }}</div>
                                @if($row->debt > 0)
                                    <div style="font-size: 0.75rem; color: #ef4444;">Hutang: {{ $this->rupiah($row->debt) }}</div>
                                @else
                                    <div style="font-size: 0.75rem; color: #22c55e;">Lunas</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 2rem 0; color: var(--fi-color-gray-500, #6b7280);">Belum ada data penjualan</div>
                    @endforelse
                </div>
            </x-filament::section>

            <!-- Recent Transactions -->
            <x-filament::section>
                <x-slot name="heading">Transaksi Terbaru</x-slot>
                
                <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                    @forelse ($this->recentSales() as $sale)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px solid var(--fi-color-gray-200, #e5e7eb);">
                            <div>
                                <div style="font-weight: 600;">{{ $sale->number }}</div>
                                <div style="font-size: 0.75rem; color: var(--fi-color-gray-500, #6b7280); display: flex; align-items: center; gap: 0.25rem;">
                                    {{ $sale->store?->name }} &bull; 
                                    <span style="color: {{ $sale->payment_method == 'qris' ? '#3b82f6' : '#22c55e' }}; font-weight: 600;">
                                        {{ $sale->payment_method == 'qris' ? 'QRIS' : 'Tunai' }}
                                    </span>
                                    &bull; {{ $sale->created_at->format('d M H:i') }}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700;">{{ $this->rupiah($sale->grand_total) }}</div>
                                @if((float) $sale->debt_amount > 0)
                                    <span style="font-size: 0.75rem; background-color: rgba(245, 158, 11, 0.14); color: #92400e; padding: 0.125rem 0.5rem; border-radius: 9999px; font-weight: 600;">Belum lunas</span>
                                @else
                                    <span style="font-size: 0.75rem; background-color: rgba(34, 197, 94, 0.1); color: #22c55e; padding: 0.125rem 0.5rem; border-radius: 9999px; font-weight: 600;">Lunas</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 2rem 0; color: var(--fi-color-gray-500, #6b7280);">Belum ada transaksi</div>
                    @endforelse
                </div>
            </x-filament::section>

        </div>
    </div>
</x-filament-panels::page>
