@extends('layouts.app')

@section('title', 'Registro de Atividades')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Registro de Atividades</h1>
        <p class="text-gray-500 text-sm mt-1">Trilha de auditoria de todas as ações do sistema</p>
    </div>

    <form method="GET" action="{{ route('admin.activity.index') }}" class="bg-white rounded-xl shadow p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Ação</label>
                <select name="action" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    <option value="">Todas</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Buscar na descrição</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="ex: redefiniu, login, exportou"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">Filtrar</button>
            <a href="{{ route('admin.activity.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm px-4 py-2 rounded-lg">Limpar</a>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Data/Hora</th>
                        <th class="px-4 py-3">Usuário</th>
                        <th class="px-4 py-3">Ação</th>
                        <th class="px-4 py-3">Descrição</th>
                        <th class="px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 text-gray-900">
                            {{ $log->user?->name ?? 'Sistema/Anônimo' }}
                            @if($log->user)
                                <span class="block text-xs text-gray-400">{{ $log->user->email }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-indigo-50 text-indigo-700">{{ $log->action }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $log->description }}</td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-400">Nenhuma atividade registrada.</td>
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
    </div>

</div>
@endsection
