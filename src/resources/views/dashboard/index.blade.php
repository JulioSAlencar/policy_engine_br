@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard de Auditoria de IA</h1>
            <p class="text-gray-500 text-sm mt-1">Visão geral dos dados capturados e análises de governança</p>
        </div>
        {{-- Botão de Atualizar KPIs sem reload --}}
        <button id="btn-refresh"
                type="button"
                title="Atualizar indicadores"
                class="inline-flex items-center gap-1.5 bg-white border border-gray-300 hover:border-indigo-400 text-gray-600 hover:text-indigo-600 text-sm px-3 py-2 rounded-lg transition-all duration-300 shadow-sm self-start sm:self-auto">
            <svg id="refresh-icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Atualizar
        </button>
    </div>

    {{-- Filtro de período --}}
    <form method="GET" action="{{ route('dashboard') }}" class="bg-white rounded-xl shadow p-4">
        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Data de Início</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Data de Fim</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
            </div>
            <div class="flex gap-2">
                <x-button type="submit" variant="primary">Filtrar</x-button>
                <x-button :href="route('dashboard')" variant="secondary">Últimos 30 dias</x-button>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3">
            Período exibido:
            <strong>{{ \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') }}</strong>
            a
            <strong>{{ \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') }}</strong>
        </p>
    </form>

    {{-- KPI Cards — atualizados pelo AJAX refresh --}}
    <div id="kpi-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 transition-all duration-300">
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-indigo-500">
            <p class="text-sm text-gray-500">Total de Logs</p>
            <p class="text-3xl font-bold text-gray-900 mt-1" id="kpi-total">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-yellow-400">
            <p class="text-sm text-gray-500">Aguardando / Em Análise</p>
            <p class="text-3xl font-bold text-gray-900 mt-1" id="kpi-pending">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-orange-500">
            <p class="text-sm text-gray-500">Dados Sensíveis Detectados</p>
            <p class="text-3xl font-bold text-gray-900 mt-1" id="kpi-sensitive">{{ number_format($stats['sensitive']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-red-600">
            <p class="text-sm text-gray-500">Risco Crítico</p>
            <p class="text-3xl font-bold text-gray-900 mt-1" id="kpi-critical">{{ number_format($stats['critical']) }}</p>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
            <h2 class="text-base font-semibold text-gray-700 mb-4">
                Volume de Dados Capturados
                ({{ \Illuminate\Support\Carbon::parse($startDate)->format('d/m') }} – {{ \Illuminate\Support\Carbon::parse($endDate)->format('d/m') }})
            </h2>
            <div id="chart-line"></div>
        </div>
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-base font-semibold text-gray-700 mb-4">Distribuição por Nível de Risco</h2>
            <div id="chart-pie"></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-base font-semibold text-gray-700 mb-4">Top Usuários com Dados Sensíveis Detectados</h2>
        <div id="chart-bar"></div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Dados iniciais vindos do PHP ──────────────────────────────────────────
    const lineData = @json($volumeByDay);
    const riskData = @json($riskDistribution);
    const userData = @json($byUser);

    const riskLabels = { low: 'Baixo', medium: 'Médio', high: 'Alto', critical: 'Crítico' };
    const riskColors = { low: '#22c55e', medium: '#eab308', high: '#f97316', critical: '#ef4444' };

    // ── Inicializa gráficos e guarda referências para atualização posterior ───
    const chartLine = new ApexCharts(document.getElementById('chart-line'), {
        chart:  { type: 'area', height: 280, toolbar: { show: false } },
        series: [
            { name: 'Total de Logs',         data: lineData.map(d => ({ x: d.log_date, y: d.total })) },
            { name: 'Com Dados Sensíveis',   data: lineData.map(d => ({ x: d.log_date, y: d.sensitive_count || 0 })) },
        ],
        colors:     ['#1351B4', '#FFCD07'],
        fill:       { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
        xaxis:      { type: 'datetime', labels: { format: 'dd/MM' } },
        yaxis:      { labels: { formatter: v => Math.round(v) } },
        tooltip:    { x: { format: 'dd/MM/yyyy' } },
        stroke:     { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false },
        legend:     { position: 'top' },
    });
    chartLine.render();

    const riskKeys = Object.keys(riskData);
    const chartPie = new ApexCharts(document.getElementById('chart-pie'), {
        chart:       { type: 'donut', height: 280 },
        series:      riskKeys.map(k => riskData[k]),
        labels:      riskKeys.map(k => riskLabels[k] || k),
        colors:      riskKeys.map(k => riskColors[k] || '#94a3b8'),
        legend:      { position: 'bottom' },
        dataLabels:  { enabled: true },
        plotOptions: { pie: { donut: { size: '60%' } } },
    });
    chartPie.render();

    const chartBar = new ApexCharts(document.getElementById('chart-bar'), {
        chart:       { type: 'bar', height: 260, toolbar: { show: false } },
        series:      [{ name: 'Logs com Dados Sensíveis', data: userData.map(u => u.sensitive_count || 0) }],
        xaxis:       { categories: userData.map(u => u.user_identifier) },
        colors:      ['#1351B4'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
        dataLabels:  { enabled: false },
        yaxis:       { labels: { formatter: v => Math.round(v) } },
    });
    chartBar.render();

    // ── Refresh AJAX — atualiza KPIs e gráficos sem recarregar a página ──────
    const btnRefresh  = document.getElementById('btn-refresh');
    const refreshIcon = document.getElementById('refresh-icon');
    const kpiContainer = document.getElementById('kpi-container');

    btnRefresh.addEventListener('click', async function () {
        btnRefresh.disabled = true;
        refreshIcon.classList.add('animate-spin');

        try {
            const res = await fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();

            // ── Atualiza KPIs com fade ────────────────────────────────────
            kpiContainer.style.opacity = '0';
            await new Promise(r => setTimeout(r, 200));

            document.getElementById('kpi-total').textContent    = data.stats.total.toLocaleString('pt-BR');
            document.getElementById('kpi-pending').textContent   = data.stats.pending.toLocaleString('pt-BR');
            document.getElementById('kpi-sensitive').textContent = data.stats.sensitive.toLocaleString('pt-BR');
            document.getElementById('kpi-critical').textContent  = data.stats.critical.toLocaleString('pt-BR');

            kpiContainer.style.opacity = '1';

            // ── Atualiza gráficos via ApexCharts API ──────────────────────
            chartLine.updateSeries([
                { name: 'Total de Logs',       data: data.volumeByDay.map(d => ({ x: d.log_date, y: d.total })) },
                { name: 'Com Dados Sensíveis', data: data.volumeByDay.map(d => ({ x: d.log_date, y: d.sensitive_count || 0 })) },
            ]);

            const newRiskKeys = Object.keys(data.riskDistribution);
            chartPie.updateOptions({ labels: newRiskKeys.map(k => riskLabels[k] || k) });
            chartPie.updateSeries(newRiskKeys.map(k => data.riskDistribution[k]));

            chartBar.updateOptions({ xaxis: { categories: data.byUser.map(u => u.user_identifier) } });
            chartBar.updateSeries([{ name: 'Logs com Dados Sensíveis', data: data.byUser.map(u => u.sensitive_count || 0) }]);

        } catch (err) {
            console.error('[GovCert] Erro ao atualizar dashboard:', err);
        } finally {
            refreshIcon.classList.remove('animate-spin');
            btnRefresh.disabled = false;
        }
    });
})();
</script>
@endpush
