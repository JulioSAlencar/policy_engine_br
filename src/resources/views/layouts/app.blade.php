<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Auditoria de IA') — GovCert</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/govcert-mark.svg') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Paleta oficial gov.br — remapeia a escala "indigo" para o azul gov.br
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        indigo: {
                            50:  '#f2f7fd', 100: '#d4e5f7', 200: '#adcdee', 300: '#5992ed',
                            400: '#2670e8', 500: '#155bcb', 600: '#1351b4', 700: '#0c326f',
                            800: '#071d41', 900: '#071d41',
                        },
                        gov: {
                            blue:   '#1351B4',
                            navy:   '#071D41',
                            yellow: '#FFCD07',
                            green:  '#168821',
                            red:    '#E52207',
                        },
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <x-navbar />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
