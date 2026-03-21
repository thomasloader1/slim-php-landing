{{-- SEO meta tags compartido — recibe: $settings, $pageTitle, $pageUrl --}}

<!-- SEO básico -->
<meta name="description" content="{{ $settings['seo_description'] ?? 'Mi perfil digital y enlaces importantes.' }}">
<meta name="keywords"    content="{{ $settings['seo_keywords'] ?? 'perfil, enlaces, bio' }}">
<meta name="author"      content="{{ $settings['seo_author'] ?? $settings['site_name'] ?? 'Landing' }}">
<meta name="robots"      content="{{ ($settings['seo_noindex'] ?? '0') === '1' ? 'noindex, nofollow' : 'index, follow' }}">
@if(!empty($pageUrl))
<link rel="canonical" href="{{ $pageUrl }}">
@endif

<!-- Open Graph -->
<meta property="og:type"        content="website">
<meta property="og:title"       content="{{ $pageTitle ?? $settings['site_name'] ?? '' }}">
<meta property="og:description" content="{{ $settings['seo_description'] ?? '' }}">
<meta property="og:image"       content="{{ !empty($settings['seo_og_image']) ? $settings['seo_og_image'] : ($settings['landing_avatar_url'] ?? '') }}">
<meta property="og:locale"      content="{{ $settings['seo_locale'] ?? 'es_AR' }}">
<meta property="og:site_name"   content="{{ $settings['site_name'] ?? '' }}">
@if(!empty($pageUrl))
<meta property="og:url"         content="{{ $pageUrl }}">
@endif

<!-- Twitter Cards -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="{{ $pageTitle ?? $settings['site_name'] ?? '' }}">
<meta name="twitter:description" content="{{ $settings['seo_description'] ?? '' }}">
<meta name="twitter:image"       content="{{ !empty($settings['seo_og_image']) ? $settings['seo_og_image'] : ($settings['landing_avatar_url'] ?? '') }}">
@if(!empty($settings['seo_twitter_handle']))
<meta name="twitter:creator"     content="@{{ $settings['seo_twitter_handle'] }}">
@endif

<!-- Favicon -->
@include('partials/favicon-head')
