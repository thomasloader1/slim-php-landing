@extends('admin/layout')

@section('title', 'UX Builder')
@section('header', 'UX Builder')
@section('subheader', $page === 'shop' ? 'Diseñá tu tienda: banners, carrusel, textos y cómo se muestran los productos.' : 'Ordená las secciones, editá los textos y sumá componentes a tu landing.')

@section('content')
@php
    $input  = 'w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-blue-500/50 transition-all';
    $label  = 'block text-xs font-medium text-slate-400 mb-1.5';
    $waOn   = ($settings['whatsapp_enabled'] ?? '0') === '1';
    $previewUrl = url($pages[$page]['path']);
@endphp

{{-- Páginas editables --}}
@if(count($pages) > 1)
<nav class="mb-6 -mx-1 overflow-x-auto" aria-label="Página a diseñar">
    <div class="flex gap-1 px-1 border-b border-slate-800 min-w-max">
        @foreach($pages as $key => $meta)
            <a href="{{ url($key === 'landing' ? 'admin/builder' : 'admin/builder/' . $key) }}" @if($key === $page) aria-current="page" @endif
               class="px-4 py-2.5 text-sm font-bold flex items-center gap-2 border-b-2 -mb-px transition-colors
                      {{ $key === $page ? 'border-blue-500 text-white' : 'border-transparent text-slate-500 hover:text-slate-300' }}">
                <i class="fa-solid {{ $key === 'shop' ? 'fa-store' : 'fa-house' }} text-xs" aria-hidden="true"></i> {{ $meta['label'] }}
            </a>
        @endforeach
    </div>
</nav>
@endif

<div class="mb-6 flex items-start gap-3 bg-blue-500/5 border border-blue-500/20 rounded-2xl px-4 py-3 text-sm text-slate-300">
    <i class="fa-solid fa-palette text-blue-400 mt-0.5" aria-hidden="true"></i>
    <p>Colores, fondo y logo son los mismos para la landing y la tienda. Se cambian en
        <a href="{{ url('admin/settings') }}" class="text-blue-400 hover:text-blue-300 font-semibold">Configuración → Apariencia</a>.</p>
</div>

