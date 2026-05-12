@extends('layouts.public')
@section('content')
<form method="post" action="{{ route('login') }}" class="mx-auto max-w-md rounded-2xl bg-white p-8 shadow-sm">@csrf<h1 class="mb-6 text-2xl font-bold">Entrar</h1><label class="block">E-mail<input class="mt-1 w-full rounded border p-2" name="email" type="email" required></label><label class="mt-4 block">Senha<input class="mt-1 w-full rounded border p-2" name="password" type="password" required></label><button class="btn-primary mt-6 rounded px-4 py-2">Entrar</button></form>
@endsection
