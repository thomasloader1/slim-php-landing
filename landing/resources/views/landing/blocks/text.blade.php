@if(($data['text'] ?? '') !== '')
    <div class="w-full text-{{ $data['align'] ?? 'center' }} text-white/80 text-base leading-relaxed font-light px-2">{!! nl2br(e($data['text'])) !!}</div>
@endif
