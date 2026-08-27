<x-filament-panels::page>
    @if ($this->lastTicket)
        <x-filament::section>
            <div
                wire:key="receipt-{{ $this->lastTicket->id }}"
                x-data
                x-init="setTimeout(() => window.print(), 300)"
                class="flex flex-col items-center gap-2 py-6 text-center"
            >
                <span class="text-sm text-gray-500 dark:text-gray-400">Tiket berhasil dijual</span>
                <span class="text-2xl font-bold">{{ $this->lastTicket->event->name }}</span>
                <span class="text-lg">{{ $this->lastTicket->buyer_name }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->lastTicket->is_member ? 'Member' : 'Reguler' }} —
                    Rp {{ number_format($this->lastTicket->price, 0, ',', '.') }} —
                    {{ $this->lastTicket->payment_method->label() }}
                </span>

                {{-- Print-only receipt — invisible on screen, this is what
                actually reaches the thermal printer when window.print() fires. --}}
                <div class="print-only">
                    @if ($logoUrl = $this->lastTicket->event->logoUrl())
                        <img src="{{ $logoUrl }}" alt="" style="display: block; max-width: 45mm; max-height: 30mm; margin: 0 auto 6px;" />
                    @else
                        <p style="margin: 0; font-size: 12px;">{{ config('app.name') }}</p>
                    @endif

                    <p style="margin: 0; font-size: 11px; letter-spacing: 1px;">TIKET EVENT</p>
                    <p style="margin: 4px 0 0; font-size: 10px;">Trx no: {{ $this->lastTicket->transaction_number }}</p>

                    <div style="margin: 8px 0; border-top: 1px dashed #000;"></div>

                    <p style="margin: 0; font-size: 16px; font-weight: bold;">{{ $this->lastTicket->event->name }}</p>
                    <p style="margin: 6px 0 0; font-size: 12px;">{{ $this->lastTicket->buyer_name }}</p>
                    <p style="margin: 2px 0 0; font-size: 12px;">{{ $this->lastTicket->is_member ? 'Member' : 'Reguler' }}</p>

                    <div style="margin: 8px 0; border-top: 1px dashed #000;"></div>

                    <p style="margin: 0; font-size: 24px; font-weight: bold;">Rp {{ number_format($this->lastTicket->price, 0, ',', '.') }}</p>
                    <p style="margin: 4px 0 0; font-size: 12px;">{{ $this->lastTicket->payment_method->label() }}</p>

                    <div style="margin: 8px 0; border-top: 1px dashed #000;"></div>

                    <p style="margin: 0; font-size: 10px;">{{ $this->lastTicket->created_at->translatedFormat('d M Y, H:i') }}</p>
                    <p style="margin: 2px 0 0; font-size: 10px;">Kasir: {{ $this->lastTicket->soldByUser->name }}</p>

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
            <div class="flex flex-col gap-6">
                {{-- Two columns from sm up: on a desktop these fields used to
                run one per row at full page width, leaving most of the screen
                empty. Still stacks on the phone or tablet a cashier actually
                works from. --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Event</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="eventId">
                                <option value="">— Pilih event —</option>
                                @foreach ($this->openEvents as $event)
                                    <option value="{{ $event->id }}">{{ $event->name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>

                        @if ($this->selectedEvent)
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Reguler Rp {{ number_format($this->selectedEvent->regular_price, 0, ',', '.') }} —
                                Member Rp {{ number_format($this->selectedEvent->member_price, 0, ',', '.') }}
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Nama Pembeli</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" wire:model.live="buyerName" placeholder="Nama pembeli" />
                        </x-filament::input.wrapper>
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

                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-2">
                            <x-filament::input.checkbox wire:model.live="isMember" />
                            <span class="text-sm font-medium">Member</span>
                        </label>

                        @if ($isMember)
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    type="text"
                                    wire:model.live="memberReference"
                                    placeholder="Scan atau ketik barcode member"
                                />
                            </x-filament::input.wrapper>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-100 pt-4 dark:border-white/10">
                    <x-filament::button size="lg" wire:click="sell">
                        Bayar &amp; Cetak
                    </x-filament::button>
                </div>
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
