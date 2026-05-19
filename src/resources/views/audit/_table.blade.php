{{--
    Partial reutilizado pela view completa e pelo endpoint AJAX de refresh.
    Recebe: $logs (LengthAwarePaginator)
--}}
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
            <tr class="audit-row cursor-pointer hover:bg-gray-50 transition-colors duration-150"
                data-id="{{ $log->id }}"
                data-user="{{ $log->user_identifier }}"
                data-agent="{{ $log->ai_agent_name }}"
                data-url="{{ $log->url_source }}"
                data-risk="{{ $log->risk_level }}"
                data-leak="{{ $log->leak_type }}"
                data-input="{{ $log->input_text }}"
                data-output="{{ $log->output_text }}"
                data-justification="{{ $log->gemini_justification }}"
                data-error="{{ $log->error_reason }}">

                <td class="px-4 py-3 font-mono text-gray-400 text-xs">#{{ $log->id }}</td>

                <td class="px-4 py-3 font-medium text-gray-900">{{ $log->user_identifier }}</td>

                <td class="px-4 py-3 text-gray-500 max-w-[160px] truncate" title="{{ $log->url_source }}">
                    {{ parse_url($log->url_source, PHP_URL_HOST) }}
                </td>

                <td class="px-4 py-3">
                    @if($log->has_sensitive_data === null)
                        <span class="text-gray-300">—</span>
                    @elseif($log->has_sensitive_data)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">Sim</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 border border-green-200">Não</span>
                    @endif
                </td>

                <td class="px-4 py-3">
                    @php
                        $riskColors = ['low' => 'bg-green-100 text-green-800 border-green-200', 'medium' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'high' => 'bg-orange-100 text-orange-800 border-orange-200', 'critical' => 'bg-red-100 text-red-800 border-red-200'];
                        $riskLabels = ['low' => 'Baixo', 'medium' => 'Médio', 'high' => 'Alto', 'critical' => 'Crítico'];
                    @endphp
                    @if($log->risk_level)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $riskColors[$log->risk_level] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $riskLabels[$log->risk_level] ?? $log->risk_level }}
                        </span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>

                <td class="px-4 py-3">
                    <x-status-badge :status="$log->status" :reason="$log->error_reason" />
                </td>

                <td class="px-4 py-3 text-gray-500 whitespace-nowrap text-xs">
                    {{ $log->captured_at?->format('d/m/Y H:i') }}
                </td>

                <td class="px-4 py-3 text-gray-600 max-w-[220px]">
                    <span class="block truncate text-xs" title="{{ $log->gemini_justification }}">
                        {{ \Illuminate\Support\Str::limit($log->gemini_justification, 80) ?: '—' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                    Nenhum log encontrado para os filtros selecionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($logs->hasPages())
<div class="px-4 py-3 border-t border-gray-100">
    {{ $logs->links() }}
</div>
@endif
