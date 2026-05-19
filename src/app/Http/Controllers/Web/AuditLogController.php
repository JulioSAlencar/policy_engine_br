<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $logs = $this->buildFilteredQuery($request)->paginate(25)->withQueryString();

        // Requisição AJAX (botão "Atualizar") — retorna o partial HTML da tabela
        if ($request->ajax()) {
            return response()->json([
                'html' => view('audit._table', compact('logs'))->render(),
            ]);
        }

        return view('audit.index', compact('logs'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query    = $this->buildFilteredQuery($request);
        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';

        ActivityLog::record(
            'audit.export_csv',
            'Exportou logs de auditoria em CSV',
            null,
            ['filtros' => $request->only(['date_from', 'date_to', 'user_identifier', 'risk_level', 'status'])]
        );

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'Usuário', 'URL Origem', 'Dados Sensíveis',
                'Nível de Risco', 'Tipo de Vazamento', 'Status', 'Motivo de Falha',
                'Justificativa', 'Capturado em', 'Processado em',
            ]);

            $query->chunk(200, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->user_identifier,
                        $log->url_source,
                        $log->has_sensitive_data ? 'Sim' : 'Não',
                        $log->risk_level ?? 'N/A',
                        $log->leak_type ?? 'N/A',
                        $log->status,
                        $log->error_reason ?? '',
                        $log->gemini_justification ?? '',
                        $log->captured_at?->format('d/m/Y H:i:s'),
                        $log->processed_at?->format('d/m/Y H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $logs   = $this->buildFilteredQuery($request)->limit(500)->get();
        $charts = $request->input('charts', []);

        ActivityLog::record(
            'audit.export_pdf',
            'Exportou relatório de auditoria em PDF',
            null,
            ['filtros' => $request->only(['date_from', 'date_to', 'user_identifier', 'risk_level', 'status'])]
        );

        $pdf = Pdf::loadView('audit.pdf', compact('logs', 'charts'))
            ->setPaper('a4', 'landscape')
            ->setOptions(['dpi' => 150, 'isHtml5ParserEnabled' => true]);

        return $pdf->download('relatorio_auditoria_' . now()->format('Y-m-d') . '.pdf');
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = AuditLog::query()->latest();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('user_identifier')) {
            $query->where('user_identifier', 'like', '%' . $request->user_identifier . '%');
        }
        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->risk_level);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }
}
