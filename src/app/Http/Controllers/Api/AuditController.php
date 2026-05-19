<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAuditLog;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_identifier' => 'required|string|max:255',
            'input_text'      => 'required|string',
            'output_text'     => 'required|string',
            'url_source'      => 'required|string|url|max:2048',
        ]);

        // O servidor é a única fonte de verdade do timestamp em sistemas de auditoria.
        // O relógio do cliente (extensão) jamais deve ser confiado — pode ser adulterado.
        $serverNow = now();

        Log::info('[GovCert] Log recebido', [
            'user'        => $validated['user_identifier'],
            'url_source'  => $validated['url_source'],
            'captured_at' => $serverNow->toDateTimeString(),
        ]);

        try {
            $log = AuditLog::create([
                'user_id'         => $request->user()?->id,
                'user_identifier' => $validated['user_identifier'],
                'input_text'      => $validated['input_text'],
                'output_text'     => $validated['output_text'],
                'url_source'      => $validated['url_source'],
                'captured_at'     => $serverNow,
                'status'          => 'pending',
            ]);

            ProcessAuditLog::dispatch($log->id)->onQueue('gemini');

            Log::info('[GovCert] Log salvo e enfileirado', ['log_id' => $log->id]);

            return response()->json(['success' => true, 'log_id' => $log->id], 202);
        } catch (Throwable $e) {
            Log::error('[GovCert] Falha ao salvar log', [
                'error'      => $e->getMessage(),
                'user'       => $validated['user_identifier'],
                'url_source' => $validated['url_source'],
            ]);

            return response()->json(['error' => 'Falha interna ao processar o log.'], 500);
        }
    }
}
