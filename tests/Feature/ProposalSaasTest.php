<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Mail\ProposalApprovedMail;
use App\Mail\ProposalRejectedMail;
use App\Mail\ProposalSentMail;
use App\Mail\ProposalViewedMail;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalPdfService;
use App\Services\ProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
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

    public function test_user_can_send_proposal_to_customer_by_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id, 'email' => 'cliente@example.com']);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Site institucional',
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 800]],
        ]);

        $this->actingAs($user)
            ->postJson(route('propostas.send', $proposal))
            ->assertOk()
            ->assertJsonPath('status', ProposalStatus::Sent->value);

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Sent, $proposal->status);
        $this->assertNotNull($proposal->sent_at);

        Mail::assertSent(ProposalSentMail::class, function (ProposalSentMail $mail) use ($customer): bool {
            return $mail->hasTo($customer->email);
        });
        $this->assertDatabaseHas('proposal_events', [
            'proposal_id' => $proposal->id,
            'type' => 'sent',
        ]);
    }

    public function test_proposal_is_not_marked_sent_when_customer_email_delivery_fails(): void
    {
        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id, 'email' => 'cliente@example.com']);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Site institucional',
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 800]],
        ]);
        $pendingMail = \Mockery::mock();
        $pendingMail->shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP indisponivel'));
        Mail::shouldReceive('to')->once()->with($customer->email)->andReturn($pendingMail);

        $this->actingAs($user)
            ->postJson(route('propostas.send', $proposal))
            ->assertStatus(500);

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Draft, $proposal->status);
        $this->assertNull($proposal->sent_at);
        $this->assertDatabaseMissing('proposal_events', [
            'proposal_id' => $proposal->id,
            'type' => 'sent',
        ]);
    }

    public function test_user_can_download_real_pdf_for_proposal_when_plan_allows_it(): void
    {
        $user = User::factory()->create([
            'plan_id' => Plan::factory()->unlimited()->create(['allows_pdf' => true])->id,
            'business_name' => 'Studio Criativo',
            'primary_color' => '#123456',
            'secondary_color' => '#654321',
            'default_footer_text' => 'Obrigado pela preferencia.',
        ]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Projeto PDF',
            'description' => 'Documento comercial',
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 800]],
        ]);

        $response = $this->actingAs($user)->get(route('propostas.pdf', $proposal));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('/Type /Catalog', $response->getContent());
        $this->assertDatabaseHas('proposal_events', [
            'proposal_id' => $proposal->id,
            'type' => 'pdf_downloaded',
        ]);
    }

    public function test_pdf_long_free_text_blocks_are_split_across_pages(): void
    {
        $user = User::factory()->create([
            'plan_id' => Plan::factory()->unlimited()->create(['allows_pdf' => true])->id,
            'contact_details' => collect(range(1, 80))
                ->map(fn (int $line): string => "Contato linha {$line} com informacoes detalhadas")
                ->implode("\n"),
        ]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Projeto com texto longo',
            'commercial_terms' => collect(range(1, 140))
                ->map(fn (int $line): string => "Linha comercial {$line} com detalhes contratuais importantes")
                ->implode("\n"),
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 800]],
        ]);

        $pdf = app(ProposalPdfService::class)->render($proposal->fresh(['customer', 'items', 'user']));

        $this->assertMatchesRegularExpression('/\/Count [2-9]\d*/', $pdf);
        $this->assertStringContainsString('Linha comercial 140', $pdf);
        preg_match_all('/1 0 0 1 \d+\.\d+ (-?\d+\.\d+) Tm/', $pdf, $matches);
        $lowestTextPosition = min(array_map('floatval', $matches[1]));

        $this->assertGreaterThanOrEqual(34, $lowestTextPosition);
    }

    public function test_proposal_send_requires_customer_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id, 'email' => null]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Consultoria',
            'items' => [['description' => 'Diagnóstico', 'quantity' => 1, 'unit_price' => 400]],
        ]);

        $this->actingAs($user)
            ->postJson(route('propostas.send', $proposal))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('customer.email');

        $this->assertSame(ProposalStatus::Draft, $proposal->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_customer_can_approve_public_proposal_link(): void
    {
        Mail::fake();

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

        Mail::assertSent(ProposalApprovedMail::class, function (ProposalApprovedMail $mail) use ($user): bool {
            return $mail->hasTo($user->email);
        });
        $this->assertDatabaseHas('proposal_events', [
            'proposal_id' => $proposal->id,
            'type' => 'approved',
        ]);
    }

    public function test_owner_is_notified_when_customer_views_sent_proposal_once(): void
    {
        Mail::fake();

        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id, 'email' => 'cliente@example.com']);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Landing page',
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 500]],
        ]);

        $this->actingAs($user)->postJson(route('propostas.send', $proposal))->assertOk();
        $this->get(route('public.proposals.show', $proposal->publicToken->token))->assertOk();
        $this->get(route('public.proposals.show', $proposal->publicToken->token))->assertOk();

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Viewed, $proposal->status);
        $this->assertNotNull($proposal->viewed_at);
        Mail::assertSent(ProposalViewedMail::class, 1);
        Mail::assertSent(ProposalViewedMail::class, fn (ProposalViewedMail $mail) => $mail->hasTo($user->email));
        $this->assertSame(1, $proposal->events()->where('type', 'viewed')->count());
    }

    public function test_customer_can_view_public_proposal_when_owner_view_notification_fails(): void
    {
        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Landing page',
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 500]],
        ]);
        $proposal->update(['status' => ProposalStatus::Sent, 'sent_at' => now()]);
        $pendingMail = \Mockery::mock();
        $pendingMail->shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP indisponivel'));
        Mail::shouldReceive('to')->once()->with($user->email)->andReturn($pendingMail);

        $this->get(route('public.proposals.show', $proposal->publicToken->token))
            ->assertOk()
            ->assertSee('Landing page');

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Viewed, $proposal->status);
        $this->assertNotNull($proposal->viewed_at);
        $this->assertSame(1, $proposal->events()->where('type', 'viewed')->count());
    }

    public function test_viewed_notification_links_to_authenticated_proposal_page(): void
    {
        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Landing page',
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 500]],
        ]);

        $html = (new ProposalViewedMail($proposal->fresh(['customer', 'items', 'publicToken', 'user'])))->render();

        $this->assertStringContainsString('/propostas?proposal='.$proposal->id, $html);
    }

    public function test_customer_and_proposal_lists_can_be_filtered_and_paginated(): void
    {
        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $acme = Customer::factory()->create(['user_id' => $user->id, 'name' => 'Acme Comercial']);
        $beta = Customer::factory()->create(['user_id' => $user->id, 'name' => 'Beta Studio']);
        Customer::factory()->count(3)->create(['user_id' => $user->id]);
        Proposal::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $acme->id,
            'title' => 'Portal Acme',
            'status' => ProposalStatus::Approved,
        ]);
        Proposal::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $beta->id,
            'title' => 'Contrato Beta',
            'status' => ProposalStatus::Draft,
        ]);

        $this->actingAs($user)
            ->getJson(route('clientes.index', ['q' => 'Acme', 'per_page' => 1]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Acme Comercial');

        $this->actingAs($user)
            ->getJson(route('propostas.index', ['status' => ProposalStatus::Approved->value, 'per_page' => 1]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.title', 'Portal Acme')
            ->assertJsonPath('data.0.status', ProposalStatus::Approved->value);
    }

    public function test_dashboard_stats_can_be_filtered_by_created_date(): void
    {
        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        Proposal::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'status' => ProposalStatus::Approved,
            'total' => 250,
            'created_at' => now(),
        ]);
        Proposal::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'status' => ProposalStatus::Approved,
            'total' => 900,
            'created_at' => now()->subMonths(2),
        ]);

        $this->actingAs($user)
            ->getJson(route('dashboard', ['from' => now()->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()
            ->assertJsonPath('created_in_period', 1)
            ->assertJsonPath('approved_in_period', 1)
            ->assertJsonPath('approved_total_in_period', 250);
    }

    public function test_owner_is_notified_when_customer_rejects_public_proposal_once(): void
    {
        Mail::fake();

        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Landing page',
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 500]],
        ]);

        $this->post(route('public.proposals.reject', $proposal->publicToken->token))->assertRedirect();
        $this->post(route('public.proposals.reject', $proposal->publicToken->token))->assertRedirect();

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Rejected, $proposal->status);
        $this->assertNotNull($proposal->rejected_at);
        Mail::assertSent(ProposalRejectedMail::class, 1);
        Mail::assertSent(ProposalRejectedMail::class, fn (ProposalRejectedMail $mail) => $mail->hasTo($user->email));
        $this->assertSame(1, $proposal->events()->where('type', 'rejected')->count());
    }

    public function test_public_proposal_link_uses_owner_branding_for_guests(): void
    {
        $plan = Plan::factory()->unlimited()->create(['allows_custom_logo' => true]);
        $user = User::factory()->create([
            'plan_id' => $plan->id,
            'business_name' => 'Studio Criativo',
            'logo_path' => 'logos/studio.png',
            'primary_color' => '#123456',
            'secondary_color' => '#654321',
            'default_footer_text' => 'Obrigado pela preferência.',
        ]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Identidade visual',
            'items' => [['description' => 'Logo', 'quantity' => 1, 'unit_price' => 300]],
        ]);

        $this->get(route('public.proposals.show', $proposal->publicToken->token))
            ->assertOk()
            ->assertSee('--color-primary:#123456', false)
            ->assertSee('--color-secondary:#654321', false)
            ->assertSee('/storage/logos/studio.png', false)
            ->assertSee('Logo Studio Criativo')
            ->assertSee('Obrigado pela preferência.');
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
        $this->actingAs($admin)->get(route('admin.index'))->assertOk();
    }

    public function test_proposal_send_and_approval_dispatch_transactional_emails(): void
    {
        Mail::fake();

        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create([
            'user_id' => $user->id,
            'email' => 'cliente@example.com',
        ]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Landing page',
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 500]],
        ]);

        $this->actingAs($user)->postJson(route('propostas.send', $proposal))->assertOk();

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Sent, $proposal->status);
        $this->assertNotNull($proposal->sent_at);
        Mail::assertSent(ProposalSentMail::class, fn (ProposalSentMail $mail) => $mail->hasTo('cliente@example.com'));

        $this->post(route('public.proposals.approve', $proposal->publicToken->token))->assertRedirect();
        $this->post(route('public.proposals.approve', $proposal->publicToken->token))->assertRedirect();

        Mail::assertSent(ProposalApprovedMail::class, 1);
        Mail::assertSent(ProposalApprovedMail::class, fn (ProposalApprovedMail $mail) => $mail->hasTo($user->email));
    }

    public function test_public_proposal_actions_are_hidden_after_approval(): void
    {
        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Landing page',
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 500]],
        ]);

        $this->post(route('public.proposals.approve', $proposal->publicToken->token))->assertRedirect();

        $this->get(route('public.proposals.show', $proposal->publicToken->token))
            ->assertOk()
            ->assertSee('Esta proposta já foi aprovada.')
            ->assertDontSee('Aprovar')
            ->assertDontSee('Recusar');
    }

    public function test_finalized_proposal_cannot_be_changed_by_owner(): void
    {
        Mail::fake();

        $user = User::factory()->create(['plan_id' => Plan::factory()->unlimited()->create()->id]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $proposal = app(ProposalService::class)->create($user, [
            'customer_id' => $customer->id,
            'title' => 'Landing page',
            'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 500]],
        ]);

        $this->post(route('public.proposals.approve', $proposal->publicToken->token))->assertRedirect();

        $this->actingAs($user)
            ->putJson(route('propostas.update', $proposal), [
                'customer_id' => $customer->id,
                'title' => 'Landing page alterada',
                'items' => [['description' => 'Design', 'quantity' => 1, 'unit_price' => 700]],
            ])
            ->assertForbidden();

        $this->actingAs($user)->postJson(route('propostas.send', $proposal))->assertForbidden();
        $this->actingAs($user)->deleteJson(route('propostas.destroy', $proposal))->assertForbidden();

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Approved, $proposal->status);
        $this->assertSame('Landing page', $proposal->title);
    }

    public function test_admin_can_manage_resources_inside_target_user_context_with_audit(): void
    {
        $plan = Plan::factory()->unlimited()->create();
        $admin = User::factory()->create(['plan_id' => $plan->id, 'role' => UserRole::Admin]);
        $targetUser = User::factory()->create(['plan_id' => $plan->id]);

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$targetUser->id}/clientes", [
                'name' => 'Cliente administrado',
                'email' => 'cliente@example.com',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Cliente administrado');

        $this->assertDatabaseHas('customers', [
            'user_id' => $targetUser->id,
            'name' => 'Cliente administrado',
        ]);
        $this->assertSame(0, $admin->customers()->count());
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $admin->id,
            'target_user_id' => $targetUser->id,
            'action' => 'target_customer.created',
        ]);
    }

    public function test_admin_can_update_finalized_target_proposal_from_admin_context(): void
    {
        $plan = Plan::factory()->unlimited()->create();
        $admin = User::factory()->create(['plan_id' => $plan->id, 'role' => UserRole::Admin]);
        $targetUser = User::factory()->create(['plan_id' => $plan->id]);
        $customer = Customer::factory()->create(['user_id' => $targetUser->id]);
        $proposal = app(ProposalService::class)->create($targetUser, [
            'customer_id' => $customer->id,
            'title' => 'Proposta finalizada',
            'items' => [['description' => 'Servico', 'quantity' => 1, 'unit_price' => 100]],
        ]);
        $proposal->update(['status' => ProposalStatus::Approved, 'approved_at' => now()]);

        $this->actingAs($admin)
            ->putJson("/admin/usuarios/{$targetUser->id}/propostas/{$proposal->id}", [
                'customer_id' => $customer->id,
                'title' => 'Proposta alterada pelo admin',
                'items' => [['description' => 'Servico premium', 'quantity' => 2, 'unit_price' => 150]],
            ])
            ->assertOk()
            ->assertJsonPath('title', 'Proposta alterada pelo admin')
            ->assertJsonPath('total', '300.00');

        $proposal->refresh();
        $this->assertSame('Proposta alterada pelo admin', $proposal->title);
        $this->assertSame(ProposalStatus::Approved, $proposal->status);
        $this->assertDatabaseHas('proposal_events', [
            'proposal_id' => $proposal->id,
            'user_id' => $admin->id,
            'type' => 'updated',
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $admin->id,
            'target_user_id' => $targetUser->id,
            'action' => 'target_proposal.updated',
        ]);
    }
}
