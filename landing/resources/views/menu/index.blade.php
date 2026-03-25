<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @php
        $brandName = $settings['menu_brand_name'] ?? '';
        $pageTitle = $brandName ?: ($settings['menu_header_text'] ?? 'Nuestra Carta');
        $siteName  = $settings['site_name'] ?? '';
        $accent    = $settings['menu_accent_color']  ?? '#fec771';
        $bgBase    = $settings['menu_bg_color']      ?? '#1a1208';
        $textColor = $settings['menu_text_color']    ?? '#f5efe0';
        $bgImage   = $settings['menu_bg_image_url']  ?? '';
        $overlay   = ($settings['menu_bg_overlay']   ?? '0') === '1';
        $opacity   = (float)($settings['menu_bg_overlay_opacity'] ?? 0.5);
        $showPay   = ($settings['menu_show_payment'] ?? '0') === '1';
        $payAlias  = $settings['menu_payment_alias'] ?? '';
        $payMethods = array_filter(explode(',', $settings['menu_payment_methods'] ?? 'cash,transfer'));
        $subtitle  = $settings['menu_brand_subtitle'] ?? '';
        $footerTxt = $settings['menu_footer_text']   ?? '';

        // Derivados de color para usar en CSS
        $accentHex  = ltrim($accent, '#');
        $ar = hexdec(substr($accentHex,0,2));
        $ag = hexdec(substr($accentHex,2,2));
        $ab = hexdec(substr($accentHex,4,2));

        $bgHex = ltrim($bgBase, '#');
        $bgr   = hexdec(substr($bgHex,0,2));
        $bgg   = hexdec(substr($bgHex,2,2));
        $bgb   = hexdec(substr($bgHex,4,2));
    @endphp

    <title>{{ $pageTitle }}{{ $siteName ? ' | ' . $siteName : '' }}</title>

    @include('partials/seo-head', [
        'settings'  => $settings,
        'pageTitle' => $pageTitle . ($siteName ? ' | ' . $siteName : ''),
        'pageUrl'   => $pageUrl ?? '',
    ])

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* ── Design Tokens (generados desde DB) ─────────────────── */
        :root {
            --accent:        {{ $accent }};
            --accent-r:      {{ $ar }};
            --accent-g:      {{ $ag }};
            --accent-b:      {{ $ab }};
            --bg-base:       {{ $bgBase }};
            --bg-r:          {{ $bgr }};
            --bg-g:          {{ $bgg }};
            --bg-b:          {{ $bgb }};
            --text:          {{ $textColor }};
            --border:        rgba({{ $ar }},{{ $ag }},{{ $ab }},0.18);
            --border-hover:  rgba({{ $ar }},{{ $ag }},{{ $ab }},0.38);
            --bg-card:       rgba(255,255,255,0.04);
            --bg-card-hover: rgba(255,255,255,0.07);
            --text-muted:    color-mix(in srgb, var(--text) 50%, transparent);
        }

        /* ── Base ────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-base);
            color: var(--text);
            margin: 0;
            min-height: 100vh;
        }

        /* Fondo con textura radial cálida */
        .body-glow {
            position: fixed;
            inset: 0;
            z-index: -2;
            background-color: var(--bg-base);
            background-image:
                radial-gradient(ellipse at 20% 0%, rgba(var(--accent-r),var(--accent-g),var(--accent-b),0.07) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 100%, rgba(var(--accent-r),var(--accent-g),var(--accent-b),0.04) 0%, transparent 60%);
        }

        /* Imagen de fondo */
        @if(!empty($bgImage))
        .body-bg-image {
            position: fixed;
            inset: 0;
            z-index: -1;
            background-image: url('{{ $bgImage }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            @if($overlay)
            opacity: {{ 1 - $opacity }};
            @else
            opacity: 1;
            @endif
        }
        @if($overlay)
        .body-overlay {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: rgba({{ $bgr }},{{ $bgg }},{{ $bgb }},{{ $opacity }});
            pointer-events: none;
        }
        @endif
        @endif

        /* ── Typography ──────────────────────────────────────────── */
        .font-display { font-family: 'Playfair Display', serif; }

        /* ── Sticky nav ──────────────────────────────────────────── */
        .sticky-nav {
            position: sticky;
            top: 0;
            z-index: 20;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            background: rgba(var(--bg-r),var(--bg-g),var(--bg-b),0.88);
            border-bottom: 1px solid var(--border);
        }

        /* ── Buscador ────────────────────────────────────────────── */
        #search {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(8px);
            border: 1px solid var(--border);
            color: var(--text);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        #search::placeholder { color: var(--text-muted); }
        #search:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(var(--accent-r),var(--accent-g),var(--accent-b),0.12);
        }

        /* ── Category pills ──────────────────────────────────────── */
        .cat-pill {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .cat-pill:hover { border-color: var(--accent); color: var(--text); }
        .cat-pill.active {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--bg-base);
            font-weight: 700;
        }
        .pills-scroll { overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; }
        .pills-scroll::-webkit-scrollbar { display: none; }

        /* ── Menu card ───────────────────────────────────────────── */
        .menu-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            overflow: hidden;
            transition: transform 0.25s ease, border-color 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
            /* Entrada animada */
            opacity: 0;
            transform: translateY(16px);
        }
        .menu-card.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .menu-card:hover {
            transform: translateY(-4px);
            border-color: var(--border-hover);
            background: var(--bg-card-hover);
            box-shadow: 0 8px 32px rgba(var(--accent-r),var(--accent-g),var(--accent-b),0.08);
        }

        /* ── Price badge ─────────────────────────────────────────── */
        .price-badge {
            background: rgba(var(--accent-r),var(--accent-g),var(--accent-b),0.12);
            color: var(--accent);
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0.25rem 0.65rem;
            border-radius: 0.6rem;
            white-space: nowrap;
        }
        .price-size {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ── Badges ──────────────────────────────────────────────── */
        .badge-premium {
            background: rgba(212,168,87,0.15);
            color: #d4a857;
            border: 1px solid rgba(212,168,87,0.3);
            font-size: 0.62rem; font-weight: 700; letter-spacing: 0.06em;
            text-transform: uppercase; padding: 0.12rem 0.45rem; border-radius: 99px;
        }
        .badge-preorder {
            background: rgba(200,121,65,0.15);
            color: #c87941;
            border: 1px solid rgba(200,121,65,0.3);
            font-size: 0.62rem; font-weight: 700; letter-spacing: 0.06em;
            text-transform: uppercase; padding: 0.12rem 0.45rem; border-radius: 99px;
        }
        .badge-special {
            background: rgba(168,100,220,0.15);
            color: #b46fd8;
            border: 1px solid rgba(168,100,220,0.3);
            font-size: 0.62rem; font-weight: 700; letter-spacing: 0.06em;
            text-transform: uppercase; padding: 0.12rem 0.45rem; border-radius: 99px;
        }
        .badge-gift {
            background: rgba(100,200,100,0.1);
            color: #7ec87e;
            border: 1px solid rgba(100,200,100,0.25);
            font-size: 0.62rem; font-weight: 600;
            padding: 0.12rem 0.45rem; border-radius: 99px;
        }

        /* ── Icon circle ─────────────────────────────────────────── */
        .icon-circle {
            width: 2.8rem; height: 2.8rem; border-radius: 50%;
            background: rgba(var(--accent-r),var(--accent-g),var(--accent-b),0.1);
            border: 1px solid rgba(var(--accent-r),var(--accent-g),var(--accent-b),0.22);
            display: flex; align-items: center; justify-content: center;
            color: var(--accent); flex-shrink: 0;
        }

        /* ── Section divider ─────────────────────────────────────── */
        .section-title-line::before,
        .section-title-line::after {
            content: ''; flex: 1; height: 1px;
            background: linear-gradient(to right, transparent, var(--border), transparent);
        }

        /* ── Note bajo sección ───────────────────────────────────── */
        .note-info     { color: #60a5fa; }
        .note-preorder { color: #c87941; }
        .note-time     { color: #f59e0b; }

        /* ── Flavor chips ────────────────────────────────────────── */
        .flavor-chip {
            display: inline-block;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 99px;
            padding: 0.1rem 0.5rem;
            font-size: 0.68rem;
            color: var(--text-muted);
            margin: 0.15rem 0.1rem 0;
        }

        /* ── Payment strip ───────────────────────────────────────── */
        .payment-strip {
            background: rgba(var(--accent-r),var(--accent-g),var(--accent-b),0.05);
            border-top: 1px solid var(--border);
        }

        /* ── No results ──────────────────────────────────────────── */
        #no-results { display: none; }

        /* ── Scroll to top ───────────────────────────────────────── */
        #scroll-top {
            position: fixed; bottom: 1.5rem; right: 1.5rem;
            width: 2.5rem; height: 2.5rem; border-radius: 50%;
            background: var(--accent); color: var(--bg-base);
            border: none; cursor: pointer;
            display: none; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(var(--accent-r),var(--accent-g),var(--accent-b),0.3);
            transition: transform 0.2s;
            z-index: 50;
        }
        #scroll-top.visible { display: flex; }
        #scroll-top:hover   { transform: scale(1.1); }
    </style>
</head>
<body>

    <!-- Fondos -->
    <div class="body-glow" aria-hidden="true"></div>
    @if(!empty($bgImage))
        <div class="body-bg-image" aria-hidden="true"></div>
        @if($overlay)
            <div class="body-overlay" aria-hidden="true"></div>
        @endif
    @endif


    {{-- ╔══ HEADER ════════════════════════════════════════════════╗ --}}
    <header class="relative z-10 text-center pt-14 pb-10 px-4" role="banner">

        <a href="{{ url() }}"
           class="inline-flex items-center gap-2 text-sm mb-6 transition-opacity hover:opacity-80"
           style="color: var(--text-muted);"
           aria-label="Volver al inicio">
            <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i> Volver
        </a>

        @if($brandName)
            <!-- Ícono central -->
            <div class="flex justify-center mb-4">
                <div class="icon-circle" style="width:4rem;height:4rem;" aria-hidden="true">
                    <i class="fa-solid fa-bread-slice text-2xl"></i>
                </div>
            </div>
            <h1 class="font-display text-4xl sm:text-5xl font-black tracking-tight mb-1"
                style="color: var(--accent);">
                {!! nl2br(e($brandName)) !!}
            </h1>
            @if($subtitle)
                <p class="text-sm font-medium tracking-widest uppercase mb-3"
                   style="color: var(--text-muted);">
                    {{ $subtitle }}
                </p>
            @endif
        @else
            <h1 class="font-display text-4xl sm:text-5xl font-black tracking-tight mb-3"
                style="color: var(--text);">
                {{ $settings['menu_header_text'] ?? 'Nuestra Carta' }}
            </h1>
        @endif

        <div class="flex items-center justify-center gap-3 mt-2" aria-hidden="true">
            <span style="color:var(--border);">— ✦ —</span>
        </div>
    </header>
    {{-- ╚══════════════════════════════════════════════════════════╝ --}}


    {{-- ╔══ STICKY NAV ════════════════════════════════════════════╗ --}}
    <div class="sticky-nav py-3 px-4">
        <div class="max-w-5xl mx-auto space-y-2.5">

            <!-- Buscador -->
            <div class="relative" role="search">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"
                      style="color: var(--text-muted);" aria-hidden="true">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </span>
                <input id="search" type="search"
                       placeholder="Buscar en la carta..."
                       aria-label="Buscar productos en el menú"
                       autocomplete="off"
                       class="w-full rounded-2xl pl-11 pr-5 py-2.5 text-base">
            </div>

            <!-- Filtros por sección -->
            @if($sections->count() > 1)
            <nav aria-label="Filtrar por sección" class="pills-scroll">
                <div class="flex gap-2 pb-0.5" role="list">
                    <button class="cat-pill active rounded-full px-4 py-1.5 text-sm"
                            data-cat="all" role="listitem"
                            aria-pressed="true">
                        <i class="fa-solid fa-border-all mr-1.5 text-xs" aria-hidden="true"></i>Todos
                    </button>
                    @if($unsectioned->count())
                    <button class="cat-pill rounded-full px-4 py-1.5 text-sm"
                            data-cat="sec-0" role="listitem" aria-pressed="false">
                        <i class="fa-solid fa-circle-dot mr-1.5 text-xs" aria-hidden="true"></i>Sin sección
                    </button>
                    @endif
                    @foreach($sections as $section)
                    @if($section->activeItems->count())
                    <button class="cat-pill rounded-full px-4 py-1.5 text-sm"
                            data-cat="sec-{{ $section->id }}" role="listitem" aria-pressed="false">
                        @if($section->icon)
                            <i class="{{ $section->icon }} mr-1.5 text-xs" aria-hidden="true"></i>
                        @endif
                        {{ $section->name }}
                    </button>
                    @endif
                    @endforeach
                </div>
            </nav>
            @endif

        </div>
    </div>
    {{-- ╚══════════════════════════════════════════════════════════╝ --}}


    {{-- ╔══ CONTENIDO ═════════════════════════════════════════════╗ --}}
    <main id="menu-root" class="relative z-10 max-w-5xl mx-auto px-4 pt-10 pb-24 space-y-16"
          aria-label="Carta de productos">

        {{-- Items sin sección --}}
        @if($unsectioned->count())
        <section class="cat-section" data-cat="sec-0" aria-label="Productos sin categoría">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" role="list">
                @foreach($unsectioned as $item)
                    @include('menu/_item_card', ['item' => $item])
                @endforeach
            </div>
        </section>
        @endif

        {{-- Secciones --}}
        @foreach($sections as $section)
            @if($section->activeItems->count())
            <section class="cat-section" data-cat="sec-{{ $section->id }}"
                     aria-labelledby="title-sec-{{ $section->id }}">

                {{-- Título de sección --}}
                <div class="flex items-center gap-3 mb-2 section-title-line">
                    <h2 id="title-sec-{{ $section->id }}"
                        class="font-display text-2xl sm:text-3xl font-bold flex items-center gap-3 whitespace-nowrap"
                        style="color: var(--text);">
                        @if($section->icon)
                            <span class="icon-circle" aria-hidden="true">
                                <i class="{{ $section->icon }} text-sm"></i>
                            </span>
                        @endif
                        {{ $section->name }}
                    </h2>
                </div>

                {{-- Aviso opcional --}}
                @if($section->note && $section->note_type !== 'none')
                <div class="flex items-center gap-2 mb-7 ml-0.5"
                     role="note"
                     aria-label="{{ $section->note }}">
                    @php
                        $noteIcons = [
                            'info'     => 'fa-circle-info note-info',
                            'preorder' => 'fa-circle-exclamation note-preorder',
                            'time'     => 'fa-clock note-time',
                        ];
                        $noteIcon = $noteIcons[$section->note_type] ?? 'fa-circle-info note-info';
                        $noteCls  = 'note-' . $section->note_type;
                    @endphp
                    <i class="fa-solid {{ $noteIcon }} text-sm" aria-hidden="true"></i>
                    <p class="text-xs {{ $noteCls }}">{!! e($section->note) !!}</p>
                </div>
                @elseif($section->note)
                    <p class="text-xs mb-7 ml-0.5" style="color: var(--text-muted);">{{ $section->note }}</p>
                @else
                    <div class="mb-7"></div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" role="list"
                     aria-label="Productos de {{ $section->name }}">
                    @foreach($section->activeItems as $item)
                        @include('menu/_item_card', ['item' => $item])
                    @endforeach
                </div>

            </section>
            @endif
        @endforeach

        {{-- Menú vacío --}}
        @if($unsectioned->count() === 0 && $sections->filter(fn($s) => $s->activeItems->count() > 0)->count() === 0)
        <div class="text-center py-24" style="color: var(--text-muted);" aria-live="polite">
            <i class="fa-solid fa-utensils text-5xl mb-4 block opacity-20" aria-hidden="true"></i>
            <p class="text-lg opacity-40">La carta está vacía por el momento.</p>
        </div>
        @endif

        <!-- Sin resultados de búsqueda -->
        <div id="no-results" class="text-center py-20" aria-live="polite" aria-atomic="true">
            <i class="fa-solid fa-magnifying-glass text-5xl mb-5 block" style="color: var(--border);" aria-hidden="true"></i>
            <p class="text-lg" style="color: var(--text-muted);">No se encontraron resultados.</p>
            <p class="text-sm mt-2" style="color: var(--text-muted);">Probá con otro término de búsqueda.</p>
        </div>

    </main>
    {{-- ╚══════════════════════════════════════════════════════════╝ --}}


    {{-- ╔══ FOOTER PAGO ════════════════════════════════════════════╗ --}}
    @if($showPay || $footerTxt)
    <footer class="relative z-10 payment-strip px-4 py-10 text-center" role="contentinfo">
        <div class="max-w-2xl mx-auto">

            @if($showPay)
            <h3 class="font-display text-xl font-bold mb-5" style="color: var(--text);">
                <i class="fa-solid fa-credit-card mr-2" style="color: var(--accent);" aria-hidden="true"></i>
                Medios de Pago
            </h3>

            <div class="flex justify-center gap-4 flex-wrap mb-5">
                @php
                    $payLabels = [
                        'cash'     => ['Efectivo',       'fa-money-bill-wave'],
                        'transfer' => ['Transferencia',  'fa-building-columns'],
                        'qr'       => ['QR / Mercado Pago','fa-qrcode'],
                        'card'     => ['Tarjeta',        'fa-credit-card'],
                    ];
                @endphp
                @foreach($payMethods as $method)
                    @if(isset($payLabels[$method]))
                    <div class="flex items-center gap-2 px-5 py-3 rounded-2xl"
                         style="background: rgba(255,255,255,0.05); border: 1px solid var(--border);"
                         role="img" aria-label="{{ $payLabels[$method][0] }} aceptado">
                        <i class="fa-solid {{ $payLabels[$method][1] }}" style="color: var(--accent);" aria-hidden="true"></i>
                        <span class="font-semibold text-sm" style="color: var(--text);">{{ $payLabels[$method][0] }}</span>
                    </div>
                    @endif
                @endforeach
            </div>

            @if($payAlias)
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                 style="background: rgba(var(--accent-r),var(--accent-g),var(--accent-b),0.08); border: 1px solid var(--border);"
                 aria-label="Alias para transferencia: {{ $payAlias }}">
                <span class="text-xs" style="color: var(--text-muted);">Alias:</span>
                <span class="font-bold tracking-wide text-sm" style="color: var(--accent);">{{ $payAlias }}</span>
                <button onclick="copyAlias('{{ e($payAlias) }}')" title="Copiar alias"
                        aria-label="Copiar alias al portapapeles"
                        class="ml-1 opacity-60 hover:opacity-100 transition-opacity cursor-pointer"
                        style="background:none; border:none; color: var(--accent); padding:0;">
                    <i class="fa-solid fa-copy text-xs" aria-hidden="true"></i>
                </button>
            </div>
            <p id="copy-msg" class="text-xs mt-2 transition-opacity opacity-0" style="color: var(--accent);"
               aria-live="polite">¡Alias copiado!</p>
            @endif
            @endif

            @if($footerTxt)
            <p class="text-sm mt-6" style="color: var(--text-muted);">{{ $footerTxt }}</p>
            @endif

        </div>
    </footer>
    @endif
    {{-- ╚══════════════════════════════════════════════════════════╝ --}}


    <!-- Scroll to top -->
    <button id="scroll-top" aria-label="Volver al inicio de la página">
        <i class="fa-solid fa-chevron-up text-sm" aria-hidden="true"></i>
    </button>


    <script>
    (function () {
        'use strict';

        /* ── Entrance animation ── */
        var cards = document.querySelectorAll('.menu-card');
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        io.unobserve(e.target);
                    }
                });
            }, { threshold: 0.08 });
            cards.forEach(function (c) { io.observe(c); });
        } else {
            cards.forEach(function (c) { c.classList.add('visible'); });
        }

        /* ── Category filter ── */
        var pills    = document.querySelectorAll('.cat-pill');
        var sections = document.querySelectorAll('.cat-section');

        function setCategory(cat) {
            pills.forEach(function (p) {
                var active = (p.dataset.cat === cat);
                p.classList.toggle('active', active);
                p.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            sections.forEach(function (sec) {
                var show = (cat === 'all' || sec.dataset.cat === cat);
                sec.style.display = show ? '' : 'none';
            });
            filterCards(document.getElementById('search').value);
        }

        pills.forEach(function (p) {
            p.addEventListener('click', function () { setCategory(this.dataset.cat); });
        });

        /* ── Search ── */
        var noResults = document.getElementById('no-results');

        function filterCards(q) {
            q = q.toLowerCase().trim();
            var total = 0;
            sections.forEach(function (sec) {
                if (sec.style.display === 'none') return;
                var items   = sec.querySelectorAll('.menu-card');
                var visible = 0;
                items.forEach(function (card) {
                    var match = !q
                        || (card.dataset.title || '').includes(q)
                        || (card.dataset.desc  || '').includes(q);
                    card.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                total += visible;
            });
            noResults.style.display = (q && total === 0) ? '' : 'none';
        }

        document.getElementById('search').addEventListener('input', function () {
            filterCards(this.value);
        });

        /* ── Copy alias ── */
        window.copyAlias = function (alias) {
            function show() {
                var msg = document.getElementById('copy-msg');
                if (msg) { msg.style.opacity = '1'; setTimeout(function () { msg.style.opacity = '0'; }, 2000); }
            }
            if (navigator.clipboard) {
                navigator.clipboard.writeText(alias).then(show);
            } else {
                var el = document.createElement('textarea');
                el.value = alias; document.body.appendChild(el); el.select();
                document.execCommand('copy'); document.body.removeChild(el); show();
            }
        };

        /* ── Scroll to top ── */
        var scrollBtn = document.getElementById('scroll-top');
        window.addEventListener('scroll', function () {
            scrollBtn.classList.toggle('visible', window.scrollY > 400);
        }, { passive: true });
        scrollBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

    })();
    </script>

</body>
</html>
