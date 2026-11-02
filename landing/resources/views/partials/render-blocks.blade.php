{{--
    Renderiza los bloques del UX Builder en orden.
    Variables:
      $blocks  layout ya filtrado (PageLayoutService::isRenderable)
      $page    'landing' | 'shop' → busca primero {page}/blocks/{type},
               luego blocks/shared/{type} y por último landing/blocks/{type}
      $wrap    [type => clases de la <section>], con '*' como default
    El resto de variables del padre (settings, links, sections…) llegan solas.
--}}
@foreach($blocks as $block)
    @php
        $__type = $block['type'];
        $__view = null;
        foreach ([$page . '/blocks/' . $__type, 'blocks/shared/' . $__type, 'landing/blocks/' . $__type] as $__candidate) {
            if ($__env->exists($__candidate)) { $__view = $__candidate; break; }
        }
    @endphp
    @if($__view)
        <section class="{{ $wrap[$__type] ?? $wrap['*'] }}" data-block="{{ $__type }}">
            @include($__view, ['data' => $block['data'], 'blockId' => $block['id']])
        </section>
    @endif
@endforeach
