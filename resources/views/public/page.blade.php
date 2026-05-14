@extends('layouts.public')

@php
    $planFeatureText = function ($plan): array {
        $proposalLimit = $plan->monthly_proposal_limit
            ? $plan->monthly_proposal_limit.' propostas por mes'
            : 'Propostas ilimitadas';
        $customerLimit = $plan->customer_limit
            ? $plan->customer_limit.' clientes cadastrados'
            : 'Clientes ilimitados';

        return array_filter([
            $proposalLimit,
            $customerLimit,
            $plan->allows_pdf ? 'Exportacao em PDF' : null,
            $plan->allows_custom_logo ? 'Logo personalizada' : null,
            'Clientes e servicos reutilizaveis',
            'Link publico de aprovacao',
        ]);
    };

    $formatPlanPrice = fn ($plan) => $plan->monthly_price_cents > 0
        ? 'R$ '.number_format($plan->monthly_price_cents / 100, 2, ',', '.').'/mes'
        : 'R$ 0/mes';
@endphp

@section('content')
    @if($page === 'home')
        <section class="grid gap-10 py-10 lg:grid-cols-[1fr_440px] lg:items-center">
            <div>
                <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-[var(--color-primary)]">SaaS para propostas comerciais</p>
                <h1 class="max-w-3xl text-5xl font-black leading-tight text-[var(--color-secondary)]">{{ $title }}</h1>
                <p class="mt-5 max-w-2xl text-lg text-[var(--color-secondary)]/80">{{ $description }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="rounded-lg bg-[var(--color-primary)] px-5 py-3 font-semibold text-white">Criar conta</a>
                    <a href="{{ route('features') }}" class="rounded-lg border border-slate-300 px-5 py-3 font-semibold text-[var(--color-secondary)]">Ver recursos</a>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-5">
                    <p class="text-sm font-semibold text-[var(--color-primary)]">Proposta comercial</p>
                    <h2 class="mt-1 text-2xl font-bold text-[var(--color-secondary)]">Site institucional</h2>
                    <p class="mt-2 text-sm text-slate-500">Cliente: Studio Aurora</p>
                </div>
                <div class="p-5">
                    <div class="overflow-hidden rounded-lg border border-slate-200">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr><th class="p-3">Item</th><th>Qtd.</th><th>Total</th></tr>
                            </thead>
                            <tbody>
                                <tr class="border-t"><td class="p-3 font-medium">Design e desenvolvimento</td><td>1</td><td>R$ 2.400,00</td></tr>
                                <tr class="border-t"><td class="p-3 font-medium">Publicacao</td><td>1</td><td>R$ 400,00</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex items-center justify-between rounded-lg bg-slate-950 p-4 text-white">
                        <span>Total aprovado</span>
                        <strong>R$ 2.800,00</strong>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Aprovada</span>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-700">PDF disponivel</span>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700">Link publico</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 py-8 md:grid-cols-3">
            <article class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-bold text-[var(--color-secondary)]">Monte propostas rapidamente</h2>
                <p class="mt-2 text-sm text-slate-600">Use clientes e servicos cadastrados para preencher itens, valores e condicoes comerciais com menos retrabalho.</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-bold text-[var(--color-secondary)]">Envie um link de aprovacao</h2>
                <p class="mt-2 text-sm text-slate-600">O cliente acessa a proposta em uma pagina publica, aprova ou recusa, e o historico fica registrado.</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-bold text-[var(--color-secondary)]">Acompanhe o resultado</h2>
                <p class="mt-2 text-sm text-slate-600">Veja status, valores aprovados, eventos de visualizacao e filtros por periodo no painel autenticado.</p>
            </article>
        </section>
    @elseif($page === 'recursos')
        <section class="py-10">
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-[var(--color-primary)]">Recursos</p>
            <h1 class="max-w-3xl text-4xl font-black text-[var(--color-secondary)]">{{ $title }}</h1>
            <p class="mt-4 max-w-2xl text-lg text-[var(--color-secondary)]/80">{{ $description }}</p>
        </section>

        <section class="grid gap-4 md:grid-cols-2">
            @foreach([
                ['Clientes organizados', 'Cadastre dados de contato, documentos, endereco e observacoes para reaproveitar em novas propostas.'],
                ['Catalogo de servicos', 'Mantenha servicos ativos com descricao e preco para preencher itens da proposta sem perder liberdade de edicao.'],
                ['Editor visual de propostas', 'Monte titulo, descricao, validade, desconto, itens, observacoes e condicoes comerciais em uma unica tela.'],
                ['Link publico de aprovacao', 'Compartilhe uma pagina de proposta com botoes de aprovar e recusar, sem exigir login do cliente.'],
                ['Historico de eventos', 'Registre criacao, envio, visualizacao, aprovacao, recusa e download para acompanhar o ciclo da proposta.'],
                ['Painel admin contextual', 'Administre usuarios, planos, configuracoes e acesse contas especificas com auditoria das acoes.'],
            ] as [$featureTitle, $featureDescription])
                <article class="rounded-lg border border-slate-200 bg-white p-6">
                    <h2 class="text-lg font-bold text-[var(--color-secondary)]">{{ $featureTitle }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $featureDescription }}</p>
                </article>
            @endforeach
        </section>
    @elseif($page === 'precos')
        <section class="py-10">
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-[var(--color-primary)]">Planos</p>
            <h1 class="max-w-3xl text-4xl font-black text-[var(--color-secondary)]">{{ $title }}</h1>
            <p class="mt-4 max-w-2xl text-lg text-[var(--color-secondary)]/80">{{ $description }}</p>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            @forelse($plans as $plan)
                <article class="flex rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex w-full flex-col">
                        <div>
                            <h2 class="text-xl font-bold text-[var(--color-secondary)]">{{ $plan->name }}</h2>
                            <p class="mt-3 text-3xl font-black text-[var(--color-primary)]">{{ $formatPlanPrice($plan) }}</p>
                        </div>
                        <ul class="mt-5 grid gap-3 text-sm text-slate-600">
                            @foreach($planFeatureText($plan) as $feature)
                                <li class="flex gap-2"><span class="font-bold text-emerald-600">&check;</span><span>{{ $feature }}</span></li>
                            @endforeach
                        </ul>
                        <a href="{{ route('register') }}" class="mt-6 rounded-lg bg-slate-950 px-4 py-3 text-center text-sm font-semibold text-white">Comecar com {{ $plan->name }}</a>
                    </div>
                </article>
            @empty
                <article class="rounded-lg border border-slate-200 bg-white p-6 md:col-span-3">
                    <h2 class="text-xl font-bold text-[var(--color-secondary)]">Planos em configuracao</h2>
                    <p class="mt-2 text-slate-600">Os planos ativos ainda nao foram publicados. Acesse novamente em breve ou fale com o administrador da plataforma.</p>
                </article>
            @endforelse
        </section>

        <section class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="text-xl font-bold text-[var(--color-secondary)]">O que esta incluso em todos os planos</h2>
            <div class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-2">
                <p>Cadastro de clientes e servicos reutilizaveis.</p>
                <p>Editor de propostas com itens, desconto e validade.</p>
                <p>Links publicos para aprovacao ou recusa.</p>
                <p>Painel com filtros de periodo, status e pesquisa.</p>
            </div>
        </section>
    @elseif($page === 'termos')
        <section class="prose prose-slate max-w-none rounded-lg border border-slate-200 bg-white p-8">
            <p class="text-sm font-semibold uppercase tracking-wide text-[var(--color-primary)]">Termos</p>
            <h1>{{ $title }}</h1>
            <p>Ao usar o Proposta Facil, o usuario declara que possui permissao para cadastrar dados de clientes, servicos, propostas comerciais e demais informacoes inseridas na plataforma.</p>

            <h2>Uso da plataforma</h2>
            <p>A plataforma oferece ferramentas para criar propostas, organizar clientes e servicos, gerar links publicos e acompanhar eventos relacionados a essas propostas. O usuario e responsavel pelo conteudo comercial, valores, prazos e condicoes informadas.</p>

            <h2>Contas e acesso</h2>
            <p>Cada conta deve manter dados de acesso protegidos. Administradores da plataforma podem realizar manutencoes, ajustes de plano, suporte operacional e auditoria quando necessario.</p>

            <h2>Propostas e aprovacoes</h2>
            <p>Os links publicos permitem que clientes visualizem, aprovem ou recusem propostas. A aprovacao registrada na plataforma deve ser conferida pelo usuario conforme suas regras comerciais, contratos e politicas internas.</p>

            <h2>Limites e planos</h2>
            <p>Recursos como quantidade de propostas, limite de clientes, PDF e logo personalizada podem variar conforme o plano contratado ou configurado pelo administrador.</p>

            <h2>Alteracoes</h2>
            <p>Estes termos podem ser atualizados para refletir melhorias do produto, novas regras de uso ou necessidades legais e operacionais.</p>
        </section>
    @elseif($page === 'privacidade')
        <section class="prose prose-slate max-w-none rounded-lg border border-slate-200 bg-white p-8">
            <p class="text-sm font-semibold uppercase tracking-wide text-[var(--color-primary)]">Privacidade</p>
            <h1>{{ $title }}</h1>
            <p>Esta politica descreve como o Proposta Facil trata dados usados para criar contas, cadastrar clientes, montar propostas e registrar eventos da plataforma.</p>

            <h2>Dados tratados</h2>
            <p>Podem ser tratados nome, e-mail, telefone, documento, endereco, informacoes comerciais, dados de marca, servicos, valores de propostas e eventos como envio, visualizacao, aprovacao e recusa.</p>

            <h2>Finalidade</h2>
            <p>Os dados sao usados para operar a plataforma, autenticar usuarios, montar propostas, exibir links publicos, registrar historico, prestar suporte e manter seguranca operacional.</p>

            <h2>Compartilhamento</h2>
            <p>Dados de proposta podem ser acessados por clientes por meio de links publicos gerados pelo proprio usuario. Administradores podem acessar dados quando necessario para suporte, configuracao, manutencao ou auditoria.</p>

            <h2>Seguranca</h2>
            <p>A plataforma usa controles de autenticacao, escopo por usuario e registros administrativos para reduzir acesso indevido. O usuario tambem deve proteger suas credenciais e revisar os dados antes de compartilhar links publicos.</p>

            <h2>Retencao e atualizacao</h2>
            <p>Dados podem ser mantidos enquanto a conta estiver ativa ou enquanto forem necessarios para operacao, historico, suporte e obrigacoes aplicaveis. O usuario pode atualizar cadastros diretamente na area autenticada.</p>
        </section>
    @endif
@endsection
