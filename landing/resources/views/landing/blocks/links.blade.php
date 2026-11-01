@php $displayMode = $settings['landing_links_display'] ?? 'list'; @endphp

@if($displayMode === 'grid')
{{-- Modo Grilla: iconos solamente --}}
<div class="w-full grid grid-cols-3 sm:grid-cols-4 gap-6">
    @forelse($links as $link)
        @php $__hasBg = !empty($link->bgColor); @endphp
        <a href="{{ $link->url }}"
           class="{{ $__hasBg ? '' : 'glass link-hover' }} aspect-square rounded-2xl flex items-center justify-center transition-all duration-300 group"
           style="{{ $__hasBg ? 'background-color:' . $link->bgColor . '; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.3);' : '' }}"
           target="_blank" title="{{ $link->title }}">
            <div class="flex items-center justify-center text-2xl group-hover:scale-110 transition-transform"
                 style="color: {{ $__hasBg ? bgContrastColor($link->bgColor) : (($settings['landing_accent_force'] ?? '1') === '1' ? 'var(--accent)' : ($link->color ?: 'var(--accent)')) }};">
                {!! $link->getIconHtml() !!}
            </div>
        </a>
    @empty
        <div class="col-span-full glass p-12 rounded-3xl text-center border-dashed border-white/10">
            <i class="fa-solid fa-link-slash t-faint text-4xl mb-4 block"></i>
            <p class="t-subtle text-base">No hay enlaces todavía.</p>
        </div>
    @endforelse
</div>
@else
{{-- Modo Lista (default) --}}
<div class="w-full flex flex-col space-y-5">
    @forelse($links as $link)
        @php $__hasBg = !empty($link->bgColor); $__contrast = $__hasBg ? bgContrastColor($link->bgColor) : null; @endphp
        <a href="{{ $link->url }}"
           class="{{ $__hasBg ? '' : 'glass link-hover' }} p-4 px-6 rounded-2xl flex items-center transition-all duration-300 group"
           style="{{ $__hasBg ? 'background-color:' . $link->bgColor . '; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.3);' : '' }}"
           target="_blank">
            <div class="w-10 h-10 flex items-center justify-center text-xl group-hover:scale-110 transition-transform"
                 style="color: {{ $__hasBg ? $__contrast : (($settings['landing_accent_force'] ?? '1') === '1' ? 'var(--accent)' : ($link->color ?: 'var(--accent)')) }};">
                                         {!! $link->getIconHtml() !!}
            </div>
            <span class="flex-1 font-semibold text-lg ml-3" style="{{ $__hasBg ? 'color:' . $__contrast : '' }}">{{ $link->title }}</span>
            <i class="fa-solid fa-arrow-up-right-from-square text-xs transition-all" style="{{ $__hasBg ? 'color:' . $__contrast . '; opacity:0.4;' : 'color: var(--text-faint);' }}"></i>
        </a>
    @empty
        <div class="glass p-12 rounded-3xl text-center border-dashed border-white/10">
            <i class="fa-solid fa-link-slash t-faint text-4xl mb-4 block"></i>
            <p class="t-subtle text-base">No hay enlaces todavía.</p>
        </div>
    @endforelse
</div>
@endif
