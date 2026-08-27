{{--
    Catch-all for every client error without a page of its own — 400, 402,
    405, 408, 413, 422 and the rest. Laravel falls back from errors::{code}
    to errors::4xx, so this is what makes "all error codes" actually all of
    them instead of just the handful Laravel ships views for.
--}}
@php
    $status = isset($exception) && method_exists($exception, 'getStatusCode')
        ? $exception->getStatusCode()
        : 400;
@endphp

@include('errors.layout', [
    'code' => $status,
    'title' => 'Permintaan Tidak Bisa Diproses',
    'message' => 'Permintaan ini ditolak karena tidak sesuai dengan yang diharapkan server. Coba ulangi dari awal lewat menu, jangan dari alamat yang diketik langsung.',
])
