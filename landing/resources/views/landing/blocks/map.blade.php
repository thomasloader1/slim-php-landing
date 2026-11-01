@php $mapSrc = ($data['query'] ?? '') !== '' ? \App\Support\MapEmbed::fromUrlOrAddress($data['query']) : null; @endphp
@if($mapSrc)
    <div class="w-full glass rounded-2xl overflow-hidden">
        @if(($data['title'] ?? '') !== '')
            <p class="px-5 py-3 text-sm font-semibold"><i class="fa-solid fa-location-dot accent-text mr-2"></i>{{ $data['title'] }}</p>
        @endif
        <iframe src="{{ $mapSrc }}" class="w-full aspect-video" style="border:0;" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade" allowfullscreen title="{{ $data['title'] ?: 'Mapa' }}"></iframe>
    </div>
@endif
