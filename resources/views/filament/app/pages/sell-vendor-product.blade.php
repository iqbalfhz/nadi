<x-filament-panels::page>
    @if ($this->lastTransactionNumber)
        @php($items = $this->lastSaleItems)
        @php($first = $items->first())
        <x-filament::section>
            <div
                wire:key="receipt-{{ $this->lastTransactionNumber }}"
                x-data
                x-init="setTimeout(() => window.print(), 300)"
                class="flex flex-col items-center gap-2 py-6 text-center"
            >
                <span class="text-sm text-gray-500 dark:text-gray-400">Penjualan berhasil dicatat</span>
                <span class="text-2xl font-bold">{{ $items->count() }} item — Rp {{ number_format($items->sum('price'), 0, ',', '.') }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $first->payment_method->label() }}</span>

                {{-- Print-only receipt — invisible on screen, this is what
                actually reaches the thermal printer when window.print() fires. --}}
                <div class="print-only">
                    <p style="margin: 0; font-size: 12px;">{{ config('app.name') }}</p>
                    <p style="margin: 0; font-size: 11px; letter-spacing: 1px;">{{ $first->bazaar->name }}</p>
                    <p style="margin: 4px 0 0; font-size: 10px;">Trx no: {{ $this->lastTransactionNumber }}</p>

                    <div style="margin: 8px 0; border-top: 1px dashed #000;"></div>

                    @foreach ($items as $item)
                        <p style="margin: 6px 0 0; font-size: 12px; font-weight: bold;">{{ $item->vendorProduct->name }}</p>
                        <p style="margin: 0; font-size: 11px;">
                            Kios {{ $item->vendor->name }} —
                            {{ number_format($item->quantity, 0, ',', '.') }} {{ $item->pricing_unit->unitSuffix() }}
                        </p>
                        <p style="margin: 0; font-size: 12px;">Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                    @endforeach

                    <div style="margin: 8px 0; border-top: 1px dashed #000;"></div>

                    <p style="margin: 0; font-size: 24px; font-weight: bold;">Rp {{ number_format($items->sum('price'), 0, ',', '.') }}</p>
                    <p style="margin: 4px 0 0; font-size: 12px;">{{ $first->payment_method->label() }}</p>

                    <div style="margin: 8px 0; border-top: 1px dashed #000;"></div>

                    <p style="margin: 0; font-size: 10px;">{{ $first->created_at->translatedFormat('d M Y, H:i') }}</p>
                    <p style="margin: 2px 0 0; font-size: 10px;">Kasir: {{ $first->soldByUser->name }}</p>

                    <p style="margin: 10px 0 0; font-size: 11px; font-weight: bold;">Terima Kasih</p>
                </div>

                <div class="mt-4 flex gap-2">
                    <x-filament::button color="gray" onclick="window.print()">
                        Cetak Ulang
                    </x-filament::button>

                    <x-filament::button wire:click="nextSale">
                        Transaksi Berikutnya
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @else
        <div class="flex flex-col gap-4">
            {{-- Bazar selector — chosen once per shift, stays fixed while
            browsing kios/produk below. --}}
            <x-filament::section>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                    <label class="shrink-0 text-sm font-medium">Bazar</label>
                    <div class="w-full sm:max-w-sm">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="bazaarId">
                                <option value="">— Pilih bazar —</option>
                                @foreach ($this->openBazaars as $bazaar)
                                    <option value="{{ $bazaar->id }}">{{ $bazaar->name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </x-filament::section>

            @if ($bazaarId)
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:items-start">
                    {{-- Main: kios tabs + product grid --}}
                    <div class="flex flex-col gap-4 lg:col-span-2">
                        <x-filament::section>
                            <div class="flex flex-col gap-4">
                                <div>
                                    <span class="mb-2 block text-sm font-medium">Kios</span>
                                    <div class="flex flex-wrap gap-2">
                                        @forelse ($this->vendorsForSelectedBazaar as $vendor)
                                            <button
                                                type="button"
                                                wire:click="$set('vendorId', {{ $vendor->id }})"
                                                @class([
                                                    'rounded-full px-4 py-1.5 text-sm font-medium transition-colors',
                                                    'bg-primary-600 text-white' => $vendorId === $vendor->id,
                                                    'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10' => $vendorId !== $vendor->id,
                                                ])
                                            >
                                                {{ $vendor->name }}
                                            </button>
                                        @empty
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Belum ada kios untuk bazar ini.</span>
                                        @endforelse
                                    </div>
                                </div>

                                @if ($vendorId)
                                    <div>
                                        <span class="mb-2 block text-sm font-medium">Produk</span>

                                        @if ($this->productsForSelectedVendor->isEmpty())
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Kios ini belum punya produk.</span>
                                        @else
                                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                                @foreach ($this->productsForSelectedVendor as $product)
                                                    @php($remaining = $product->remainingStock())
                                                    @php($outOfStock = $remaining !== null && $remaining <= 0)
                                                    <button
                                                        type="button"
                                                        @unless ($outOfStock) wire:click="$set('vendorProductId', {{ $product->id }})" @endunless
                                                        @disabled($outOfStock)
                                                        @class([
                                                            'flex flex-col items-start gap-1 rounded-xl border p-3 text-left transition-colors',
                                                            'border-primary-600 ring-2 ring-primary-600 bg-primary-50 dark:bg-primary-500/10' => ! $outOfStock && $vendorProductId === $product->id,
                                                            'border-gray-200 hover:border-primary-400 dark:border-white/10 dark:hover:border-primary-400' => ! $outOfStock && $vendorProductId !== $product->id,
                                                            'cursor-not-allowed border-gray-200 opacity-50 dark:border-white/10' => $outOfStock,
                                                        ])
                                                    >
                                                        <span class="font-medium leading-tight">{{ $product->name }}</span>
                                                        <x-filament::badge color="gray" size="xs">
                                                            {{ $product->pricing_unit->label() }}
                                                        </x-filament::badge>
                                                        <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                                        </span>
                                                        @if ($outOfStock)
                                                            <x-filament::badge color="danger" size="xs">Stok habis</x-filament::badge>
                                                        @elseif ($remaining !== null)
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                Sisa {{ number_format($remaining, 0, ',', '.') }} {{ $product->pricing_unit->unitSuffix() }}
                                                            </span>
                                                        @endif
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($this->selectedProduct)
                                    <div
                                        wire:key="quantity-{{ $this->selectedProduct->id }}"
                                        class="flex flex-col gap-3 rounded-xl border border-primary-200 bg-primary-50/60 p-4 dark:border-primary-400/30 dark:bg-primary-500/10 sm:flex-row sm:items-end sm:gap-4"
                                    >
                                        <div class="flex flex-1 flex-col gap-2">
                                            <label class="text-sm font-medium">
                                                {{ $this->selectedProduct->name }} — {{ $this->selectedProduct->pricing_unit->quantityFieldLabel() }}
                                            </label>
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    wire:model.live="quantity"
                                                    placeholder="{{ $this->selectedProduct->pricing_unit->quantityFieldLabel() }}"
                                                />
                                            </x-filament::input.wrapper>

                                            @if ($this->estimatedPrice !== null)
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    Perkiraan harga: Rp {{ number_format($this->estimatedPrice, 0, ',', '.') }}
                                                </p>
                                            @endif
                                        </div>

                                        <x-filament::button wire:click="addToCart" icon="heroicon-o-plus">
                                            Tambah ke Keranjang
                                        </x-filament::button>
                                    </div>
                                @endif
                            </div>
                        </x-filament::section>
                    </div>

                    {{-- Cart panel — stays visible while browsing products on desktop. --}}
                    <div class="lg:sticky lg:top-4">
                        <x-filament::section>
                            <div class="flex flex-col gap-4">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2 text-base font-semibold">
                                        <x-filament::icon icon="heroicon-o-shopping-cart" class="h-5 w-5" />
                                        Keranjang
                                    </span>
                                    @if (count($this->cartItems) > 0)
                                        <x-filament::badge color="primary">{{ count($this->cartItems) }} item</x-filament::badge>
                                    @endif
                                </div>

                                @if (count($this->cartItems) === 0)
                                    <x-filament::empty-state
                                        icon="heroicon-o-shopping-cart"
                                        heading="Keranjang masih kosong"
                                        description="Pilih kios dan produk di sebelah kiri untuk mulai menambahkan."
                                    />
                                @else
                                    <div class="flex flex-col gap-3">
                                        @foreach ($this->cartItems as $item)
                                            <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-3 dark:border-white/10">
                                                <div class="flex flex-col">
                                                    <span class="font-medium leading-tight">{{ $item['productName'] }}</span>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                                        Kios {{ $item['vendorName'] }} — {{ number_format($item['quantity'], 0, ',', '.') }} {{ $item['unitSuffix'] }}
                                                    </span>
                                                    <span class="text-sm font-semibold">Rp {{ number_format($item['price'], 0, ',', '.') }}</span>
                                                </div>

                                                <x-filament::icon-button
                                                    icon="heroicon-o-trash"
                                                    color="danger"
                                                    wire:click="removeFromCart({{ $item['index'] }})"
                                                    label="Hapus"
                                                />
                                            </div>
                                        @endforeach

                                        <div class="flex items-center justify-between pt-1">
                                            <span class="text-lg font-bold">Total</span>
                                            <span class="text-lg font-bold">Rp {{ number_format($this->cartTotal, 0, ',', '.') }}</span>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium">Metode Pembayaran</label>
                                            <x-filament::input.wrapper>
                                                <x-filament::input.select wire:model.live="paymentMethod">
                                                    <option value="">— Pilih metode —</option>
                                                    @foreach ($this->paymentMethods as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </x-filament::input.select>
                                            </x-filament::input.wrapper>
                                        </div>

                                        <x-filament::button wire:click="checkout" size="lg" class="w-full justify-center">
                                            Bayar &amp; Cetak
                                        </x-filament::button>
                                    </div>
                                @endif
                            </div>
                        </x-filament::section>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <style>
        .print-only { display: none; }

        @media print {
            body * { visibility: hidden; }
            .print-only, .print-only * { visibility: visible; }
            .print-only {
                display: block;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                text-align: center;
                font-family: monospace;
            }
            @page { size: 80mm auto; margin: 4mm; }
        }
    </style>
</x-filament-panels::page>
