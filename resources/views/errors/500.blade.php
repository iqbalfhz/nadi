@include('errors.layout', [
    'code' => 500,
    'title' => 'Terjadi Kesalahan',
    'message' => 'Ada yang bermasalah di sisi server, bukan dari yang Anda lakukan. Kejadiannya sudah tercatat di log. Coba muat ulang, dan kalau masih berulang laporkan ke admin.',
    'retry' => true,
])
