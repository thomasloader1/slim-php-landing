<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings['menu_header_text'] ?? 'Menú' }} | {{ $settings['site_name'] ?? 'Carta' }}</title>
    @include('partials/seo-head', [
        'settings'  => $settings,
        'pageTitle' => ($settings['menu_header_text'] ?? 'Menú') . ' | ' . ($settings['site_name'] ?? ''),
        'pageUrl'   => $pageUrl ?? '',
    ])

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Utilities -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --accent:  {{ $settings['landing_accent_color'] ?? '#fec771' }};
            --bg-base: {{ $settings['landing_bg_color']    ?? '#020617' }};
            --text:    {{ $settings['landing_text_color']  ?? '#ffffff' }};
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-base);
            color: var(--text);
            margin: 0;
            min-height: 100vh;
        }
        /* Fondo con imagen si existe */
        @if(!empty($settings['landing_bg_image_url']))
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url('{{ $settings['landing_bg_image_url'] }}');
            background-size: cover;
            background-position: center;
            @if(($settings['landing_bg_overlay'] ?? '0') === '1')
            background-color: var(--overlay, #000);
            @endif
            opacity: {{ $settings['landing_bg_overlay_opacity'] ?? '0.3' }};
            z-index: -1;
        }
        @endif

        /* Buscador */
        #search {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text);
            outline: none;
            transition: border-color 0.2s;
        }
        #search::placeholder { color: rgba(255,255,255,0.3); }
        #search:focus { border-color: var(--accent); }

        /* Separador de sección */
        .section-divider {
            border-color: color-mix(in srgb, var(--accent) 25%, transparent);
        }

        /* Card hover */
        .menu-card:hover {
            border-color: color-mix(in srgb, var(--accent) 40%, transparent);
        }

        /* Barra de búsqueda – ícono */
        .search-icon { color: rgba(255,255,255,0.3); }

        /* Sin resultados */
        #no-results { display: none; }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="text-center pt-12 pb-8 px-4">
        <a href="{{ url() }}"
           class="inline-flex items-center gap-2 text-sm mb-6 opacity-50 hover:opacity-80 transition-opacity"
           style="color: var(--text);">
            <i class="fa-solid fa-chevron-left text-xs"></i> Volver
        </a>
        <h1 class="text-4xl sm:text-5xl font-black tracking-tight"
            style="color: var(--text);">
            {{ $settings['menu_header_text'] ?? 'Nuestra Carta' }}
        </h1>
    </header>

    <!-- Buscador -->
    <div class="max-w-2xl mx-auto px-4 mb-10">
        <div class="relative">
            <span class="search-icon absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-sm"></i>
            </span>
            <input id="search" type="text"
                   placeholder="Buscar en el menú..."
                   class="w-full rounded-2xl pl-11 pr-5 py-3.5 text-base">
        </div>
    </div>

    <!-- Contenido del menú -->
    <main id="menu-root" class="max-w-5xl mx-auto px-4 pb-20 space-y-14">

        {{-- Items sin sección --}}
        @if($unsectioned->count())
        <section data-section-index="0">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($unsectioned as $item)
                    @include('menu/_item_card', ['item' => $item])
                @endforeach
            </div>
        </section>
        @endif

        {{-- Secciones agrupadas --}}
        @foreach($sections as $idx => $section)
            @if($section->activeItems->count())
            <section data-section-index="{{ $idx + 1 }}">
                <h2 class="text-2xl font-bold mb-6 pb-3 border-b section-divider"
                    style="color: var(--text);">
                    {{ $section->name }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($section->activeItems as $item)
                        @include('menu/_item_card', ['item' => $item])
                    @endforeach
                </div>
            </section>
            @endif
        @endforeach

        {{-- Menú vacío --}}
        @if($unsectioned->count() === 0 && $sections->filter(fn($s) => $s->activeItems->count() > 0)->count() === 0)
        <div class="text-center py-20 opacity-30">
            <i class="fa-solid fa-utensils text-5xl mb-4 block"></i>
            <p class="text-lg">La carta está vacía por el momento.</p>
        </div>
        @endif

        <!-- Sin resultados de búsqueda -->
        <div id="no-results" class="text-center py-16 opacity-40">
            <i class="fa-solid fa-magnifying-glass text-4xl mb-4 block"></i>
            <p class="text-base">No se encontraron resultados.</p>
        </div>

    </main>

    <!-- Footer -->
    @if(!empty($settings['menu_footer_text']))
    <footer class="text-center pb-10 px-4">
        <p class="text-sm" style="color: color-mix(in srgb, var(--text) 40%, transparent);">
            {{ $settings['menu_footer_text'] }}
        </p>
    </footer>
    @endif

    <script>
    (function () {
        var searchEl   = document.getElementById('search');
        var noResults  = document.getElementById('no-results');
        var sections   = document.querySelectorAll('#menu-root section[data-section-index]');

        function filter(q) {
            q = q.toLowerCase().trim();
            var totalVisible = 0;

            sections.forEach(function (sec) {
                var cards   = sec.querySelectorAll('.menu-card');
                var visible = 0;

                cards.forEach(function (card) {
                    var match = !q ||
                        (card.dataset.title || '').includes(q) ||
                        (card.dataset.desc  || '').includes(q);
                    card.style.display = match ? '' : 'none';
                    if (match) visible++;
                });

                sec.style.display = visible > 0 ? '' : 'none';
                totalVisible += visible;
            });

            noResults.style.display = (q && totalVisible === 0) ? '' : 'none';
        }

        if (searchEl) {
            searchEl.addEventListener('input', function () { filter(this.value); });
        }
    })();
    </script>

</body>
</html>
