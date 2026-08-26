<x-filament-panels::page>
    @if ($this->lastSale)
        <x-filament::section>
            <div
                wire:key="receipt-{{ $this->lastSale->id }}"
                x-data
                x-init="setTimeout(() => window.print(), 300)"
                class="flex flex-col items-center gap-2 py-6 text-center"
            >
                <span class="text-sm text-gray-500 dark:text-gray-400">Penjualan berhasil dicatat</span>
                <span class="text-2xl font-bold">{{ $this->lastSale->vendorProduct->name }}</span>
                <span class="text-lg">Kios {{ $this->lastSale->vendor->name }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ number_format($this->lastSale->quantity, 0, ',', '.') }} {{ $this->lastSale->pricing_unit->unitSuffix() }} —
                    Rp {{ number_format($this->lastSale->price, 0, ',', '.') }} —
                    {{ $this->lastSale->payment_method->label() }}
                </span>

                {{-- Print-only receipt — invisible on screen, this is what
                actually reaches the thermal printer when window.print() fires. --}}
                <div class="print-only">
                    <p style="margin: 0; font-size: 12px;">{{ config('app.name') }}</p>
                    <p style="margin: 0; font-size: 11px; letter-spacing: 1px;">{{ $this->lastSale->bazaar->name }}</p>
                    <p style="margin: 4px 0 0; font-size: 10px;">Trx no: {{ $this->lastSale->transaction_number }}</p>

                    <div style="margin: 8px 0; border-top: 1px dashed #000;"></div>

                    <p style="margin: 0; font-size: 12px;">Kios: {{ $this->lastSale->vendor->name }}</p>
                    <p style="margin: 6px 0 0; font-size: 16px; font-weight: bold;">{{ $this->lastSale->vendorProduct->name }}</p>
                    <p style="margin: 2px 0 0; font-size: 12px;">
                        {{ number_format($this->lastSale->quantity, 0, ',', '.') }} {{ $this->lastSale->pricing_unit->unitSuffix() }}
                    </p>

                    <div style="margin: 8px 0; border-top: 1px dashed #000;"></div>

                    <p style="margin: 0; font-size: 24px; font-weight: bold;">Rp {{ number_format($this->lastSale->price, 0, ',', '.') }}</p>
                    <p style="margin: 4px 0 0; font-size: 12px;">{{ $this->lastSale->payment_method->label() }}</p>

                    <div style="margin: 8px 0; border-top: 1px dashed #000;"></div>

                    <p style="margin: 0; font-size: 10px;">{{ $this->lastSale->created_at->translatedFormat('d M Y, H:i') }}</p>
                    <p style="margin: 2px 0 0; font-size: 10px;">Kasir: {{ $this->lastSale->soldByUser->name }}</p>

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
        <x-filament::section>
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Bazar</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="bazaarId">
                            <option value="">— Pilih bazar —</option>
                            @foreach ($this->openBazaars as $bazaar)
                                <option value="{{ $bazaar->id }}">{{ $bazaar->name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                @if ($bazaarId)
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Kios</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="vendorId">
                                <option value="">— Pilih kios —</option>
                                @foreach ($this->vendorsForSelectedBazaar as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                @endif

                @if ($vendorId)
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Produk</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="vendorProductId">
                                <option value="">— Pilih produk —</option>
                                @foreach ($this->productsForSelectedVendor as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->pricing_unit->label() }})</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                @endif

                @if ($this->selectedProduct)
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">{{ $this->selectedProduct->pricing_unit->quantityFieldLabel() }}</label>
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

                    <div>
                        <x-filament::button wire:click="sell">
                            Bayar &amp; Cetak
                        </x-filament::button>
                    </div>
                @endif
            </div>
        </x-filament::section>
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
