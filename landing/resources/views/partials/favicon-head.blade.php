{{-- Favicon tags — incluir en el <head> del layout principal --}}
@php
    $__faviconVersion = $settings['favicon_version'] ?? '';
    $__hasFavicons    = !empty($__faviconVersion);
    $__v              = $__hasFavicons ? '?v=' . $__faviconVersion : '';
@endphp

@if($__hasFavicons)
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('favicons/favicon-32x32.png') }}{{ $__v }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('favicons/favicon-16x16.png') }}{{ $__v }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('favicons/apple-touch-icon.png') }}{{ $__v }}">
    <link rel="manifest" href="{{ url('favicons/site.webmanifest') }}{{ $__v }}">
@elseif(!empty($settings['landing_favicon_url']))
    <link rel="icon" href="{{ $settings['landing_favicon_url'] }}">
@endif
