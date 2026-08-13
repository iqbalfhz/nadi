<div
    x-data="{
        audioUnlocked: localStorage.getItem('nadi-audio-unlocked') === '1',
        speak(text) {
            const utterance = new SpeechSynthesisUtterance(text)
            utterance.lang = 'id-ID'
            utterance.rate = 0.9
            window.speechSynthesis.speak(utterance)
        },
        unlockAudio() {
            // Browsers refuse to play synthesized speech until the tab has
            // had at least one real user gesture — a TV/kiosk screen that's
            // opened once and never touched again would otherwise stay
            // permanently silent. This one click unlocks it for good.
            this.speak('Suara antrian aktif.')
            localStorage.setItem('nadi-audio-unlocked', '1')
            this.audioUnlocked = true
        },
        init() {
            window.Echo.channel('queue-display').listen('.number.called', (e) => {
                const digits = e.formattedNumber.split('').join(', ')
                this.speak(`Nomor antrian ${digits}, silakan menuju ${e.counterLabel ?? e.categoryName}`)
            })
        },
    }"
    class="relative flex h-screen flex-col overflow-hidden"
>
    <div
        x-show="! audioUnlocked"
        x-cloak
        class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-4 bg-[#0b1220]/95 px-6 text-center"
    >
        <p class="text-xl font-medium">Aktifkan suara panggilan untuk layar ini</p>
        <p class="max-w-sm text-sm text-white/50">
            Browser perlu satu kali klik sebelum bisa memutar suara secara otomatis. Cukup dilakukan sekali saat memasang layar ini.
        </p>
        <button
            type="button"
            x-on:click="unlockAudio()"
            class="rounded-lg bg-[#FFB020] px-6 py-3 text-sm font-semibold text-[#101827] transition hover:brightness-95"
        >
            Aktifkan Suara
        </button>
    </div>

    <div class="flex shrink-0 items-center gap-4 px-8 py-6">
        <img src="/images/nadi-icon.png" alt="NADI" class="size-10 rounded-xl">
        <h1 class="text-2xl font-semibold">Antrian — {{ config('app.name') }}</h1>
    </div>

    {{-- Ad panel — independent x-data scope, cycles through active
    Advertisement records (video plays to completion, images hold for
    their configured duration). Takes up the bulk of the screen, with
    the queue numbers always visible in the strip below it. --}}
    <div class="min-h-0 flex-1 px-8 pb-8">
        <div
            x-data="{
                ads: @js($this->advertisements->map(fn ($ad) => [
                    'url' => $ad->getFirstMediaUrl('file'),
                    'isVideo' => $ad->isVideo(),
                    'duration' => $ad->duration_seconds ?? 8,
                ])),
                index: 0,
                timer: null,
                get current() {
                    return this.ads[this.index] ?? null
                },
                next() {
                    this.index = (this.index + 1) % this.ads.length
                    this.playCurrent()
                },
                playCurrent() {
                    clearTimeout(this.timer)

                    const ad = this.current

                    if (! ad) return

                    if (ad.isVideo) {
                        // Same source on both the blurred backdrop and the
                        // full-content video — kept roughly in sync since both
                        // .play() calls fire in the same tick. A frame or two
                        // of drift doesn't matter for an ambient backdrop.
                        for (const ref of [this.$refs.video, this.$refs.videoBg]) {
                            ref.src = ad.url
                            ref.currentTime = 0
                            ref.play()
                        }
                    } else {
                        this.timer = setTimeout(() => this.next(), (ad.duration || 8) * 1000)
                    }
                },
                init() {
                    this.playCurrent()
                },
            }"
            class="relative h-full w-full overflow-hidden rounded-3xl border border-white/10 bg-black"
        >
            <template x-if="ads.length === 0">
                <div class="flex h-full flex-col items-center justify-center gap-3">
                    <img src="/images/nadi-icon.png" alt="NADI" class="size-16 rounded-2xl opacity-40">
                    <p class="text-white/30">NADI</p>
                </div>
            </template>

            {{-- Blurred, cropped backdrop — fills the whole panel edge-to-edge
            regardless of the ad's own aspect ratio, so there's never dead
            empty space beside content that doesn't match the panel's shape. --}}
            <video
                x-ref="videoBg"
                x-show="current && current.isVideo"
                x-cloak
                muted
                playsinline
                aria-hidden="true"
                class="absolute inset-0 h-full w-full scale-110 object-cover opacity-60 blur-2xl"
            ></video>

            <img
                x-show="current && ! current.isVideo"
                x-cloak
                aria-hidden="true"
                :src="current && ! current.isVideo ? current.url : ''"
                class="absolute inset-0 h-full w-full scale-110 object-cover opacity-60 blur-2xl"
            >

            {{-- Full, uncropped content on top of the backdrop. --}}
            <video
                x-ref="video"
                x-show="current && current.isVideo"
                x-cloak
                muted
                playsinline
                x-on:ended="next()"
                class="relative h-full w-full object-contain"
            ></video>

            <img
                x-show="current && ! current.isVideo"
                x-cloak
                :src="current && ! current.isVideo ? current.url : ''"
                class="relative h-full w-full object-contain"
            >
        </div>
    </div>

    {{-- Queue numbers — always visible along the bottom, centered, under the ad area. --}}
    <div class="flex shrink-0 flex-wrap items-center justify-center gap-x-16 gap-y-6 border-t border-white/10 bg-white/5 px-8 py-8">
        @forelse ($this->categories as $category)
            @php($current = $category->latestCalledTicket)
            <div class="flex flex-col items-center gap-1">
                <span class="text-base text-white/50">{{ $category->name }}</span>
                <span class="text-6xl font-bold {{ $current ? 'text-[#FFB020]' : 'text-white/20' }}">
                    {{ $current?->formatted_number ?? '—' }}
                </span>
                @if ($current?->counter_label)
                    <span class="text-sm text-white/50">{{ $current->counter_label }}</span>
                @endif
            </div>
        @empty
            <p class="text-white/50">Belum ada layanan yang aktif.</p>
        @endforelse
    </div>
</div>
