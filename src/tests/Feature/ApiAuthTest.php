<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_se_registrar(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Novo Usuário',
            'email' => 'novo@chatbot.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['message', 'user']);
                 
        $this->assertDatabaseHas('users', [
            'email' => 'novo@chatbot.com'
        ]);
    }

    public function test_usuario_consegue_fazer_login_e_receber_token(): void
    {
        $user = User::factory()->create([
            'email' => 'teste@chatbot.com',
            'password' => bcrypt('senha123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste@chatbot.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['access_token', 'user']);
    }

    public function test_usuario_consegue_acessar_rota_protegida_com_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
                 ->assertJson(['email' => $user->email]);
    }

    public function test_usuario_nao_acessa_rota_protegida_sem_token(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }
}