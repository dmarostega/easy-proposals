@extends('layouts.public')

@section('content')
    <section class="space-y-8">
        <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-[var(--color-primary)]/20">
            <p class="text-sm font-semibold uppercase tracking-wide text-[var(--color-primary)]">Minha marca</p>
            <h1 class="mt-2 text-3xl font-bold text-[var(--color-secondary)]">Personalize suas propostas</h1>
            <p class="mt-3 max-w-2xl text-slate-600">Configure nome comercial, cores, contatos e o rodapé padrão exibidos nos documentos e links públicos.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="font-semibold">Revise os campos abaixo:</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="grid gap-6 rounded-3xl bg-white p-8 shadow-sm ring-1 ring-[var(--color-primary)]/20 md:grid-cols-2">
            @csrf
            @method('PUT')

            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-700">Nome comercial</span>
                <input name="business_name" value="{{ old('business_name', $user->business_name) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Ex.: Studio Criativo" />
            </label>

            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-700">Logo personalizada</span>
                <input name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="w-full rounded-xl border border-slate-300 px-4 py-3" />
                <span class="block text-xs text-slate-500">
                    @if ($user->plan?->allows_custom_logo)
                        PNG, JPG ou WebP até 2MB.
                    @else
                        Disponível em planos com personalização de marca.
                    @endif
                </span>
            </label>

            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-700">Cor primária</span>
                <input name="primary_color" type="color" value="{{ old('primary_color', $user->primary_color) }}" class="h-12 w-full rounded-xl border border-slate-300 px-2 py-1" />
            </label>

            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-700">Cor secundária</span>
                <input name="secondary_color" type="color" value="{{ old('secondary_color', $user->secondary_color) }}" class="h-12 w-full rounded-xl border border-slate-300 px-2 py-1" />
            </label>

            <label class="space-y-2 md:col-span-2">
                <span class="text-sm font-semibold text-slate-700">Dados de contato</span>
                <textarea name="contact_details" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Telefone, e-mail, endereço ou canais de atendimento">{{ old('contact_details', $user->contact_details) }}</textarea>
            </label>

            <label class="space-y-2 md:col-span-2">
                <span class="text-sm font-semibold text-slate-700">Rodapé padrão</span>
                <textarea name="default_footer_text" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Mensagem padrão para o fim das propostas">{{ old('default_footer_text', $user->default_footer_text) }}</textarea>
            </label>

            <div class="flex items-center justify-between gap-4 md:col-span-2">
                @if ($user->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($user->logo_path) }}" alt="Logo atual" class="h-14 max-w-40 rounded-lg object-contain ring-1 ring-slate-200" />
                @else
                    <span class="text-sm text-slate-500">Nenhum logo enviado.</span>
                @endif
                <button class="rounded-xl bg-[var(--color-primary)] px-5 py-3 font-semibold text-white shadow-sm">Salvar personalização</button>
            </div>
        </form>
    </section>
@endsection
