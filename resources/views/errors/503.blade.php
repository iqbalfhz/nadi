@include('errors.layout', [
    'code' => 503,
    'title' => 'Sedang Dalam Perbaikan',
    'message' => 'NADI sedang dalam pemeliharaan sebentar. Coba lagi beberapa menit lagi — tidak ada data yang hilang.',
    'retry' => true,
])
