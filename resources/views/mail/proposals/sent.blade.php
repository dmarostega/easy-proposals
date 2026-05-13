@php($senderName = $proposal->user->business_name ?: $proposal->user->name)
<p>Olá, {{ $proposal->customer->name }}.</p>

<p>{{ $senderName }} enviou uma proposta para você:</p>

<p><strong>{{ $proposal->title }}</strong></p>
<p>Total: R$ {{ number_format((float) $proposal->total, 2, ',', '.') }}</p>

<p>
    Acesse o link abaixo para visualizar, aprovar ou recusar a proposta:<br>
    <a href="{{ $publicUrl }}">{{ $publicUrl }}</a>
</p>

@if ($proposal->valid_until)
    <p>Validade: {{ $proposal->valid_until->format('d/m/Y') }}</p>
@endif

<p>Obrigado.</p>
