<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProposalSaasTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_proposal_with_items_and_public_token(): void
    {
        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Site institucional',
            'description' => 'Criação de site',
            'discount' => 50,
            'items' => [['description' => 'Design e desenvolvimento', 'quantity' => 2, 'unit_price' => 500]],
        ]);

        $this->assertSame('950.00', $proposal->total);
        $this->assertCount(1, $proposal->items);
        $this->assertNotNull($proposal->publicToken);
    }

    public function test_free_plan_monthly_proposal_limit_is_enforced(): void
    {
        $plan = Plan::factory()->create(['monthly_proposal_limit' => 3]);
        $user = User::factory()->create(['plan_id' => $plan->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        Proposal::factory()->count(3)->create(['user_id' => $user->id, 'customer_id' => $customer->id]);

        $this->expectException(ValidationException::class);
        app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Nova proposta',
            'items' => [['description' => 'Serviço', 'quantity' => 1, 'unit_price' => 100]],
        ]);
    }

    public function test_customer_can_approve_public_proposal_link(): void
    {
        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Identidade visual',
            'items' => [['description' => 'Logo', 'quantity' => 1, 'unit_price' => 300]],
        ]);

        $this->post(route('public.proposals.approve', $proposal->publicToken->token))->assertRedirect();

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Approved, $proposal->status);
        $this->assertNotNull($proposal->approved_at);
    }

    public function test_admin_area_is_protected_by_admin_role(): void
    {
        $plan = Plan::factory()->create();
        $regularUser = User::factory()->create(['plan_id' => $plan->id]);
        $admin = User::factory()->create(['plan_id' => $plan->id, 'role' => UserRole::Admin]);

        $this->actingAs($regularUser)->get(route('admin.reports'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.reports'))->assertOk();
    }
}
