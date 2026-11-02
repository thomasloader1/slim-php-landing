{{--
    Bloque "Preguntas frecuentes" (UX Builder → landing). Contenido en /admin/seo/faq.
    Se renderiza en HTML plano (<details>) para que buscadores e IA lo lean sin JS;
    el FAQPage JSON-LD solo se emite cuando este bloque está visible.
--}}
@if(!empty($faqItems) && count($faqItems) > 0)
    <div class="w-full">
        <h2 class="text-xl font-bold tracking-tight mb-4 text-center">Preguntas frecuentes</h2>
        <div class="space-y-3">
            @foreach($faqItems as $faq)
                <details class="glass rounded-2xl px-5 py-4 group">
                    <summary class="cursor-pointer list-none flex items-center justify-between gap-3 font-semibold text-sm">
                        <span>{{ $faq->question }}</span>
                        <i class="fa-solid fa-chevron-down text-xs t-subtle transition-transform group-open:rotate-180" aria-hidden="true"></i>
                    </summary>
                    <div class="t-subtle text-sm mt-3 leading-relaxed">{!! nl2br(e(trim(strip_tags((string) $faq->answer)))) !!}</div>
                </details>
            @endforeach
        </div>
    </div>
@endif
