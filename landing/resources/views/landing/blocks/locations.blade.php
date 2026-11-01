@if(!empty($locations) && count($locations) > 0)
    @php
        $locDisplay = $settings['locations_display'] ?? 'list';
        $locCols    = (int)($settings['locations_columns'] ?? 2);
        $colClass   = $locDisplay === 'grid' ? match($locCols) {
            1 => 'grid-cols-1',
            3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            default => 'grid-cols-1 sm:grid-cols-2',
        } : '';
    @endphp
    <div class="w-full {{ $locDisplay === 'grid' ? 'grid gap-4 ' . $colClass : 'space-y-4' }}">
        @foreach($locations as $loc)
            @php
                $embedSrc = $loc->mode === 'embed' ? $loc->getEmbedSrc() : null;
                $mapsLink = $loc->maps_link;
            @endphp

            @if($embedSrc)
                {{-- Tarjeta con mapa embebido (iframe) --}}
                <div class="glass rounded-2xl overflow-hidden">
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-white text-sm font-semibold truncate">{{ $loc->name }}</p>
                            @if($loc->address)
                                <p class="text-white/30 text-xs truncate">{{ $loc->address }}</p>
                            @endif
                        </div>
                        <div class="shrink-0 flex items-center gap-3">
                            @if($loc->whatsapp_url)
                                <a href="{{ $loc->whatsapp_url }}" data-wa-web="{{ $loc->whatsapp_web_url }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 text-sm text-green-400 hover:text-green-300 transition-colors font-medium">
                                    <i class="fa-brands fa-whatsapp text-base"></i>
                                    <span class="hidden sm:inline">{{ $loc->whatsapp }}</span>
                                </a>
                            @endif
                            @if($mapsLink)
                                <a href="{{ $mapsLink }}" target="_blank" rel="noopener noreferrer" title="Abrir en Google Maps"
                                   class="text-white/40 hover:text-white transition-colors text-sm">
                                    <i class="fa-solid fa-diamond-turn-right"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                    <iframe src="{{ $embedSrc }}" class="w-full aspect-video"
                            style="border:0;" allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="{{ $loc->name }}"></iframe>
                </div>

            @elseif($mapsLink)
                {{-- Tarjeta tipo botón (o embed sin datos suficientes para el mapa) --}}
                <div class="glass rounded-2xl overflow-hidden">
                    <a href="{{ $mapsLink }}" target="_blank" rel="noopener noreferrer"
                       class="link-hover p-4 px-5 flex items-center gap-3 transition-all duration-300 group w-full">
                        <div class="w-10 h-10 shrink-0 flex items-center justify-center text-xl accent-text group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="font-semibold text-base block truncate">{{ $loc->name }}</span>
                            @if($loc->address)
                                <span class="text-white/40 text-sm truncate block">{{ $loc->address }}</span>
                            @endif
                        </div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-white/20 text-xs shrink-0 transition-all"></i>
                    </a>
                    @if($loc->whatsapp_url)
                        <div class="border-t border-white/5 px-5 py-3">
                            <a href="{{ $loc->whatsapp_url }}" data-wa-web="{{ $loc->whatsapp_web_url }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 text-sm text-green-400 hover:text-green-300 transition-colors font-medium">
                                <i class="fa-brands fa-whatsapp text-base"></i>
                                <span>{{ $loc->whatsapp }}</span>
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
@endif
