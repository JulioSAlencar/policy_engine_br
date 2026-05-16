<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_autenticado_e_redirecionado_para_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/audit/logs')->assertRedirect('/login');
    }

    public function test_usuario_autenticado_acessa_o_dashboard(): void
    {
        $user = User::factory()->create();
        AuditLog::factory()->completed('high')->count(3)->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200)
                 ->assertSee('Dashboard de Auditoria');
    }

    public function test_usuario_autenticado_acessa_a_tabela_de_logs(): void
    {
        $user = User::factory()->create();
        AuditLog::factory()->completed('critical')->create(['user_identifier' => 'alvo-123']);

        $response = $this->actingAs($user)->get('/audit/logs');

        $response->assertStatus(200)
                 ->assertSee('alvo-123');
    }

    public function test_filtro_por_nivel_de_risco_na_tabela(): void
    {
        $user = User::factory()->create();
        AuditLog::factory()->completed('critical')->create(['user_identifier' => 'usuario-critico']);
        AuditLog::factory()->completed('low')->create(['user_identifier' => 'usuario-baixo']);

        $response = $this->actingAs($user)->get('/audit/logs?risk_level=critical');

        $response->assertStatus(200)
                 ->assertSee('usuario-critico')
                 ->assertDontSee('usuario-baixo');
    }

    public function test_exportacao_csv_responde_com_sucesso(): void
    {
        $user = User::factory()->create();
        AuditLog::factory()->completed()->count(2)->create();

        $response = $this->actingAs($user)->get('/audit/logs/export/csv');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
