@include('errors.layout', [
    'code' => 403,
    'title' => 'Akses Ditolak',
    'message' => 'Akun Anda tidak punya izin untuk membuka halaman ini. Kalau menurut Anda seharusnya bisa, minta admin menambahkan izinnya lewat menu Roles.',
])
