<div
    x-data="{
        speak(text) {
            const utterance = new SpeechSynthesisUtterance(text)
            utterance.lang = 'id-ID'
            utterance.rate = 0.9
            window.speechSynthesis.speak(utterance)
        },
        init() {
            window.Echo.channel('queue-display').listen('.number.called', (e) => {
                const digits = e.formattedNumber.split('').join(', ')
                this.speak(`Nomor antrian ${digits}, silakan menuju ${e.counterLabel ?? e.categoryName}`)
            })
        },
    }"
    class="flex min-h-screen flex-col gap-8 px-8 py-10"
>
    <div class="flex items-center gap-4">
        <img src="/images/nadi-icon.png" alt="NADI" class="size-12 rounded-xl">
        <h1 class="text-3xl font-semibold">Antrian — {{ config('app.name') }}</h1>
    </div>

    <div class="grid flex-1 grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->categories as $category)
            @php($current = $category->latestCalledTicket)
            <div class="flex flex-col items-center justify-center gap-3 rounded-3xl border border-white/10 bg-white/5 py-12">
                <span class="text-lg text-white/50">{{ $category->name }}</span>
                <span class="text-7xl font-bold {{ $current ? 'text-[#FFB020]' : 'text-white/20' }}">
                    {{ $current?->formatted_number ?? '—' }}
                </span>
                @if ($current?->counter_label)
                    <span class="text-white/50">{{ $current->counter_label }}</span>
                @endif
            </div>
        @empty
            <p class="col-span-full text-center text-white/50">Belum ada layanan yang aktif.</p>
        @endforelse
    </div>
</div>
