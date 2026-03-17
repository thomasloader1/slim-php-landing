<div class="menu-card border border-white/10 rounded-2xl overflow-hidden transition-all duration-300 hover:-translate-y-1"
     style="background: rgba(255,255,255,0.04); backdrop-filter: blur(10px);"
     data-title="{{ strtolower($item->title) }}"
     data-desc="{{ strtolower($item->description ?? '') }}">

    @if($item->image_url)
        <img src="{{ $item->image_url }}" alt="{{ $item->title }}"
             class="w-full h-44 object-cover">
    @else
        <div class="w-full h-28 flex items-center justify-center" style="background: rgba(255,255,255,0.03);">
            <i class="fa-solid fa-utensils text-3xl" style="color: rgba(255,255,255,0.08);"></i>
        </div>
    @endif

    <div class="p-5">
        <div class="flex justify-between items-start gap-3 mb-2">
            <h3 class="font-bold text-lg leading-tight" style="color: var(--text);">{{ $item->title }}</h3>
            <span class="text-sm font-bold px-2.5 py-1 rounded-lg shrink-0 whitespace-nowrap"
                  style="background: color-mix(in srgb, var(--accent) 15%, transparent); color: var(--accent);">
                {{ $item->price_formatted }}
            </span>
        </div>
        @if($item->description)
            <p class="text-sm leading-relaxed" style="color: color-mix(in srgb, var(--text) 55%, transparent);">
                {{ $item->description }}
            </p>
        @endif
    </div>
</div>
