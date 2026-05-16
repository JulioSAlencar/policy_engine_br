<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total'     => AuditLog::count(),
            'pending'   => AuditLog::where('status', 'pending')->count(),
            'sensitive' => AuditLog::where('has_sensitive_data', true)->count(),
            'critical'  => AuditLog::where('risk_level', 'critical')->count(),
        ];

        // Volume por dia (últimos 30 dias) — usado no gráfico de linha
        $volumeByDay = AuditLog::select(
            DB::raw('DATE(created_at) as log_date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(has_sensitive_data) as sensitive_count')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->get();

        // Distribuição por nível de risco — usado no gráfico de pizza
        $riskDistribution = AuditLog::select('risk_level', DB::raw('COUNT(*) as total'))
            ->whereNotNull('risk_level')
            ->groupBy('risk_level')
            ->pluck('total', 'risk_level');

        // Sensibilidade por usuário (top 10) — gráfico de barras
        $byUser = AuditLog::select('user_identifier', DB::raw('COUNT(*) as total'), DB::raw('SUM(has_sensitive_data) as sensitive_count'))
            ->where('has_sensitive_data', true)
            ->groupBy('user_identifier')
            ->orderByDesc('sensitive_count')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact('stats', 'volumeByDay', 'riskDistribution', 'byUser'));
    }
}
