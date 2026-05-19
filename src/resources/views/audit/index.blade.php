@extends('layouts.app')

@section('title', 'Logs de Auditoria')

@section('content')
<div class="space-y-6">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Logs de Auditoria</h1>
            <p class="text-gray-500 text-sm mt-1">Registros capturados pela extensão Chrome</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Botão de Atualizar sem reload --}}
            <button id="btn-refresh"
                    type="button"
                    title="Atualizar tabela"
                    class="inline-flex items-center gap-1.5 bg-white border border-gray-300 hover:border-indigo-400 text-gray-600 hover:text-indigo-600 text-sm px-3 py-2 rounded-lg transition-all duration-300 shadow-sm">
                <svg id="refresh-icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Atualizar
            </button>

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

    {{-- Filtros --}}
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
                    <option value="pending"     {{ request('status') === 'pending'     ? 'selected' : '' }}>Pendente</option>
                    <option value="in_analysis" {{ request('status') === 'in_analysis' ? 'selected' : '' }}>Em Análise</option>
                    <option value="completed"   {{ request('status') === 'completed'   ? 'selected' : '' }}>Concluído</option>
                    <option value="failed"      {{ request('status') === 'failed'      ? 'selected' : '' }}>Falha</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">Filtrar</button>
            <a href="{{ route('audit.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm px-4 py-2 rounded-lg">Limpar</a>
        </div>
    </form>

    {{-- Tabela com container animado --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div id="audit-table-container" class="transition-opacity duration-200">
            @include('audit._table', ['logs' => $logs])
        </div>
    </div>

</div>

{{-- Modal de simulação de chat --}}
<x-modal id="chat-modal" max-width="max-w-2xl" panel-class="bg-gray-50">
    <div class="flex items-center justify-between px-5 py-4 bg-indigo-800 text-white">
        <div>
            <h3 class="font-semibold">Interação Auditada <span id="m-id" class="text-indigo-300 text-sm font-mono"></span></h3>
            <p class="text-xs text-indigo-200" id="m-user"></p>
        </div>
        <button id="chat-close" type="button"
            class="text-indigo-200 hover:text-white hover:bg-indigo-700 rounded-lg w-8 h-8 flex items-center justify-center text-xl leading-none transition-all duration-300"
            aria-label="Fechar">&times;</button>
    </div>

    <div class="flex-1 overflow-y-auto p-5 space-y-4">
        <div class="flex justify-end">
            <div class="max-w-[80%] bg-indigo-600 text-white rounded-2xl rounded-br-sm px-4 py-3">
                <p class="text-[10px] uppercase tracking-wide text-indigo-200 mb-1">Usuário</p>
                <p id="m-input" class="text-sm whitespace-pre-wrap break-words"></p>
            </div>
        </div>
        <div class="flex justify-start">
            <div class="max-w-[80%] bg-white border border-gray-200 text-gray-800 rounded-2xl rounded-bl-sm px-4 py-3 shadow-sm">
                <p id="m-agent" class="text-xs font-semibold text-indigo-700 mb-1"></p>
                <p id="m-output" class="text-sm whitespace-pre-wrap break-words"></p>
            </div>
        </div>
    </div>

    <div class="border-t border-gray-200 bg-amber-50 px-5 py-4">
        <div class="flex items-center gap-2 mb-1">
            <span class="text-amber-600 font-semibold text-sm">Parecer da Auditoria</span>
            <span id="m-leak" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"></span>
        </div>
        <p id="m-justification" class="text-sm text-gray-700 whitespace-pre-wrap break-words"></p>
    </div>
</x-modal>

{{-- Form oculto para PDF --}}
<form id="form-pdf" method="POST" action="{{ route('audit.export.pdf', request()->query()) }}" style="display:none">
    @csrf
</form>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Refresh AJAX da tabela ────────────────────────────────────────────────
    const btnRefresh     = document.getElementById('btn-refresh');
    const refreshIcon    = document.getElementById('refresh-icon');
    const tableContainer = document.getElementById('audit-table-container');

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

            // Fade out → troca → fade in
            tableContainer.style.opacity = '0';
            await new Promise(r => setTimeout(r, 200));
            tableContainer.innerHTML = data.html;
            tableContainer.style.opacity = '1';

        } catch (err) {
            console.error('[GovCert] Erro ao atualizar tabela:', err);
        } finally {
            refreshIcon.classList.remove('animate-spin');
            btnRefresh.disabled = false;
        }
    });

    // ── Modal de chat — event delegation (funciona mesmo após AJAX refresh) ──
    const modal = document.getElementById('chat-modal');
    if (!modal) return;

    const leakClasses = {
        'Dados Pessoais': 'bg-orange-100 text-orange-800',
        'Credenciais':    'bg-red-100 text-red-800',
        'Código Fonte':   'bg-purple-100 text-purple-800',
        'Nenhum':         'bg-gray-200 text-gray-700',
    };

    function openModal(d) {
        document.getElementById('m-id').textContent            = '#' + (d.id || '');
        document.getElementById('m-user').textContent          = d.user || '';
        document.getElementById('m-input').textContent         = d.input || '(sem conteúdo)';
        document.getElementById('m-agent').textContent         = d.agent || 'Agente de IA';
        document.getElementById('m-output').textContent        = d.output || '(sem resposta)';
        document.getElementById('m-justification').textContent =
            d.justification || 'Este registro ainda não foi analisado.';

        const leak  = d.leak || '—';
        const badge = document.getElementById('m-leak');
        badge.textContent = leak;
        badge.className   = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium '
            + (leakClasses[leak] || 'bg-gray-200 text-gray-700');

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Delegação no container — cliques funcionam antes e depois do AJAX
    tableContainer.addEventListener('click', function (e) {
        const row = e.target.closest('.audit-row');
        if (row) openModal(row.dataset);
    });

    document.getElementById('chat-close').addEventListener('click', closeModal);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    // ── Export PDF ───────────────────────────────────────────────────────────
    document.getElementById('btn-export-pdf').addEventListener('click', function () {
        this.disabled = true;
        this.textContent = 'Gerando PDF...';
        document.getElementById('form-pdf').submit();
        setTimeout(() => { this.disabled = false; this.textContent = 'Exportar PDF'; }, 2000);
    });
})();
</script>
@endpush
