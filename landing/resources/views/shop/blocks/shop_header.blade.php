{{--
    Encabezado de la tienda. Textos desde Tienda → Configuración (menu_brand_*);
    logo heredado de la landing. Cada parte se puede ocultar desde el UX Builder.
    Con el título oculto, el <h1> queda para lectores de pantalla.
--}}
@php
    $brand    = trim($settings['menu_brand_name'] ?? '');
    $title    = $brand !== '' ? $brand : (trim($settings['menu_header_text'] ?? '') ?: 'Nuestra tienda');
    $sub      = trim($settings['menu_brand_subtitle'] ?? '');
    $logo     = ($settings['landing_logo_url'] ?? '') ?: ($settings['landing_avatar_url'] ?? '');
    $align    = $data['align'] ?? 'center';
    $items    = ['left' => 'items-start text-left', 'center' => 'items-center text-center', 'right' => 'items-end text-right'][$align] ?? 'items-center text-center';
    $showLogo = ($data['show_logo'] ?? true) && $logo !== '';
    $showSub  = ($data['show_subtitle'] ?? true) && $sub !== '';
@endphp
<header class="max-w-7xl mx-auto px-4 sm:px-6 pt-6 sm:pt-10">
    @if($data['show_back'] ?? true)
        <a href="{{ url() }}" class="inline-flex items-center gap-2 text-sm font-semibold t-muted hover-t-text transition-colors py-2">
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i> Volver al inicio
        </a>
    @endif

    <div class="flex flex-col gap-3 {{ $items }} {{ ($data['show_title'] ?? true) || $showLogo || $showSub ? 'mt-4 sm:mt-6' : '' }}">
        @if($showLogo)
            <img src="{{ $logo }}" alt="" class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover shadow-lg" style="border:1px solid var(--border)">
        @endif
        <h1 class="{{ ($data['show_title'] ?? true) ? 'text-3xl sm:text-5xl font-extrabold tracking-tight leading-[1.05]' : 'sr-only' }}">{!! nl2br(e($title)) !!}</h1>
        @if($showSub)
            <p class="text-sm sm:text-base t-muted max-w-2xl">{{ $sub }}</p>
        @endif
    </div>
</header>
