{{--
    Card de producto (tienda). Layout e-commerce: imagen con proporción fija, badges
    sobre la imagen, título a 2 líneas, precio destacado y acción anclada abajo para
    que todas las cards de una fila queden alineadas.
    Variables: $item, $cat (sec-N), $ratio (clase aspect-* o alto fijo), $showImage, $showDesc, $catLabel?, $shopOpen
    El JS del carrito depende de: .menu-card, data-title/desc/cat, .shop-add (data-id,
    data-title, data-stock, data-variants), .shop-variant y .shop-add-btn.
--}}
@php
    $flavors   = $item->flavors_array;
    $oos       = (bool) $item->is_out_of_stock;
    $titleId   = 'p-' . ($item->id ?? substr(md5($item->title . ($item->sku ?? '')), 0, 8)) . '-title';
    $catLabel  = $catLabel ?? '';
    $ratio     = $ratio ?? 'aspect-square';
    $showImage = $showImage ?? true;
    $showDesc  = $showDesc ?? true;
    $unit      = $item->unit ? ' ' . $item->unit : '';
    $extraPrices = [];
    if ($item->price_2 !== null) { $extraPrices[] = [$item->price_label_2, $item->price_2_formatted]; }
    if ($item->price_3 !== null) { $extraPrices[] = [$item->price_label_3, $item->price_3_formatted]; }
    $badge = match ($item->badge_type) {
        'premium'  => ['Premium', 'p-badge-premium'],
        'preorder' => ['Pedido previo', 'p-badge-preorder'],
        'special'  => ['Especial', 'p-badge-special'],
        default    => null,
    };
@endphp

<article class="menu-card product-card ds-card{{ $oos ? ' is-out-of-stock' : '' }}"
         id="{{ $item->anchor }}"
         aria-labelledby="{{ $titleId }}"
         data-cat="{{ $cat ?? 'sec-0' }}"
         data-title="{{ mb_strtolower($item->title) }}"
         data-desc="{{ mb_strtolower(($item->description ?? '') . ' ' . ($item->flavors ?? '')) }}">

    @if($showImage)
    <div class="product-media {{ $ratio }}">
        @if($item->image_url)
            <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy" decoding="async">
        @else
            <div class="product-ph" aria-hidden="true"><i class="fa-regular fa-image"></i></div>
        @endif

        @if($oos || $badge)
            <div class="product-badges">
                @if($oos)<span class="p-badge p-badge-oos">Sin stock</span>@endif
                @if($badge)<span class="p-badge {{ $badge[1] }}">{{ $badge[0] }}</span>@endif
            </div>
        @endif
    </div>

    @endif

    <div class="product-body">
        @if(!$showImage && ($oos || $badge))
            <div class="flex flex-wrap gap-1.5">
                @if($oos)<span class="p-badge p-badge-oos">Sin stock</span>@endif
                @if($badge)<span class="p-badge {{ $badge[1] }}">{{ $badge[0] }}</span>@endif
            </div>
        @endif
        @if($catLabel !== '')
            <p class="product-cat">{{ $catLabel }}</p>
        @endif

        <h3 id="{{ $titleId }}" class="product-title">{{ $item->title }}</h3>

        @if($showDesc && $item->description)
            <p class="product-desc">{{ $item->description }}</p>
        @endif

        @if(!empty($flavors))
            <ul class="flex flex-wrap gap-1" aria-label="Variedades">
                @foreach(array_slice($flavors, 0, 4) as $flavor)
                    <li class="ds-chip">{{ $flavor }}</li>
                @endforeach
                @if(count($flavors) > 4)
                    <li class="ds-chip" title="{{ implode(', ', array_slice($flavors, 4)) }}">+{{ count($flavors) - 4 }}</li>
                @endif
            </ul>
        @endif

        <div class="product-price">
            <p>
                @if($item->price_label_1)<span class="label">{{ $item->price_label_1 }}</span>@endif
                <span class="amount">{{ $item->price_formatted }}</span>@if($unit && empty($extraPrices))<span class="label">{{ $unit }}</span>@endif
            </p>
            @foreach($extraPrices as [$lbl, $price])
                <p class="extra">
                    @if($lbl)<span class="label">{{ $lbl }}</span>@endif
                    <span>{{ $price }}</span>
                </p>
            @endforeach
        </div>

        @if($item->gift_note)
            <p class="ds-chip ds-chip-accent self-start"><i class="fa-solid fa-gift text-[10px]" aria-hidden="true"></i> {{ $item->gift_note }}</p>
        @endif

        @if(!empty($shopOpen) && $item->id)
            @php
                $variants = [['v' => 1, 'label' => $item->price_label_1 ?: '', 'price' => (float) $item->price]];
                if ($item->price_2 !== null) { $variants[] = ['v' => 2, 'label' => $item->price_label_2 ?: 'Opción 2', 'price' => (float) $item->price_2]; }
                if ($item->price_3 !== null) { $variants[] = ['v' => 3, 'label' => $item->price_label_3 ?: 'Opción 3', 'price' => (float) $item->price_3]; }
            @endphp
            <div class="shop-add product-actions"
                 data-id="{{ $item->id }}" data-title="{{ $item->title }}"
                 data-stock="{{ $item->stock === null ? '' : max(0, $item->stock) }}"
                 data-variants='@json($variants)'>
                @if(count($variants) > 1)
                    <select class="shop-variant" aria-label="Opción de {{ $item->title }}" {{ $oos ? 'disabled' : '' }}>
                        @foreach($variants as $v)
                            <option value="{{ $v['v'] }}">{{ $v['label'] }} · {{ \App\Models\MenuItem::formatMoney($v['price']) }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="button" class="shop-add-btn ds-btn ds-btn-accent w-full" {{ $oos ? 'disabled' : '' }}
                        aria-label="{{ $oos ? $item->title . ': sin stock' : 'Agregar ' . $item->title . ' al pedido' }}">
                    <i class="fa-solid {{ $oos ? 'fa-ban' : 'fa-cart-plus' }} text-sm" aria-hidden="true"></i>
                    {{ $oos ? 'Sin stock' : 'Agregar' }}
                </button>
            </div>
        @endif
    </div>
</article>
