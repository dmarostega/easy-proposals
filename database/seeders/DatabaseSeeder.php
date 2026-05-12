<?php

namespace Database\Seeders;

use App\Enums\PlanSlug;
use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $free = Plan::updateOrCreate(['slug' => PlanSlug::Free->value], ['name' => 'Gratuito', 'monthly_price_cents' => 0, 'monthly_proposal_limit' => 3, 'customer_limit' => 10, 'allows_pdf' => false, 'allows_custom_logo' => false, 'is_active' => true]);
        Plan::updateOrCreate(['slug' => PlanSlug::Pro->value], ['name' => 'Pro', 'monthly_price_cents' => 2900, 'monthly_proposal_limit' => 50, 'customer_limit' => null, 'allows_pdf' => true, 'allows_custom_logo' => false, 'is_active' => true]);
        Plan::updateOrCreate(['slug' => PlanSlug::Plus->value], ['name' => 'Plus', 'monthly_price_cents' => 5900, 'monthly_proposal_limit' => null, 'customer_limit' => null, 'allows_pdf' => true, 'allows_custom_logo' => true, 'is_active' => true]);

        User::updateOrCreate(['email' => 'admin@propostafacil.test'], ['name' => 'Administrador', 'password' => Hash::make('password'), 'role' => UserRole::Admin, 'is_active' => true, 'plan_id' => $free->id]);

        foreach ([
            'app_name' => 'Proposta Fácil', 'base_domain' => 'https://propostafacil.example', 'contact_email' => 'contato@propostafacil.example',
            'primary_color' => '#2563eb', 'secondary_color' => '#0f172a', 'default_meta_title' => 'Proposta Fácil',
            'default_meta_description' => 'Crie propostas comerciais profissionais com aprovação online.', 'default_seo_text' => 'SaaS para propostas e orçamentos.',
        ] as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
