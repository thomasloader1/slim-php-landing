<!DOCTYPE html>
<html lang="{{ seo_lang($settings) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ seo_title($settings['landing_title'] ?? '', $settings) }}</title>

    @include('partials/seo-head', [
        'settings'        => $settings,
        'pageTitle'       => seo_title($settings['landing_title'] ?? '', $settings),
        'pageUrl'         => $pageUrl ?? '',
        'pageDescription' => $pageDescription ?? '',
    ])

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Utilities -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    @include('partials/theme-tokens', ['settings' => $settings])
    <style>
        /* Bloques sin contenido (ej: bio vacía) no ocupan lugar en el gap */
        section[data-block]:not(:has(*)) { display: none; }
    </style>

    {{-- JSON-LD Schema.org (App\Services\StructuredDataService) --}}
    @foreach($jsonLd ?? [] as $__ld)
    <script type="application/ld+json">{!! \App\Services\StructuredDataService::encode($__ld) !!}</script>
    @endforeach
</head>
<body class="min-h-screen flex flex-col items-center py-20 px-6 overflow-x-hidden relative">
    @include('partials/theme-background', ['settings' => $settings])

    <div class="max-w-md w-full flex flex-col items-center gap-10 relative z-10">
        @unless(collect($blocks)->contains(fn ($b) => $b['type'] === 'title'))
            {{-- Sin bloque de título visible: la página igual necesita un H1 para buscadores --}}
            <h1 class="sr-only">{{ seo_title($settings['landing_title'] ?? '', $settings) }}</h1>
        @endunless
        {{-- Bloques en el orden definido por el UX Builder (admin/builder) --}}
        @include('partials/render-blocks', [
            'blocks' => $blocks,
            'page'   => 'landing',
            'wrap'   => ['*' => 'w-full flex flex-col items-center'],
        ])
    </div>

    @include('partials/landing-footer')

    @include('partials/whatsapp-float')
    @include('partials/demo-badge')
</body>
</html>
