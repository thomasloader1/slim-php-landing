{{--
    Design system compartido (landing + tienda).
    Todos los colores salen de Configuración → Apariencia (landing_*): una sola fuente de verdad.
    Los tokens se adaptan solos a fondos claros u oscuros.
    Variables: $settings
--}}
@php
    $__accent = $settings['landing_accent_color'] ?? '#fec771';
    $__bg     = $settings['landing_bg_color'] ?? '#020617';
    $__text   = $settings['landing_text_color'] ?? '#ffffff';
    $__light  = is_light_color($__bg);
@endphp
<style>
    :root {
        /* ── Color (Configuración) ── */
        --accent: {{ $__accent }};
        --accent-contrast: {{ bgContrastColor($__accent) }};
        --bg-base: {{ $__bg }};
        --text: {{ $__text }};
        --text-muted: color-mix(in srgb, var(--text) 75%, transparent);
        --text-subtle: color-mix(in srgb, var(--text) 45%, transparent);
        --text-faint: color-mix(in srgb, var(--text) 25%, transparent);
        --overlay: {{ $settings['landing_bg_overlay'] ?? '#000000' }};
        --opacity: {{ overlay_opacity_percent($settings['landing_bg_overlay_opacity'] ?? null) / 100 }};

        /* ── Superficies (derivadas: funcionan en fondo claro u oscuro) ── */
        --border: color-mix(in srgb, var(--text) 12%, transparent);
        --border-strong: color-mix(in srgb, var(--text) 24%, transparent);
        --accent-soft: color-mix(in srgb, var(--accent) 14%, transparent);
        --surface-solid: color-mix(in srgb, var(--bg-base) 94%, var(--text));
@if($__light)
        --surface: color-mix(in srgb, #ffffff 82%, transparent);
        --surface-hover: #ffffff;
        --shadow-sm: 0 1px 2px rgb(0 0 0 / .05), 0 4px 14px -4px rgb(0 0 0 / .08);
        --shadow-md: 0 18px 40px -16px rgb(0 0 0 / .22);
@else
        --surface: color-mix(in srgb, var(--text) 5%, transparent);
        --surface-hover: color-mix(in srgb, var(--text) 9%, transparent);
        --shadow-sm: 0 1px 2px rgb(0 0 0 / .25);
        --shadow-md: 0 18px 40px -16px rgb(0 0 0 / .6);
@endif

        /* ── Forma y ritmo ── */
        --radius-sm: .75rem;
        --radius-md: 1rem;
        --radius-lg: 1.5rem;
        --radius-pill: 999px;
        --ease: cubic-bezier(.2, .7, .2, 1);
        --font: 'Outfit', system-ui, sans-serif;
    }

    body {
        font-family: var(--font);
        background-color: var(--bg-base);
        color: var(--text);
        margin: 0;
        padding: 0;
        min-height: 100vh;
    }

    /* ── Utilidades históricas de la landing (no cambiar: las usan los bloques) ── */
    .glass {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .link-hover:hover {
        border-color: var(--accent);
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
    }
    .accent-text { color: var(--accent); }
    .accent-bg { background-color: var(--accent); }
    .accent-border { border-color: var(--accent); }
    .t-muted  { color: var(--text-muted); }
    .t-subtle { color: var(--text-subtle); }
    .t-faint  { color: var(--text-faint); }
    .hover-t-text:hover { color: var(--text); }
    .bio-content ul { list-style: disc; padding-left: 1.5rem; text-align: left; }
    .bio-content ol { list-style: decimal; padding-left: 1.5rem; text-align: left; }
    .bio-content strong { font-weight: 700; }
    .bio-content em { font-style: italic; }
    .bio-content a { color: var(--accent); text-decoration: underline; }
    .bio-content p { margin-bottom: 0.5rem; }

    /* ── Componentes del design system ── */
    .ds-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        transition: transform .25s var(--ease), box-shadow .25s var(--ease), border-color .25s var(--ease), background .25s var(--ease);
    }
    .ds-card:hover { border-color: var(--border-strong); background: var(--surface-hover); box-shadow: var(--shadow-md); }

    .ds-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
        min-height: 44px; padding: .65rem 1.15rem;
        border-radius: var(--radius-sm); border: 1px solid transparent;
        font-weight: 700; font-size: .9rem; line-height: 1.1;
        cursor: pointer; text-decoration: none;
        transition: transform .15s var(--ease), filter .15s, background .15s, border-color .15s;
    }
    .ds-btn:hover { transform: translateY(-1px); }
    .ds-btn:active { transform: translateY(0); }
    .ds-btn:disabled, .ds-btn[aria-disabled="true"] { opacity: .45; cursor: not-allowed; transform: none; }
    .ds-btn-accent { background: var(--accent); color: var(--accent-contrast); }
    .ds-btn-accent:hover { filter: brightness(1.07); }
    .ds-btn-ghost { background: var(--surface); color: var(--text); border-color: var(--border); }
    .ds-btn-ghost:hover { border-color: var(--accent); }

    .ds-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .2rem .6rem; border-radius: var(--radius-pill);
        font-size: .72rem; font-weight: 600;
        background: var(--surface); color: var(--text-muted); border: 1px solid var(--border);
    }
    .ds-chip-accent { background: var(--accent-soft); color: var(--text); border-color: color-mix(in srgb, var(--accent) 35%, transparent); }

    .ds-input {
        width: 100%; min-height: 44px; padding: .65rem .95rem;
        border-radius: var(--radius-md); font-size: 1rem;
        background: var(--surface); color: var(--text);
        border: 1px solid var(--border); outline: none;
        transition: border-color .2s, box-shadow .2s;
    }
    .ds-input::placeholder { color: var(--text-subtle); }
    .ds-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }

    /* ── Media: banners y carrusel ── */
    .ds-media {
        position: relative; width: 100%; overflow: hidden;
        border-radius: var(--radius-lg);
        background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 45%, var(--bg-base)));
        color: var(--accent-contrast);
        max-height: 72vh;
    }
    .ds-media-sm { aspect-ratio: 3 / 1;  min-height: 160px; }
    .ds-media-md { aspect-ratio: 21 / 9; min-height: 220px; }
    .ds-media-lg { aspect-ratio: 16 / 9; min-height: 300px; }
    .ds-media > picture > img, .ds-media > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .ds-media-shade {
        position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(to top, rgb(0 0 0 / var(--shade, .35)), rgb(0 0 0 / calc(var(--shade, .35) * .25)));
    }
    .ds-media-body {
        position: relative; z-index: 1; height: 100%;
        display: flex; flex-direction: column; justify-content: flex-end; gap: .5rem;
        padding: clamp(1.25rem, 4vw, 3rem);
        color: #fff; text-shadow: 0 1px 14px rgb(0 0 0 / .35);
    }
    .ds-media.no-image .ds-media-body { color: var(--accent-contrast); text-shadow: none; justify-content: center; }
    .ds-media-body.is-left   { align-items: flex-start; text-align: left; }
    .ds-media-body.is-center { align-items: center; text-align: center; }
    .ds-media-body.is-right  { align-items: flex-end; text-align: right; }
    .ds-media-title { font-size: clamp(1.4rem, 3.6vw, 2.75rem); font-weight: 800; line-height: 1.05; letter-spacing: -.02em; max-width: 22ch; }
    .ds-media-sub { font-size: clamp(.9rem, 1.6vw, 1.15rem); opacity: .92; max-width: 46ch; }
    .ds-media-link { position: absolute; inset: 0; z-index: 2; }

    .ds-carousel { position: relative; }
    .ds-carousel-track {
        display: flex; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth;
        border-radius: var(--radius-lg); scrollbar-width: none;
    }
    .ds-carousel-track::-webkit-scrollbar { display: none; }
    .ds-carousel-track > .ds-media { flex: 0 0 100%; scroll-snap-align: start; border-radius: 0; }
    .ds-carousel-nav {
        position: absolute; top: 50%; transform: translateY(-50%); z-index: 3;
        width: 44px; height: 44px; border-radius: 50%; border: 0; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgb(255 255 255 / .85); color: #111; box-shadow: var(--shadow-sm);
        transition: background .15s, transform .15s;
    }
    .ds-carousel-nav:hover { background: #fff; transform: translateY(-50%) scale(1.05); }
    .ds-carousel-nav.prev { left: .75rem; }
    .ds-carousel-nav.next { right: .75rem; }
    .ds-carousel-bar {
        position: absolute; left: 0; right: 0; bottom: .75rem; z-index: 3;
        display: flex; align-items: center; justify-content: center; gap: .4rem;
    }
    .ds-carousel-dot {
        width: 24px; height: 24px; border: 0; padding: 0; background: transparent; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .ds-carousel-dot::before {
        content: ''; width: 8px; height: 8px; border-radius: 999px;
        background: rgb(255 255 255 / .55); box-shadow: 0 0 0 1px rgb(0 0 0 / .15); transition: width .2s, background .2s;
    }
    .ds-carousel-dot[aria-current="true"]::before { width: 22px; background: #fff; }
    .ds-carousel-pause {
        width: 28px; height: 28px; border-radius: 50%; border: 0; cursor: pointer; margin-left: .35rem;
        background: rgb(0 0 0 / .35); color: #fff; font-size: .7rem;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .ds-carousel.single .ds-carousel-nav, .ds-carousel.single .ds-carousel-bar { display: none; }
    @media (max-width: 639px) { .ds-carousel-nav { display: none; } }

    /* Foco visible y consistente para teclado */
    :where(a, button, input, select, textarea, [tabindex]):focus-visible {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
        border-radius: 6px;
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; scroll-behavior: auto !important; }
    }
</style>
