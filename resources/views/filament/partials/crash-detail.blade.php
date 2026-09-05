{{--
    Inline styles, not Tailwind classes: the admin panel has no Vite theme of
    its own, so utilities written here would never be compiled (same reason as
    media-gallery.blade.php).

    The stack trace is machine text — the one place in NADI where that is the
    point rather than a bug. It is shown to whoever maintains the app, never to
    an officer.
--}}
<div style="display:flex;flex-direction:column;gap:1rem">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(9rem,1fr));gap:0.75rem;font-size:0.875rem">
        <div>
            <div style="opacity:0.6">{{ __('Versi Aplikasi') }}</div>
            <div style="font-weight:600">{{ $crash->app_version ?? '—' }}</div>
        </div>
        <div>
            <div style="opacity:0.6">{{ __('Jumlah Kejadian') }}</div>
            <div style="font-weight:600">{{ number_format($crash->occurrences, 0, ',', '.') }}</div>
        </div>
        <div>
            <div style="opacity:0.6">{{ __('Perangkat') }}</div>
            <div style="font-weight:600">{{ $crash->device ?? '—' }}</div>
        </div>
        <div>
            <div style="opacity:0.6">{{ __('Sistem Operasi') }}</div>
            <div style="font-weight:600">{{ trim(($crash->platform ?? '').' '.($crash->os_version ?? '')) ?: '—' }}</div>
        </div>
        <div>
            <div style="opacity:0.6">{{ __('Pertama Terjadi') }}</div>
            <div style="font-weight:600">{{ $crash->first_occurred_at->format('d M Y H:i') }}</div>
        </div>
        <div>
            <div style="opacity:0.6">{{ __('Terakhir Terjadi') }}</div>
            <div style="font-weight:600">{{ $crash->last_occurred_at->format('d M Y H:i') }}</div>
        </div>
    </div>

    <div>
        <div style="opacity:0.6;font-size:0.875rem;margin-bottom:0.25rem">{{ __('Pesan') }}</div>
        <p style="margin:0;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:0.8125rem;word-break:break-word">{{ $crash->message }}</p>
    </div>

    @if (filled($crash->stack))
        <div>
            <div style="opacity:0.6;font-size:0.875rem;margin-bottom:0.25rem">{{ __('Jejak Kesalahan') }}</div>
            <pre style="margin:0;padding:0.75rem;max-height:24rem;overflow:auto;border-radius:0.5rem;border:1px solid rgba(128,128,128,0.25);font-size:0.75rem;line-height:1.5;white-space:pre">{{ $crash->stack }}</pre>
        </div>
    @endif
</div>
