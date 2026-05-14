@extends('layouts.public')

@section('content')
<form method="post" action="{{ route('login') }}" class="mx-auto max-w-md rounded-2xl bg-white p-8 shadow-sm">
    @csrf
    <h1 class="mb-6 text-2xl font-bold">Entrar</h1>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Revise os campos abaixo:</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <label class="block">
        E-mail
        <input class="mt-1 w-full rounded border p-2 @error('email') border-red-400 @enderror" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" @error('email') aria-invalid="true" @enderror>
        @error('email')
            <span class="mt-1 block text-sm text-red-700">{{ $message }}</span>
        @enderror
    </label>
    <label class="mt-4 block">
        Senha
        <input class="mt-1 w-full rounded border p-2 @error('password') border-red-400 @enderror" name="password" type="password" required autocomplete="current-password" @error('password') aria-invalid="true" @enderror>
        @error('password')
            <span class="mt-1 block text-sm text-red-700">{{ $message }}</span>
        @enderror
    </label>
    <div class="mt-6 flex items-center justify-between gap-4">
        <button class="btn-primary rounded px-4 py-2">Entrar</button>
        <a href="{{ route('register') }}" class="text-sm font-medium text-[var(--color-primary)]">Criar conta</a>
    </div>
</form>
@endsection
