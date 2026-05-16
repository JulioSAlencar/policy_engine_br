<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAuditLog;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_identifier' => 'required|string|max:255',
            'input_text'      => 'required|string',
            'output_text'     => 'required|string',
            'url_source'      => 'required|string|url|max:2048',
            'timestamp'       => 'required|date',
        ]);

        $log = AuditLog::create([
            'user_id'         => $request->user()?->id,
            'user_identifier' => $validated['user_identifier'],
            'input_text'      => $validated['input_text'],
            'output_text'     => $validated['output_text'],
            'url_source'      => $validated['url_source'],
            'captured_at'     => $validated['timestamp'],
            'status'          => 'pending',
        ]);

        ProcessAuditLog::dispatch($log->id)->onQueue('gemini');

        return response()->json([
            'message' => 'Log recebido e enfileirado para análise.',
            'log_id'  => $log->id,
        ], 202);
    }
}
