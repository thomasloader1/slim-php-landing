{{--
    Bloque core "Productos" de la tienda: buscador, filtros por categoría y grilla de cards.
    Opciones (UX Builder → Tienda): columnas, proporción de imagen, buscador, filtros,
    agrupar por categoría, títulos de categoría y descripción.
    Variables del padre: $sections, $unsectioned, $shopOpen
--}}
@php
    $cols = [
        '2' => 'grid-cols-2',
        '3' => 'grid-cols-2 lg:grid-cols-3',
        '4' => 'grid-cols-2 md:grid-cols-3 xl:grid-cols-4',
    ][$data['columns'] ?? '3'] ?? 'grid-cols-2 lg:grid-cols-3';
    $ratio = [
        'square'    => 'aspect-square',
        'landscape' => 'aspect-[4/3]',
        'portrait'  => 'aspect-[3/4]',
    ][$data['card_ratio'] ?? 'square'] ?? 'aspect-square';
    $grouped    = (bool) ($data['group_by_category'] ?? true);
    $showTitles = (bool) ($data['show_category_titles'] ?? true);
    $showSearch = (bool) ($data['show_search'] ?? true);
    $showDesc   = (bool) ($data['show_description'] ?? true);

    $activeSections = $sections->filter(fn ($s) => $s->activeItems->count() > 0)->values();
    $showFilters = (bool) ($data['show_filters'] ?? true) && ($activeSections->count() + ($unsectioned->count() ? 1 : 0)) > 1;
    $isEmpty = $unsectioned->count() === 0 && $activeSections->count() === 0;

    // Imagen de la card: grande (con la forma elegida), chica (franja baja) o sin imagen
    $imageSize = $data['image_size'] ?? 'lg';
    if ($imageSize === 'sm') {
        $ratio = 'h-28 sm:h-36';
    }
    $card = ['ratio' => $ratio, 'showDesc' => $showDesc, 'showImage' => $imageSize !== 'none'];
    $grid = 'grid ' . $cols . ' gap-3 sm:gap-5 lg:gap-6';
    $noteIcons = [
        'info'     => 'fa-circle-info',
        'preorder' => 'fa-circle-exclamation',
        'time'     => 'fa-clock',
    ];
@endphp

@if($showSearch || $showFilters)
<div class="shop-toolbar">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-col md:flex-row md:items-center gap-3">
        @if($showSearch)
            <div class="relative md:w-80 shrink-0" role="search">
                <i class="fa-solid fa-magnifying-glass text-sm absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none t-subtle" aria-hidden="true"></i>
                <label for="search" class="sr-only">Buscar productos</label>
                <input id="search" type="search" placeholder="Buscar productos…" autocomplete="off" class="ds-input pl-11">
            </div>
        @endif

        @if($showFilters)
            <nav aria-label="Filtrar por categoría" class="pills-scroll min-w-0 flex-1">
                <ul class="flex gap-2 py-0.5">
                    <li><button type="button" class="cat-pill active" data-cat="all" aria-pressed="true">Todos</button></li>
                    @if($unsectioned->count())
                        <li><button type="button" class="cat-pill" data-cat="sec-0" aria-pressed="false">Otros</button></li>
                    @endif
                    @foreach($activeSections as $section)
                        <li>
                            <button type="button" class="cat-pill" data-cat="sec-{{ $section->id }}" aria-pressed="false">
                                @if($section->icon)<i class="{{ $section->icon }} text-xs" aria-hidden="true"></i>@endif
                                {{ $section->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </div>
</div>
@endif

<div id="menu-root" class="max-w-7xl mx-auto px-4 sm:px-6 pt-6 sm:pt-8 space-y-12 sm:space-y-16" aria-label="Productos">
    @if($grouped)
        @if($unsectioned->count())
            <div class="cat-section" data-cat="sec-0">
                <ul class="{{ $grid }}" aria-label="Productos">
                    @foreach($unsectioned as $item)
                        <li class="flex">@include('menu/_item_card', ['item' => $item, 'cat' => 'sec-0'] + $card)</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @foreach($activeSections as $section)
            <div class="cat-section" data-cat="sec-{{ $section->id }}" aria-labelledby="title-sec-{{ $section->id }}" role="region">
                <div class="mb-5 sm:mb-6 {{ $showTitles ? '' : 'sr-only' }}">
                    <h2 id="title-sec-{{ $section->id }}" class="text-xl sm:text-2xl font-extrabold tracking-tight flex items-center gap-3">
                        @if($section->icon)
                            <span class="cat-icon" aria-hidden="true"><i class="{{ $section->icon }} text-sm"></i></span>
                        @endif
                        {{ $section->name }}
                        <span class="text-sm font-semibold t-subtle">({{ $section->activeItems->count() }})</span>
                    </h2>
                    @if($section->note)
                        <p class="text-sm t-muted mt-2 flex items-center gap-2" role="note">
                            @if($section->note_type && $section->note_type !== 'none')
                                <i class="fa-solid {{ $noteIcons[$section->note_type] ?? 'fa-circle-info' }} accent-text" aria-hidden="true"></i>
                            @endif
                            {{ $section->note }}
                        </p>
                    @endif
                </div>
                <ul class="{{ $grid }}" aria-label="Productos de {{ $section->name }}">
                    @foreach($section->activeItems as $item)
                        <li class="flex">@include('menu/_item_card', ['item' => $item, 'cat' => 'sec-' . $section->id] + $card)</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    @else
        {{-- Una sola grilla con todos los productos; la categoría se muestra en cada card --}}
        <div class="cat-section" data-cat="all">
            <ul class="{{ $grid }}" aria-label="Productos">
                @foreach($unsectioned as $item)
                    <li class="flex">@include('menu/_item_card', ['item' => $item, 'cat' => 'sec-0', 'catLabel' => ''] + $card)</li>
                @endforeach
                @foreach($activeSections as $section)
                    @foreach($section->activeItems as $item)
                        <li class="flex">@include('menu/_item_card', ['item' => $item, 'cat' => 'sec-' . $section->id, 'catLabel' => $section->name] + $card)</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif

    @if($isEmpty)
        <div class="text-center py-24 t-muted">
            <i class="fa-solid fa-store text-5xl mb-4 block opacity-30" aria-hidden="true"></i>
            <p class="text-lg">Todavía no hay productos publicados.</p>
        </div>
    @endif

    <div id="no-results" class="text-center py-20" aria-live="polite" aria-atomic="true" hidden>
        <i class="fa-solid fa-magnifying-glass text-4xl mb-4 block t-faint" aria-hidden="true"></i>
        <p class="text-lg t-muted">No encontramos productos con ese nombre.</p>
        <p class="text-sm mt-1 t-subtle">Probá con otra palabra o elegí otra categoría.</p>
    </div>
</div>
