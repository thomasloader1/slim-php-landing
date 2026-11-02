{{--
    Carrusel accesible (landing y tienda), sin librerías: scroll-snap + JS mínimo.
    - Botones anterior/siguiente, puntos con aria-current, flechas del teclado.
    - Autoplay opcional: se pausa con hover/foco, tiene botón de pausa y se
      desactiva si el usuario pidió reducir movimiento.
    - Textos ocultables: con show_text=false el título queda solo para lectores de pantalla.
--}}
@php
    $slides   = $data['slides'] ?? [];
    $n        = count($slides);
    $showText = (bool) ($data['show_text'] ?? true);
    $autoplay = (bool) ($data['autoplay'] ?? true) && $n > 1;
    $interval = max(3, min(10, (int) ($data['interval'] ?? 5)));
    $height   = $data['height'] ?? 'md';
    $shade    = ((int) ($data['overlay'] ?? 35)) / 100;
    $cid      = 'car-' . preg_replace('/[^a-z0-9_]/', '', (string) ($blockId ?? uniqid()));
@endphp
@if($n > 0)
<div class="ds-carousel w-full {{ $n === 1 ? 'single' : '' }}" id="{{ $cid }}"
     role="region" aria-roledescription="carrusel" aria-label="Destacados"
     data-carousel data-interval="{{ $autoplay ? $interval * 1000 : 0 }}">
    <div class="ds-carousel-track" id="{{ $cid }}-track" aria-live="{{ $autoplay ? 'off' : 'polite' }}">
        @foreach($slides as $i => $s)
            @php
                $hasText  = $s['title'] !== '' || $s['subtitle'] !== '' || ($s['cta_label'] !== '' && $s['cta_url'] !== '');
                $external = (bool) preg_match('#^https?://#i', $s['cta_url']);
                $alt      = $s['alt'] !== '' ? $s['alt'] : ($showText ? '' : $s['title']);
            @endphp
            <div class="ds-media ds-media-{{ $height }}" role="group" aria-roledescription="diapositiva"
                 aria-label="{{ $i + 1 }} de {{ $n }}{{ $s['title'] !== '' ? ': ' . $s['title'] : '' }}"
                 style="--shade: {{ $showText && $hasText ? $shade : 0 }}">
                <img src="{{ $s['image'] }}" alt="{{ $alt }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" decoding="async">
                <div class="ds-media-shade" aria-hidden="true"></div>
                @if($showText && $hasText)
                    <div class="ds-media-body is-center pb-12">
                        @if($s['title'] !== '')<h2 class="ds-media-title">{{ $s['title'] }}</h2>@endif
                        @if($s['subtitle'] !== '')<p class="ds-media-sub">{{ $s['subtitle'] }}</p>@endif
                        @if($s['cta_label'] !== '' && $s['cta_url'] !== '')
                            <a href="{{ $s['cta_url'] }}" class="ds-btn ds-btn-accent mt-2" style="text-shadow:none"
                               @if($external) target="_blank" rel="noopener noreferrer" @endif>
                                {{ $s['cta_label'] }} <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                @elseif($s['cta_url'] !== '')
                    <a href="{{ $s['cta_url'] }}" class="ds-media-link"
                       aria-label="{{ $s['cta_label'] ?: ($s['title'] ?: ($s['alt'] ?: 'Ver más')) }}"
                       @if($external) target="_blank" rel="noopener noreferrer" @endif></a>
                @endif
            </div>
        @endforeach
    </div>

    <button type="button" class="ds-carousel-nav prev" data-prev aria-controls="{{ $cid }}-track" aria-label="Diapositiva anterior">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
    </button>
    <button type="button" class="ds-carousel-nav next" data-next aria-controls="{{ $cid }}-track" aria-label="Diapositiva siguiente">
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    </button>

    <div class="ds-carousel-bar">
        @for($i = 0; $i < $n; $i++)
            <button type="button" class="ds-carousel-dot" data-go="{{ $i }}" aria-controls="{{ $cid }}-track"
                    aria-label="Ir a la diapositiva {{ $i + 1 }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}"></button>
        @endfor
        @if($autoplay)
            <button type="button" class="ds-carousel-pause" data-pause aria-label="Pausar carrusel">
                <i class="fa-solid fa-pause" aria-hidden="true"></i>
            </button>
        @endif
    </div>
</div>

{{-- El JS se imprime una sola vez por página (@once necesita ramsey/uuid, que no está instalado) --}}
@if(empty($GLOBALS['__ds_carousel_js']))
@php $GLOBALS['__ds_carousel_js'] = true; @endphp
<script>
(function () {
    'use strict';
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // El script se imprime con el primer carrusel: espera al DOM para tomar también los siguientes
    function init() { document.querySelectorAll('[data-carousel]').forEach(setup); }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }

    function setup(root) {
        var track  = root.querySelector('.ds-carousel-track');
        var slides = track.children;
        var dots   = root.querySelectorAll('[data-go]');
        var pause  = root.querySelector('[data-pause]');
        var delay  = parseInt(root.dataset.interval, 10) || 0;
        var index  = 0, timer = null, userPaused = reduce, hovering = false;

        function setCurrent(i) {
            index = i;
            dots.forEach(function (d, k) { d.setAttribute('aria-current', k === i ? 'true' : 'false'); });
        }
        function go(i) {
            var n = slides.length;
            i = (i + n) % n;
            var target = i * track.clientWidth;
            track.scrollTo({ left: target, behavior: reduce ? 'auto' : 'smooth' });
            setCurrent(i);
            // Respaldo: si el navegador no anima (pestaña en segundo plano, webviews) salta directo
            setTimeout(function () {
                if (Math.abs(track.scrollLeft - target) > 2) { track.scrollLeft = target; setCurrent(i); }
            }, 700);
        }

        var t;
        track.addEventListener('scroll', function () {
            clearTimeout(t);
            t = setTimeout(function () { setCurrent(Math.round(track.scrollLeft / Math.max(1, track.clientWidth))); }, 80);
        }, { passive: true });

        root.querySelector('[data-prev]').addEventListener('click', function () { go(index - 1); });
        root.querySelector('[data-next]').addEventListener('click', function () { go(index + 1); });
        dots.forEach(function (d) { d.addEventListener('click', function () { go(parseInt(d.dataset.go, 10)); }); });
        root.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft')  { e.preventDefault(); go(index - 1); }
            if (e.key === 'ArrowRight') { e.preventDefault(); go(index + 1); }
        });

        function stop() { clearInterval(timer); timer = null; }
        function start() {
            stop();
            if (delay && !userPaused && !hovering && !document.hidden) {
                timer = setInterval(function () { go(index + 1); }, delay);
            }
        }
        function syncPauseBtn() {
            if (!pause) return;
            pause.setAttribute('aria-label', userPaused ? 'Reproducir carrusel' : 'Pausar carrusel');
            pause.querySelector('i').className = 'fa-solid ' + (userPaused ? 'fa-play' : 'fa-pause');
        }
        if (pause) {
            pause.addEventListener('click', function () { userPaused = !userPaused; syncPauseBtn(); start(); });
        }
        root.addEventListener('mouseenter', function () { hovering = true; stop(); });
        root.addEventListener('mouseleave', function () { hovering = false; start(); });
        root.addEventListener('focusin', function () { hovering = true; stop(); });
        root.addEventListener('focusout', function (e) { if (!root.contains(e.relatedTarget)) { hovering = false; start(); } });
        document.addEventListener('visibilitychange', start);

        syncPauseBtn();
        start();
    }
})();
</script>
@endif
@endif
