@php
    $logoSizeMap = ['sm' => '64px', 'md' => '96px', 'lg' => '128px', 'xl' => '160px'];
    $logoSize = $logoSizeMap[$settings['landing_logo_size'] ?? 'sm'] ?? '64px';
@endphp
@if(!empty($settings['landing_logo_url']))
    <div class="flex justify-center">
        <img src="{{ $settings['landing_logo_url'] }}" alt="{{ $settings['site_name'] ?? 'Logo' }}" class="w-auto object-contain" style="max-height: {{ $logoSize }}">
    </div>
@else
    {{-- Avatar: solo si no hay logo --}}
    <div class="relative group">
        <div class="absolute -inset-1 opacity-20 rounded-full blur transition duration-1000 group-hover:opacity-40" style="background: var(--accent);"></div>
        <div class="relative w-28 h-28 rounded-full bg-slate-900/50 border-2 border-white/10 flex items-center justify-center overflow-hidden shadow-2xl glass">
            @if(!empty($settings['landing_avatar_url']))
                <img src="{{ $settings['landing_avatar_url'] }}" alt="Avatar" class="w-full h-full object-cover">
            @else
                <i class="fa-solid fa-user text-5xl opacity-20"></i>
            @endif
        </div>
    </div>
@endif
