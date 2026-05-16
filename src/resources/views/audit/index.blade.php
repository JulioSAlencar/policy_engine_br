@extends('layouts.app')

@section('title', 'Logs de Auditoria')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Logs de Auditoria</h1>
            <p class="text-gray-500 text-sm mt-1">Registros capturados pela extensão Chrome</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('audit.export.csv', request()->query()) }}"
               class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
                Exportar CSV
            </a>
            <button id="btn-export-pdf"
               class="inline-flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
                Exportar PDF
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" action="{{ route('audit.index') }}" class="bg-white rounded-xl shadow p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Data Início</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Data Fim</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Usuário</label>
                <input type="text" name="user_identifier" value="{{ request('user_identifier') }}"
                    placeholder="Identificador do usuário"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nível de Risco</label>
                <select name="risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    <option value="">Todos</option>
                    <option value="low"      {{ request('risk_level') === 'low'      ? 'selected' : '' }}>Baixo</option>
                    <option value="medium"   {{ request('risk_level') === 'medium'   ? 'selected' : '' }}>Médio</option>
                    <option value="high"     {{ request('risk_level') === 'high'     ? 'selected' : '' }}>Alto</option>
                    <option value="critical" {{ request('risk_level') === 'critical' ? 'selected' : '' }}>Crítico</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    <option value="">Todos</option>
                    <option value="pending"    {{ request('status') === 'pending'    ? 'selected' : '' }}>Pendente</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processando</option>
                    <option value="completed"  {{ request('status') === 'completed'  ? 'selected' : '' }}>Concluído</option>
                    <option value="failed"     {{ request('status') === 'failed'     ? 'selected' : '' }}>Falhou</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">Filtrar</button>
            <a href="{{ route('audit.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm px-4 py-2 rounded-lg">Limpar</a>
        </div>
    </form>

    <!-- Tabela -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Usuário</th>
                        <th class="px-4 py-3">URL Origem</th>
                        <th class="px-4 py-3">Dados Sensíveis</th>
                        <th class="px-4 py-3">Risco</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Capturado em</th>
                        <th class="px-4 py-3">Justificativa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="audit-row cursor-pointer hover:bg-gray-100 transition-colors"
                        data-id="{{ $log->id }}"
                        data-user="{{ $log->user_identifier }}"
                        data-agent="{{ $log->ai_agent_name }}"
                        data-url="{{ $log->url_source }}"
                        data-risk="{{ $log->risk_level }}"
                        data-leak="{{ $log->leak_type }}"
                        data-input="{{ $log->input_text }}"
                        data-output="{{ $log->output_text }}"
                        data-justification="{{ $log->gemini_justification }}">
                        <td class="px-4 py-3 font-mono text-gray-500">#{{ $log->id }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $log->user_identifier }}</td>
                        <td class="px-4 py-3 text-gray-500 max-w-xs truncate" title="{{ $log->url_source }}">
                            {{ parse_url($log->url_source, PHP_URL_HOST) }}
                        </td>
                        <td class="px-4 py-3">
                            @if($log->has_sensitive_data === null)
                                <span class="text-gray-400">—</span>
                            @elseif($log->has_sensitive_data)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Sim</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Não</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $riskColors = ['low' => 'bg-green-100 text-green-800', 'medium' => 'bg-yellow-100 text-yellow-800', 'high' => 'bg-orange-100 text-orange-800', 'critical' => 'bg-red-100 text-red-800'];
                                $riskLabels = ['low' => 'Baixo', 'medium' => 'Médio', 'high' => 'Alto', 'critical' => 'Crítico'];
                            @endphp
                            @if($log->risk_level)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $riskColors[$log->risk_level] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $riskLabels[$log->risk_level] ?? $log->risk_level }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = ['pending' => 'bg-yellow-100 text-yellow-800', 'processing' => 'bg-blue-100 text-blue-800', 'completed' => 'bg-green-100 text-green-800', 'failed' => 'bg-red-100 text-red-800'];
                                $statusLabels = ['pending' => 'Pendente', 'processing' => 'Processando', 'completed' => 'Concluído', 'failed' => 'Falhou'];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$log->status] ?? '' }}">
                                {{ $statusLabels[$log->status] ?? $log->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ $log->captured_at?->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <span class="block max-w-xs truncate text-xs" title="{{ $log->gemini_justification }}">
                                {{ \Illuminate\Support\Str::limit($log->gemini_justification, 80) ?: '—' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-400">Nenhum log encontrado para os filtros selecionados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Modal único — Simulação de Chat (componente <x-modal>; IDs preservados p/ o JS) --}}
<x-modal id="chat-modal" max-width="max-w-2xl" panel-class="bg-gray-50">
    <!-- Cabeçalho -->
    <div class="flex items-center justify-between px-5 py-4 bg-indigo-800 text-white">
        <div>
            <h3 class="font-semibold">Interação Auditada <span id="m-id" class="text-indigo-300 text-sm font-mono"></span></h3>
            <p class="text-xs text-indigo-200" id="m-user"></p>
        </div>
        <button id="chat-close" type="button"
            class="text-indigo-200 hover:text-white hover:bg-indigo-700 rounded-lg w-8 h-8 flex items-center justify-center text-xl leading-none transition-all duration-300 ease-in-out"
            aria-label="Fechar">&times;</button>
    </div>

    <!-- Corpo: simulação de chat -->
    <div class="flex-1 overflow-y-auto p-5 space-y-4">

        <!-- Balão do usuário (direita) -->
        <div class="flex justify-end">
            <div class="max-w-[80%] bg-indigo-600 text-white rounded-2xl rounded-br-sm px-4 py-3">
                <p class="text-[10px] uppercase tracking-wide text-indigo-200 mb-1">Usuário</p>
                <p id="m-input" class="text-sm whitespace-pre-wrap break-words"></p>
            </div>
        </div>

        <!-- Balão do agente de IA (esquerda) -->
        <div class="flex justify-start">
            <div class="max-w-[80%] bg-white border border-gray-200 text-gray-800 rounded-2xl rounded-bl-sm px-4 py-3 shadow-sm">
                <p id="m-agent" class="text-xs font-semibold text-indigo-700 mb-1"></p>
                <p id="m-output" class="text-sm whitespace-pre-wrap break-words"></p>
            </div>
        </div>
    </div>

    <!-- Parecer da Auditoria -->
    <div class="border-t border-gray-200 bg-amber-50 px-5 py-4">
        <div class="flex items-center gap-2 mb-1">
            <span class="text-amber-600 font-semibold text-sm">Parecer da Auditoria</span>
            <span id="m-leak" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"></span>
        </div>
        <p id="m-justification" class="text-sm text-gray-700 whitespace-pre-wrap break-words"></p>
    </div>
</x-modal>

<!-- Form oculto para PDF -->
<form id="form-pdf" method="POST" action="{{ route('audit.export.pdf', request()->query()) }}" style="display:none">
    @csrf
    <input type="hidden" name="charts[line]" id="chart-line-data">
    <input type="hidden" name="charts[pie]" id="chart-pie-data">
    <input type="hidden" name="charts[bar]" id="chart-bar-data">
</form>
@endsection

@push('scripts')
<script>
document.getElementById('btn-export-pdf').addEventListener('click', async function () {
    this.disabled = true;
    this.textContent = 'Gerando PDF...';
    document.getElementById('form-pdf').submit();
    setTimeout(() => { this.disabled = false; this.textContent = 'Exportar PDF'; }, 2000);
});

// ─── Modal de simulação de chat ───────────────────────────────────────────
(function () {
    const modal = document.getElementById('chat-modal');
    if (!modal) return;

    const leakClasses = {
        'Dados Pessoais': 'bg-orange-100 text-orange-800',
        'Credenciais':    'bg-red-100 text-red-800',
        'Código Fonte':   'bg-purple-100 text-purple-800',
        'Nenhum':         'bg-gray-200 text-gray-700',
    };

    function openModal(data) {
        document.getElementById('m-id').textContent    = '#' + (data.id || '');
        document.getElementById('m-user').textContent  = data.user || '';
        document.getElementById('m-input').textContent = data.input || '(sem conteúdo)';
        document.getElementById('m-agent').textContent = data.agent || 'Agente de IA';
        document.getElementById('m-output').textContent = data.output || '(sem resposta)';
        document.getElementById('m-justification').textContent =
            data.justification || 'Este registro ainda não foi analisado pela IA.';

        const leak = data.leak || '—';
        const badge = document.getElementById('m-leak');
        badge.textContent = leak;
        badge.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium '
            + (leakClasses[leak] || 'bg-gray-200 text-gray-700');

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    document.querySelectorAll('.audit-row').forEach(function (row) {
        row.addEventListener('click', function () {
            openModal(row.dataset);
        });
    });

    document.getElementById('chat-close').addEventListener('click', closeModal);

    modal.querySelectorAll('[data-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
})();
</script>
@endpush
