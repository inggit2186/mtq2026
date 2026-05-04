<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'e-MTQ') }} @yield('title', '')</title>
    <style>[x-cloak]{display:none!important;}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="grid-bg min-h-screen overflow-x-hidden">
    @php
        $sweetAlertPayload = [
            'status' => session('status'),
            'errors' => isset($errors) && $errors->any() ? $errors->all() : [],
        ];
    @endphp
    <script type="application/json" id="mtq-swal-payload">{!! json_encode($sweetAlertPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <main class="relative mx-auto flex min-h-screen max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>
