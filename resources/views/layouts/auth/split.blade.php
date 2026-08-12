<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#FAF8F5] antialiased dark:bg-[#0b1220]">
        <div class="relative grid min-h-svh lg:grid-cols-2">
            {{-- Brand panel — intentionally always-dark, independent of the site's light/dark toggle --}}
            <div class="relative hidden overflow-hidden bg-[#101827] lg:flex lg:flex-col lg:justify-between lg:p-12">
                <svg
                    class="pointer-events-none absolute inset-0 h-full w-full opacity-20"
                    viewBox="0 0 400 800"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <g stroke="#FF5D3A" stroke-width="1">
                        <line x1="48" y1="96" x2="132" y2="168" />
                        <line x1="132" y1="168" x2="118" y2="286" />
                        <line x1="118" y1="286" x2="224" y2="332" />
                        <line x1="224" y1="332" x2="340" y2="268" />
                        <line x1="224" y1="332" x2="212" y2="452" />
                        <line x1="212" y1="452" x2="96" y2="512" />
                        <line x1="212" y1="452" x2="308" y2="548" />
                        <line x1="308" y1="548" x2="284" y2="668" />
                        <line x1="284" y1="668" x2="368" y2="732" />
                        <line x1="96" y1="512" x2="60" y2="628" />
                    </g>
                    <g fill="#FFB020">
                        <circle cx="48" cy="96" r="4" />
                        <circle cx="132" cy="168" r="3" />
                        <circle cx="118" cy="286" r="4" />
                        <circle cx="340" cy="268" r="3" />
                        <circle cx="224" cy="332" r="5" />
                        <circle cx="212" cy="452" r="4" />
                        <circle cx="96" cy="512" r="3" />
                        <circle cx="308" cy="548" r="4" />
                        <circle cx="60" cy="628" r="3" />
                        <circle cx="284" cy="668" r="4" />
                        <circle cx="368" cy="732" r="3" />
                    </g>
                </svg>

                <a href="{{ route('home') }}" class="relative z-10 flex items-center gap-3" wire:navigate>
                    <img src="/images/nadi-icon.png" alt="NADI" class="size-10 rounded-xl">
                    <span class="text-lg font-semibold tracking-tight text-white">NADI</span>
                </a>

                <div class="relative z-10 flex flex-1 flex-col items-center justify-center gap-10 py-16">
                    <div class="relative flex size-44 items-center justify-center">
                        <span class="absolute inset-0 rounded-full bg-[#FF5D3A]/20" style="animation: nadi-pulse 2.4s ease-out infinite;"></span>
                        <span class="absolute inset-4 rounded-full bg-[#FFB020]/10" style="animation: nadi-pulse 2.4s ease-out infinite; animation-delay: .4s;"></span>
                        <img src="/images/nadi-icon.png" alt="" class="relative size-28 rounded-3xl shadow-2xl shadow-black/50">
                    </div>

                    <div class="max-w-xs text-center">
                        <p class="text-xl font-medium text-balance text-white">Satu denyut, seluruh operasional kantor.</p>
                        <p class="mt-3 text-sm text-balance text-white/50">Booking ruang, antrian, checklist, dan pengantaran dokumen — dalam satu platform terpadu.</p>
                    </div>
                </div>

                <p class="relative z-10 text-xs text-white/30">&copy; {{ now()->year }} NADI — Sistem Operasional Kantor Terpadu</p>
            </div>

            {{-- Form panel --}}
            <div class="flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16">
                <div class="mx-auto w-full max-w-sm">
                    <a href="{{ route('home') }}" class="mb-10 flex flex-col items-center gap-2 lg:hidden" wire:navigate>
                        <img src="/images/nadi-icon.png" alt="NADI" class="size-12 rounded-2xl">
                        <span class="sr-only">{{ config('app.name') }}</span>
                    </a>

                    {{ $slot }}
                </div>
            </div>
        </div>

        <style>
            @keyframes nadi-pulse {
                0% { transform: scale(0.85); opacity: .9; }
                70% { transform: scale(1.35); opacity: 0; }
                100% { transform: scale(1.35); opacity: 0; }
            }
        </style>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
