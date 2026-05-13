@extends('layouts.public')

@php
    $owner = $proposal->user;
    $brandName = $owner->business_name ?: $owner->name;
    $money = fn ($value) => 'R$ '.number_format((float) $value, 2, ',', '.');
@endphp

@section('content')
<section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-[var(--color-primary)]/10">
    <div class="bg-[var(--color-secondary)] px-6 py-8 text-white md:px-10">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-white/70">Proposta comercial</p>
                <h1 class="mt-3 max-w-3xl text-3xl font-black md:text-4xl">{{ $proposal->title }}</h1>
                @if($proposal->description)
                    <p class="mt-3 max-w-3xl text-white/80">{{ $proposal->description }}</p>
                @endif
            </div>
            <div class="rounded-2xl bg-white/10 p-4 text-sm ring-1 ring-white/15">
                <p class="text-white/70">Cliente</p>
                <strong class="mt-1 block text-lg">{{ $proposal->customer->name }}</strong>
                <p class="mt-3 text-white/70">Validade</p>
                <strong class="mt-1 block">{{ $proposal->valid_until?->format('d/m/Y') ?? 'Sem validade definida' }}</strong>
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="mx-6 mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 md:mx-10">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-8 p-6 md:p-10 lg:grid-cols-[1fr_320px]">
        <div>
            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full min-w-[620px] text-left text-sm">
                    <thead class="bg-slate-50 text-[var(--color-secondary)]">
                        <tr>
                            <th class="p-4">Item</th>
                            <th>Qtd.</th>
                            <th>Unitário</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proposal->items as $item)
                            <tr class="border-t border-slate-200">
                                <td class="p-4 font-medium text-slate-900">{{ $item->description }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $money($item->unit_price) }}</td>
                                <td class="font-semibold">{{ $money($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($proposal->commercial_terms)
                <div class="mt-6 rounded-2xl bg-slate-50 p-5">
                    <h2 class="font-semibold text-[var(--color-secondary)]">Condições comerciais</h2>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $proposal->commercial_terms }}</p>
                </div>
            @endif

            @if($proposal->notes)
                <div class="mt-4 rounded-2xl bg-slate-50 p-5">
                    <h2 class="font-semibold text-[var(--color-secondary)]">Observações</h2>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $proposal->notes }}</p>
                </div>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200 p-5">
                <p class="flex justify-between text-sm text-slate-600"><span>Subtotal</span><strong>{{ $money($proposal->subtotal) }}</strong></p>
                <p class="mt-2 flex justify-between text-sm text-slate-600"><span>Desconto</span><strong>{{ $money($proposal->discount) }}</strong></p>
                <p class="mt-4 border-t border-slate-200 pt-4 text-sm text-slate-500">Total da proposta</p>
                <strong class="mt-1 block text-3xl text-[var(--color-secondary)]">{{ $money($proposal->total) }}</strong>
            </div>

            @if(! in_array($proposal->status, [\App\Enums\ProposalStatus::Approved, \App\Enums\ProposalStatus::Rejected], true))
                <div class="grid gap-3">
                    <form method="post" action="{{ route('public.proposals.approve', $proposal->publicToken->token) }}">
                        @csrf
                        <button class="w-full rounded-xl bg-[var(--color-primary)] px-5 py-3 font-semibold text-white">Aprovar proposta</button>
                    </form>
                    <form method="post" action="{{ route('public.proposals.reject', $proposal->publicToken->token) }}">
                        @csrf
                        <button class="w-full rounded-xl border border-slate-300 px-5 py-3 font-semibold text-[var(--color-secondary)]">Recusar</button>
                    </form>
                </div>
            @else
                <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    Esta proposta já foi {{ $proposal->status === \App\Enums\ProposalStatus::Approved ? 'aprovada' : 'recusada' }}.
                </p>
            @endif

            <div class="rounded-2xl border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Enviado por</p>
                <strong class="mt-1 block text-[var(--color-secondary)]">{{ $brandName }}</strong>
                @if($owner->contact_details)
                    <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $owner->contact_details }}</p>
                @endif
            </div>
        </aside>
    </div>
</section>
@endsection
