<div class="flex min-h-screen flex-col items-center justify-center gap-10 px-6 py-12">
    @if ($this->issuedTicket)
        <div
            wire:key="issued-{{ $this->issuedTicket->id }}"
            x-data
            x-init="
                setTimeout(() => window.print(), 300)
                setTimeout(() => $wire.dismissTicket(), 8000)
            "
            class="flex flex-col items-center gap-4 text-center"
        >
            <p class="text-xl text-white/60">Nomor Anda</p>
            <p class="text-8xl font-bold tracking-wide text-[#FFB020]">{{ $this->issuedTicket->formatted_number }}</p>
            <p class="text-lg text-white/70">{{ $this->issuedTicket->category->name }}</p>
            <p class="mt-6 text-sm text-white/40">Silakan tunggu nomor Anda dipanggil.</p>

            {{-- Print-only receipt — invisible on screen, this is what actually
            reaches the thermal printer when window.print() fires above.

            The kiosk runs signed out, so it already renders in the venue's
            language; @venueLanguage is here so that every printed receipt in
            the app carries the same guard, with no exception to remember. --}}
            @venueLanguage
            <div class="print-only">
                <p style="margin: 0; font-size: 12px;">{{ config('app.name') }}</p>
                <p style="margin: 4px 0 0; font-size: 11px;">NOMOR ANTRIAN</p>
                <p style="margin: 8px 0; font-size: 32px; font-weight: bold;">{{ $this->issuedTicket->formatted_number }}</p>
                <p style="margin: 0; font-size: 12px;">{{ $this->issuedTicket->category->name }}</p>
                <p style="margin: 8px 0 0; font-size: 10px;">{{ $this->issuedTicket->created_at->translatedFormat('d M Y, H:i') }}</p>
            </div>
            @endVenueLanguage
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

    <style>
        .print-only { display: none; }

        /* Default 80mm thermal receipt width — change to 58mm if your printer
        uses the narrower paper roll. */
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
</div>
