<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Proposta Fácil' }}</title>
    <meta name="description" content="{{ $description ?? 'SaaS para criar propostas comerciais e orçamentos profissionais.' }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'Proposta Fácil' }}">
    <meta property="og:description" content="{{ $description ?? 'SaaS para criar propostas comerciais e orçamentos profissionais.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Proposta Fácil',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'BRL',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="bg-slate-50 text-slate-900" style="--color-primary:{{ auth()->user()?->primary_color ?? '#2563eb' }};--color-secondary:{{ auth()->user()?->secondary_color ?? '#0f172a' }}">
<header class="mx-auto flex max-w-6xl items-center justify-between p-6">
    <a href="{{ route('home') }}" class="text-xl font-bold text-[var(--color-primary)]">Proposta Fácil</a>
    <nav class="flex items-center gap-4 text-sm">
        <a href="{{ route('features') }}">Recursos</a>
        <a href="{{ route('pricing') }}">Preços</a>
        <a href="{{ route('terms') }}">Termos</a>
        <a href="{{ route('privacy') }}">Privacidade</a>
        @auth
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('profile.edit') }}">Perfil</a>
        @else
            <a href="{{ route('login') }}">Entrar</a>
        @endauth
    </nav>
</header>
<main class="mx-auto max-w-6xl p-6">@yield('content')</main>
<footer class="mx-auto max-w-6xl p-6 text-sm text-slate-500">© {{ date('Y') }} Proposta Fácil. Feito para freelancers e pequenos negócios.</footer>
</body>
</html>
