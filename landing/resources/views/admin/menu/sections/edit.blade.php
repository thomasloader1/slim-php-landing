@extends('admin/layout')

@section('title', 'Editar Sección')
@section('header', 'Editar Sección')
@section('subheader', 'Modificá los datos de esta categoría.')

@section('content')
<div class="max-w-xl">
    <form action="{{ url('admin/menu/sections/edit/' . $section->id) }}" method="POST">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white">Datos de la Sección</h3>
            </div>
            <div class="p-6 space-y-5">

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Nombre <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required value="{{ $section->name }}"
                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                           placeholder="Ej: Entradas, Bebidas, Postres...">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">
                        Ícono <span class="text-slate-500 font-normal">(clase Font Awesome)</span>
                    </label>
                    <div class="flex gap-3 items-center">
                        <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-amber-400 flex-shrink-0">
                            <i class="{{ $section->icon ?? 'fa-solid fa-utensils' }} text-base" id="icon_preview_el"></i>
                        </div>
                        <input type="text" name="icon" id="icon_input"
                               value="{{ $section->icon ?? '' }}"
                               class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors font-mono text-sm"
                               placeholder="fa-solid fa-star">
                    </div>
                    <div class="flex flex-wrap gap-1.5 mt-2" id="quick_icons">
                        @foreach([
                            'fa-solid fa-star'            => 'Estrella',
                            'fa-solid fa-bread-slice'     => 'Pan',
                            'fa-solid fa-pizza-slice'     => 'Pizza',
                            'fa-solid fa-gem'             => 'Premium',
                            'fa-solid fa-cookie-bite'     => 'Repostería',
                            'fa-solid fa-circle-half-stroke' => 'Tarta',
                            'fa-solid fa-bowl-food'       => 'Plato',
                            'fa-solid fa-jar'             => 'Frasco',
                            'fa-solid fa-leaf'            => 'Vegetariano',
                            'fa-solid fa-fire'            => 'Picante',
                            'fa-solid fa-glass-water'     => 'Bebida',
                            'fa-solid fa-cake-candles'    => 'Torta',
                        ] as $cls => $lbl)
                        <button type="button"
                                class="quick-icon px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-400 hover:text-amber-400 transition-colors text-xs flex items-center gap-1.5"
                                data-icon="{{ $cls }}" title="{{ $lbl }}">
                            <i class="{{ $cls }} text-sm"></i> {{ $lbl }}
                        </button>
                        @endforeach
                    </div>
                </div>

                <hr class="border-slate-800">

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Aviso de sección</label>
                    <input type="text" name="note" value="{{ $section->note ?? '' }}"
                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                           placeholder="Ej: Entregas solo los viernes · Pedido con 72hs anticipación">
                    <p class="text-xs text-slate-500 mt-1">Texto corto bajo el título de la sección. Opcional.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Tipo de aviso</label>
                    <select name="note_type"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition-colors">
                        @foreach(['none' => 'Sin aviso', 'info' => 'ℹ️ Informativo', 'preorder' => '🔔 Pedido previo', 'time' => '🕐 Tiempo / Anticipación'] as $val => $lbl)
                            <option value="{{ $val }}" {{ ($section->note_type ?? 'none') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="active" value="1" class="sr-only peer" {{ $section->active ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                    <span class="text-sm text-slate-300">Sección activa</span>
                </div>

            </div>
            <div class="p-6 border-t border-slate-800 flex gap-3">
                <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-6 py-2.5 rounded-xl transition-all">
                    Guardar Cambios
                </button>
                <a href="{{ url('admin/menu/sections') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-6 py-2.5 rounded-xl transition-all">
                    Cancelar
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var iconInput = document.getElementById('icon_input');
    var previewEl = document.getElementById('icon_preview_el');
    var quickBtns = document.querySelectorAll('.quick-icon');

    function updatePreview(cls) {
        previewEl.className = (cls || 'fa-solid fa-utensils') + ' text-base';
    }

    if (iconInput) {
        iconInput.addEventListener('input', function () { updatePreview(this.value.trim()); });
    }
    quickBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            iconInput.value = this.dataset.icon;
            updatePreview(this.dataset.icon);
        });
    });
})();
</script>
@endpush
