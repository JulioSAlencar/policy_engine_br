@extends('layouts.app')

@section('title', 'API Token da Extensão')

@section('content')
<div class="max-w-2xl space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">API Token da Extensão</h1>
            <p class="text-gray-500 text-sm mt-1">Gere o token usado pela extensão do Chrome — sem precisar de terminal</p>
        </div>
        <x-button :href="route('profile.edit')" variant="secondary" size="sm">← Voltar ao Perfil</x-button>
    </div>

    {{-- Token recém-gerado: exibido UMA ÚNICA VEZ (flash one-time) --}}
    @if (session('plainTextToken'))
        <div class="bg-green-50 border-2 border-green-500 rounded-xl p-5">
            <div class="flex items-start gap-3">
                <div class="text-green-600 text-2xl leading-none">✓</div>
                <div class="flex-1">
                    <h2 class="font-semibold text-green-800">Token gerado com sucesso!</h2>
                    <p class="text-sm text-green-700 mt-1">
                        Copie o token abaixo <strong>agora</strong> e cole no campo
                        <em>API Token</em> da tela de Opções da extensão. Por segurança,
                        ele <strong>não será exibido novamente</strong>.
                    </p>
                    <div class="mt-3 bg-white border border-green-300 rounded-lg p-3 font-mono text-sm text-gray-800 break-all select-all">
                        {{ session('plainTextToken') }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Status atual do token --}}
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Status do Acesso</h2>

        @if ($token)
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                <span class="text-sm text-gray-700">
                    Token ativo gerado em
                    <strong>{{ $token->created_at->format('d/m/Y \à\s H:i') }}</strong>
                </span>
            </div>
            <p class="text-xs text-gray-400 mt-2">
                Último uso:
                {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'ainda não utilizado' }}
            </p>
        @else
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Inativo</span>
                <span class="text-sm text-gray-700">Nenhum token ativo.</span>
            </div>
            <p class="text-xs text-gray-400 mt-2">A extensão não conseguirá enviar dados até que um token seja gerado.</p>
        @endif
    </div>

    {{-- Ações --}}
    <div class="bg-white rounded-xl shadow p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-800">Ações</h2>

        <div class="flex flex-col sm:flex-row gap-3">
            <form method="POST" action="{{ route('profile.tokens.store') }}"
                  onsubmit="return confirm('{{ $token ? 'Isto vai REVOGAR o token atual e gerar um novo. A extensão precisará ser reconfigurada. Continuar?' : 'Gerar um novo token para a extensão?' }}');">
                @csrf
                <x-button type="submit" variant="primary">
                    {{ $token ? 'Substituir Token' : 'Gerar Token' }}
                </x-button>
            </form>

            @if ($token)
                <form method="POST" action="{{ route('profile.tokens.destroy') }}"
                      onsubmit="return confirm('Revogar o acesso? A extensão deixará de enviar dados imediatamente.');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger">Revogar Acesso</x-button>
                </form>
            @endif
        </div>

        <p class="text-xs text-gray-500">
            Você tem, no máximo, <strong>um token ativo</strong>. Gerar um novo revoga
            automaticamente o anterior. Use <strong>Revogar Acesso</strong> imediatamente
            se suspeitar que o token vazou.
        </p>
    </div>

</div>
@endsection
