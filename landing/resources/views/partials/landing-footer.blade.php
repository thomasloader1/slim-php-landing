{{--
    Footer de la landing. Lo configura solo el root en Admin → Módulos
    (landing_footer_enabled / _text / _link_label / _url).
--}}
@php
    $__footerOn    = ($settings['landing_footer_enabled'] ?? '1') === '1';
    $__footerText  = trim($settings['landing_footer_text'] ?? 'Desarrollado por');
    $__footerLabel = trim($settings['landing_footer_link_label'] ?? 'gomepab.com.ar');
    $__footerUrl   = trim($settings['landing_footer_url'] ?? 'https://gomepab.com.ar');
@endphp
@if($__footerOn && ($__footerText !== '' || $__footerLabel !== ''))
    <footer class="relative z-10 mt-auto pt-16 text-center text-xs" style="color: var(--text-subtle);">
        {{ $__footerText }}
        @if($__footerLabel !== '')
            @if($__footerUrl !== '')
                <a href="{{ $__footerUrl }}" target="_blank" rel="noopener" class="font-semibold underline-offset-4 hover:underline" style="color: var(--text-muted);">{{ $__footerLabel }}</a>
            @else
                <span class="font-semibold" style="color: var(--text-muted);">{{ $__footerLabel }}</span>
            @endif
        @endif
    </footer>
@endif
