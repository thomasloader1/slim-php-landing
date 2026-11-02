{{-- Tarjeta editable de un bloque del UX Builder. Variables: $block, $blockTypes, $settings --}}
@php
    $meta     = $blockTypes[$block['type']];
    $d        = $block['data'] ?? [];
    $moduleOff = $meta['module'] && ($settings[$meta['module']] ?? '1') !== '1';
    $input    = 'w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-blue-500/50 transition-all';
    $select   = $input . ' appearance-none';
    $label    = 'block text-xs font-medium text-slate-400 mb-1.5';
    $manage   = ['profile' => 'admin/settings', 'links' => 'admin/links', 'locations' => 'admin/locations', 'faq' => 'admin/seo/faq'];
@endphp
<li class="block-item bg-slate-900 border border-slate-800 rounded-2xl {{ $block['visible'] ? '' : 'is-hidden' }}"
    data-id="{{ $block['id'] }}" data-type="{{ $block['type'] }}">
    <div class="flex items-center gap-3 p-3 sm:p-4">
        <button type="button" class="drag-handle cursor-grab text-slate-600 hover:text-slate-400 px-1" title="Arrastrar para ordenar" aria-label="Arrastrar para ordenar">
            <i class="fa-solid fa-grip-vertical"></i>
        </button>
        <span class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center {{ $meta['core'] ? 'bg-slate-800 text-slate-300' : 'bg-blue-500/10 text-blue-400' }}">
            <i class="fa-solid {{ $meta['icon'] }} text-sm"></i>
        </span>
        <button type="button" class="block-toggle flex-1 min-w-0 text-left" aria-expanded="false">
            <span class="block text-sm font-bold text-white truncate">{{ $meta['label'] }}</span>
            <span class="block-summary block text-xs text-slate-500 truncate">
                @if($moduleOff)
                    <span class="text-amber-400"><i class="fa-solid fa-triangle-exclamation"></i> Módulo desactivado</span>
                @elseif($meta['core'])
                    Sección fija
                @elseif($block['type'] === 'carousel')
                    {{ count($d['slides'] ?? []) }} diapositiva(s)
                @else
                    {{ html_entity_decode(strip_tags($d['text'] ?? $d['label'] ?? $d['title'] ?? $d['query'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') }}
                @endif
            </span>
        </button>
        <button type="button" class="block-visibility p-2 rounded-lg text-slate-400 hover:bg-slate-800 transition-all"
                title="Mostrar / ocultar en la página" aria-label="Mostrar u ocultar {{ $meta['label'] }}" aria-pressed="{{ $block['visible'] ? 'true' : 'false' }}">
            <i class="fa-solid {{ $block['visible'] ? 'fa-eye' : 'fa-eye-slash' }}"></i>
        </button>
        @unless($meta['core'])
            <button type="button" class="block-remove p-2 rounded-lg text-slate-500 hover:bg-red-500/10 hover:text-red-400 transition-all" title="Eliminar componente" aria-label="Eliminar {{ $meta['label'] }}">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        @endunless
    </div>

    <div class="block-body hidden border-t border-slate-800 p-4 space-y-4">
        @switch($block['type'])
            @case('title')
                <div>
                    <label class="{{ $label }}">Título</label>
                    <input type="text" name="landing_title" maxlength="150" value="{{ $settings['landing_title'] ?? '' }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Subtítulo</label>
                    <input type="text" name="landing_subtitle" maxlength="200" value="{{ $settings['landing_subtitle'] ?? '' }}" class="{{ $input }}">
                </div>
                @break

            @case('bio')
                <div>
                    <label class="{{ $label }}">Biografía</label>
                    @include('admin/partials/bio-editor', ['value' => $settings['landing_bio'] ?? ''])
                </div>
                @break

            @case('profile')
            @case('links')
            @case('locations')
            @case('faq')
                <p class="text-sm text-slate-400">
                    El contenido de esta sección se administra en su propia pantalla.
                    <a href="{{ url($manage[$block['type']]) }}" class="text-blue-400 hover:text-blue-300 font-semibold ml-1">Ir a gestionar <i class="fa-solid fa-arrow-right text-xs"></i></a>
                </p>
                @break

            @case('shop_header')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $label }}">Nombre de la tienda</label>
                        <input type="text" name="menu_brand_name" maxlength="150" value="{{ $settings['menu_brand_name'] ?? '' }}" class="{{ $input }}" placeholder="Ej: Panadería Rufi">
                    </div>
                    <div>
                        <label class="{{ $label }}">Bajada</label>
                        <input type="text" name="menu_brand_subtitle" maxlength="200" value="{{ $settings['menu_brand_subtitle'] ?? '' }}" class="{{ $input }}" placeholder="Ej: Hecho a mano, todos los días">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}">Título si no hay nombre</label>
                        <input type="text" name="menu_header_text" maxlength="150" value="{{ $settings['menu_header_text'] ?? '' }}" class="{{ $input }}" placeholder="Nuestra tienda">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach(['show_back' => 'Link "Volver al inicio"', 'show_logo' => 'Logo de la landing', 'show_title' => 'Título', 'show_subtitle' => 'Bajada'] as $f => $l)
                        <label class="flex items-center gap-3 text-sm text-slate-300 cursor-pointer">
                            <input type="checkbox" data-field="{{ $f }}" {{ ($d[$f] ?? true) ? 'checked' : '' }} class="w-4 h-4 accent-blue-500"> {{ $l }}
                        </label>
                    @endforeach
                </div>
                <div class="max-w-xs">
                    <label class="{{ $label }}">Alineación</label>
                    <select data-field="align" class="{{ $select }}">
                        @foreach(['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha'] as $v => $l)
                            <option value="{{ $v }}" {{ ($d['align'] ?? 'center') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-xs text-slate-500">Si ocultás el título, igual queda disponible para lectores de pantalla y buscadores.</p>
                @break

            @case('products')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $label }}">Columnas en escritorio</label>
                        <select data-field="columns" class="{{ $select }}">
                            @foreach(['2' => '2 columnas', '3' => '3 columnas', '4' => '4 columnas'] as $v => $l)
                                <option value="{{ $v }}" {{ ($d['columns'] ?? '3') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">En celulares siempre se ven 2 por fila.</p>
                    </div>
                    <div>
                        <label class="{{ $label }}">Forma de la imagen</label>
                        <select data-field="card_ratio" class="{{ $select }}">
                            @foreach(['square' => 'Cuadrada (1:1)', 'landscape' => 'Horizontal (4:3)', 'portrait' => 'Vertical (3:4)'] as $v => $l)
                                <option value="{{ $v }}" {{ ($d['card_ratio'] ?? 'square') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach([
                        'show_search'          => 'Buscador',
                        'show_filters'         => 'Filtros por categoría',
                        'group_by_category'    => 'Agrupar por categoría',
                        'show_category_titles' => 'Títulos de categoría',
                        'show_description'     => 'Descripción en la card',
                    ] as $f => $l)
                        <label class="flex items-center gap-3 text-sm text-slate-300 cursor-pointer">
                            <input type="checkbox" data-field="{{ $f }}" {{ ($d[$f] ?? true) ? 'checked' : '' }} class="w-4 h-4 accent-blue-500"> {{ $l }}
                        </label>
                    @endforeach
                </div>
                <p class="text-sm text-slate-400">
                    Los productos y categorías se cargan en su propia pantalla.
                    <a href="{{ url('admin/menu/items') }}" class="text-blue-400 hover:text-blue-300 font-semibold ml-1">Ir a Productos <i class="fa-solid fa-arrow-right text-xs"></i></a>
                </p>
                @break

            @case('payments')
                <p class="text-sm text-slate-400">
                    Medios de pago, alias y texto del pie se configuran en Tienda → Configuración.
                    <a href="{{ url('admin/menu/settings') }}" class="text-blue-400 hover:text-blue-300 font-semibold ml-1">Ir a configurar <i class="fa-solid fa-arrow-right text-xs"></i></a>
                </p>
                @break

            @case('banner')
                @include('admin/builder/_image-field', ['name' => 'image', 'value' => $d['image'] ?? '', 'label' => 'Imagen', 'hint' => 'Ideal 1920×820, hasta 5 MB'])
                @include('admin/builder/_image-field', ['name' => 'image_mobile', 'value' => $d['image_mobile'] ?? '', 'label' => 'Imagen para celular (opcional)', 'hint' => 'Ideal 900×900'])
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $label }}">Título</label>
                        <input type="text" data-field="title" maxlength="150" value="{{ $d['title'] ?? '' }}" class="{{ $input }}" placeholder="Ej: Promo de la semana">
                    </div>
                    <div>
                        <label class="{{ $label }}">Bajada</label>
                        <input type="text" data-field="subtitle" maxlength="300" value="{{ $d['subtitle'] ?? '' }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Texto del botón <span class="text-slate-600">(opcional)</span></label>
                        <input type="text" data-field="cta_label" maxlength="60" value="{{ $d['cta_label'] ?? '' }}" class="{{ $input }}" placeholder="Ej: Ver productos">
                    </div>
                    <div>
                        <label class="{{ $label }}">Enlace <span class="text-slate-600">(opcional)</span></label>
                        <input type="text" data-field="cta_url" maxlength="500" value="{{ $d['cta_url'] ?? '' }}" class="{{ $input }}" placeholder="/shop o https://…">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}">Descripción de la imagen <span class="text-slate-600">(accesibilidad)</span></label>
                        <input type="text" data-field="alt" maxlength="150" value="{{ $d['alt'] ?? '' }}" class="{{ $input }}">
                    </div>
                </div>
                <label class="flex items-center gap-3 text-sm text-slate-300 cursor-pointer">
                    <input type="checkbox" data-field="show_text" {{ ($d['show_text'] ?? true) ? 'checked' : '' }} class="w-4 h-4 accent-blue-500">
                    Mostrar título, bajada y botón sobre la imagen
                </label>
                <p class="text-xs text-slate-500 -mt-2 ml-7">Desactivalo si la imagen ya trae el texto. Con enlace, todo el banner queda clickeable.</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="{{ $label }}">Alto</label>
                        <select data-field="height" class="{{ $select }}">
                            @foreach(['sm' => 'Bajo', 'md' => 'Medio', 'lg' => 'Alto'] as $v => $l)
                                <option value="{{ $v }}" {{ ($d['height'] ?? 'md') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Alineación del texto</label>
                        <select data-field="align" class="{{ $select }}">
                            @foreach(['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha'] as $v => $l)
                                <option value="{{ $v }}" {{ ($d['align'] ?? 'center') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Oscurecer imagen <span class="text-slate-600">(<span data-range-out>{{ $d['overlay'] ?? 35 }}</span>%)</span></label>
                        <input type="range" data-field="overlay" min="0" max="80" step="5" value="{{ $d['overlay'] ?? 35 }}" class="w-full accent-blue-500 mt-2">
                    </div>
                </div>
                @break

            @case('carousel')
                <ul class="slides-list space-y-3">
                    @foreach($d['slides'] ?? [] as $s)
                        @include('admin/builder/_slide', ['s' => $s])
                    @endforeach
                </ul>
                <button type="button" class="slide-add w-full border border-dashed border-slate-700 hover:border-blue-500/50 text-slate-400 hover:text-white rounded-2xl py-3 text-sm font-bold transition-all">
                    <i class="fa-solid fa-plus mr-1.5" aria-hidden="true"></i> Agregar diapositiva
                </button>
                <p class="text-xs text-slate-500">Hasta {{ \App\Services\PageLayoutService::MAX_SLIDES }} diapositivas. Las que no tengan imagen no se muestran.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label class="flex items-center gap-3 text-sm text-slate-300 cursor-pointer">
                        <input type="checkbox" data-field="show_text" {{ ($d['show_text'] ?? true) ? 'checked' : '' }} class="w-4 h-4 accent-blue-500"> Mostrar textos sobre las imágenes
                    </label>
                    <label class="flex items-center gap-3 text-sm text-slate-300 cursor-pointer">
                        <input type="checkbox" data-field="autoplay" {{ ($d['autoplay'] ?? true) ? 'checked' : '' }} class="w-4 h-4 accent-blue-500"> Pasar solo
                    </label>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="{{ $label }}">Alto</label>
                        <select data-field="height" class="{{ $select }}">
                            @foreach(['sm' => 'Bajo', 'md' => 'Medio', 'lg' => 'Alto'] as $v => $l)
                                <option value="{{ $v }}" {{ ($d['height'] ?? 'md') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Segundos por diapositiva</label>
                        <input type="number" data-field="interval" min="3" max="10" value="{{ $d['interval'] ?? 5 }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Oscurecer imagen <span class="text-slate-600">(<span data-range-out>{{ $d['overlay'] ?? 35 }}</span>%)</span></label>
                        <input type="range" data-field="overlay" min="0" max="80" step="5" value="{{ $d['overlay'] ?? 35 }}" class="w-full accent-blue-500 mt-2">
                    </div>
                </div>
                @break

            @case('heading')
                <div>
                    <label class="{{ $label }}">Texto</label>
                    <input type="text" data-field="text" maxlength="150" value="{{ $d['text'] ?? '' }}" class="{{ $input }}" placeholder="Ej: Nuestros servicios">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $label }}">Tamaño</label>
                        <select data-field="size" class="{{ $select }}">
                            @foreach(['sm' => 'Chico', 'md' => 'Mediano', 'lg' => 'Grande'] as $v => $l)
                                <option value="{{ $v }}" {{ ($d['size'] ?? 'md') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Alineación</label>
                        <select data-field="align" class="{{ $select }}">
                            @foreach(['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha'] as $v => $l)
                                <option value="{{ $v }}" {{ ($d['align'] ?? 'center') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @break

            @case('text')
                <div>
                    <label class="{{ $label }}">Contenido</label>
                    @include('admin/partials/bio-editor', ['value' => $d['text'] ?? '', 'field' => 'text', 'placeholder' => 'Escribí un texto. Podés usar negrita, listas y links.'])
                </div>
                <div>
                    <label class="{{ $label }}">Alineación</label>
                    <select data-field="align" class="{{ $select }}">
                        @foreach(['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha'] as $v => $l)
                            <option value="{{ $v }}" {{ ($d['align'] ?? 'center') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                @break

            @case('button')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $label }}">Texto del botón</label>
                        <input type="text" data-field="label" maxlength="100" value="{{ $d['label'] ?? '' }}" class="{{ $input }}" placeholder="Ej: Reservá tu turno">
                    </div>
                    <div>
                        <label class="{{ $label }}">Enlace</label>
                        <input type="text" data-field="url" maxlength="500" value="{{ $d['url'] ?? '' }}" class="{{ $input }}" placeholder="https://...">
                    </div>
                    <div>
                        <label class="{{ $label }}">Ícono <span class="text-slate-600">(Font Awesome, opcional)</span></label>
                        <input type="text" data-field="icon" maxlength="60" value="{{ $d['icon'] ?? '' }}" class="{{ $input }} font-mono" placeholder="fa-calendar-check">
                    </div>
                    <div>
                        <label class="{{ $label }}">Estilo</label>
                        <select data-field="style" class="{{ $select }}">
                            @foreach(['glass' => 'Vidrio (como los enlaces)', 'accent' => 'Color de acento', 'outline' => 'Contorno'] as $v => $l)
                                <option value="{{ $v }}" {{ ($d['style'] ?? 'glass') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @break

            @case('map')
                <div>
                    <label class="{{ $label }}">Título <span class="text-slate-600">(opcional)</span></label>
                    <input type="text" data-field="title" maxlength="150" value="{{ $d['title'] ?? '' }}" class="{{ $input }}" placeholder="Ej: Dónde estamos">
                </div>
                <div>
                    <label class="{{ $label }}">Dirección o link de Google Maps</label>
                    <input type="text" data-field="query" maxlength="500" value="{{ $d['query'] ?? '' }}" class="{{ $input }}" placeholder="Av. Corrientes 1234, CABA  ó  https://www.google.com/maps/place/...">
                    <p class="text-xs text-slate-500 mt-1">Se muestra como mapa embebido. Los links cortos (maps.app.goo.gl) no traen la ubicación: usá la dirección.</p>
                </div>
                @break

            @case('divider')
                <div>
                    <label class="{{ $label }}">Estilo</label>
                    <select data-field="style" class="{{ $select }}">
                        @foreach(['line' => 'Línea', 'dots' => 'Puntos', 'space' => 'Espacio en blanco'] as $v => $l)
                            <option value="{{ $v }}" {{ ($d['style'] ?? 'line') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                @break
        @endswitch
    </div>
</li>
