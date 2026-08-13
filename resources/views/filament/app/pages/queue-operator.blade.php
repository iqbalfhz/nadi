<x-filament-panels::page>
    <x-filament::section>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium">Loket / Layanan</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="categoryId" :disabled="(bool) $this->currentTicket">
                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->code }})</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium">Nama Loket / Counter Anda</label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live="counterLabel"
                        placeholder="Contoh: Loket 1"
                        :disabled="(bool) $this->currentTicket"
                    />
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="flex flex-col items-center gap-2 py-6 text-center">
            <span class="text-sm text-gray-500 dark:text-gray-400">Sedang Dilayani</span>
            <span class="text-6xl font-bold">
                {{ $this->currentTicket?->formatted_number ?? '—' }}
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ $this->waitingCount }} orang menunggu di loket ini
            </span>
        </div>

        <div class="flex flex-wrap justify-center gap-3">
            @if (! $this->currentTicket)
                <x-filament::button
                    wire:click="callNext"
                    :disabled="! $categoryId || trim($counterLabel) === ''"
                >
                    Panggil Berikutnya
                </x-filament::button>
            @else
                <x-filament::button color="gray" wire:click="recall">
                    Panggil Ulang
                </x-filament::button>

                <x-filament::button color="success" wire:click="markDone">
                    Selesai
                </x-filament::button>

                <x-filament::button color="danger" wire:click="markSkipped">
                    Lewati
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
