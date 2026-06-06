<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings['landing_title'] ?? 'Landing' }} | {{ $settings['site_name'] ?? 'Digital Profile' }}</title>
    
    @include('partials/seo-head', [
        'settings'  => $settings,
        'pageTitle' => $settings['landing_title'] ?? $settings['site_name'] ?? '',
        'pageUrl'   => $pageUrl ?? '',
    ])

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Utilities -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --accent: {{ $settings['landing_accent_color'] ?? '#fec771' }};
            --bg-base: {{ $settings['landing_bg_color'] ?? '#020617' }};
            --text: {{ $settings['landing_text_color'] ?? '#ffffff' }};
            --overlay: {{ $settings['landing_bg_overlay'] ?? '#000000' }};
            --opacity: {{ ($settings['landing_bg_overlay_opacity'] ?? 50) / 100 }};
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-base);
            color: var(--text);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .main-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            @if(!empty($settings['landing_bg_image_url']))
                background-image: url('{{ $settings['landing_bg_image_url'] }}');
                background-size: cover;
                background-position: center;
                background-attachment: fixed;
            @endif
        }
        .background-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--overlay);
            opacity: var(--opacity);
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .link-hover:hover {
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
        }
        .accent-text { color: var(--accent); }
        .accent-bg { background-color: var(--accent); }
        .accent-border { border-color: var(--accent); }
        /* Estilos para contenido rich text de la bio */
        .bio-content ul { list-style: disc; padding-left: 1.5rem; text-align: left; }
        .bio-content ol { list-style: decimal; padding-left: 1.5rem; text-align: left; }
        .bio-content strong { font-weight: 700; }
        .bio-content em { font-style: italic; }
        .bio-content a { color: var(--accent); text-decoration: underline; }
        .bio-content p { margin-bottom: 0.5rem; }
    </style>

    @php
        /* ── Función de auto-contraste para bg_color ── */
        function bgContrastColor($hex) {
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return (0.299*$r + 0.587*$g + 0.114*$b) / 255 > 0.5 ? '#000000' : '#ffffff';
        }
    @endphp

    @php
        /* ── JSON-LD Schema.org ──────────────────────────────────────── */
        $schemaType   = $settings['seo_schema_type'] ?? 'Person';
        $siteName     = $settings['site_name'] ?? '';
        $siteUrl      = rtrim($settings['seo_site_url'] ?? '', '/') ?: null;
        $description  = strip_tags($settings['seo_description'] ?? '');
        $image        = !empty($settings['seo_og_image']) ? $settings['seo_og_image'] : ($settings['landing_avatar_url'] ?? '');

        // Extraer URLs sociales y de contacto de los links
        $sameAs     = [];
        $telephone  = null;
        $emailAddr  = null;
        foreach ($links as $link) {
            if ($link->type === 'social' && !empty($link->url)) {
                $sameAs[] = $link->url;
            }
            if ($link->type === 'phone' && !empty($link->url)) {
                // Extraer número limpio de tel: o wa.me
                $telephone = preg_replace('/^(tel:|https?:\/\/wa\.me\/)/', '', $link->url);
            }
            if ($link->type === 'email' && !empty($link->url)) {
                $emailAddr = preg_replace('/^mailto:/', '', $link->url);
            }
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => $schemaType,
        ];

        if ($schemaType === 'LocalBusiness') {
            $businessType = $settings['seo_business_type'] ?? '';
            if ($businessType) {
                $schema['@type'] = $businessType;
            }
            $schema['name']        = $siteName;
            $schema['description'] = $description;
            if ($siteUrl)    $schema['url']   = $siteUrl . '/';
            if ($image)      $schema['image'] = $image;
            if ($telephone)  $schema['telephone'] = $telephone;
            if ($emailAddr)  $schema['email']     = $emailAddr;
            $address = $settings['seo_address'] ?? '';
            if ($address) {
                $schema['address'] = [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => $address,
                ];
            }
            if ($sameAs) $schema['sameAs'] = $sameAs;

        } elseif ($schemaType === 'Organization') {
            $schema['name']        = $siteName;
            $schema['description'] = $description;
            if ($siteUrl)  $schema['url']  = $siteUrl . '/';
            if ($image)    $schema['logo'] = $image;
            if ($sameAs)   $schema['sameAs'] = $sameAs;

        } else {
            // Person (default)
            $schema['name']        = $settings['landing_title'] ?? $siteName;
            $schema['description'] = $description;
            if ($siteUrl) $schema['url']   = $siteUrl . '/';
            if ($image)   $schema['image'] = $image;
            if ($sameAs)  $schema['sameAs'] = $sameAs;
        }
    @endphp
    <script type="application/ld+json">
    {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    @if(!empty($faqItems) && count($faqItems) > 0)
    @php
        $faqSchema = [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(function($faq) {
                return [
                    '@type' => 'Question',
                    'name'  => $faq->question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => strip_tags($faq->answer),
                    ],
                ];
            }, is_array($faqItems) ? $faqItems : $faqItems->all()),
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
    @endif
</head>
<body class="min-h-screen flex flex-col items-center py-20 px-6 overflow-x-hidden relative">
    <!-- Background System -->
    <div class="main-background">
        <div class="background-overlay"></div>
    </div>

    <!-- Decorative gradients -->
    <div class="fixed top-[-10%] right-[-10%] w-[150px] h-[150px] md:w-[300px] md:h-[300px] lg:w-[400px] lg:h-[400px] rounded-full opacity-20 blur-[100px]" style="background: var(--accent);"></div>
    <div class="fixed bottom-[-10%] left-[-10%] w-[100px] h-[100px] md:w-[200px] md:h-[200px] lg:w-[300px] lg:h-[300px] rounded-full opacity-10 blur-[80px]" style="background: var(--accent);"></div>

    <div class="max-w-md w-full flex flex-col items-center relative z-10">
        @php
            $logoSizeMap = ['sm' => '64px', 'md' => '96px', 'lg' => '128px', 'xl' => '160px'];
            $logoSize = $logoSizeMap[$settings['landing_logo_size'] ?? 'sm'] ?? '64px';
        @endphp

        @if(($settings['module_avatar_enabled'] ?? '1') === '1')
        <!-- Logo Section -->
        @if(!empty($settings['landing_logo_url']))
            <div class="mb-8 flex justify-center">
                <img src="{{ $settings['landing_logo_url'] }}" alt="{{ $settings['site_name'] ?? 'Logo' }}" class="w-auto object-contain" style="max-height: {{ $logoSize }}">
            </div>
        @endif

        <!-- Profile Section: solo mostrar si no hay logo -->
        @if(empty($settings['landing_logo_url']))
        <div class="relative mb-8 group">
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
        @endif

        @if(($settings['module_title_enabled'] ?? '1') === '1')
        <h1 class="text-4xl font-black mb-2 tracking-tighter text-center">
            {{ $settings['landing_title'] ?? 'My Profile' }}
        </h1>
        <p class="text-white font-medium mb-12 text-center tracking-tight text-lg">{{ $settings['landing_subtitle'] ?? 'Digital Creator' }}</p>
        @endif

        <!-- Links Section -->
        @if(($settings['module_links_enabled'] ?? '1') === '1')
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
                    <i class="fa-solid fa-link-slash text-white/20 text-4xl mb-4 block"></i>
                    <p class="text-white/40 text-base">No hay enlaces todavía.</p>
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
                    <i class="fa-solid fa-arrow-up-right-from-square text-xs transition-all" style="{{ $__hasBg ? 'color:' . $__contrast . '; opacity:0.4;' : 'color: rgba(255,255,255,0.2);' }}"></i>
                </a>
            @empty
                <div class="glass p-12 rounded-3xl text-center border-dashed border-white/10">
                    <i class="fa-solid fa-link-slash text-white/20 text-4xl mb-4 block"></i>
                    <p class="text-white/40 text-base">No hay enlaces todavía.</p>
                </div>
            @endforelse
        </div>
        @endif
        @endif

        <!-- Bio Section -->
        @if(($settings['module_bio_enabled'] ?? '1') === '1')
        @if(!empty($settings['landing_bio']))
            <div class="mt-14 text-center px-4">
                <div class="bio-content text-white text-base leading-relaxed max-w-sm mx-auto font-light">
                    {{ $settings['landing_bio'] }}
                </div>
            </div>
        @endif
        @endif

        <!-- Ubicaciones -->
        @if(($settings['module_locations_enabled'] ?? '1') === '1' && !empty($locations))
            @php
                $locDisplay = $settings['locations_display'] ?? 'list';
                $locCols    = (int)($settings['locations_columns'] ?? 2);
                $colClass   = $locDisplay === 'grid' ? match($locCols) {
                    1 => 'grid-cols-1',
                    3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
                    default => 'grid-cols-1 sm:grid-cols-2',
                } : '';
            @endphp
            <div class="w-full mt-8 {{ $locDisplay === 'grid' ? 'grid gap-4 ' . $colClass : 'space-y-4' }}">
                @foreach($locations as $loc)
                    @php $embedSrc = $loc->getEmbedSrc(); @endphp

                    @if($loc->mode === 'button' && !empty($loc->url))
                        {{-- Tarjeta tipo botón --}}
                        <div class="glass rounded-2xl overflow-hidden">
                            <a href="{{ $loc->url }}" target="_blank" rel="noopener noreferrer"
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
                                <i class="fa-solid fa-arrow-up-right-from-square text-white/20 text-xs shrink-0 group-hover:text-accent transition-all"></i>
                            </a>
                            @if($loc->whatsapp_url)
                                <div class="border-t border-white/5 px-5 py-3">
                                    <a href="{{ $loc->whatsapp_url }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-2 text-sm text-green-400 hover:text-green-300 transition-colors font-medium">
                                        <i class="fa-brands fa-whatsapp text-base"></i>
                                        <span>{{ $loc->whatsapp }}</span>
                                    </a>
                                </div>
                            @endif
                        </div>

                    @elseif($loc->mode === 'embed' && $embedSrc)
                        {{-- Tarjeta con mapa embebido --}}
                        <div class="glass rounded-2xl overflow-hidden">
                            <div class="px-5 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-white text-sm font-semibold truncate">{{ $loc->name }}</p>
                                    @if($loc->address)
                                        <p class="text-white/30 text-xs truncate">{{ $loc->address }}</p>
                                    @endif
                                </div>
                                @if($loc->whatsapp_url)
                                    <a href="{{ $loc->whatsapp_url }}" target="_blank" rel="noopener noreferrer"
                                       class="shrink-0 inline-flex items-center gap-1.5 text-sm text-green-400 hover:text-green-300 transition-colors font-medium">
                                        <i class="fa-brands fa-whatsapp text-base"></i>
                                        <span class="hidden sm:inline">{{ $loc->whatsapp }}</span>
                                    </a>
                                @endif
                            </div>
                            <iframe src="{{ $embedSrc }}" class="w-full aspect-video"
                                    style="border:0;" allowfullscreen="" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    title="{{ $loc->name }}"></iframe>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

        <!-- Footer -->
       <!--  <footer class="mt-24 pt-8 border-t border-white/5 w-full text-center">
            <p class="text-[11px] text-white/20 uppercase tracking-[0.2em] font-bold">
                Created with <span class="accent-text">♥</span> by {{ $settings['site_name'] ?? 'Landing' }}
            </p>
        </footer> -->
    </div>
</body>
</html>
