<!DOCTYPE html>
<html lang="{{ seo_lang($settings) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @php
        $brandName = $settings['menu_brand_name'] ?? '';
        $pageTitle = $brandName ?: ($settings['menu_header_text'] ?? 'Tienda');
        $siteName  = $settings['site_name'] ?? '';
        $shopTitle = $shopTitle ?? seo_title($pageTitle, $settings);
    @endphp

    <title>{{ $shopTitle }}</title>

    @include('partials/seo-head', [
        'settings'        => $settings,
        'pageTitle'       => $shopTitle,
        'pageUrl'         => $pageUrl ?? '',
        'pageDescription' => $pageDescription ?? '',
    ])

    {{-- JSON-LD Schema.org (App\Services\StructuredDataService) --}}
    @foreach($jsonLd ?? [] as $__ld)
    <script type="application/ld+json">{!! \App\Services\StructuredDataService::encode($__ld) !!}</script>
    @endforeach

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Colores, superficies y componentes: mismos tokens que la landing (Configuración → Apariencia) --}}
    @include('partials/theme-tokens', ['settings' => $settings])

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        section[data-block]:not(:has(*)) { display: none; }
        /* Deep links /shop#prod-sku (JSON-LD, llms.txt): no quedar debajo de la barra sticky */
        .menu-card[id] { scroll-margin-top: 7rem; }
        .menu-card:target { outline: 2px solid var(--accent); outline-offset: 3px; }

        /* ── Barra de búsqueda + filtros (sticky) ───────────────── */
        .shop-toolbar {
            position: sticky; top: 0; z-index: 30;
            background: color-mix(in srgb, var(--bg-base) 86%, transparent);
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
        }
        .pills-scroll { overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; mask-image: linear-gradient(to right, #000 92%, transparent); }
        .pills-scroll::-webkit-scrollbar { display: none; }
        .cat-pill {
            display: inline-flex; align-items: center; gap: .4rem; white-space: nowrap;
            min-height: 40px; padding: .45rem 1rem; border-radius: var(--radius-pill);
            font-size: .875rem; font-weight: 600; cursor: pointer;
            background: var(--surface); color: var(--text-muted); border: 1px solid var(--border);
            transition: background .2s, color .2s, border-color .2s;
        }
        .cat-pill:hover { color: var(--text); border-color: var(--border-strong); }
        .cat-pill.active { background: var(--accent); border-color: var(--accent); color: var(--accent-contrast); }
        .cat-icon {
            width: 2.25rem; height: 2.25rem; border-radius: var(--radius-sm);
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--accent-soft); color: var(--accent);
        }

        /* ── Card de producto ─────────────────────────────────── */
        .product-card {
            display: flex; flex-direction: column; width: 100%; overflow: hidden;
            opacity: 0; transform: translateY(12px);
        }
        .product-card.visible { opacity: 1; transform: none; }
        .product-card.visible:hover { transform: translateY(-4px); }
        .product-media { position: relative; overflow: hidden; background: var(--surface); }
        .product-media img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s var(--ease); }
        .product-card:hover .product-media img { transform: scale(1.04); }
        .product-ph {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: var(--text-faint);
            background: linear-gradient(135deg, var(--accent-soft), transparent);
        }
        .product-badges { position: absolute; top: .6rem; left: .6rem; right: .6rem; display: flex; flex-wrap: wrap; gap: .3rem; }
        .p-badge {
            font-size: .62rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
            padding: .25rem .55rem; border-radius: var(--radius-pill); box-shadow: 0 1px 4px rgb(0 0 0 / .15);
        }
        .p-badge-premium  { background: #fff4d6; color: #6b4a00; }
        .p-badge-preorder { background: #ffe6d5; color: #8a3a0c; }
        .p-badge-special  { background: #f1e4fb; color: #5f2689; }
        .p-badge-oos      { background: #fde2e2; color: #991b1b; }
        .product-card.is-out-of-stock .product-media img { filter: grayscale(1); opacity: .6; }

        .product-body { display: flex; flex-direction: column; gap: .45rem; flex: 1; padding: .85rem; }
        @media (min-width: 640px) { .product-body { padding: 1.1rem 1.15rem 1.2rem; gap: .55rem; } }
        .product-cat { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subtle); }
        .product-title {
            font-weight: 700; font-size: .95rem; line-height: 1.25; color: var(--text);
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        @media (min-width: 640px) { .product-title { font-size: 1.05rem; } }
        .product-desc {
            font-size: .82rem; line-height: 1.45; color: var(--text-muted);
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .product-price { display: flex; flex-direction: column; gap: .1rem; margin-top: auto; padding-top: .25rem; }
        .product-price p { display: flex; align-items: baseline; flex-wrap: wrap; gap: .35rem; }
        .product-price .amount { font-size: 1.2rem; font-weight: 800; letter-spacing: -.01em; color: var(--text); }
        @media (min-width: 640px) { .product-price .amount { font-size: 1.35rem; } }
        .product-price .label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--text-subtle); }
        .product-price .extra { font-size: .85rem; font-weight: 600; color: var(--text-muted); }
        .product-card.is-out-of-stock .product-price { opacity: .55; }
        .product-actions { display: flex; flex-direction: column; gap: .5rem; padding-top: .35rem; }
        .shop-variant {
            width: 100%; min-height: 40px; padding: .45rem .7rem; font-size: .82rem;
            border-radius: var(--radius-sm); color: var(--text);
            background: var(--surface-solid); border: 1px solid var(--border);
        }
        .shop-add-btn.added { background: #16a34a; color: #fff; }

        /* ── Scroll to top ───────────────────────────────────── */
        #scroll-top {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 50;
            width: 2.75rem; height: 2.75rem; border-radius: 50%; border: none; cursor: pointer;
            display: none; align-items: center; justify-content: center;
            background: var(--accent); color: var(--accent-contrast); box-shadow: var(--shadow-md);
        }
        #scroll-top.visible { display: flex; }

        /* ── Carrito ─────────────────────────────────────────── */
        #cart-fab {
            position: fixed; left: 50%; bottom: 1.25rem; transform: translateX(-50%); z-index: 60;
            display: none; align-items: center; gap: .75rem; padding: .85rem 1.35rem; border-radius: var(--radius-pill);
            background: var(--accent); color: var(--accent-contrast); font-weight: 800; border: none; cursor: pointer;
            box-shadow: 0 10px 30px rgb(0 0 0 / .35);
        }
        #cart-fab.visible { display: inline-flex; }
        #cart-fab .count {
            background: var(--accent-contrast); color: var(--accent); border-radius: var(--radius-pill);
            min-width: 1.6rem; height: 1.6rem; padding: 0 .4rem; font-size: .8rem;
            display: inline-flex; align-items: center; justify-content: center;
        }
        body.has-cart #scroll-top { bottom: 5.5rem; }
        #cart-panel { position: fixed; inset: 0; z-index: 70; display: none; }
        #cart-panel.open { display: block; }
        #cart-panel .backdrop { position: absolute; inset: 0; background: rgb(0 0 0 / .55); }
        #cart-panel .sheet {
            position: absolute; right: 0; bottom: 0; width: 100%; max-width: 28rem; max-height: 92vh; overflow-y: auto;
            background: var(--bg-base); color: var(--text); border: 1px solid var(--border);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0; padding: 1.25rem; box-shadow: var(--shadow-md);
        }
        @media (min-width: 640px) { #cart-panel .sheet { top: 0; max-height: none; border-radius: 0; } }
        .cart-line { display: flex; gap: .75rem; align-items: center; padding: .75rem 0; border-bottom: 1px solid var(--border); }
        .cart-line .err { color: #ef4444; font-size: .75rem; }
        .qty-btn {
            width: 2.25rem; height: 2.25rem; border-radius: var(--radius-sm); cursor: pointer;
            border: 1px solid var(--border); background: var(--surface); color: var(--text);
        }
        .qty-btn:disabled { opacity: .35; cursor: not-allowed; }
        .shop-input {
            width: 100%; min-height: 44px; padding: .7rem .9rem; border-radius: var(--radius-md); font-size: 1rem;
            background: var(--surface); color: var(--text); border: 1px solid var(--border); outline: none;
        }
        .shop-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
        .shop-primary {
            width: 100%; min-height: 48px; display: flex; align-items: center; justify-content: center; gap: .5rem;
            background: #25d366; color: #0b2e17; font-weight: 800; padding: .85rem 1rem;
            border-radius: var(--radius-md); border: none; cursor: pointer;
        }
        .shop-primary:disabled { opacity: .6; cursor: wait; }
        .hp-field { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
    </style>
</head>
<body class="overflow-x-hidden">
    @include('partials/theme-background', ['settings' => $settings])

    <a href="#menu-root" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[80] ds-btn ds-btn-accent">Ir a los productos</a>

    {{-- Bloques en el orden definido por el UX Builder (admin/builder/shop) --}}
    <main class="relative z-10 flex flex-col gap-8 sm:gap-12 pb-28">
        @include('partials/render-blocks', [
            'blocks' => $blocks,
            'page'   => 'shop',
            'wrap'   => [
                'shop_header' => 'w-full',
                'products'    => 'w-full',
                'payments'    => 'w-full',
                'banner'      => 'w-full max-w-7xl mx-auto px-4 sm:px-6',
                'carousel'    => 'w-full max-w-7xl mx-auto px-4 sm:px-6',
                '*'           => 'w-full max-w-3xl mx-auto px-4 sm:px-6 flex flex-col items-center',
            ],
        ])
    </main>

    @if($shopOpen)
    {{-- ╔══ SHOP: CARRITO ═════════════════════════════════════════╗ --}}
    <button id="cart-fab" type="button" aria-haspopup="dialog" aria-controls="cart-panel">
        <i class="fa-solid fa-basket-shopping" aria-hidden="true"></i>
        <span>Ver pedido</span>
        <span class="count" id="cart-count">0</span>
        <span id="cart-fab-total"></span>
    </button>

    <div id="cart-panel" role="dialog" aria-modal="true" aria-labelledby="cart-title">
        <div class="backdrop" data-close></div>
        <div class="sheet">
            <div class="flex items-center justify-between mb-2">
                <h2 id="cart-title" class="text-2xl font-extrabold tracking-tight">Tu pedido</h2>
                <button type="button" data-close class="qty-btn" aria-label="Cerrar"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>

            <div id="cart-view">
                <div id="cart-lines" aria-live="polite"></div>
                <p id="cart-empty" class="py-8 text-center" style="color:var(--text-muted)">Todavía no agregaste productos.</p>
                <div class="flex justify-between items-center py-4 text-lg font-bold">
                    <span>Total</span><span id="cart-total" style="color:var(--accent)">$0</span>
                </div>

                <form id="cart-form" novalidate class="space-y-3">
                    <div>
                        <label for="cart-name" class="block text-sm mb-1" style="color:var(--text-muted)">Tu nombre</label>
                        <input id="cart-name" name="name" class="shop-input" autocomplete="name" maxlength="100" required>
                        <p class="err text-xs mt-1" data-err="name" style="color:#e07070"></p>
                    </div>
                    <div>
                        <label for="cart-phone" class="block text-sm mb-1" style="color:var(--text-muted)">Tu teléfono (WhatsApp)</label>
                        <input id="cart-phone" name="phone" type="tel" class="shop-input" autocomplete="tel" maxlength="30" placeholder="Ej: +54 9 11 5555-1234" required>
                        <p class="text-xs mt-1" style="color:var(--text-muted)">Con código de país, así te podemos responder por WhatsApp.</p>
                        <p class="err text-xs mt-1" data-err="phone" style="color:#e07070"></p>
                    </div>
                    <div class="hp-field" aria-hidden="true">
                        <label>No completar <input name="website" tabindex="-1" autocomplete="off"></label>
                    </div>
                    <p class="err text-sm" data-err="general" style="color:#e07070" role="alert"></p>
                    <button type="submit" class="shop-primary" id="cart-submit">
                        <i class="fa-brands fa-whatsapp text-xl" aria-hidden="true"></i> Enviar pedido por WhatsApp
                    </button>
                    <p class="text-xs text-center" style="color:var(--text-muted)">Se abre WhatsApp con tu pedido listo para enviar. Te confirmamos por ahí.</p>
                </form>
            </div>

            <div id="cart-done" class="hidden text-center py-8">
                <i class="fa-solid fa-circle-check text-5xl mb-4" style="color:#25d366" aria-hidden="true"></i>
                <h3 class="text-2xl font-extrabold tracking-tight mb-1">¡Pedido <span id="done-code"></span> listo!</h3>
                <p class="mb-6" style="color:var(--text-muted)">Si WhatsApp no se abrió, tocá el botón para enviarlo.</p>
                <a id="done-link" href="#" target="_blank" rel="noopener" class="shop-primary">
                    <i class="fa-brands fa-whatsapp text-xl" aria-hidden="true"></i> Abrir WhatsApp
                </a>
                <button type="button" data-close class="mt-4 text-sm underline" style="color:var(--text-muted)">Seguir mirando</button>
            </div>
        </div>
    </div>
    {{-- ╚══════════════════════════════════════════════════════════╝ --}}
    @endif

    <!-- Scroll to top -->
    <button id="scroll-top" aria-label="Volver al inicio de la página">
        <i class="fa-solid fa-chevron-up text-sm" aria-hidden="true"></i>
    </button>


    <script>
    (function () {
        'use strict';

        /* ── Entrada animada de las cards ── */
        var cards = Array.prototype.slice.call(document.querySelectorAll('.menu-card'));
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
                });
            }, { threshold: 0.05 });
            cards.forEach(function (c) { io.observe(c); });
        } else {
            cards.forEach(function (c) { c.classList.add('visible'); });
        }

        /* ── Filtro por categoría + búsqueda (funciona agrupado o en una sola grilla) ── */
        var pills     = document.querySelectorAll('.cat-pill');
        var sections  = document.querySelectorAll('.cat-section');
        var search    = document.getElementById('search');
        var noResults = document.getElementById('no-results');
        var current   = 'all';

        function apply() {
            var q = search ? search.value.toLowerCase().trim() : '';
            var total = 0;
            cards.forEach(function (card) {
                var inCat = current === 'all' || card.dataset.cat === current;
                var match = !q || (card.dataset.title || '').indexOf(q) > -1 || (card.dataset.desc || '').indexOf(q) > -1;
                var show = inCat && match;
                card.parentElement.hidden = !show; // el <li> contenedor
                if (show) { card.classList.add('visible'); total++; }
            });
            sections.forEach(function (sec) {
                sec.hidden = !sec.querySelector('li:not([hidden])');
            });
            if (noResults) { noResults.hidden = !(cards.length && total === 0); }
        }

        pills.forEach(function (p) {
            p.addEventListener('click', function () {
                current = p.dataset.cat;
                pills.forEach(function (o) {
                    var on = o === p;
                    o.classList.toggle('active', on);
                    o.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                apply();
            });
        });
        if (search) { search.addEventListener('input', apply); }

        /* ── Copiar alias ── */
        document.querySelectorAll('[data-copy-alias]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var alias = btn.dataset.copyAlias;
                var msg = document.getElementById('copy-msg');
                function show() {
                    if (!msg) return;
                    msg.textContent = '¡Alias copiado!';
                    msg.style.opacity = '1';
                    setTimeout(function () { msg.style.opacity = '0'; }, 2000);
                }
                if (navigator.clipboard) { navigator.clipboard.writeText(alias).then(show); return; }
                var el = document.createElement('textarea');
                el.value = alias; document.body.appendChild(el); el.select();
                document.execCommand('copy'); document.body.removeChild(el); show();
            });
        });

        /* ── Volver arriba ── */
        var scrollBtn = document.getElementById('scroll-top');
        window.addEventListener('scroll', function () {
            scrollBtn.classList.toggle('visible', window.scrollY > 600);
        }, { passive: true });
        scrollBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    })();
    </script>

    @if($shopOpen)
    <script>
    (function () {
        'use strict';
        var ORDER_URL = @json(url('shop/orders'));
        var WA_MODE = @json($waMode);
        var KEY = 'shop-cart:' + location.host;
        var fab = document.getElementById('cart-fab'), panel = document.getElementById('cart-panel');
        var linesEl = document.getElementById('cart-lines'), form = document.getElementById('cart-form');
        var cart = [];
        try { cart = JSON.parse(localStorage.getItem(KEY) || '[]') || []; } catch (e) { cart = []; }

        function money(n) {
            var hasDec = Math.abs(n - Math.round(n)) >= 0.005;
            return '$' + n.toLocaleString('es-AR', { minimumFractionDigits: hasDec ? 2 : 0, maximumFractionDigits: hasDec ? 2 : 0 });
        }
        function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
        function save() { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} }
        function units() { return cart.reduce(function (a, l) { return a + l.qty; }, 0); }
        function total() { return cart.reduce(function (a, l) { return a + l.qty * l.price; }, 0); }
        function usedStock(id) { return cart.reduce(function (a, l) { return a + (l.id === id ? l.qty : 0); }, 0); }
        function lineKey(l) { return l.id + ':' + l.variant; }
        function isMobile() { return /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent) || (navigator.maxTouchPoints > 1 && /Mac/.test(navigator.platform)); }
        function waUrl(res) { return (WA_MODE === 'app' || (WA_MODE === 'auto' && isMobile())) ? res.wa_app : res.wa_web; }

        function render(errors) {
            errors = errors || {};
            var n = units();
            document.getElementById('cart-count').textContent = n;
            document.getElementById('cart-fab-total').textContent = n ? money(total()) : '';
            fab.classList.toggle('visible', n > 0);
            document.body.classList.toggle('has-cart', n > 0);
            document.getElementById('cart-total').textContent = money(total());
            document.getElementById('cart-empty').style.display = n ? 'none' : 'block';
            form.style.display = n ? 'block' : 'none';
            linesEl.innerHTML = cart.map(function (l, i) {
                var atMax = l.max !== null && usedStock(l.id) >= l.max;
                var err = errors[lineKey(l)];
                return '<div class="cart-line">' +
                    '<div class="flex-1 min-w-0"><p class="font-bold">' + esc(l.title) + (l.label ? ' <span style="color:var(--text-muted)">(' + esc(l.label) + ')</span>' : '') + '</p>' +
                    '<p class="text-sm" style="color:var(--text-muted)">' + money(l.price) + ' c/u</p>' +
                    (err ? '<p class="err">' + esc(err) + '</p>' : '') + '</div>' +
                    '<div class="flex items-center gap-2">' +
                    '<button type="button" class="qty-btn" data-dec="' + i + '" aria-label="Quitar uno de ' + esc(l.title) + '">−</button>' +
                    '<span class="w-6 text-center font-bold" aria-label="Cantidad">' + l.qty + '</span>' +
                    '<button type="button" class="qty-btn" data-inc="' + i + '" ' + (atMax ? 'disabled' : '') + ' aria-label="Agregar uno de ' + esc(l.title) + '">+</button>' +
                    '</div><span class="w-20 text-right font-bold">' + money(l.qty * l.price) + '</span></div>';
            }).join('');
        }

        function add(box) {
            var id = parseInt(box.dataset.id, 10);
            var variants = JSON.parse(box.dataset.variants);
            var sel = box.querySelector('.shop-variant');
            var vNum = sel ? parseInt(sel.value, 10) : 1;
            var v = variants.filter(function (x) { return x.v === vNum; })[0] || variants[0];
            var max = box.dataset.stock === '' ? null : parseInt(box.dataset.stock, 10);
            if (max !== null && usedStock(id) >= max) { return false; }
            var line = cart.filter(function (l) { return l.id === id && l.variant === v.v; })[0];
            if (line) { line.qty++; } else {
                cart.push({ id: id, variant: v.v, title: box.dataset.title, label: v.label, price: v.price, qty: 1, max: max });
            }
            save(); render(); return true;
        }

        document.querySelectorAll('.shop-add').forEach(function (box) {
            var btn = box.querySelector('.shop-add-btn');
            var original = btn.innerHTML;
            btn.addEventListener('click', function () {
                if (!add(box)) {
                    btn.textContent = 'No hay más stock';
                    setTimeout(function () { btn.innerHTML = original; }, 1500);
                    return;
                }
                btn.classList.add('added');
                setTimeout(function () { btn.classList.remove('added'); }, 600);
            });
        });

        linesEl.addEventListener('click', function (e) {
            var inc = e.target.closest('[data-inc]'), dec = e.target.closest('[data-dec]');
            if (inc) { var l = cart[+inc.dataset.inc]; if (l.max === null || usedStock(l.id) < l.max) { l.qty++; } }
            if (dec) { var i = +dec.dataset.dec; cart[i].qty--; if (cart[i].qty <= 0) { cart.splice(i, 1); } }
            if (inc || dec) { save(); render(); }
        });

        function open() { panel.classList.add('open'); document.getElementById('cart-view').classList.remove('hidden'); document.getElementById('cart-done').classList.add('hidden'); }
        function close() { panel.classList.remove('open'); }
        fab.addEventListener('click', open);
        panel.addEventListener('click', function (e) { if (e.target.closest('[data-close]')) { close(); } });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });

        function showErrors(errs) {
            form.querySelectorAll('[data-err]').forEach(function (p) { p.textContent = errs[p.dataset.err] || ''; });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = document.getElementById('cart-submit');
            var payload = {
                name: form.elements['name'].value, phone: form.elements['phone'].value, website: form.elements['website'].value,
                items: cart.map(function (l) { return { id: l.id, variant: l.variant, qty: l.qty }; })
            };
            // La ventana se abre YA (gesto del usuario) para que el navegador no la bloquee
            var win = window.open('', '_blank');
            btn.disabled = true; showErrors({});
            fetch(ORDER_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
                .then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
                .then(function (res) {
                    btn.disabled = false;
                    if (res.status === 201) {
                        var url = waUrl(res.body);
                        if (win) { win.location.href = url; }
                        document.getElementById('done-code').textContent = res.body.code;
                        document.getElementById('done-link').href = url;
                        document.getElementById('cart-view').classList.add('hidden');
                        document.getElementById('cart-done').classList.remove('hidden');
                        cart = []; save(); render();
                        return;
                    }
                    if (win) { win.close(); }
                    var errs = res.body.errors || {};
                    var itemErrors = Object.keys(errs).some(function (k) { return k.indexOf(':') > -1; });
                    showErrors({ name: errs.name, phone: errs.phone, general: errs.cart || res.body.error || (itemErrors ? 'Revisá los productos marcados.' : '') });
                    render(errs);
                })
                .catch(function () {
                    btn.disabled = false;
                    if (win) { win.close(); }
                    showErrors({ general: 'No se pudo enviar el pedido. Revisá tu conexión e intentá de nuevo.' });
                });
        });

        // El carrito guardado puede tener precios/stock viejos: se actualiza con lo que muestra la página
        var onPage = {};
        document.querySelectorAll('.shop-add').forEach(function (box) {
            onPage[box.dataset.id] = { box: box, variants: JSON.parse(box.dataset.variants), max: box.dataset.stock === '' ? null : parseInt(box.dataset.stock, 10) };
        });
        cart = cart.filter(function (l) {
            var p = onPage[l.id];
            var v = p && p.variants.filter(function (x) { return x.v === l.variant; })[0];
            if (!v || p.max === 0) { return false; }
            l.price = v.price; l.label = v.label; l.max = p.max; l.title = p.box.dataset.title;
            return true;
        });
        save();
        render();
    })();
    </script>
    @endif

    @include('partials/demo-badge')
</body>
</html>
