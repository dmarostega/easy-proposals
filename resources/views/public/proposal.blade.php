@extends('layouts.public')

@php
    $money = fn ($value) => 'R$ '.number_format((float) $value, 2, ',', '.');
@endphp

@section('content')
<section class="rounded-3xl border border-[var(--color-primary)]/10 bg-white p-8 shadow-sm">
    @if(session('status'))
        <div class="mb-4 rounded bg-emerald-50 p-3 text-emerald-700">{{ session('status') }}</div>
    @endif

    <p class="text-sm text-slate-500">Cliente: {{ $proposal->customer->name }}</p>
    <p class="mt-1 text-sm text-slate-500">Validade: {{ $proposal->valid_until?->format('d/m/Y') ?? 'Sem validade definida' }}</p>

    <h1 class="mt-2 text-3xl font-bold text-[var(--color-secondary)]">{{ $proposal->title }}</h1>

    @if($proposal->description)
        <p class="mt-2 text-slate-600">{{ $proposal->description }}</p>
    @endif

    <div class="mt-6 overflow-x-auto rounded border">
        <table class="w-full min-w-[520px] text-left text-sm">
            <thead>
                <tr class="bg-[var(--color-primary)]/10 text-[var(--color-secondary)]">
                    <th class="p-3">Item</th>
                    <th>Qtd.</th>
                    <th>Unitário</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proposal->items as $item)
                    <tr class="border-t">
                        <td class="p-3">{{ $item->description }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $money($item->unit_price) }}</td>
                        <td>{{ $money($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 text-right">
        <p class="text-sm">Subtotal: {{ $money($proposal->subtotal) }}</p>
        <p class="text-sm">Desconto: {{ $money($proposal->discount) }}</p>
        <p class="text-2xl font-bold">Total: {{ $money($proposal->total) }}</p>
    </div>

    @if($proposal->commercial_terms)
        <div class="mt-6 rounded-2xl bg-slate-50 p-4">
            <h2 class="font-semibold text-[var(--color-secondary)]">Condições comerciais</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $proposal->commercial_terms }}</p>
        </div>
    @endif

    @if($proposal->notes)
        <div class="mt-3 rounded-2xl bg-slate-50 p-4">
            <h2 class="font-semibold text-[var(--color-secondary)]">Observações</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $proposal->notes }}</p>
        </div>
    @endif

    @if(! in_array($proposal->status, [\App\Enums\ProposalStatus::Approved, \App\Enums\ProposalStatus::Rejected], true))
        <div class="mt-6 flex gap-3">
            <form method="post" action="{{ route('public.proposals.approve', $proposal->publicToken->token) }}">
                @csrf
                <button class="rounded bg-[var(--color-primary)] px-4 py-2 text-white">Aprovar</button>
            </form>
            <form method="post" action="{{ route('public.proposals.reject', $proposal->publicToken->token) }}">
                @csrf
                <button class="rounded bg-[var(--color-secondary)] px-4 py-2 text-white">Recusar</button>
            </form>
        </div>
    @else
        <p class="mt-6 rounded bg-slate-50 p-3 text-slate-700">Esta proposta já foi {{ $proposal->status === \App\Enums\ProposalStatus::Approved ? 'aprovada' : 'recusada' }}.</p>
    @endif
</section>
@endsection
