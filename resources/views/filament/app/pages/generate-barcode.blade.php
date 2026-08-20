<x-filament-panels::page>
    @if ($this->lastBarcode)
        <x-filament::section>
            <div wire:key="result-{{ $this->lastBarcode->id }}" class="flex flex-col items-center gap-4 py-6 text-center">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $this->lastBarcode->format->label() }} berhasil dibuat</span>

                <img
                    src="data:image/png;base64,{{ base64_encode($this->lastBarcode->renderPng()) }}"
                    alt="{{ $this->lastBarcode->content }}"
                    class="max-w-full rounded-lg border border-gray-200 dark:border-gray-700"
                />

                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $this->lastBarcode->content }}</span>

                <div class="mt-2 flex gap-2">
                    <x-filament::button
                        tag="a"
                        href="{{ route('barcodes.download', $this->lastBarcode) }}"
                        color="gray"
                    >
                        Download
                    </x-filament::button>

                    <x-filament::button wire:click="generateAnother">
                        Generate Lagi
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Jenis</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="format">
                            <option value="">— Pilih jenis —</option>
                            @foreach ($this->formats as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Konten</label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model.live="content"
                            placeholder="Teks, link, atau angka yang mau di-encode"
                        />
                    </x-filament::input.wrapper>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        EAN-13 butuh 12-13 digit angka. Code 128 dan Code 39 bisa teks bebas.
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Label (opsional)</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model.live="label" placeholder="Nama untuk memudahkan cari lagi nanti" />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <x-filament::button wire:click="generate">
                        Generate
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
