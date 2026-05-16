@extends('layouts.app')

@section('title', 'Meu Perfil')

@section('content')
<div class="max-w-2xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Meu Perfil</h1>
        <p class="text-gray-500 text-sm mt-1">Atualize seus dados e senha de acesso</p>
    </div>

    <!-- Dados do perfil -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Informações Pessoais</h2>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 @error('name') border-red-400 @enderror">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input type="email" value="{{ $user->email }}" disabled
                    class="w-full border border-gray-200 bg-gray-50 text-gray-500 rounded-lg px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-400">O e-mail não pode ser alterado.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Papel</label>
                <input type="text" value="{{ $user->isAdmin() ? 'Administrador' : 'Auditor' }}" disabled
                    class="w-full border border-gray-200 bg-gray-50 text-gray-500 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="pt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    <!-- Alteração de senha -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Alterar Senha</h2>

        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha Atual</label>
                <input type="password" name="current_password" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 @error('current_password') border-red-400 @enderror">
                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
                <input type="password" name="password" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 @error('password') border-red-400 @enderror">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha</label>
                <input type="password" name="password_confirmation" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
            </div>

            <div class="pt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">
                    Alterar Senha
                </button>
            </div>
        </form>
    </div>

    <!-- Acesso da Extensão (API Token) -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-1">Acesso da Extensão (API Token)</h2>
        <p class="text-gray-500 text-sm mb-4">
            Gere ou revogue o token usado pela extensão do Chrome — direto pelo painel,
            sem precisar de terminal.
        </p>
        <x-button :href="route('profile.tokens')" variant="primary">Gerenciar Token da Extensão</x-button>
    </div>

</div>
@endsection
