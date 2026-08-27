@include('errors.layout', [
    'code' => 429,
    'title' => 'Terlalu Banyak Permintaan',
    'message' => 'Permintaan dari perangkat Anda terlalu sering dalam waktu singkat. Tunggu sebentar, lalu coba lagi.',
    'retry' => true,
])
