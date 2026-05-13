<p>Olá, {{ $proposal->user->name }}.</p>

<p>A proposta <strong>{{ $proposal->title }}</strong> foi aprovada por {{ $proposal->customer->name }}.</p>
<p>Total aprovado: R$ {{ number_format((float) $proposal->total, 2, ',', '.') }}</p>

<p>Acesse o painel para acompanhar os próximos passos.</p>
