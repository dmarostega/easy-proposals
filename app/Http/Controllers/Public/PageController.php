<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

class PageController extends Controller
{
    public function home(): View
    {
        return $this->render('home', [
            'title' => 'Proposta Facil - propostas comerciais profissionais',
            'description' => 'Crie propostas, orcamentos e links publicos de aprovacao para seus clientes.',
        ]);
    }

    public function features(): View
    {
        return $this->render('recursos', [
            'title' => 'Recursos do Proposta Facil',
            'description' => 'Clientes, servicos reutilizaveis, propostas com aprovacao publica, PDF e painel administrativo.',
        ]);
    }

    public function pricing(): View
    {
        return $this->render('precos', [
            'title' => 'Precos do Proposta Facil',
            'description' => 'Planos Gratuito, Pro e Plus para freelancers, prestadores de servico e pequenos negocios.',
        ]);
    }

    public function terms(): View
    {
        return $this->render('termos', [
            'title' => 'Termos de Uso',
            'description' => 'Termos de uso do Proposta Facil para contas, propostas, clientes e envio de links publicos.',
        ]);
    }

    public function privacy(): View
    {
        return $this->render('privacidade', [
            'title' => 'Politica de Privacidade',
            'description' => 'Como o Proposta Facil trata dados de usuarios, clientes, propostas e eventos da plataforma.',
        ]);
    }

    private function render(string $page, array $metadata): View
    {
        return view('public.page', [
            ...$metadata,
            'page' => $page,
            'plans' => $this->activePlans(),
        ]);
    }

    private function activePlans(): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('monthly_price_cents')
            ->orderBy('id')
            ->get();
    }
}
