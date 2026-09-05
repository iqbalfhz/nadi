<x-filament-panels::page>
    <x-filament::section :heading="__('Tugas Terbuka')">
        @forelse ($this->openTasks as $delivery)
            <div class="flex items-center justify-between gap-4 border-b border-gray-100 py-3 last:border-0 dark:border-white/10">
                <div>
                    <p class="font-medium">{{ $delivery->tracking_number }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $delivery->document_description }}</p>
                    {{-- Asal sebelum tujuan: kurir perlu tahu ke mana dulu,
                    bukan ke mana akhirnya. --}}
                    <p class="text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('Diambil Dari') }}:</span>
                        <span class="font-medium">{{ $delivery->origin ?? '—' }}</span>
                        <span class="text-gray-400">→</span>
                        <span class="font-medium">{{ $delivery->destination }}</span>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Pemohon') }}: {{ $delivery->sender?->name ?? '—' }}
                    </p>
                </div>
                <x-filament::button size="sm" wire:click="claim({{ $delivery->id }})">
                    {{ __('Ambil') }}
                </x-filament::button>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Tidak ada tugas terbuka saat ini.') }}</p>
        @endforelse
    </x-filament::section>

    <x-filament::section :heading="__('Tugas Saya')">
        @forelse ($this->myTasks as $delivery)
            <div class="flex flex-col gap-3 border-b border-gray-100 py-3 last:border-0 dark:border-white/10">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <p class="font-medium">{{ $delivery->tracking_number }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $delivery->document_description }}</p>
                    {{-- Asal sebelum tujuan: kurir perlu tahu ke mana dulu,
                    bukan ke mana akhirnya. --}}
                    <p class="text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('Diambil Dari') }}:</span>
                        <span class="font-medium">{{ $delivery->origin ?? '—' }}</span>
                        <span class="text-gray-400">→</span>
                        <span class="font-medium">{{ $delivery->destination }}</span>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Pemohon') }}: {{ $delivery->sender?->name ?? '—' }}
                    </p>
                        <x-filament::badge :color="$delivery->status->color()">
                            {{ $delivery->status->label() }}
                        </x-filament::badge>
                    </div>

                    @if ($delivery->status === \App\Enums\MessengerDeliveryStatus::PickedUp)
                        <x-filament::button size="sm" wire:click="startTransit({{ $delivery->id }})">
                            {{ __('Mulai Perjalanan') }}
                        </x-filament::button>
                    @elseif ($delivery->status === \App\Enums\MessengerDeliveryStatus::InTransit && $completingDeliveryId !== $delivery->id)
                        <x-filament::button size="sm" color="success" wire:click="startCompleting({{ $delivery->id }})">
                            {{ __('Tandai Terkirim') }}
                        </x-filament::button>
                    @endif
                </div>

                @if ($completingDeliveryId === $delivery->id)
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                        {{ $this->form }}

                        <div class="mt-4 flex gap-2">
                            <x-filament::button wire:click="completeDelivery">
                                {{ __('Submit') }}
                            </x-filament::button>
                            <x-filament::button color="gray" wire:click="cancelCompleting">
                                {{ __('Batal') }}
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada tugas yang Anda ambil.') }}</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
