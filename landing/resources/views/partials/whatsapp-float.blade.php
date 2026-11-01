{{--
    Botón flotante de WhatsApp.
    - href por defecto: wa.me (abre la app en el celular)
    - data-wa-web: WhatsApp Web (web.whatsapp.com/send) para escritorio
    El script de abajo elige según whatsapp_open_mode: auto | web | app.
    También aplica a cualquier otro enlace con data-wa-web (ej: ubicaciones).
--}}
@php
    $waNumber  = $settings['whatsapp_number'] ?? '';
    $waEnabled = ($settings['whatsapp_enabled'] ?? '0') === '1' && \App\Support\WhatsApp::isValidNumber($waNumber);
    $waMode    = $settings['whatsapp_open_mode'] ?? 'auto';
    $waLabel   = $settings['whatsapp_label'] ?? '';
    $waLeft    = ($settings['whatsapp_position'] ?? 'right') === 'left';
@endphp
@if($waEnabled)
    <a href="{{ \App\Support\WhatsApp::appUrl($waNumber, $settings['whatsapp_message'] ?? null) }}"
       data-wa-web="{{ \App\Support\WhatsApp::webUrl($waNumber, $settings['whatsapp_message'] ?? null) }}"
       target="_blank" rel="noopener noreferrer"
       aria-label="{{ $waLabel ?: 'Escribinos por WhatsApp' }}"
       class="wa-float fixed bottom-6 {{ $waLeft ? 'left-6' : 'right-6' }} z-50 flex items-center rounded-full shadow-2xl transition-transform hover:scale-105"
       style="background:#25D366; color:#fff;">
        <span class="w-14 h-14 flex items-center justify-center text-3xl"><i class="fa-brands fa-whatsapp"></i></span>
        @if($waLabel !== '')
            <span class="hidden sm:inline font-semibold pr-5">{{ $waLabel }}</span>
        @endif
    </a>
@endif
<script>
(function () {
    var mode = @json($waMode);
    // Celulares y tablets abren la app (web.whatsapp.com en mobile manda a la página de descarga)
    var isMobile = /Android|iPhone|iPad|iPod|Mobile|Windows Phone/i.test(navigator.userAgent)
        || (navigator.maxTouchPoints > 1 && /Macintosh/.test(navigator.userAgent));
    var useWeb = mode === 'web' || (mode === 'auto' && !isMobile);
    if (!useWeb) return;
    document.querySelectorAll('a[data-wa-web]').forEach(function (a) {
        if (a.dataset.waWeb) a.href = a.dataset.waWeb;
    });
})();
</script>
