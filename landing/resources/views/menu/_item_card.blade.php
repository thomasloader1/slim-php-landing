@php
    $flavors = $item->flavors_array;   // array via accessor del modelo
    $hasPriceLabel = !empty($item->price_label_1);
    $hasPrice2     = $item->price_2 !== null;
    $hasPrice3     = $item->price_3 !== null;
@endphp

<article class="menu-card"
         role="listitem"
         data-title="{{ strtolower($item->title) }}"
         data-desc="{{ strtolower($item->description ?? '') }} {{ strtolower($item->flavors ?? '') }}">

    {{-- Imagen --}}
    @if($item->image_url)
        <img src="{{ $item->image_url }}" alt="{{ $item->title }}"
             class="w-full h-44 object-cover"
             loading="lazy">
    @endif

    <div class="p-5">

        {{-- Encabezado: título + badges --}}
        <div class="flex items-start gap-2 mb-2 flex-wrap">
            <h3 class="font-bold text-lg leading-tight flex-1" style="color: var(--text);">
                {{ $item->title }}
            </h3>
            <div class="flex flex-wrap gap-1 pt-0.5">
                @if($item->badge_type === 'premium')
                    <span class="badge-premium" aria-label="Producto Premium">Premium</span>
                @elseif($item->badge_type === 'preorder')
                    <span class="badge-preorder" aria-label="Requiere pedido previo">Pedido previo</span>
                @elseif($item->badge_type === 'special')
                    <span class="badge-special" aria-label="Producto especial">Especial</span>
                @endif
            </div>
        </div>

        {{-- Descripción --}}
        @if($item->description)
            <p class="text-sm leading-relaxed mb-3" style="color: var(--text-muted);">
                {{ $item->description }}
            </p>
        @endif

        {{-- Sabores --}}
        @if(!empty($flavors))
            <div class="mb-3" aria-label="Sabores disponibles">
                @foreach($flavors as $flavor)
                    <span class="flavor-chip">{{ $flavor }}</span>
                @endforeach
            </div>
        @endif

        {{-- Precios --}}
        <div class="flex flex-wrap items-center gap-1.5 mt-auto" aria-label="Precios">

            {{-- Precio 1 --}}
            <div class="flex items-center gap-1.5">
                @if($hasPriceLabel)
                    <span class="price-size">{{ $item->price_label_1 }}</span>
                @endif
                <span class="price-badge"
                      aria-label="{{ $hasPriceLabel ? $item->price_label_1 . ' ' : '' }}{{ $item->price_formatted }}{{ $item->unit ? ' '.$item->unit : '' }}">
                    {{ $item->price_formatted }}{{ $item->unit && !$hasPrice2 ? ' ' . $item->unit : '' }}
                </span>
            </div>

            {{-- Precio 2 --}}
            @if($hasPrice2)
                <span class="price-size opacity-30">·</span>
                <div class="flex items-center gap-1.5">
                    @if($item->price_label_2)
                        <span class="price-size">{{ $item->price_label_2 }}</span>
                    @endif
                    <span class="price-badge"
                          aria-label="{{ $item->price_label_2 ? $item->price_label_2 . ' ' : '' }}{{ $item->price_2_formatted }}{{ $item->unit && !$hasPrice3 ? ' '.$item->unit : '' }}">
                        {{ $item->price_2_formatted }}{{ $item->unit && !$hasPrice3 ? ' ' . $item->unit : '' }}
                    </span>
                </div>
            @endif

            {{-- Precio 3 --}}
            @if($hasPrice3)
                <span class="price-size opacity-30">·</span>
                <div class="flex items-center gap-1.5">
                    @if($item->price_label_3)
                        <span class="price-size">{{ $item->price_label_3 }}</span>
                    @endif
                    <span class="price-badge"
                          aria-label="{{ $item->price_label_3 ? $item->price_label_3 . ' ' : '' }}{{ $item->price_3_formatted }}{{ $item->unit ? ' '.$item->unit : '' }}">
                        {{ $item->price_3_formatted }}{{ $item->unit ? ' ' . $item->unit : '' }}
                    </span>
                </div>
            @endif

        </div>

        {{-- Nota regalo --}}
        @if($item->gift_note)
            <div class="mt-2">
                <span class="badge-gift">
                    <i class="fa-solid fa-gift text-xs mr-0.5" aria-hidden="true"></i>
                    {{ $item->gift_note }}
                </span>
            </div>
        @endif

    </div>
</article>
