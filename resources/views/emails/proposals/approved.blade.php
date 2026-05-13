<x-mail::message>
# Proposta aprovada

A proposta **{{ $proposal->title }}** foi aprovada por {{ $proposal->customer->name }}.

Valor aprovado: **R$ {{ number_format((float) $proposal->total, 2, ',', '.') }}**.

<x-mail::button :url="$url">
Abrir proposta
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
