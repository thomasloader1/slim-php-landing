@php $textHtml = \App\Support\HtmlSanitizer::bio($data['text'] ?? ''); @endphp
@if($textHtml !== '')
    <div class="bio-content w-full text-{{ $data['align'] ?? 'center' }} text-base leading-relaxed font-light px-2" style="color: var(--text-muted);">{!! $textHtml !!}</div>
@endif
