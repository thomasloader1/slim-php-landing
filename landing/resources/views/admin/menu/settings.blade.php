@extends('admin/layout')

@section('title', 'Configuración del Menú')
@section('header', 'Configuración del Menú')
@section('subheader', 'Activá, personalizá el diseño y configurá el módulo de carta.')

@section('content')
@php
    $menuEnabled  = ($settings['menu_enabled'] ?? '0') === '1';
    $bgOverlay    = ($settings['menu_bg_overlay'] ?? '0') === '1';
    $showPayment  = ($settings['menu_show_payment'] ?? '0') === '1';
    $hasBgImage   = !empty($settings['menu_bg_image_url']);
@endphp

<div class="flex justify-end mb-4 gap-3 flex-wrap">
    @if($menuEnabled)
        <a href="{{ url('menu') }}" target="_blank"
           class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i> Ver Menú
        </a>
    @endif
    <a href="{{ url('admin/menu/sections') }}"
       class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-layer-group"></i> Secciones
    </a>
    <a href="{{ url('admin/menu/items') }}"
       class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-list"></i> Ítems
    </a>
</div>

@if(!$menuEnabled)
<div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-4 mb-6 flex items-start gap-3">
    <i class="fa-solid fa-circle-info text-amber-400 mt-0.5 flex-shrink-0"></i>
    <p class="text-amber-300 text-sm">
        El menú está <strong>desactivado</strong>. La página <code class="bg-amber-500/20 px-1 rounded">/menu</code> retornará un 404 hasta que lo actives.
    </p>
</div>
@endif

