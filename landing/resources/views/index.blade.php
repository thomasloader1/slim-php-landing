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
            --opacity: {{ overlay_opacity_percent($settings['landing_bg_overlay_opacity'] ?? null) / 100 }};
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
        /* Bloques sin contenido (ej: bio vacía) no ocupan lugar en el gap */
        section[data-block]:not(:has(*)) { display: none; }
    </style>

    @php
        /* ── Función de auto-contraste para bg_color ── */
        if (!function_exists('bgContrastColor')) { function bgContrastColor($hex) {
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return (0.299*$r + 0.587*$g + 0.114*$b) / 255 > 0.5 ? '#000000' : '#ffffff';
        } }
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

    <div class="max-w-md w-full flex flex-col items-center gap-10 relative z-10">
        {{-- Bloques en el orden definido por el UX Builder (admin/builder) --}}
        @foreach($blocks as $block)
            <section class="w-full flex flex-col items-center" data-block="{{ $block['type'] }}">
                @include('landing/blocks/' . $block['type'], ['data' => $block['data']])
            </section>
        @endforeach
    </div>

    @include('partials/whatsapp-float')
    @include('partials/demo-badge')
</body>
</html>
