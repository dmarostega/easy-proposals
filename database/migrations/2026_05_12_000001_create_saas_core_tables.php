<?php

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('monthly_price_cents')->default(0);
            $table->unsignedInteger('monthly_proposal_limit')->nullable();
            $table->unsignedInteger('customer_limit')->nullable();
            $table->boolean('allows_pdf')->default(false);
            $table->boolean('allows_custom_logo')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('plan_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->default(UserRole::User->value)->after('password')->index();
            $table->boolean('is_active')->default(true)->after('role')->index();
            $table->string('business_name')->nullable()->after('is_active');
            $table->string('logo_path')->nullable()->after('business_name');
            $table->string('primary_color', 20)->default('#2563eb')->after('logo_path');
            $table->string('secondary_color', 20)->default('#0f172a')->after('primary_color');
            $table->text('default_footer_text')->nullable()->after('secondary_color');
            $table->text('contact_details')->nullable()->after('default_footer_text');
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('document')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'name']);
        });

        Schema::create('service_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['user_id', 'name']);
        });

        Schema::create('proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('valid_until')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('commercial_terms')->nullable();
            $table->string('status')->default(ProposalStatus::Draft->value)->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['customer_id', 'status']);
        });

        Schema::create('proposal_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('proposal_public_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('token', 80)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('proposal_public_tokens');
        Schema::dropIfExists('proposal_items');
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('service_items');
        Schema::dropIfExists('customers');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['role', 'is_active', 'business_name', 'logo_path', 'primary_color', 'secondary_color', 'default_footer_text', 'contact_details']);
        });
        Schema::dropIfExists('plans');
    }
};
