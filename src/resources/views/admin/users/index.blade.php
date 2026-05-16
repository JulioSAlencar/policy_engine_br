@extends('layouts.app')

@section('title', 'Gestão de Usuários')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Gestão de Usuários</h1>
        <p class="text-gray-500 text-sm mt-1">Controle de papéis e status de acesso (somente administradores)</p>
    </div>

    <!-- Alerta de sucesso (AJAX) -->
    <div id="user-flash" class="hidden bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded"></div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Nome Completo</th>
                        <th class="px-4 py-3">E-mail</th>
                        <th class="px-4 py-3">Papel</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($users as $user)
                    <tr id="user-row-{{ $user->id }}" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-gray-500">#{{ $user->id }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 cell-name">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-600 cell-email">{{ $user->email }}</td>
                        <td class="px-4 py-3 cell-role">
                            @if($user->role === 'admin')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">Admin</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">Auditor</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($user->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inativo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <x-button
                                    class="btn-edit-user"
                                    variant="soft"
                                    size="sm"
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-role="{{ $user->role }}"
                                    data-action="{{ route('admin.users.update', $user) }}">
                                    Editar
                                </x-button>
                                <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                    @csrf
                                    <x-button type="submit" size="sm"
                                        :variant="$user->is_active ? 'soft-danger' : 'soft-success'">
                                        {{ $user->is_active ? 'Desativar' : 'Ativar' }}
                                    </x-button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.reset-link', $user) }}">
                                    @csrf
                                    <x-button type="submit" variant="soft-indigo" size="sm">
                                        Enviar Link de Reset
                                    </x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $users->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Modal único — Edição de Usuário (componente <x-modal>; IDs preservados p/ o JS) --}}
<x-modal id="edit-modal" max-width="max-w-md">
    <div class="flex items-center justify-between px-5 py-4 bg-indigo-800 text-white">
        <h3 class="font-semibold">Editar Usuário <span id="em-id" class="text-indigo-300 text-sm font-mono"></span></h3>
        <button id="edit-close" type="button"
            class="text-indigo-200 hover:text-white hover:bg-indigo-700 rounded-lg w-8 h-8 flex items-center justify-center text-xl leading-none transition-all duration-300 ease-in-out"
            aria-label="Fechar">&times;</button>
    </div>

    <form id="edit-form" class="p-5 space-y-4">
        <!-- Erro geral -->
        <div id="em-error" class="hidden bg-red-100 border border-red-400 text-red-800 px-3 py-2 rounded text-sm"></div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
            <input type="text" name="name" id="em-name"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
            <p class="mt-1 text-sm text-red-600 hidden" data-error-for="name"></p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
            <input type="email" name="email" id="em-email"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
            <p class="mt-1 text-sm text-red-600 hidden" data-error-for="email"></p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Papel</label>
            <select name="role" id="em-role"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                <option value="auditor">Auditor</option>
                <option value="admin">Administrador</option>
            </select>
            <p class="mt-1 text-sm text-red-600 hidden" data-error-for="role"></p>
        </div>

        <div class="flex items-center gap-2 pt-2">
            <x-button type="submit" id="em-submit" variant="primary">Salvar Alterações</x-button>
            <x-button type="button" variant="secondary" data-close>Cancelar</x-button>
        </div>
    </form>
</x-modal>
@endsection

@push('scripts')
<script>
(function () {
    const modal   = document.getElementById('edit-modal');
    const form    = document.getElementById('edit-form');
    const submit  = document.getElementById('em-submit');
    const csrf    = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let actionUrl = null;
    let currentId = null;

    const fields = ['name', 'email', 'role'];

    function clearErrors() {
        document.getElementById('em-error').classList.add('hidden');
        document.getElementById('em-error').textContent = '';
        form.querySelectorAll('[data-error-for]').forEach(function (el) {
            el.classList.add('hidden');
            el.textContent = '';
        });
    }

    function openModal(btn) {
        clearErrors();
        currentId = btn.dataset.id;
        actionUrl = btn.dataset.action;
        document.getElementById('em-id').textContent   = '#' + currentId;
        document.getElementById('em-name').value       = btn.dataset.name;
        document.getElementById('em-email').value      = btn.dataset.email;
        document.getElementById('em-role').value       = btn.dataset.role;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function showError(field, message) {
        const el = form.querySelector('[data-error-for="' + field + '"]');
        if (el) { el.textContent = message; el.classList.remove('hidden'); }
    }

    function flashSuccess(message) {
        const flash = document.getElementById('user-flash');
        flash.textContent = message;
        flash.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => flash.classList.add('hidden'), 5000);
    }

    function roleBadge(role) {
        return role === 'admin'
            ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">Admin</span>'
            : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">Auditor</span>';
    }

    function updateRow(user) {
        const row = document.getElementById('user-row-' + user.id);
        if (!row) return;
        row.querySelector('.cell-name').textContent  = user.name;
        row.querySelector('.cell-email').textContent = user.email;
        row.querySelector('.cell-role').innerHTML    = roleBadge(user.role);

        // Mantém os data-* do botão Editar sincronizados
        const btn = row.querySelector('.btn-edit-user');
        if (btn) {
            btn.dataset.name  = user.name;
            btn.dataset.email = user.email;
            btn.dataset.role  = user.role;
        }
    }

    document.querySelectorAll('.btn-edit-user').forEach(function (btn) {
        btn.addEventListener('click', function () { openModal(btn); });
    });

    document.getElementById('edit-close').addEventListener('click', closeModal);
    modal.querySelectorAll('[data-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        submit.disabled = true;
        submit.textContent = 'Salvando...';

        const payload = {};
        fields.forEach(f => payload[f] = document.getElementById('em-' + f).value);

        try {
            const res = await fetch(actionUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            if (res.ok) {
                const data = await res.json();
                updateRow(data.user);
                closeModal();
                flashSuccess(data.message || 'Usuário atualizado com sucesso.');
            } else if (res.status === 422) {
                const data = await res.json();
                Object.keys(data.errors || {}).forEach(function (field) {
                    showError(field, data.errors[field][0]);
                });
            } else {
                const box = document.getElementById('em-error');
                box.textContent = 'Erro inesperado (' + res.status + '). Tente novamente.';
                box.classList.remove('hidden');
            }
        } catch (err) {
            const box = document.getElementById('em-error');
            box.textContent = 'Falha de comunicação com o servidor.';
            box.classList.remove('hidden');
        } finally {
            submit.disabled = false;
            submit.textContent = 'Salvar Alterações';
        }
    });
})();
</script>
@endpush
