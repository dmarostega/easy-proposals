<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_validation_errors_are_visible(): void
    {
        $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login'), [])
            ->assertOk()
            ->assertSee('Revise os campos abaixo:')
            ->assertSee('O campo e-mail é obrigatório.')
            ->assertSee('O campo senha é obrigatório.');
    }

    public function test_invalid_login_credentials_are_visible(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login'), [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ])
            ->assertOk()
            ->assertSee('Credenciais inválidas.');
    }

    public function test_inactive_user_login_shows_validation_message(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login'), [
                'email' => 'inactive@example.com',
                'password' => 'password',
            ])
            ->assertOk()
            ->assertSee('Usuário inativo. Entre em contato com o administrador.');

        $this->assertGuest();
    }

    public function test_register_validation_errors_are_visible(): void
    {
        $this->followingRedirects()
            ->from(route('register'))
            ->post(route('register'), [])
            ->assertOk()
            ->assertSee('Revise os campos abaixo:')
            ->assertSee('O campo nome é obrigatório.')
            ->assertSee('O campo e-mail é obrigatório.')
            ->assertSee('O campo senha é obrigatório.');
    }
}
