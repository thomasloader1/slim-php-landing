@switch($data['style'] ?? 'line')
    @case('space')
        <div class="h-6" aria-hidden="true"></div>
        @break
    @case('dots')
        <div class="w-full flex justify-center gap-2 opacity-40" aria-hidden="true">
            <span class="w-1.5 h-1.5 rounded-full accent-bg"></span><span class="w-1.5 h-1.5 rounded-full accent-bg"></span><span class="w-1.5 h-1.5 rounded-full accent-bg"></span>
        </div>
        @break
    @default
        <hr class="w-full border-0 h-px bg-white/10">
@endswitch
