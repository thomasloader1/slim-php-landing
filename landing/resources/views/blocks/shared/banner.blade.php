{{--
    Banner (landing y tienda). Imagen opcional para mobile, textos ocultables,
    CTA opcional. Con los textos ocultos el título queda para lectores de pantalla
    y, si hay enlace, todo el banner es clickeable.
--}}
@php
    $img      = $data['image'] ?? '';
    $imgMob   = $data['image_mobile'] ?? '';
    $title    = $data['title'] ?? '';
    $sub      = $data['subtitle'] ?? '';
    $ctaLabel = $data['cta_label'] ?? '';
    $ctaUrl   = $data['cta_url'] ?? '';
    $showText = (bool) ($data['show_text'] ?? true);
    $hasText  = $title !== '' || $sub !== '' || ($ctaLabel !== '' && $ctaUrl !== '');
    $shade    = ((int) ($data['overlay'] ?? 35)) / 100;
    $external = (bool) preg_match('#^https?://#i', $ctaUrl);
    $alt      = ($data['alt'] ?? '') !== '' ? $data['alt'] : ($showText ? '' : $title);
@endphp
@if($img !== '' || ($showText && $hasText))
    <div class="ds-media ds-media-{{ $data['height'] ?? 'md' }} {{ $img === '' ? 'no-image' : '' }}" style="--shade: {{ $img !== '' && $showText && $hasText ? $shade : 0 }}">
        @if($img !== '')
            <picture>
                @if($imgMob !== '')
                    <source media="(max-width: 640px)" srcset="{{ $imgMob }}">
                @endif
                <img src="{{ $img }}" alt="{{ $alt }}" loading="lazy" decoding="async">
            </picture>
            <div class="ds-media-shade" aria-hidden="true"></div>
        @endif

        @if($showText && $hasText)
            <div class="ds-media-body is-{{ $data['align'] ?? 'center' }}">
                @if($title !== '')<h2 class="ds-media-title">{{ $title }}</h2>@endif
                @if($sub !== '')<p class="ds-media-sub">{{ $sub }}</p>@endif
                @if($ctaLabel !== '' && $ctaUrl !== '')
                    <a href="{{ $ctaUrl }}" class="ds-btn ds-btn-accent mt-2" style="text-shadow:none"
                       @if($external) target="_blank" rel="noopener noreferrer" @endif>
                        {{ $ctaLabel }} <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        @else
            @if($title !== '')<h2 class="sr-only">{{ $title }}</h2>@endif
            @if($ctaUrl !== '')
                <a href="{{ $ctaUrl }}" class="ds-media-link" aria-label="{{ $ctaLabel ?: ($title ?: (($data['alt'] ?? '') ?: 'Ver más')) }}"
                   @if($external) target="_blank" rel="noopener noreferrer" @endif></a>
            @endif
        @endif
    </div>
@endif