<form id="builder-form" action="{{ url($page === 'landing' ? 'admin/builder' : 'admin/builder/' . $page) }}" method="POST"
      class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_400px] gap-6 items-start">
    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="layout" id="layout-input">

    <div class="space-y-6 min-w-0">
        {{-- ── Bloques ──────────────────────────────────────────── --}}
        <div class="bg-slate-900/40 border border-slate-800 rounded-3xl p-4 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-white">Estructura de la página</h3>
                    <p class="text-xs text-slate-500">Arrastrá para reordenar. Tocá un bloque para editarlo.</p>
                </div>
                <div class="relative" id="add-menu">
                    <button type="button" id="add-toggle" aria-expanded="false"
                            class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Agregar componente
                    </button>
                    <div id="add-options" class="hidden absolute right-0 mt-2 w-56 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl p-2 z-20">
                        @foreach($blockTypes as $type => $meta)
                            @unless($meta['core'])
                                <button type="button" data-add="{{ $type }}"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-slate-300 hover:bg-slate-800 transition-all text-left">
                                    <i class="fa-solid {{ $meta['icon'] }} w-4 text-blue-400"></i> {{ $meta['label'] }}
                                </button>
                            @endunless
                        @endforeach
                    </div>
                </div>
            </div>

            <ul id="block-list" class="space-y-3">
                @foreach($layout as $block)
                    @include('admin/builder/_block', ['block' => $block])
                @endforeach
            </ul>
        </div>

        @if($page === 'landing')
        {{-- ── WhatsApp flotante ────────────────────────────────── --}}
        <div class="bg-slate-900/40 border border-slate-800 rounded-3xl p-4 sm:p-6 space-y-5">
            <label class="flex items-start gap-4 cursor-pointer group">
                <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700 shrink-0 mt-0.5">
                    <input type="checkbox" name="whatsapp_enabled" value="1" id="wa-enabled" {{ $waOn ? 'checked' : '' }} class="sr-only peer">
                    <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-green-500"></div>
                </div>
                <div>
                    <span class="flex items-center gap-2 text-white font-bold"><i class="fa-brands fa-whatsapp text-green-400 text-lg"></i> Botón flotante de WhatsApp</span>
                    <span class="text-xs text-slate-500">Botón fijo en una esquina de la landing que abre un chat con mensaje pre-cargado.</span>
                </div>
            </label>

            <div id="wa-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4 {{ $waOn ? '' : 'hidden' }}">
                <div>
                    <label class="{{ $label }}">Número (con código de país)</label>
                    <input type="tel" name="whatsapp_number" id="wa-number" maxlength="30" data-required-if="whatsapp_enabled" pattern="[\s+\(\)\-]*(?:\d[\s\(\)\-]*){8,15}" title="Número con código de país, entre 8 y 15 dígitos (ej: 5491155551234)" value="{{ $settings['whatsapp_number'] ?? '' }}"
                           placeholder="+54 9 11 1234-5678" class="{{ $input }} font-mono">
                    <p id="wa-number-hint" class="text-xs text-slate-500 mt-1">Argentina celular: +54 9 + código de área + número.</p>
                </div>
                <div>
                    <label class="{{ $label }}">Texto junto al ícono <span class="text-slate-600">(opcional)</span></label>
                    <input type="text" name="whatsapp_label" maxlength="60" value="{{ $settings['whatsapp_label'] ?? '' }}"
                           placeholder="Ej: ¿Hablamos?" class="{{ $input }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Mensaje pre-cargado</label>
                    <textarea name="whatsapp_message" id="wa-message" rows="3" maxlength="500" class="{{ $input }} resize-y"
                              placeholder="Hola! Te escribo desde la web, quería consultar por...">{{ $settings['whatsapp_message'] ?? '' }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}">Abrir en</label>
                    <select name="whatsapp_open_mode" id="wa-mode" class="{{ $input }}">
                        @foreach(['auto' => 'Automático (WhatsApp Web en PC, app en celular)', 'web' => 'Siempre WhatsApp Web', 'app' => 'Siempre la app (wa.me)'] as $v => $l)
                            <option value="{{ $v }}" {{ ($settings['whatsapp_open_mode'] ?? 'auto') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Posición</label>
                    <select name="whatsapp_position" class="{{ $input }}">
                        @foreach(['right' => 'Abajo a la derecha', 'left' => 'Abajo a la izquierda'] as $v => $l)
                            <option value="{{ $v }}" {{ ($settings['whatsapp_position'] ?? 'right') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 bg-slate-950 border border-slate-800 rounded-xl p-3 flex items-center gap-3">
                    <i class="fa-solid fa-link text-slate-600"></i>
                    <code id="wa-preview-url" class="text-xs text-slate-400 truncate flex-1"></code>
                    <a id="wa-test" href="#" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-green-400 hover:text-green-300 shrink-0">Probar</a>
                </div>
            </div>
        </div>

        @endif

        <div class="flex items-center justify-end gap-3 sticky bottom-0 z-10 -mx-1 px-1 py-3 bg-slate-950/90 backdrop-blur border-t border-slate-800/60">
            <span id="dirty-flag" class="hidden text-xs text-amber-400 bg-slate-950/90 px-3 py-2 rounded-xl border border-amber-500/20">
                <i class="fa-solid fa-circle text-[6px] align-middle mr-1"></i> Cambios sin guardar
            </span>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i> Guardar diseño
            </button>
        </div>
    </div>

    {{-- ── Vista previa ─────────────────────────────────────────── --}}
    <aside class="xl:sticky xl:top-6">
        <div class="bg-slate-900/40 border border-slate-800 rounded-3xl p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-white"><i class="fa-solid fa-mobile-screen mr-2 text-slate-500"></i>Vista previa</h3>
                <div class="flex items-center gap-1">
                    <button type="button" id="preview-reload" class="p-2 rounded-lg text-slate-400 hover:bg-slate-800" title="Recargar"><i class="fa-solid fa-rotate-right"></i></button>
                    <a href="{{ $previewUrl }}" target="_blank" class="p-2 rounded-lg text-slate-400 hover:bg-slate-800" title="Abrir en otra pestaña" aria-label="Abrir la página en otra pestaña"><i class="fa-solid fa-up-right-from-square"></i></a>
                </div>
            </div>
            <div class="mx-auto rounded-[2rem] border-4 border-slate-800 overflow-hidden bg-slate-950" style="width: 100%; max-width: 375px; height: 700px;">
                <iframe id="preview-frame" src="{{ $previewUrl }}" title="Vista previa de {{ $pages[$page]['label'] }}" class="w-full h-full" style="border:0;"></iframe>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 text-center">Muestra la versión guardada. Guardá para ver los cambios.</p>
        </div>
    </aside>
</form>

{{-- Plantillas para componentes nuevos --}}
@foreach($blockTypes as $type => $meta)
    @unless($meta['core'])
        <template id="tpl-{{ $type }}">
            @include('admin/builder/_block', ['block' => ['id' => '__NEW__', 'type' => $type, 'visible' => true, 'data' => []]])
        </template>
    @endunless
@endforeach
<template id="tpl-slide">
    @include('admin/builder/_slide', ['s' => []])
</template>

@push('scripts')
<style>
    .block-item.is-hidden { opacity: .5; }
    .block-item.sortable-ghost { opacity: .3; border-style: dashed; }
</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    var form  = document.getElementById('builder-form');
    var list  = document.getElementById('block-list');
    var dirty = false;

    function markDirty() {
        dirty = true;
        document.getElementById('dirty-flag').classList.remove('hidden');
    }
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });

    Sortable.create(list, { handle: '.drag-handle', animation: 150, onEnd: markDirty });

    /* Carrusel: diapositivas (agregar, quitar, ordenar) */
    var MAX_SLIDES = {{ \App\Services\PageLayoutService::MAX_SLIDES }};
    function renumberSlides(block) {
        var items = block.querySelectorAll('.slide-item');
        items.forEach(function (li, i) { li.querySelector('.slide-number').textContent = 'Diapositiva ' + (i + 1); });
        var add = block.querySelector('.slide-add');
        if (add) { add.disabled = items.length >= MAX_SLIDES; add.classList.toggle('opacity-40', items.length >= MAX_SLIDES); }
        var summary = block.querySelector('.block-summary');
        if (summary) { summary.textContent = items.length + ' diapositiva(s)'; }
    }
    function bindSlides(block) {
        var slidesList = block.querySelector('.slides-list');
        if (!slidesList) return;
        Sortable.create(slidesList, { handle: '.slide-handle', animation: 150, onEnd: function () { renumberSlides(block); markDirty(); } });
        block.querySelector('.slide-add').addEventListener('click', function () {
            if (slidesList.children.length >= MAX_SLIDES) return;
            var li = document.getElementById('tpl-slide').content.firstElementChild.cloneNode(true);
            slidesList.appendChild(li);
            renumberSlides(block);
            li.querySelector('input').focus();
            markDirty();
        });
        slidesList.addEventListener('click', function (e) {
            var rm = e.target.closest('.slide-remove');
            if (rm && confirm('¿Eliminar esta diapositiva?')) { rm.closest('.slide-item').remove(); renumberSlides(block); markDirty(); }
        });
        renumberSlides(block);
    }

    /* Imágenes: subir archivo o pegar link (delegado: sirve para bloques y diapositivas nuevas) */
    var UPLOAD_URL = @json(url('admin/builder/upload'));
    var CSRF = @json($_SESSION['csrf_token'] ?? '');
    function setImage(field, url) {
        var input = field.querySelector('.img-url'), thumb = field.querySelector('.img-thumb');
        input.value = url;
        thumb.src = url;
        thumb.classList.toggle('hidden', !url);
        field.querySelector('.img-empty').classList.toggle('hidden', !!url);
        field.querySelector('.img-clear').classList.toggle('hidden', !url);
        markDirty();
    }
    form.addEventListener('change', function (e) {
        if (!e.target.classList.contains('img-file') || !e.target.files.length) return;
        var field = e.target.closest('.img-field'), status = field.querySelector('.img-status');
        var fd = new FormData();
        fd.append('image', e.target.files[0]);
        status.textContent = 'Subiendo…';
        status.className = 'img-status text-xs text-slate-400';
        fetch(UPLOAD_URL, { method: 'POST', body: fd, headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) throw new Error(res.message || 'No se pudo subir la imagen.');
                setImage(field, res.url);
                status.textContent = 'Imagen subida. Guardá el diseño para publicarla.';
                status.className = 'img-status text-xs text-green-400';
            })
            .catch(function (err) {
                status.textContent = err.message || 'No se pudo subir la imagen.';
                status.className = 'img-status text-xs text-red-400';
            })
            .finally(function () { e.target.value = ''; });
    });
    form.addEventListener('input', function (e) {
        if (e.target.classList.contains('img-url')) {
            var field = e.target.closest('.img-field'), url = e.target.value.trim(), thumb = field.querySelector('.img-thumb');
            thumb.src = url;
            thumb.classList.toggle('hidden', !url);
            field.querySelector('.img-empty').classList.toggle('hidden', !!url);
            field.querySelector('.img-clear').classList.toggle('hidden', !url);
        }
        if (e.target.type === 'range') {
            var out = e.target.parentElement.querySelector('[data-range-out]');
            if (out) out.textContent = e.target.value;
        }
    });
    form.addEventListener('click', function (e) {
        var clear = e.target.closest('.img-clear');
        if (clear) setImage(clear.closest('.img-field'), '');
    });

    function newId() {
        return 'b_' + Math.random().toString(36).slice(2, 12).replace(/[^a-z0-9]/g, '0').padEnd(10, '0');
    }

    function bindBlock(item) {
        var body = item.querySelector('.block-body');
        var toggle = item.querySelector('.block-toggle');
        toggle.addEventListener('click', function () {
            var open = body.classList.toggle('hidden') === false;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        var vis = item.querySelector('.block-visibility');
        vis.addEventListener('click', function () {
            var visible = vis.getAttribute('aria-pressed') !== 'true';
            vis.setAttribute('aria-pressed', visible ? 'true' : 'false');
            vis.querySelector('i').className = 'fa-solid ' + (visible ? 'fa-eye' : 'fa-eye-slash');
            item.classList.toggle('is-hidden', !visible);
            markDirty();
        });

        var rm = item.querySelector('.block-remove');
        if (rm) {
            rm.addEventListener('click', function () {
                if (confirm('¿Eliminar este componente?')) { item.remove(); markDirty(); }
            });
        }

        // Resumen en vivo del contenido del componente
        var summary = item.querySelector('.block-summary');
        var first = item.querySelector('[data-field="text"], [data-field="label"], [data-field="title"]');
        if (first && summary) {
            first.addEventListener('input', function () {
                var tmp = document.createElement('div');
                tmp.innerHTML = first.value; // el bloque Texto guarda HTML: el resumen muestra solo el texto
                summary.textContent = tmp.textContent;
            });
        }
    }
    list.querySelectorAll('.block-item').forEach(function (item) { bindBlock(item); bindSlides(item); });

    /* Agregar componente */
    var addToggle  = document.getElementById('add-toggle');
    var addOptions = document.getElementById('add-options');
    addToggle.addEventListener('click', function () {
        var open = addOptions.classList.toggle('hidden') === false;
        addToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
        if (!document.getElementById('add-menu').contains(e.target)) addOptions.classList.add('hidden');
    });
    addOptions.querySelectorAll('[data-add]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tpl  = document.getElementById('tpl-' + btn.dataset.add);
            var item = tpl.content.firstElementChild.cloneNode(true);
            item.dataset.id = newId();
            list.appendChild(item);
            if (window.initRichEditors) window.initRichEditors(item);
            bindBlock(item);
            bindSlides(item);
            item.querySelector('.block-toggle').click();
            item.scrollIntoView({ behavior: 'smooth', block: 'center' });
            var firstInput = item.querySelector('[data-field]');
            if (firstInput) firstInput.focus();
            addOptions.classList.add('hidden');
            markDirty();
        });
    });

    /* Serializar el layout al guardar */
    form.addEventListener('submit', function () {
        var layout = [];
        list.querySelectorAll('.block-item').forEach(function (item) {
            var data = {};
            item.querySelectorAll('[data-field]').forEach(function (f) {
                data[f.dataset.field] = f.type === 'checkbox' ? f.checked : f.value;
            });
            var slidesList = item.querySelector('.slides-list');
            if (slidesList) {
                data.slides = [];
                slidesList.querySelectorAll('.slide-item').forEach(function (li) {
                    var slide = {};
                    li.querySelectorAll('[data-slide-field]').forEach(function (f) { slide[f.dataset.slideField] = f.value; });
                    data.slides.push(slide);
                });
            }
            layout.push({
                id: item.dataset.id,
                type: item.dataset.type,
                visible: item.querySelector('.block-visibility').getAttribute('aria-pressed') === 'true',
                data: data
            });
        });
        document.getElementById('layout-input').value = JSON.stringify(layout);
        dirty = false;
    });

    /* WhatsApp: mostrar campos + vista previa del enlace (solo en la landing) */
    var waEnabled = document.getElementById('wa-enabled');
    if (waEnabled) {
    var waFields  = document.getElementById('wa-fields');
    var waNumber  = document.getElementById('wa-number');
    var waMessage = document.getElementById('wa-message');
    var waMode    = document.getElementById('wa-mode');
    var waHint    = document.getElementById('wa-number-hint');
    var waHintTxt = waHint.textContent;

    function waUrl() {
        var digits = waNumber.value.replace(/\D+/g, '').replace(/^00/, '');
        var msg = waMessage.value.trim();
        if (waMode.value === 'app') {
            return 'https://wa.me/' + digits + (msg ? '?text=' + encodeURIComponent(msg) : '');
        }
        return 'https://web.whatsapp.com/send?phone=' + digits + (msg ? '&text=' + encodeURIComponent(msg) : '');
    }
    function refreshWa() {
        waFields.classList.toggle('hidden', !waEnabled.checked);
        var digits = waNumber.value.replace(/\D+/g, '').replace(/^00/, '');
        var valid = digits.length >= 8 && digits.length <= 15;
        // Sin "+" / "00" y con 10 dígitos o menos casi seguro falta el código de país
        // (ej: "11 5501 1250" abriría un número de EE.UU. +1)
        var noCountry = valid && !/^\s*(\+|00)/.test(waNumber.value) && digits.length <= 10;
        waHint.textContent = waNumber.value && !valid
            ? 'Número inválido: debe tener entre 8 y 15 dígitos incluyendo el código de país.'
            : (noCountry ? 'Parece que falta el código de país. Para Argentina: +54 9 y el número sin 0 ni 15 (ej: +54 9 11 5501-1250).' : waHintTxt);
        waHint.classList.toggle('text-red-400', !!waNumber.value && !valid);
        waHint.classList.toggle('text-amber-400', noCountry);
        var url = waUrl();
        document.getElementById('wa-preview-url').textContent = valid ? url : '—';
        document.getElementById('wa-test').href = valid ? url : '#';
    }
    [waEnabled, waNumber, waMessage, waMode].forEach(function (el) {
        el.addEventListener('input', refreshWa);
        el.addEventListener('change', refreshWa);
    });
    refreshWa();
    }

    /* Vista previa */
    document.getElementById('preview-reload').addEventListener('click', function () {
        var frame = document.getElementById('preview-frame');
        frame.src = frame.src.split('?')[0] + '?t=' + Date.now();
    });
})();
</script>
@endpush
@endsection
