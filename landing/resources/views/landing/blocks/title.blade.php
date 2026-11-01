<div class="text-center">
    <h1 class="text-4xl font-black mb-2 tracking-tighter">{{ $settings['landing_title'] ?? 'My Profile' }}</h1>
    @if(($settings['landing_subtitle'] ?? 'Digital Creator') !== '')
        <p class="text-white font-medium tracking-tight text-lg">{{ $settings['landing_subtitle'] ?? 'Digital Creator' }}</p>
    @endif
</div>
