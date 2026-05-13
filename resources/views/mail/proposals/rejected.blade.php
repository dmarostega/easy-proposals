<p>Olá, {{ $proposal->user->name }}.</p>

<p>A proposta <strong>{{ $proposal->title }}</strong> foi recusada por {{ $proposal->customer->name }}.</p>
<p>Total da proposta: R$ {{ number_format((float) $proposal->total, 2, ',', '.') }}</p>

<p>Acesse o painel para revisar a negociação.</p>
