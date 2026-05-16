@extends('layouts.app')

@section('title', 'Editar Usuário')

@section('content')
<div class="max-w-xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Editar Usuário</h1>
        <p class="text-gray-500 text-sm mt-1">Atualize os dados de acesso de #{{ $user->id }}</p>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 @error('name') border-red-400 @enderror">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 @error('email') border-red-400 @enderror">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Papel</label>
                <select name="role"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 @error('role') border-red-400 @enderror">
                    <option value="auditor" {{ old('role', $user->role) === 'auditor' ? 'selected' : '' }}>Auditor</option>
                    <option value="admin"   {{ old('role', $user->role) === 'admin'   ? 'selected' : '' }}>Administrador</option>
                </select>
                @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">
                    Salvar Alterações
                </button>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm px-4 py-2 rounded-lg">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

</div>
@endsection
