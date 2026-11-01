@if(($data['label'] ?? '') !== '' && ($data['url'] ?? '') !== '')
    @php
        $style = $data['style'] ?? 'glass';
        $classes = match ($style) {
            'accent'  => 'accent-bg font-bold shadow-lg hover:brightness-110 hover:-translate-y-0.5',
            'outline' => 'border-2 accent-border accent-text font-semibold hover:bg-white/5',
            default   => 'glass link-hover font-semibold',
        };
        $isExternal = preg_match('#^https?://#i', $data['url']);
    @endphp
    <a href="{{ $data['url'] }}" @if($isExternal) target="_blank" rel="noopener noreferrer" @endif
       class="w-full p-4 px-6 rounded-2xl flex items-center justify-center gap-3 text-lg transition-all duration-300 {{ $classes }}"
       @if($style === 'accent') style="color: {{ bgContrastColor($settings['landing_accent_color'] ?? '#fec771') }}" @endif>
        @if(!empty($data['icon']))<i class="fa-solid {{ $data['icon'] }}"></i>@endif
        <span>{{ $data['label'] }}</span>
    </a>
@endif
