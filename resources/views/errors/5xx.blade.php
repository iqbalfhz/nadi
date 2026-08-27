{{--
    Catch-all for every server error without a page of its own — 502, 504,
    507 and friends, which typically come from the proxy or an upstream
    service rather than from this application.
--}}
@php
    $status = isset($exception) && method_exists($exception, 'getStatusCode')
        ? $exception->getStatusCode()
        : 500;
@endphp

@include('errors.layout', [
    'code' => $status,
    'title' => 'Server Sedang Bermasalah',
    'message' => 'Server tidak bisa menyelesaikan permintaan ini. Ini masalah di sisi kami, bukan dari yang Anda lakukan. Coba lagi sebentar lagi.',
    'retry' => true,
])
