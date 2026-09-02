{{--
    Shared by the Security page in both panels. Everything interactive is a
    Filament header action, so this only has to state where the account stands
    — and say the one thing people forget until it is too late.
--}}
<x-filament-panels::page>
    <x-filament::section
        icon="heroicon-o-shield-check"
        heading="Autentikasi dua langkah"
        description="Lapisan kedua setelah password: setiap login perlu kode dari aplikasi authenticator di HP Anda."
    >
        @if ($this->isTwoFactorEnabled())
            <div class="flex flex-wrap items-center gap-3">
                <x-filament::badge color="success" icon="heroicon-m-check-circle">
                    Aktif
                </x-filament::badge>

                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Password saja tidak cukup untuk masuk ke akun ini.
                </span>
            </div>

            <div class="mt-4">
                @if ($this->recoveryCodeCount() > 0)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Tersisa <strong>{{ $this->recoveryCodeCount() }}</strong> kode pemulihan.
                        Kode inilah satu-satunya jalan masuk kalau HP Anda hilang atau
                        aplikasi authenticator terhapus — simpan terpisah dari HP.
                    </p>
                @else
                    <p class="text-sm fi-color-danger">
                        Belum ada kode pemulihan tersimpan. Buat sekarang lewat tombol
                        "Buat ulang kode pemulihan" — tanpa itu, kehilangan HP berarti
                        kehilangan akses ke akun ini.
                    </p>
                @endif
            </div>
        @else
            <div class="flex flex-wrap items-center gap-3">
                <x-filament::badge color="gray" icon="heroicon-m-exclamation-triangle">
                    Belum aktif
                </x-filament::badge>

                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Akun ini hanya dijaga password.
                </span>
            </div>

            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Kalau dinyalakan, setiap login akan meminta kode tambahan yang berganti
                tiap 30 detik dari aplikasi di HP Anda. Artinya password yang bocor saja
                tidak cukup untuk masuk.
            </p>
        @endif
    </x-filament::section>

    <x-filament::section
        icon="heroicon-o-key"
        heading="Password"
        description="Diubah lewat halaman Profil."
        collapsible
        collapsed
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Nama, email, dan password diatur di halaman <strong>Profil</strong> —
            ada di menu yang sama dengan halaman ini.
        </p>
    </x-filament::section>
</x-filament-panels::page>
