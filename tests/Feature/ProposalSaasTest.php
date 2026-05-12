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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_inactive_authenticated_user_is_logged_out_from_protected_routes(): void
    {
        $user = User::factory()->create([
            'plan_id' => Plan::factory()->create()->id,
            'is_active' => false,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $this->assertGuest();
    }

    public function test_admin_user_update_rejects_duplicate_email(): void
    {
        $plan = Plan::factory()->create();
        $admin = User::factory()->create(['plan_id' => $plan->id, 'role' => UserRole::Admin]);
        $existingUser = User::factory()->create(['plan_id' => $plan->id, 'email' => 'existing@example.com']);
        $updatedUser = User::factory()->create(['plan_id' => $plan->id, 'email' => 'updated@example.com']);

        $this->actingAs($admin)
            ->putJson(route('admin.usuarios.update', $updatedUser), [
                'name' => $updatedUser->name,
                'email' => $existingUser->email,
                'plan_id' => $plan->id,
                'role' => UserRole::User->value,
                'is_active' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }


    public function test_user_with_branding_plan_can_upload_logo_and_update_profile(): void
    {
        Storage::fake('public');
        $plan = Plan::factory()->create(['allows_custom_logo' => true]);
        $user = User::factory()->create(['plan_id' => $plan->id]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'business_name' => 'Studio Criativo',
            'contact_details' => 'contato@example.com',
            'default_footer_text' => 'Obrigado pela preferência.',
            'primary_color' => '#123456',
            'secondary_color' => '#654321',
            'logo' => UploadedFile::fake()->image('logo.png', 400, 200),
        ]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertSame('Studio Criativo', $user->business_name);
        $this->assertSame('#123456', $user->primary_color);
        $this->assertNotNull($user->logo_path);
        Storage::disk('public')->assertExists($user->logo_path);
    }

    public function test_logo_upload_requires_plan_permission(): void
    {
        Storage::fake('public');
        $plan = Plan::factory()->create(['allows_custom_logo' => false]);
        $user = User::factory()->create(['plan_id' => $plan->id]);

        $this->actingAs($user)->put(route('profile.update'), [
            'business_name' => 'Sem marca',
            'primary_color' => '#123456',
            'secondary_color' => '#654321',
            'logo' => UploadedFile::fake()->image('logo.png', 400, 200),
        ])->assertSessionHasErrors('logo');

        $this->assertNull($user->fresh()->logo_path);
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
