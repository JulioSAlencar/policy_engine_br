@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Cabeçalho -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard de Auditoria de IA</h1>
        <p class="text-gray-500 text-sm mt-1">Visão geral dos dados capturados e análises de governança</p>
    </div>

    <!-- Filtro de período -->
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
        <p class="text-xs text-gray-400 mt-3">Período exibido: <strong>{{ \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') }}</strong> a <strong>{{ \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') }}</strong></p>
    </form>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-indigo-500">
            <p class="text-sm text-gray-500">Total de Logs</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-yellow-400">
            <p class="text-sm text-gray-500">Aguardando Análise</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-orange-500">
            <p class="text-sm text-gray-500">Dados Sensíveis Detectados</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['sensitive']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-red-600">
            <p class="text-sm text-gray-500">Risco Crítico</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['critical']) }}</p>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Linha: Volume ao longo do tempo -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
            <h2 class="text-base font-semibold text-gray-700 mb-4">Volume de Dados Capturados ({{ \Illuminate\Support\Carbon::parse($startDate)->format('d/m') }} – {{ \Illuminate\Support\Carbon::parse($endDate)->format('d/m') }})</h2>
            <div id="chart-line"></div>
        </div>

        <!-- Pizza: Distribuição de risco -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-base font-semibold text-gray-700 mb-4">Distribuição por Nível de Risco</h2>
            <div id="chart-pie"></div>
        </div>

    </div>

    <!-- Barras: Top usuários com dados sensíveis -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-base font-semibold text-gray-700 mb-4">Top Usuários com Dados Sensíveis Detectados</h2>
        <div id="chart-bar"></div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const lineData = @json($volumeByDay);
const riskData = @json($riskDistribution);
const userData = @json($byUser);

// Gráfico de Linha
new ApexCharts(document.getElementById('chart-line'), {
    chart: { type: 'area', height: 280, toolbar: { show: false } },
    series: [
        { name: 'Total de Logs', data: lineData.map(d => ({ x: d.log_date, y: d.total })) },
        { name: 'Com Dados Sensíveis', data: lineData.map(d => ({ x: d.log_date, y: d.sensitive_count || 0 })) },
    ],
    colors: ['#1351B4', '#FFCD07'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    xaxis: { type: 'datetime', labels: { format: 'dd/MM' } },
    yaxis: { labels: { formatter: v => Math.round(v) } },
    tooltip: { x: { format: 'dd/MM/yyyy' } },
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    legend: { position: 'top' },
}).render();

// Gráfico de Pizza
const riskLabels = { low: 'Baixo', medium: 'Médio', high: 'Alto', critical: 'Crítico' };
const riskColors = { low: '#22c55e', medium: '#eab308', high: '#f97316', critical: '#ef4444' };
const riskKeys   = Object.keys(riskData);
new ApexCharts(document.getElementById('chart-pie'), {
    chart: { type: 'donut', height: 280 },
    series: riskKeys.map(k => riskData[k]),
    labels: riskKeys.map(k => riskLabels[k] || k),
    colors: riskKeys.map(k => riskColors[k] || '#94a3b8'),
    legend: { position: 'bottom' },
    dataLabels: { enabled: true },
    plotOptions: { pie: { donut: { size: '60%' } } },
}).render();

// Gráfico de Barras
new ApexCharts(document.getElementById('chart-bar'), {
    chart: { type: 'bar', height: 260, toolbar: { show: false } },
    series: [{ name: 'Logs com Dados Sensíveis', data: userData.map(u => u.sensitive_count || 0) }],
    xaxis: { categories: userData.map(u => u.user_identifier) },
    colors: ['#1351B4'],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
    dataLabels: { enabled: false },
    yaxis: { labels: { formatter: v => Math.round(v) } },
}).render();
</script>
@endpush
