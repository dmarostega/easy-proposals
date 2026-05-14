<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin - Proposta Facil' }}</title>
    @include('partials.favicons')
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="bg-slate-100 text-slate-900">
    <div
        id="app"
        data-page="{{ $page }}"
        data-user='@json($user)'
        data-admin="{{ $isAdmin ? 'true' : 'false' }}"
    ></div>
</body>
</html>