<form action="{{ url('admin/menu/settings') }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-2xl">

    {{-- ── MÓDULO ──────────────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-800 bg-slate-900/50">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-toggle-on text-amber-400"></i> Módulo
            </h3>
        </div>
        <div class="p-6 space-y-5">

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-white font-bold text-sm">Activar página pública</p>
                    <p class="text-slate-500 text-xs mt-0.5">Hace visible la carta en <code class="bg-slate-800 px-1 rounded">/menu</code></p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="menu_enabled" value="1" class="sr-only peer" {{ $menuEnabled ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </div>

            <hr class="border-slate-800">

            <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">Nombre del negocio / Marca</label>
                <input type="text" name="menu_brand_name"
                       value="{{ $settings['menu_brand_name'] ?? '' }}"
                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                       placeholder="Ej: Focaccia & Co">
                <p class="text-xs text-slate-500 mt-1">Se muestra como título principal en el encabezado de la carta.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">Subtítulo del encabezado</label>
                <input type="text" name="menu_brand_subtitle"
                       value="{{ $settings['menu_brand_subtitle'] ?? '' }}"
                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                       placeholder="Ej: Lista de Precios · Enero 2026">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">Texto de cabecera (alternativo)</label>
                <input type="text" name="menu_header_text"
                       value="{{ $settings['menu_header_text'] ?? 'Nuestra Carta' }}"
                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                       placeholder="Nuestra Carta">
                <p class="text-xs text-slate-500 mt-1">Se usa si no se configura "Nombre del negocio".</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">Texto de pie de página</label>
                <textarea name="menu_footer_text" rows="2"
                          class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors resize-none"
                          placeholder="Ej: Los precios incluyen IVA. Consulte por alérgenos.">{{ $settings['menu_footer_text'] ?? '' }}</textarea>
            </div>

        </div>
    </div>

    {{-- ── DISEÑO ──────────────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-800 bg-slate-900/50">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-palette text-amber-400"></i> Diseño y Colores
            </h3>
        </div>
        <div class="p-6 space-y-5">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Color de acento</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="menu_accent_color"
                               value="{{ $settings['menu_accent_color'] ?? '#fec771' }}"
                               class="w-12 h-10 rounded-lg border border-slate-700 bg-slate-950 cursor-pointer p-1">
                        <input type="text" id="accent_hex"
                               value="{{ $settings['menu_accent_color'] ?? '#fec771' }}"
                               class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm font-mono focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="#fec771" maxlength="7">
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Badges de precio y detalles.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Color de fondo</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="menu_bg_color"
                               value="{{ $settings['menu_bg_color'] ?? '#1a1208' }}"
                               class="w-12 h-10 rounded-lg border border-slate-700 bg-slate-950 cursor-pointer p-1">
                        <input type="text" id="bg_hex"
                               value="{{ $settings['menu_bg_color'] ?? '#1a1208' }}"
                               class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm font-mono focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="#1a1208" maxlength="7">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Color de texto</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="menu_text_color"
                               value="{{ $settings['menu_text_color'] ?? '#f5efe0' }}"
                               class="w-12 h-10 rounded-lg border border-slate-700 bg-slate-950 cursor-pointer p-1">
                        <input type="text" id="text_hex"
                               value="{{ $settings['menu_text_color'] ?? '#f5efe0' }}"
                               class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm font-mono focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="#f5efe0" maxlength="7">
                    </div>
                </div>
            </div>

            {{-- Preview en vivo --}}
            <div id="color_preview"
                 class="rounded-2xl p-5 border border-slate-700 transition-all duration-300 flex items-center justify-between gap-4">
                <div>
                    <p id="prev_title" class="font-bold text-lg">Focaccia & Co</p>
                    <p id="prev_sub" class="text-sm opacity-60">Vista previa de colores</p>
                </div>
                <span id="prev_badge" class="text-sm font-bold px-3 py-1.5 rounded-xl">$10.000</span>
            </div>

        </div>
    </div>

    {{-- ── IMAGEN DE FONDO ─────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-800 bg-slate-900/50">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-image text-amber-400"></i> Imagen de Fondo
            </h3>
        </div>
        <div class="p-6 space-y-5">

            @if($hasBgImage)
                <div class="relative rounded-2xl overflow-hidden border border-slate-700">
                    <img src="{{ $settings['menu_bg_image_url'] }}" alt="Fondo actual"
                         class="w-full h-40 object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 to-transparent flex items-end p-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="clear_menu_bg_image" value="1" id="clear_bg"
                                   class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-red-500 focus:ring-0">
                            <label for="clear_bg" class="text-sm text-white cursor-pointer font-medium">Quitar imagen actual</label>
                        </div>
                    </div>
                </div>
            @endif

            <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">{{ $hasBgImage ? 'Reemplazar imagen' : 'Subir imagen de fondo' }}</label>
                <input type="file" name="menu_bg_image_file" accept="image/*" id="bg_file_input"
                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-slate-400 focus:outline-none focus:border-amber-500 transition-colors file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-500/10 file:text-amber-400">
                <p class="text-xs text-slate-500 mt-1">JPG, PNG o WebP. Máx 4 MB. Se redimensiona a 1920×1080.</p>
                <div id="bg_preview" class="mt-3 hidden">
                    <img id="bg_preview_img" src="" alt="Vista previa" class="w-full h-40 object-cover rounded-xl border border-slate-700">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-white font-bold text-sm">Overlay oscuro sobre la imagen</p>
                    <p class="text-slate-500 text-xs mt-0.5">Mejora la legibilidad del texto.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="menu_bg_overlay" value="1" class="sr-only peer" id="overlay_check" {{ $bgOverlay ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </div>

            <div id="opacity_row" class="{{ !$bgOverlay ? 'opacity-40 pointer-events-none' : '' }}">
                <label class="block text-sm font-bold text-slate-300 mb-2">
                    Opacidad del overlay: <span id="opacity_val">{{ (int)(($settings['menu_bg_overlay_opacity'] ?? 0.5) * 100) }}%</span>
                </label>
                <input type="range" name="menu_bg_overlay_opacity" min="0.1" max="0.95" step="0.05"
                       value="{{ $settings['menu_bg_overlay_opacity'] ?? '0.5' }}"
                       id="opacity_slider"
                       class="w-full accent-amber-400">
            </div>

        </div>
    </div>

    {{-- ── PAGOS ───────────────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-800 bg-slate-900/50">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-credit-card text-amber-400"></i> Medios de Pago
            </h3>
        </div>
        <div class="p-6 space-y-5">

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-white font-bold text-sm">Mostrar sección de pagos</p>
                    <p class="text-slate-500 text-xs mt-0.5">Aparece al pie del menú con los métodos configurados.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="menu_show_payment" value="1" class="sr-only peer" {{ $showPayment ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">Alias de transferencia</label>
                <input type="text" name="menu_payment_alias"
                       value="{{ $settings['menu_payment_alias'] ?? '' }}"
                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors font-mono"
                       placeholder="Ej: amamoscocinar">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-300 mb-3">Métodos aceptados</label>
                @php
                    $payMethods = array_filter(explode(',', $settings['menu_payment_methods'] ?? 'cash,transfer'));
                @endphp
                <div class="flex flex-wrap gap-3">
                    @foreach(['cash' => ['Efectivo','fa-money-bill-wave'], 'transfer' => ['Transferencia','fa-building-columns'], 'qr' => ['QR / Mercado Pago','fa-qrcode'], 'card' => ['Tarjeta','fa-credit-card']] as $val => [$label, $icon])
                    <label class="flex items-center gap-2 px-4 py-2.5 rounded-xl border cursor-pointer transition-all
                                  {{ in_array($val, $payMethods) ? 'bg-amber-500/10 border-amber-500/40 text-amber-300' : 'border-slate-700 text-slate-400 hover:border-slate-600' }}">
                        <input type="checkbox" name="pay_method_{{ $val }}" value="1"
                               class="hidden" {{ in_array($val, $payMethods) ? 'checked' : '' }}>
                        <i class="fa-solid {{ $icon }} text-sm"></i>
                        <span class="text-sm font-medium">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                {{-- Campo oculto que se construye con JS --}}
                <input type="hidden" name="menu_payment_methods" id="payment_methods_val"
                       value="{{ $settings['menu_payment_methods'] ?? 'cash,transfer' }}">
            </div>

        </div>
    </div>

    {{-- ── GUARDAR ─────────────────────────────────────────────── --}}
    <div class="flex gap-3">
        <button type="submit"
                class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-8 py-3 rounded-xl transition-all">
            Guardar Configuración
        </button>
    </div>

</form>
@endsection

@push('scripts')
<script>
(function () {
    /* ── Sincronizar inputs color ↔ texto hex ── */
    function syncColor(colorInput, textInput, targetName) {
        var nameMap = {
            menu_accent_color: 'accent',
            menu_bg_color:     'bg',
            menu_text_color:   'text'
        };
        colorInput.addEventListener('input', function () {
            textInput.value = this.value;
            updatePreview();
        });
        textInput.addEventListener('input', function () {
            if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
                colorInput.value = this.value;
                updatePreview();
            }
        });
    }

    var accentColor = document.querySelector('[name="menu_accent_color"]');
    var bgColor     = document.querySelector('[name="menu_bg_color"]');
    var textColor   = document.querySelector('[name="menu_text_color"]');
    var accentHex   = document.getElementById('accent_hex');
    var bgHex       = document.getElementById('bg_hex');
    var textHex     = document.getElementById('text_hex');

    if (accentColor && bgColor && textColor) {
        syncColor(accentColor, accentHex);
        syncColor(bgColor,     bgHex);
        syncColor(textColor,   textHex);

        /* Sincronizar hex → color input también */
        [accentHex, bgHex, textHex].forEach(function (inp) {
            inp.addEventListener('input', function () {
                if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
                    var name = this.id.replace('_hex', '');
                    var sel  = { accent: accentColor, bg: bgColor, text: textColor }[name];
                    if (sel) sel.value = this.value;
                    updatePreview();
                }
            });
        });
    }

    function updatePreview() {
        var prev  = document.getElementById('color_preview');
        var title = document.getElementById('prev_title');
        var sub   = document.getElementById('prev_sub');
        var badge = document.getElementById('prev_badge');
        if (!prev) return;
        var bg     = bgColor    ? bgColor.value     : '#1a1208';
        var accent = accentColor ? accentColor.value : '#fec771';
        var txt    = textColor  ? textColor.value   : '#f5efe0';
        prev.style.background  = bg;
        title.style.color      = txt;
        sub.style.color        = txt;
        badge.style.color      = accent;
        badge.style.background = accent + '22';
    }
    updatePreview();

    /* ── Overlay toggle ── */
    var overlayCheck  = document.getElementById('overlay_check');
    var opacityRow    = document.getElementById('opacity_row');
    var opacitySlider = document.getElementById('opacity_slider');
    var opacityVal    = document.getElementById('opacity_val');

    if (overlayCheck) {
        overlayCheck.addEventListener('change', function () {
            opacityRow.classList.toggle('opacity-40',            !this.checked);
            opacityRow.classList.toggle('pointer-events-none',   !this.checked);
        });
    }
    if (opacitySlider && opacityVal) {
        opacitySlider.addEventListener('input', function () {
            opacityVal.textContent = Math.round(this.value * 100) + '%';
        });
    }

    /* ── Preview imagen de fondo ── */
    var bgFileInput = document.getElementById('bg_file_input');
    if (bgFileInput) {
        bgFileInput.addEventListener('change', function () {
            var file = this.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('bg_preview_img').src = e.target.result;
                document.getElementById('bg_preview').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });
    }

    /* ── Checkboxes de métodos de pago → campo oculto ── */
    var payCheckboxes = document.querySelectorAll('[name^="pay_method_"]');
    var payHidden     = document.getElementById('payment_methods_val');

    function updatePayMethods() {
        var selected = [];
        payCheckboxes.forEach(function (cb) {
            if (cb.checked) {
                selected.push(cb.name.replace('pay_method_', ''));
            }
        });
        payHidden.value = selected.join(',');
    }

    payCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            var label = this.closest('label');
            if (this.checked) {
                label.classList.add('bg-amber-500/10', 'border-amber-500/40', 'text-amber-300');
                label.classList.remove('border-slate-700', 'text-slate-400');
            } else {
                label.classList.remove('bg-amber-500/10', 'border-amber-500/40', 'text-amber-300');
                label.classList.add('border-slate-700', 'text-slate-400');
            }
            updatePayMethods();
        });
    });
})();
</script>
@endpush
