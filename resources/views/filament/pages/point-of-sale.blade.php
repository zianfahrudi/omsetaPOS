<x-filament-panels::page>
    <div class="grid gap-5 2xl:grid-cols-[minmax(0,1fr)_440px]">
        <section class="space-y-5">
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                <div class="grid gap-3 lg:grid-cols-[220px_1fr_220px]">
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Toko</span>
                        <select wire:model.live="storeId" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                            @foreach ($this->stores() as $store)
                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cari produk</span>
                        <input wire:model.live.debounce.250ms="productQuery" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="Nama produk, SKU, atau barcode">
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Scan cepat</span>
                        <input wire:model="scanCode" wire:keydown.enter.prevent="scanProduct" type="text" autofocus class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="Scan barcode">
                    </label>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @forelse ($this->products() as $product)
                    <button wire:click="addProduct({{ $product->id }})" type="button" class="group overflow-hidden rounded-lg border border-gray-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-primary-400 hover:shadow-md disabled:opacity-60 dark:border-gray-800 dark:bg-gray-950" @disabled($product->tracksStock() && $product->stock <= 0)>
                        <div class="aspect-[4/3] bg-gray-100 dark:bg-gray-900">
                            @if ($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gray-100 text-3xl font-bold text-gray-400 dark:bg-gray-900">
                                    {{ str($product->name)->substr(0, 1)->upper() }}
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3 p-4">
                            <div>
                                <div class="min-h-11 font-semibold leading-snug text-gray-950 dark:text-white">{{ $product->name }}</div>
                                <div class="mt-1 truncate text-xs text-gray-500">{{ $product->barcode ?: $product->sku }}</div>
                            </div>

                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <div class="text-lg font-bold text-primary-600">{{ $this->rupiah($product->unitSalePrice()) }}</div>
                                    <div class="text-xs text-gray-500">{{ $product->tracksStock() ? "Stok {$product->stock} {$product->unit}" : 'Jasa' }}</div>
                                </div>
                                <span class="rounded-md bg-primary-50 px-2 py-1 text-xs font-semibold text-primary-700 group-hover:bg-primary-100 dark:bg-primary-950 dark:text-primary-300">
                                    Tambah
                                </span>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 p-10 text-center text-gray-500 dark:border-gray-700 sm:col-span-2 xl:col-span-3 2xl:col-span-4">
                        Produk tidak ditemukan
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="space-y-5 2xl:sticky 2xl:top-6 2xl:self-start">
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-950">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Order</div>
                        <div class="text-lg font-bold text-gray-950 dark:text-white">{{ count($cart) }} item</div>
                    </div>
                    <x-filament::button color="gray" size="sm" wire:click="resetCart">Reset</x-filament::button>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($cart as $item)
                        <div class="grid grid-cols-[52px_1fr] gap-3 rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                            <div class="h-12 w-12 overflow-hidden rounded-md bg-gray-100 dark:bg-gray-900">
                                @if ($item['image_url'])
                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center font-bold text-gray-400">
                                        {{ str($item['name'])->substr(0, 1)->upper() }}
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="truncate font-semibold">{{ $item['name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $this->rupiah($item['price']) }}</div>
                                    </div>
                                    <button wire:click="removeItem({{ $item['product_id'] }})" type="button" class="rounded-md px-2 py-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-900">×</button>
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <button wire:click="decrementItem({{ $item['product_id'] }})" type="button" class="h-8 w-8 rounded-md border border-gray-300 dark:border-gray-700">-</button>
                                        <span class="w-7 text-center font-semibold">{{ $item['quantity'] }}</span>
                                        <button wire:click="incrementItem({{ $item['product_id'] }})" type="button" class="h-8 w-8 rounded-md border border-gray-300 dark:border-gray-700">+</button>
                                    </div>
                                    <div class="font-bold">{{ $this->rupiah($item['price'] * $item['quantity']) }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-gray-500 dark:border-gray-700">
                            Pilih produk dari katalog
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-950">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Customer</div>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 2xl:grid-cols-1">
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Nama</span>
                        <input wire:model.live="customerName" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="Walk-in customer">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">No. HP</span>
                        <input wire:model.live="customerPhone" type="tel" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="Opsional">
                    </label>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-950">
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-semibold">{{ $this->rupiah($this->subtotal()) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Total</span>
                        <span class="text-3xl font-bold text-gray-950 dark:text-white">{{ $this->rupiah($this->subtotal()) }}</span>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Metode pembayaran</span>
                        <select wire:model.live="paymentMethod" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                            <option value="cash">Cash</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </label>

                    @if ($paymentMethod === 'cash')
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Nominal bayar</span>
                            <input wire:model.live="paidAmount" type="number" min="0" step="500" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="0">
                        </label>

                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-900">
                            <span class="text-sm text-gray-500">Kembalian</span>
                            <span class="font-bold">{{ $this->rupiah($this->changeAmount()) }}</span>
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-primary-300 bg-primary-50 px-4 py-4 text-center text-sm font-semibold text-primary-800 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">
                            QRIS diproses sesuai total order
                        </div>
                    @endif
                </div>

                <x-filament::button wire:click="checkout" class="mt-5 w-full" size="lg" :disabled="empty($cart)">
                    Selesaikan order
                </x-filament::button>
            </div>

            @if ($lastSaleNumber)
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
                    <div class="font-semibold">Order selesai</div>
                    <div class="mt-1 text-sm">{{ $lastSaleNumber }}</div>
                </div>
            @endif
        </aside>
    </div>
</x-filament-panels::page>
