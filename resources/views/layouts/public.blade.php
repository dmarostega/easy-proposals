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
    @php
        $proposalModel = $proposal ?? null;
        $brandingUser = $proposalModel?->user ?? auth()->user();
        $appSettings = \App\Models\AppSetting::query()->pluck('value', 'key');
        $primaryColor = $brandingUser?->primary_color ?: ($appSettings['primary_color'] ?? '#2563eb');
        $secondaryColor = $brandingUser?->secondary_color ?: ($appSettings['secondary_color'] ?? '#0f172a');
        $brandName = $brandingUser?->business_name ?: ($appSettings['app_name'] ?? 'Proposta Fácil');
        $brandLogo = $brandingUser?->plan?->allows_custom_logo && $brandingUser?->logo_path
            ? \Illuminate\Support\Facades\Storage::url($brandingUser->logo_path)
            : null;
        $brandFooter = $brandingUser?->default_footer_text ?: '© '.date('Y').' '.$brandName.'. Feito para freelancers e pequenos negócios.';
    @endphp
</head>
<body class="bg-slate-50 text-slate-900" style="--color-primary:{{ $primaryColor }};--color-secondary:{{ $secondaryColor }}">
<header class="mx-auto flex max-w-6xl items-center justify-between p-6">
    <a href="{{ route('home') }}" class="flex items-center gap-3 text-xl font-bold text-[var(--color-primary)]">
        @if ($brandLogo)
            <img src="{{ $brandLogo }}" alt="Logo {{ $brandName }}" class="h-10 max-w-40 rounded object-contain" />
        @else
            <span>{{ $brandName }}</span>
        @endif
    </a>
    <nav class="flex items-center gap-4 text-sm text-[var(--color-secondary)]">
        <a href="{{ route('features') }}">Recursos</a>
        <a href="{{ route('pricing') }}">Preços</a>
        <a href="{{ route('terms') }}">Termos</a>
        <a href="{{ route('privacy') }}">Privacidade</a>
        @auth
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('profile.edit') }}">Perfil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="font-medium text-[var(--color-primary)]">Sair</button>
            </form>
        @else
            <a href="{{ route('login') }}">Entrar</a>
            <a href="{{ route('register') }}" class="font-medium text-[var(--color-primary)]">Criar conta</a>
        @endauth
    </nav>
</header>
<main class="mx-auto max-w-6xl p-6">@yield('content')</main>
<footer class="mx-auto max-w-6xl p-6 text-sm text-[var(--color-secondary)]/80">{{ $brandFooter }}</footer>
</body>
</html>
