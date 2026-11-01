@extends('admin/layout')

@section('title', 'UX Builder')
@section('header', 'UX Builder')
@section('subheader', 'Ordená las secciones, editá los textos y sumá componentes a tu landing.')

@section('content')
@php
    $input  = 'w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-blue-500/50 transition-all';
    $label  = 'block text-xs font-medium text-slate-400 mb-1.5';
    $waOn   = ($settings['whatsapp_enabled'] ?? '0') === '1';
@endphp

<form id="builder-form" action="{{ url('admin/builder') }}" method="POST"
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
                    <input type="tel" name="whatsapp_number" id="wa-number" maxlength="30" value="{{ $settings['whatsapp_number'] ?? '' }}"
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
                    <a href="{{ url('/') }}" target="_blank" class="p-2 rounded-lg text-slate-400 hover:bg-slate-800" title="Abrir en otra pestaña"><i class="fa-solid fa-up-right-from-square"></i></a>
                </div>
            </div>
            <div class="mx-auto rounded-[2rem] border-4 border-slate-800 overflow-hidden bg-slate-950" style="width: 100%; max-width: 375px; height: 700px;">
                <iframe id="preview-frame" src="{{ url('/') }}" title="Vista previa de la landing" class="w-full h-full" style="border:0;"></iframe>
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
            first.addEventListener('input', function () { summary.textContent = first.value; });
        }
    }
    list.querySelectorAll('.block-item').forEach(bindBlock);

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
            bindBlock(item);
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
            item.querySelectorAll('[data-field]').forEach(function (f) { data[f.dataset.field] = f.value; });
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

    /* WhatsApp: mostrar campos + vista previa del enlace */
    var waEnabled = document.getElementById('wa-enabled');
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

    /* Vista previa */
    document.getElementById('preview-reload').addEventListener('click', function () {
        var frame = document.getElementById('preview-frame');
        frame.src = frame.src.split('?')[0] + '?t=' + Date.now();
    });
})();
</script>
@endpush
@endsection
