@extends('layouts.public')
@section('content')
<section class="rounded-3xl bg-white p-8 shadow-sm">
    @if(session('status'))<div class="mb-4 rounded bg-emerald-50 p-3 text-emerald-700">{{ session('status') }}</div>@endif
    <h1 class="text-3xl font-bold">{{ $proposal->title }}</h1>
    <p class="mt-2 text-slate-600">Cliente: {{ $proposal->customer->name }}</p>
    <p class="mt-2">Status: <strong>{{ $proposal->status->value }}</strong></p>
    <div class="mt-6 overflow-hidden rounded border"><table class="w-full text-left"><thead><tr class="bg-slate-100"><th class="p-3">Item</th><th>Qtd.</th><th>Unitário</th><th>Total</th></tr></thead><tbody>@foreach($proposal->items as $item)<tr class="border-t"><td class="p-3">{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>R$ {{ $item->unit_price }}</td><td>R$ {{ $item->total }}</td></tr>@endforeach</tbody></table></div>
    <p class="mt-6 text-2xl font-bold">Total: R$ {{ $proposal->total }}</p>
    <div class="mt-6 flex gap-3"><form method="post" action="{{ route('public.proposals.approve', $proposal->publicToken->token) }}">@csrf<button class="rounded bg-emerald-600 px-4 py-2 text-white">Aprovar</button></form><form method="post" action="{{ route('public.proposals.reject', $proposal->publicToken->token) }}">@csrf<button class="rounded bg-rose-600 px-4 py-2 text-white">Recusar</button></form></div>
</section>
@endsection
