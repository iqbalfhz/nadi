<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

    <title>{{ $title ?? 'Antrian' }} — {{ config('app.name') }}</title>

    <link rel="icon" href="/images/nadi-icon.png" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#101827] text-white antialiased" style="font-family: var(--font-sans)">
    {{ $slot }}

    @livewireScripts
</body>
</html>
