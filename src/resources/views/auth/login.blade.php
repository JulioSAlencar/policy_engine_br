<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — GovCert</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/govcert-mark.svg') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        indigo: {
                            50:  '#f2f7fd', 100: '#d4e5f7', 200: '#adcdee', 300: '#5992ed',
                            400: '#2670e8', 500: '#155bcb', 600: '#1351b4', 700: '#0c326f',
                            800: '#071d41', 900: '#071d41',
                        },
                    },
                },
            },
        };
    </script>
</head>
<body class="bg-gradient-to-br from-indigo-900 to-indigo-700 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <img src="{{ asset('img/govcert-logo.svg') }}" alt="GovCert" class="h-14 mx-auto mb-3">
            <p class="text-gray-500 text-sm">Auditoria de IA e Governança Pública</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required autofocus autocomplete="email"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-400 @enderror"
                    placeholder="seu@email.com"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required autocomplete="current-password"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('password') border-red-400 @enderror"
                    placeholder="••••••••"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600">
                    Lembrar-me
                </label>
            </div>

            <button
                type="submit"
                class="w-full bg-indigo-700 hover:bg-indigo-800 text-white font-semibold py-2.5 rounded-lg transition-colors"
            >
                Entrar
            </button>
        </form>
    </div>
</body>
</html>
