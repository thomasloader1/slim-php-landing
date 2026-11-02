{{--
    Fondo compartido (landing + tienda): imagen + overlay + halos del color de acento.
    Sale de Configuración → Apariencia. Incluir al inicio del <body>. Variables: $settings
--}}
<style>
    .main-background {
        position: fixed; inset: 0; z-index: -1;
        @if(!empty($settings['landing_bg_image_url']))
            background-image: url({!! json_encode((string) $settings['landing_bg_image_url'], JSON_UNESCAPED_UNICODE) !!});
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        @endif
    }
    .background-overlay {
        position: absolute; inset: 0;
        background-color: var(--overlay);
        opacity: var(--opacity);
    }
</style>
<div class="main-background" aria-hidden="true">
    <div class="background-overlay"></div>
</div>
<div class="fixed top-[-10%] right-[-10%] w-[150px] h-[150px] md:w-[300px] md:h-[300px] lg:w-[400px] lg:h-[400px] rounded-full opacity-20 blur-[100px] pointer-events-none" style="background: var(--accent);" aria-hidden="true"></div>
<div class="fixed bottom-[-10%] left-[-10%] w-[100px] h-[100px] md:w-[200px] md:h-[200px] lg:w-[300px] lg:h-[300px] rounded-full opacity-10 blur-[80px] pointer-events-none" style="background: var(--accent);" aria-hidden="true"></div>
