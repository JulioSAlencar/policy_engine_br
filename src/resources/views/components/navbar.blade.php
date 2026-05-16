@php
    // Indicador de aba ativa: borda inferior espessa (amarelo gov.br) +
    // texto branco em negrito. Sem blur (acessibilidade). Transição fluida.
    $tab = function (string ...$patterns): string {
        $active = request()->routeIs(...$patterns);

        return 'flex items-center h-16 border-b-2 px-1 text-sm '
             . 'transition-all duration-300 ease-in-out '
             . ($active
                 ? 'border-gov-yellow text-white font-semibold'
                 : 'border-transparent text-indigo-200 hover:text-white hover:border-indigo-300');
    };
@endphp

<nav class="bg-indigo-800 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-2.5 transition-all duration-300 ease-in-out hover:opacity-90">
                    <img src="{{ asset('img/govcert-mark.svg') }}" alt="GovCert" class="h-9 w-9">
                    <span class="font-bold text-xl tracking-tight">GovCert</span>
                </a>

                <a href="{{ route('dashboard') }}" class="{{ $tab('dashboard') }}">Dashboard</a>
                <a href="{{ route('audit.index') }}" class="{{ $tab('audit.*') }}">Logs de Auditoria</a>

                @if (Auth::user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}" class="{{ $tab('admin.users.*') }}">Usuários</a>
                    <a href="{{ route('admin.activity.index') }}" class="{{ $tab('admin.activity.*') }}">Atividades</a>
                @endif
            </div>

            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('profile.edit') }}" class="{{ $tab('profile.*') }}">
                    {{ Auth::user()->name }}
                    <span class="ml-1 text-indigo-400 text-xs">({{ Auth::user()->isAdmin() ? 'Admin' : 'Auditor' }})</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button type="submit" variant="primary" size="sm">Sair</x-button>
                </form>
            </div>

        </div>
    </div>
</nav>
