<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(private readonly int $auditLogId) {}

    public function handle(): void
    {
        $log = AuditLog::find($this->auditLogId);

        if (!$log || $log->status !== 'pending') {
            return;
        }

        $log->update(['status' => 'processing']);

        try {
            $result = $this->callGeminiApi($log);
            $log->update([
                'status'               => 'completed',
                'has_sensitive_data'   => $result['has_sensitive_data'],
                'risk_level'           => $result['risk_level'],
                'leak_type'            => $result['leak_type'] ?? null,
                'gemini_justification' => $result['justification'],
                'gemini_raw_response'  => $result,
                'processed_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessAuditLog failed', [
                'audit_log_id' => $this->auditLogId,
                'error'        => $e->getMessage(),
            ]);
            $log->update(['status' => 'failed']);
            throw $e;
        }
    }

    private function callGeminiApi(AuditLog $log): array
    {
        $apiKey = config('services.gemini.api_key');
        $model  = 'gemini-1.5-flash';

        $systemInstruction = <<<PROMPT
Você é um sistema especializado em Auditoria, Certificação e Governança Pública de Inteligência Artificial.
Sua função é analisar interações entre usuários e sistemas de IA para identificar riscos à privacidade,
segurança de dados e conformidade com legislações como LGPD, GDPR e regulamentos de governança pública.

Analise o INPUT e OUTPUT fornecidos e retorne EXCLUSIVAMENTE um JSON válido com a estrutura abaixo,
sem nenhum texto adicional, markdown ou explicações fora do JSON:

{
  "has_sensitive_data": boolean,
  "risk_level": "low" | "medium" | "high" | "critical",
  "leak_type": "Dados Pessoais" | "Credenciais" | "Código Fonte" | "Nenhum",
  "justification": "string com explicação detalhada em português",
  "detected_categories": ["lista de categorias detectadas"],
  "lgpd_articles": ["artigos da LGPD potencialmente violados, se aplicável"],
  "recommendations": ["lista de recomendações"]
}

Campo "leak_type" — classifique o tipo predominante de vazamento detectado:
- "Dados Pessoais": CPF, RG, nome, endereço, dados de saúde, biometria ou qualquer PII
- "Credenciais": senhas, tokens, chaves de API, dados bancários ou de cartão
- "Código Fonte": trechos de código, segredos de sistema ou propriedade intelectual técnica
- "Nenhum": quando não houver vazamento de informação sensível

Categorias de dados sensíveis a verificar:
- CPF, RG, CNH, passaporte ou outros documentos de identificação
- Dados bancários, cartão de crédito, senhas
- Endereços físicos completos
- Dados de saúde, prontuários médicos
- Dados biométricos
- Informações de menores de idade
- Segredos comerciais ou informações sigilosas de governo
- Dados que permitam identificação indireta da pessoa

Níveis de risco:
- low: nenhum dado sensível detectado
- medium: dados que podem identificar indiretamente a pessoa
- high: dados pessoais diretos como nome + CPF, endereço completo
- critical: dados bancários, senhas, dados de saúde ou segredos de Estado
PROMPT;

        $prompt = sprintf(
            "URL de origem: %s\n\nINPUT DO USUÁRIO:\n%s\n\nOUTPUT DO SISTEMA DE IA:\n%s",
            $log->url_source,
            $log->input_text,
            $log->output_text
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature'      => 0.1,
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        $content = $response->json('candidates.0.content.parts.0.text');

        if (!$content) {
            throw new \RuntimeException('Empty response from Gemini API');
        }

        $parsed = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON from Gemini: ' . $content);
        }

        return $parsed;
    }
}
