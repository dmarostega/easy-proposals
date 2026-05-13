<x-mail::message>
# Você recebeu uma proposta

Olá, {{ $proposal->customer->name }}.

{{ $proposal->user->business_name ?: $proposal->user->name }} enviou a proposta **{{ $proposal->title }}**, no valor de **R$ {{ number_format((float) $proposal->total, 2, ',', '.') }}**.

<x-mail::button :url="$url">
Ver proposta
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
