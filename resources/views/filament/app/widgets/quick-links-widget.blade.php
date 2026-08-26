<x-filament-widgets::widget>
    <x-filament::section>
        <span class="mb-3 block text-base font-semibold">Menu Cepat</span>

        @php($links = $this->getLinks())

        @if (empty($links))
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Belum ada menu yang bisa diakses. Hubungi admin kalau ini seharusnya tidak kosong.
            </p>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($links as $link)
                    <a
                        href="{{ $link['url'] }}"
                        wire:navigate
                        class="flex flex-col items-center gap-2 rounded-xl border border-gray-200 p-4 text-center transition-colors hover:border-primary-400 hover:bg-primary-50 dark:border-white/10 dark:hover:border-primary-400 dark:hover:bg-primary-500/10"
                    >
                        <x-filament::icon :icon="$link['icon']" class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                        <span class="text-sm font-medium leading-tight">{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
