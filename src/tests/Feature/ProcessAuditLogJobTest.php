<?php

namespace Tests\Feature;

use App\Jobs\ProcessAuditLog;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessAuditLogJobTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGemini(array $analysis, int $status = 200): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode($analysis)]]],
                ]],
            ], $status),
        ]);
    }

    public function test_job_processa_log_pendente_e_marca_como_completed(): void
    {
        $this->fakeGemini([
            'has_sensitive_data' => true,
            'risk_level'         => 'critical',
            'justification'      => 'CPF e dados bancários detectados no input.',
            'detected_categories'=> ['cpf', 'dados_bancarios'],
        ]);

        $log = AuditLog::factory()->create(['status' => 'pending']);

        (new ProcessAuditLog($log->id))->handle();

        $log->refresh();
        $this->assertSame('completed', $log->status);
        $this->assertTrue($log->has_sensitive_data);
        $this->assertSame('critical', $log->risk_level);
        $this->assertSame('CPF e dados bancários detectados no input.', $log->gemini_justification);
        $this->assertNotNull($log->processed_at);
        $this->assertIsArray($log->gemini_raw_response);
    }

    public function test_job_marca_como_failed_quando_gemini_retorna_erro(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('erro interno', 500),
        ]);

        $log = AuditLog::factory()->create(['status' => 'pending']);

        try {
            (new ProcessAuditLog($log->id))->handle();
        } catch (\Throwable $e) {
            // O job re-lança a exceção após marcar como failed (para retry da fila)
        }

        $this->assertSame('failed', $log->fresh()->status);
    }

    public function test_job_ignora_log_que_nao_esta_pendente(): void
    {
        Http::fake();

        $log = AuditLog::factory()->completed('low', false)->create();

        (new ProcessAuditLog($log->id))->handle();

        Http::assertNothingSent();
        $this->assertSame('completed', $log->fresh()->status);
    }
}
