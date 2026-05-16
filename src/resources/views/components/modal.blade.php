@props([
    'id',
    'maxWidth'   => 'max-w-2xl',
    'panelClass' => 'bg-white',
])

{{--
    Modal reutilizável. O conteúdo (cabeçalho/corpo/rodapé) vem pelo slot,
    preservando os IDs usados pelos scripts. O backdrop possui [data-close]
    e NÃO usa blur (acessibilidade). O JS de cada tela alterna a classe
    "hidden" neste elemento raiz (#{{ '{id}' }}).
--}}
<div id="{{ $id }}"
     class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true">

    <div class="absolute inset-0 bg-black/50 transition-all duration-300 ease-in-out" data-close></div>

    <div {{ $attributes->merge([
            'class' => "relative {$panelClass} rounded-2xl shadow-2xl w-full {$maxWidth} max-h-[90vh] flex flex-col overflow-hidden",
         ]) }}>
        {{ $slot }}
    </div>
</div>
