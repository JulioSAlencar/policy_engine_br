<?php

namespace Tests\Feature;

use App\Jobs\ProcessAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuditIngestionTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'user_identifier' => 'matricula-12345',
            'input_text'      => 'Meu CPF é 123.456.789-00, pode validar?',
            'output_text'     => 'Não posso processar dados sensíveis.',
            'url_source'      => 'https://chatgpt.com/c/abc',
            'timestamp'       => now()->toIso8601String(),
        ], $overrides);
    }

    public function test_rota_de_ingestao_exige_autenticacao(): void
    {
        $response = $this->postJson('/api/audit/logs', $this->validPayload());

        $response->assertStatus(401);
    }

    public function test_payload_valido_retorna_202_salva_pending_e_enfileira_job(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/audit/logs', $this->validPayload());

        $response->assertStatus(202)
                 ->assertJsonStructure(['message', 'log_id']);

        $this->assertDatabaseHas('audit_logs', [
            'user_identifier' => 'matricula-12345',
            'status'          => 'pending',
            'user_id'         => $user->id,
        ]);

        Queue::assertPushed(ProcessAuditLog::class);
    }

    public function test_payload_invalido_retorna_422(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/audit/logs', $this->validPayload([
            'url_source' => 'nao-e-uma-url',
            'input_text' => '',
        ]));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['url_source', 'input_text']);
    }

    public function test_nao_enfileira_job_quando_validacao_falha(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/audit/logs', ['user_identifier' => 'x'])
             ->assertStatus(422);

        Queue::assertNotPushed(ProcessAuditLog::class);
    }
}
