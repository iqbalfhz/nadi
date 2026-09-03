{{--
    Inline styles, not Tailwind classes: the admin panel has no Vite theme of
    its own, so utilities written here would never be compiled (same reason as
    public/css/nadi-sidebar.css).

    Every src is a signed URL that expires — these files sit on the private
    'internal' disk and have no permanent public address by design.
--}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(11rem,1fr));gap:0.75rem">
    @forelse ($media as $item)
        <a href="{{ $item->getTemporaryUrl(now()->addMinutes(30)) }}"
           target="_blank"
           rel="noopener noreferrer"
           title="{{ __('Buka ukuran penuh') }}"
           style="display:block;border-radius:0.5rem;overflow:hidden;border:1px solid rgba(128,128,128,0.25)">
            <img src="{{ $item->getTemporaryUrl(now()->addMinutes(30)) }}"
                 alt="{{ $item->file_name }}"
                 loading="lazy"
                 style="display:block;width:100%;height:11rem;object-fit:cover">
        </a>
    @empty
        <p style="grid-column:1/-1;margin:0;font-size:0.875rem;opacity:0.7">{{ __('Tidak ada foto pada data ini.') }}</p>
    @endforelse
</div>
