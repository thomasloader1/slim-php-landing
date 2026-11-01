{{--
    Editor rich text de la biografía (Quill 1.3.7, servido localmente desde public/assets).
    Envía el HTML en el campo "landing_bio"; el servidor lo sanitiza (App\Support\HtmlSanitizer).
    Variables: $value (HTML guardado)
--}}
@php $bioHtml = \App\Support\HtmlSanitizer::bio($value ?? ''); @endphp
<div class="bio-editor-wrap">
    <div class="js-bio-editor">{!! $bioHtml !!}</div>
    <textarea name="landing_bio" class="js-bio-input hidden" aria-hidden="true" tabindex="-1">{{ $bioHtml }}</textarea>
</div>

@php
    // Assets una sola vez por página (Blade @once requiere ramsey/uuid, que no está instalado)
    $__bioAssetsPending = empty($GLOBALS['__bio_editor_assets']);
    $GLOBALS['__bio_editor_assets'] = true;
@endphp
@if($__bioAssetsPending)
@push('scripts')
<link rel="stylesheet" href="{{ url('assets/quill-1.3.7/quill.snow.css') }}">
<style>
    /* Quill adaptado al tema oscuro del admin */
    .bio-editor-wrap { border: 1px solid #1e293b; border-radius: 0.75rem; overflow: visible; background: #020617; }
    .bio-editor-wrap .ql-toolbar.ql-snow { border: 0; border-bottom: 1px solid #1e293b; background: #0f172a; border-radius: 0.75rem 0.75rem 0 0; padding: 6px 8px; }
    .bio-editor-wrap .ql-container.ql-snow { border: 0; font-family: inherit; font-size: 0.95rem; }
    .bio-editor-wrap .ql-editor { min-height: 140px; color: #fff; line-height: 1.6; }
    .bio-editor-wrap .ql-editor.ql-blank::before { color: #475569; font-style: normal; }
    .bio-editor-wrap .ql-snow .ql-stroke { stroke: #94a3b8; }
    .bio-editor-wrap .ql-snow .ql-fill { fill: #94a3b8; }
    .bio-editor-wrap .ql-snow button:hover .ql-stroke, .bio-editor-wrap .ql-snow button.ql-active .ql-stroke { stroke: #60a5fa; }
    .bio-editor-wrap .ql-snow button:hover .ql-fill, .bio-editor-wrap .ql-snow button.ql-active .ql-fill { fill: #60a5fa; }
    .bio-editor-wrap .ql-snow .ql-tooltip { background: #0f172a; border-color: #334155; color: #cbd5e1; box-shadow: 0 10px 25px rgba(0,0,0,.5); border-radius: 0.5rem; z-index: 30; }
    .bio-editor-wrap .ql-snow .ql-tooltip input[type=text] { background: #020617; border-color: #334155; color: #fff; border-radius: 0.375rem; }
    .bio-editor-wrap .ql-snow a { color: #60a5fa; }
</style>
<script src="{{ url('assets/quill-1.3.7/quill.min.js') }}"></script>
<script>
(function () {
    document.querySelectorAll('.bio-editor-wrap').forEach(function (wrap) {
        var el = wrap.querySelector('.js-bio-editor');
        var input = wrap.querySelector('.js-bio-input');
        var quill = new Quill(el, {
            theme: 'snow',
            placeholder: 'Contá quién sos o qué hace tu negocio...',
            formats: ['bold', 'italic', 'list', 'link'],
            modules: { toolbar: [['bold', 'italic'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] }
        });
        function sync() {
            input.value = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
        }
        quill.on('text-change', function () {
            sync();
            // Para formularios que detectan cambios sin guardar (ej: UX Builder)
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
        var form = wrap.closest('form');
        if (form) form.addEventListener('submit', sync);
    });
})();
</script>
@endpush
@endif
