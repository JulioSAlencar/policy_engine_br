<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // ─── Intervalo de datas (padrão: últimos 30 dias) ─────────────────
        $start = $this->parseDate($request->input('start_date'), Carbon::now()->subDays(30));
        $end   = $this->parseDate($request->input('end_date'), Carbon::now());

        // Garante ordem cronológica caso o usuário inverta os campos
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        $range = [$start->copy()->startOfDay(), $end->copy()->endOfDay()];

        // ─── KPIs (filtrados pelo intervalo) ──────────────────────────────
        $scoped = fn () => AuditLog::whereBetween('created_at', $range);

        $stats = [
            'total'     => $scoped()->count(),
            'pending'   => $scoped()->where('status', 'pending')->count(),
            'sensitive' => $scoped()->where('has_sensitive_data', true)->count(),
            'critical'  => $scoped()->where('risk_level', 'critical')->count(),
        ];

        // Volume por dia — gráfico de linha
        $volumeByDay = AuditLog::select(
            DB::raw('DATE(created_at) as log_date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(has_sensitive_data) as sensitive_count')
        )
            ->whereBetween('created_at', $range)
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->get();

        // Distribuição por nível de risco — gráfico de pizza
        $riskDistribution = AuditLog::select('risk_level', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', $range)
            ->whereNotNull('risk_level')
            ->groupBy('risk_level')
            ->pluck('total', 'risk_level');

        // Top usuários com dados sensíveis — gráfico de barras
        $byUser = AuditLog::select('user_identifier', DB::raw('COUNT(*) as total'), DB::raw('SUM(has_sensitive_data) as sensitive_count'))
            ->whereBetween('created_at', $range)
            ->where('has_sensitive_data', true)
            ->groupBy('user_identifier')
            ->orderByDesc('sensitive_count')
            ->limit(10)
            ->get();

        return view('dashboard.index', [
            'stats'            => $stats,
            'volumeByDay'      => $volumeByDay,
            'riskDistribution' => $riskDistribution,
            'byUser'           => $byUser,
            'startDate'        => $start->toDateString(),
            'endDate'          => $end->toDateString(),
        ]);
    }

    /**
     * Converte a entrada em Carbon; usa o fallback se vazio ou inválido.
     */
    private function parseDate(?string $value, Carbon $default): Carbon
    {
        if (! $value) {
            return $default;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $default;
        }
    }
}
