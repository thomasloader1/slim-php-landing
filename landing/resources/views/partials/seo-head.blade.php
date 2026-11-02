{{--
    SEO meta tags compartido (landing y shop).
    Recibe: $settings, $pageTitle, $pageUrl (absoluta), $pageDescription (opcional),
            $pageImage (opcional, ruta o URL; default og image / avatar).
--}}
@php
    $__desc = seo_text(($pageDescription ?? '') ?: ($settings['seo_description'] ?? ''), 160);
    $__title = ($pageTitle ?? '') ?: ($settings['site_name'] ?? '');
    $__img = absolute_url(($pageImage ?? '') ?: (($settings['seo_og_image'] ?? '') ?: ($settings['landing_avatar_url'] ?? '')), $settings);
    $__handle = ltrim(trim((string) ($settings['seo_twitter_handle'] ?? '')), '@');
    $__indexable = seo_indexable($settings);
@endphp

<!-- SEO básico -->
@if($__desc !== '')
<meta name="description" content="{{ $__desc }}">
@endif
@if(!empty($settings['seo_keywords']))
<meta name="keywords"    content="{{ $settings['seo_keywords'] }}">
@endif
@if(($__author = ($settings['seo_author'] ?? '') ?: ($settings['site_name'] ?? '')) !== '')
<meta name="author"      content="{{ $__author }}">
@endif
<meta name="robots"      content="{{ $__indexable ? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' : 'noindex, nofollow' }}">
@if(!empty($pageUrl))
<link rel="canonical" href="{{ $pageUrl }}">
@endif

<!-- Open Graph -->
<meta property="og:type"        content="website">
<meta property="og:title"       content="{{ $__title }}">
@if($__desc !== '')
<meta property="og:description" content="{{ $__desc }}">
@endif
@if($__img !== '')
<meta property="og:image"       content="{{ $__img }}">
<meta property="og:image:alt"   content="{{ $__title }}">
@endif
<meta property="og:locale"      content="{{ $settings['seo_locale'] ?? 'es_AR' }}">
<meta property="og:site_name"   content="{{ $settings['site_name'] ?? '' }}">
@if(!empty($pageUrl))
<meta property="og:url"         content="{{ $pageUrl }}">
@endif

<!-- Twitter Cards -->
<meta name="twitter:card"        content="{{ $__img !== '' ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title"       content="{{ $__title }}">
@if($__desc !== '')
<meta name="twitter:description" content="{{ $__desc }}">
@endif
@if($__img !== '')
<meta name="twitter:image"       content="{{ $__img }}">
@endif
@if($__handle !== '')
<meta name="twitter:creator"     content="{{ '@' . $__handle }}">
@endif

<!-- Favicon -->
@include('partials/favicon-head')
