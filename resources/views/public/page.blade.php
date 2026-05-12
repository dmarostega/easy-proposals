@extends('layouts.public')
@section('content')
<section class="rounded-3xl bg-white p-10 shadow-sm">
    <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-[var(--color-primary)]">SaaS para propostas comerciais</p>
    <h1 class="max-w-3xl text-4xl font-bold">{{ $title }}</h1>
    <p class="mt-4 max-w-2xl text-lg text-slate-600">{{ $description }}</p>
    @if($page === 'home')
        <div class="mt-8 grid gap-4 md:grid-cols-3"><div>Crie propostas com itens, descontos e validade.</div><div>Envie link público seguro para aprovação.</div><div>Acompanhe status e valor aprovado.</div></div>
    @elseif($page === 'precos')
        <div class="mt-8 grid gap-4 md:grid-cols-3"><div><strong>Gratuito</strong><br>3 propostas/mês.</div><div><strong>Pro</strong><br>50 propostas/mês com PDF.</div><div><strong>Plus</strong><br>Propostas ilimitadas e logo personalizada.</div></div>
    @else
        <p class="mt-8 text-slate-700">Conteúdo institucional público do Proposta Fácil.</p>
    @endif
</section>
@endsection
