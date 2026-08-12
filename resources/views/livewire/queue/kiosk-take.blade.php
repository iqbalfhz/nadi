<div class="flex min-h-screen flex-col items-center justify-center gap-10 px-6 py-12">
    @if ($this->issuedTicket)
        <div
            wire:key="issued-{{ $this->issuedTicket->id }}"
            x-data
            x-init="setTimeout(() => $wire.dismissTicket(), 8000)"
            class="flex flex-col items-center gap-4 text-center"
        >
            <p class="text-xl text-white/60">Nomor Anda</p>
            <p class="text-8xl font-bold tracking-wide text-[#FFB020]">{{ $this->issuedTicket->formatted_number }}</p>
            <p class="text-lg text-white/70">{{ $this->issuedTicket->category->name }}</p>
            <p class="mt-6 text-sm text-white/40">Silakan tunggu nomor Anda dipanggil.</p>
        </div>
    @else
        <div class="flex flex-col items-center gap-3 text-center">
            <img src="/images/nadi-icon.png" alt="NADI" class="size-16 rounded-2xl">
            <h1 class="text-3xl font-semibold">Ambil Nomor Antrian</h1>
            <p class="text-white/60">Pilih layanan yang Anda butuhkan.</p>
        </div>

        <div class="grid w-full max-w-2xl grid-cols-1 gap-4 sm:grid-cols-2">
            @forelse ($this->categories as $category)
                <button
                    type="button"
                    wire:click="take({{ $category->id }})"
                    wire:loading.attr="disabled"
                    class="rounded-2xl border border-white/10 bg-white/5 px-6 py-8 text-left transition hover:border-[#FFB020]/50 hover:bg-white/10 disabled:opacity-50"
                >
                    <span class="block text-lg font-semibold">{{ $category->name }}</span>
                    <span class="block text-white/50">Kode {{ $category->code }}</span>
                </button>
            @empty
                <p class="col-span-full text-center text-white/50">Belum ada layanan yang aktif.</p>
            @endforelse
        </div>
    @endif
</div>
