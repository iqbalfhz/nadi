<div class="flex min-h-screen flex-col items-center justify-center gap-8 px-6">
    <div class="flex flex-col items-center gap-3">
        <img src="/images/nadi-icon.png" alt="NADI" class="size-16 rounded-2xl">
        <h1 class="text-2xl font-semibold">Kiosk Antrian</h1>

        @if ($this->isEnabled)
            <p class="text-white/60">Masukkan PIN perangkat untuk mengaktifkan kiosk ini.</p>
        @else
            <p class="text-center text-[#FF5D3A]">Kiosk sedang dinonaktifkan oleh admin. Hubungi admin untuk mengaktifkannya kembali.</p>
        @endif
    </div>

    @if ($this->isEnabled)
        <form wire:submit="verify" class="flex w-full max-w-xs flex-col gap-4">
            <input
                type="password"
                inputmode="numeric"
                wire:model="pin"
                autofocus
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-4 text-center text-2xl tracking-[0.5em] text-white placeholder-white/30 focus:border-[#FFB020] focus:outline-none"
                placeholder="••••••"
            >

            @error('pin')
                <p class="text-center text-sm text-[#FF5D3A]">{{ $message }}</p>
            @enderror

            <button
                type="submit"
                class="w-full rounded-xl bg-[#FFB020] px-4 py-4 text-lg font-semibold text-[#101827] transition hover:brightness-95"
            >
                Aktifkan
            </button>
        </form>
    @endif
</div>
