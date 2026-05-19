@props(['status', 'reason' => null])

@php
    $config = match($status) {
        'pending'     => ['label' => 'Pendente',    'base' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'pulse' => false],
        'in_analysis' => ['label' => 'Em Análise',  'base' => 'bg-blue-100 text-blue-800 border-blue-200',       'pulse' => true],
        'completed'   => ['label' => 'Concluído',   'base' => 'bg-green-100 text-green-800 border-green-200',    'pulse' => false],
        'failed'      => ['label' => 'Falha',        'base' => 'bg-red-100 text-red-800 border-red-200',          'pulse' => false],
        default       => ['label' => $status,        'base' => 'bg-gray-100 text-gray-700 border-gray-200',       'pulse' => false],
    };
@endphp

<span class="relative inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium border {{ $config['base'] }}">

    {{-- Indicador pulsante para "Em Análise" --}}
    @if($config['pulse'])
        <span class="flex h-2 w-2 flex-shrink-0">
            <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-blue-400 opacity-75"></span>
            <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-500"></span>
        </span>
    @endif

    {{ $config['label'] }}

    {{-- Ícone de alerta clicável com tooltip para "Falha" --}}
    @if($status === 'failed' && $reason)
        <span
            class="group relative ml-0.5 cursor-help"
            tabindex="0"
            aria-label="Motivo da falha: {{ $reason }}"
        >
            {{-- Ícone de alerta --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>

            {{-- Tooltip — aparece em hover e focus --}}
            <span
                role="tooltip"
                class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 w-72 -translate-x-1/2
                       rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-xl
                       opacity-0 transition-opacity duration-200
                       group-hover:opacity-100 group-focus:opacity-100
                       whitespace-normal break-words"
            >
                <span class="block font-semibold text-red-300 mb-0.5">Motivo da falha:</span>
                {{ $reason }}
                {{-- Seta do tooltip --}}
                <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></span>
            </span>
        </span>
    @endif

</span>
