{{--
    Laravel ships its own view for 401/402/403/404/419/429/500/503, and those
    are registered under the same errors:: namespace as this directory — so a
    code left uncovered here silently falls back to the framework's unstyled
    page instead of to 4xx/5xx. 402 never legitimately happens in NADI, but it
    still needs overriding for that reason.
--}}
@include('errors.layout', [
    'code' => 402,
    'title' => 'Permintaan Ditolak',
    'message' => 'Permintaan ini tidak bisa dilanjutkan. Kalau Anda sampai di sini dari menu NADI, tolong laporkan ke admin.',
])
