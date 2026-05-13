<x-mail::message>
# Proposta visualizada

{{ $proposal->customer->name }} visualizou a proposta **{{ $proposal->title }}**.

Valor da proposta: **R$ {{ number_format((float) $proposal->total, 2, ',', '.') }}**.

<x-mail::button :url="$url">
Abrir no painel
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
