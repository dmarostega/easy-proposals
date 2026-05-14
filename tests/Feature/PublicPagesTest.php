<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_features_pages_have_specific_content(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Monte propostas rapidamente')
            ->assertSee('Envie um link de aprovacao')
            ->assertDontSee('Conteudo institucional publico');

        $this->get(route('features'))
            ->assertOk()
            ->assertSee('Catalogo de servicos')
            ->assertSee('Painel admin contextual')
            ->assertDontSee('Conteudo institucional publico');
    }

    public function test_pricing_page_shows_active_plan_values(): void
    {
        Plan::factory()->create([
            'name' => 'Pro',
            'monthly_price_cents' => 2900,
            'monthly_proposal_limit' => 50,
            'customer_limit' => null,
            'allows_pdf' => true,
        ]);
        Plan::factory()->create([
            'name' => 'Inativo',
            'monthly_price_cents' => 9900,
            'is_active' => false,
        ]);

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Pro')
            ->assertSee('R$ 29,00/mes')
            ->assertSee('50 propostas por mes')
            ->assertSee('Clientes ilimitados')
            ->assertSee('Exportacao em PDF')
            ->assertDontSee('Inativo');
    }

    public function test_public_terms_and_privacy_have_specific_content(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Uso da plataforma')
            ->assertSee('Propostas e aprovacoes')
            ->assertDontSee('Conteudo institucional publico');

        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Dados tratados')
            ->assertSee('Retencao e atualizacao')
            ->assertDontSee('Conteudo institucional publico');
    }
}
