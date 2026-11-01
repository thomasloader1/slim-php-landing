@php $bioHtml = \App\Support\HtmlSanitizer::bio($settings['landing_bio'] ?? ''); @endphp
@if($bioHtml !== '')
    <div class="text-center px-4">
        <div class="bio-content text-white text-base leading-relaxed max-w-sm mx-auto font-light">{!! $bioHtml !!}</div>
    </div>
@endif
