<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
        <section class="space-y-4">
            <div class="grid gap-4 md:grid-cols-[1fr_auto]">
                <label class="block">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Nomor transaksi</span>
                    <input wire:model="saleNumber" wire:keydown.enter.prevent="findSale" type="text" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="TRX-...">
                </label>
                <div class="flex items-end">
                    <x-filament::button wire:click="findSale">Cari</x-filament::button>
                </div>
            </div>

            @php($sale = $this->sale())

            @if ($sale)
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="font-semibold">{{ $sale->number }}</div>
                            <div class="text-sm text-gray-500">{{ $sale->store->name }} · {{ $sale->cashier?->name }} · {{ $sale->created_at->format('d M Y H:i') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Total transaksi</div>
                            <div class="font-bold">{{ $this->rupiah($sale->grand_total) }}</div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
                    <div class="grid grid-cols-[1fr_120px_120px] gap-3 bg-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-900">
                        <span>Barang kembali</span>
                        <span>Tersedia</span>
                        <span>Qty</span>
                    </div>

                    @foreach ($sale->items as $item)
                        @php($available = $item->quantity - $item->refunded_quantity)
                        <div class="grid grid-cols-[1fr_120px_120px] items-center gap-3 border-t border-gray-100 px-4 py-3 dark:border-gray-800">
                            <div>
                                <div class="font-medium">{{ $item->product_name }}</div>
                                <div class="text-sm text-gray-500">{{ $item->product_code }} · {{ $this->rupiah($item->unit_price) }}</div>
                            </div>
                            <div>{{ $available }} / {{ $item->quantity }}</div>
                            <input wire:model.live="returnQuantities.{{ $item->id }}" type="number" min="0" max="{{ $available }}" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                    @endforeach
                </div>

                <div class="space-y-3">
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Cari barang pengganti</span>
                        <input wire:model.live.debounce.300ms="productQuery" type="text" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Nama / SKU / barcode">
                    </label>

                    @if ($this->replacementResults()->isNotEmpty())
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
                            @foreach ($this->replacementResults() as $product)
                                <button wire:click="addReplacement({{ $product->id }})" type="button" class="flex w-full items-center justify-between border-b border-gray-100 px-4 py-3 text-left last:border-b-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900">
                                    <span>
                                        <span class="block font-medium">{{ $product->name }}</span>
                                        <span class="text-sm text-gray-500">{{ $product->barcode ?: $product->sku }} · Stok {{ $product->stock }}</span>
                                    </span>
                                    <span class="font-semibold">{{ $this->rupiah($product->sell_price) }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($replacementCart)
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
                        <div class="bg-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-900">Barang pengganti</div>
                        @foreach ($replacementCart as $item)
                            <div class="grid grid-cols-[1fr_104px_120px] items-center gap-3 border-t border-gray-100 px-4 py-3 dark:border-gray-800">
                                <div>
                                    <div class="font-medium">{{ $item['name'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $item['code'] }} · {{ $this->rupiah($item['price']) }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button wire:click="decrementReplacement({{ $item['product_id'] }})" type="button" class="h-8 w-8 rounded-md border border-gray-300 dark:border-gray-700">-</button>
                                    <span class="w-8 text-center font-semibold">{{ $item['quantity'] }}</span>
                                    <button wire:click="incrementReplacement({{ $item['product_id'] }})" type="button" class="h-8 w-8 rounded-md border border-gray-300 dark:border-gray-700">+</button>
                                </div>
                                <div class="text-right font-semibold">{{ $this->rupiah($item['price'] * $item['quantity']) }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </section>

        <aside class="space-y-4">
            <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800">
                <div class="space-y-4">
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tipe refund</span>
                        <select wire:model.live="type" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            <option value="partial">Partial</option>
                            <option value="full">Full refund</option>
                            <option value="exchange">Tukar barang</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Alasan</span>
                        <textarea wire:model.live="reason" rows="3" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"></textarea>
                    </label>

                    <div class="space-y-2 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <span>Barang kembali</span>
                            <strong>{{ $this->rupiah($this->returnedTotal()) }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Barang pengganti</span>
                            <strong>{{ $this->rupiah($this->replacementTotal()) }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Uang kembali</span>
                            <strong>{{ $this->rupiah($this->refundAmount()) }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Tambahan bayar</span>
                            <strong>{{ $this->rupiah($this->expectedAdditionalPayment()) }}</strong>
                        </div>
                    </div>

                    @if ($this->expectedAdditionalPayment() > 0)
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Dibayar tambahan</span>
                            <input wire:model.live="additionalPaymentAmount" type="number" min="0" step="500" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </label>
                    @endif
                </div>

                <div class="mt-6">
                    <x-filament::button wire:click="process" class="w-full" :disabled="! $saleId">Proses refund</x-filament::button>
                </div>
            </div>

            @if ($lastRefundNumber)
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
                    Refund selesai: <strong>{{ $lastRefundNumber }}</strong>
                </div>
            @endif
        </aside>
    </div>
</x-filament-panels::page>
