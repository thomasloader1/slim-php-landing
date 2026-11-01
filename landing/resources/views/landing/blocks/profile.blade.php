@php
    // "Tamaño" en Apariencia: sm = tamaño histórico del avatar (112px)
    $sizeKey = $settings['landing_logo_size'] ?? 'sm';
    $avatarSize = ['sm' => '112px', 'md' => '144px', 'lg' => '176px', 'xl' => '208px'][$sizeKey] ?? '112px';
    $logoSize = ['sm' => '64px', 'md' => '96px', 'lg' => '128px', 'xl' => '160px'][$sizeKey] ?? '64px';
    $hasLogo = !empty($settings['landing_logo_url']);
    $hasAvatar = !empty($settings['landing_avatar_url']);
@endphp
@if($hasLogo)
    <div class="flex justify-center">
        <img src="{{ $settings['landing_logo_url'] }}" alt="{{ $settings['site_name'] ?? 'Logo' }}" class="w-auto object-contain" style="max-height: {{ $logoSize }}">
    </div>
@endif
@if($hasAvatar || !$hasLogo)
    {{-- Avatar: se muestra si está cargado (con o sin logo); sin ninguno de los dos, ícono genérico --}}
    <div class="relative group">
        <div class="absolute -inset-1 opacity-20 rounded-full blur transition duration-1000 group-hover:opacity-40" style="background: var(--accent);"></div>
        <div class="relative rounded-full bg-slate-900/50 border-2 border-white/10 flex items-center justify-center overflow-hidden shadow-2xl glass"
             style="width: {{ $avatarSize }}; height: {{ $avatarSize }};">
            @if($hasAvatar)
                <img src="{{ $settings['landing_avatar_url'] }}" alt="{{ $settings['site_name'] ?? 'Avatar' }}" class="w-full h-full object-cover">
            @else
                <i class="fa-solid fa-user text-5xl opacity-20"></i>
            @endif
        </div>
    </div>
@endif
