@extends('admin/layout')

@section('title', 'Editar Ítem')
@section('header', 'Editar Ítem')
@section('subheader', 'Modificá los datos de este plato o producto.')

@section('content')
<div class="max-w-2xl">
    <form action="{{ url('admin/menu/items/edit/' . $item->id) }}" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="space-y-5">

            {{-- ── DATOS BÁSICOS ── --}}
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                    <h3 class="text-lg font-bold text-white">Datos del Ítem</h3>
                </div>
                <div class="p-6 space-y-5">

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Título <span class="text-red-400">*</span></label>
                        <input type="text" name="title" required value="{{ $item->title }}"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Sección</label>
                        <select name="section_id"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition-colors">
                            <option value="">Sin sección</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" {{ $item->section_id == $section->id ? 'selected' : '' }}>
                                    {{ $section->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Descripción</label>
                        <textarea name="description" rows="2"
                                  class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors resize-none"
                                  placeholder="Ingredientes, preparación, alérgenos...">{{ $item->description }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Insignia especial</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach([
                                'none'     => ['Sin insignia', 'text-slate-500', 'border-slate-700'],
                                'premium'  => ['Premium', 'text-amber-300', 'border-amber-500/30'],
                                'preorder' => ['Pedido previo', 'text-orange-300', 'border-orange-500/30'],
                                'special'  => ['Especial', 'text-purple-300', 'border-purple-500/30'],
                            ] as $val => [$lbl, $textCls, $borderCls])
                            <label class="badge-radio flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-all hover:bg-slate-800 {{ $borderCls }} {{ ($item->badge_type ?? 'none') === $val ? 'ring-2 ring-amber-400 bg-slate-700' : '' }}">
                                <input type="radio" name="badge_type" value="{{ $val }}"
                                       {{ ($item->badge_type ?? 'none') === $val ? 'checked' : '' }}
                                       class="sr-only">
                                <span class="text-sm font-medium {{ $textCls }}">{{ $lbl }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="active" value="1" class="sr-only peer" {{ $item->active ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                        <span class="text-sm text-slate-300">Ítem activo</span>
                    </div>

                </div>
            </div>

            {{-- ── PRECIOS ── --}}
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-tag text-amber-400 text-base"></i> Precios
                    </h3>
                </div>
                <div class="p-6 space-y-4">

                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Precio principal</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Etiqueta</label>
                                <input type="text" name="price_label_1" value="{{ $item->price_label_1 ?? '' }}"
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                                       placeholder="Ej: Chica (opcional)">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Precio <span class="text-red-400">*</span></label>
                                <input type="number" name="price" min="0" step="0.01" value="{{ $item->price }}" required
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors">
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Precio 2 <span class="text-slate-600 font-normal">(opcional)</span></p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Etiqueta</label>
                                <input type="text" name="price_label_2" value="{{ $item->price_label_2 ?? '' }}"
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                                       placeholder="Ej: Grande">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Precio</label>
                                <input type="number" name="price_2" min="0" step="0.01"
                                       value="{{ $item->price_2 ?? '' }}"
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors"
                                       placeholder="Vacío = no mostrar">
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Precio 3 <span class="text-slate-600 font-normal">(opcional)</span></p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Etiqueta</label>
                                <input type="text" name="price_label_3" value="{{ $item->price_label_3 ?? '' }}"
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                                       placeholder="Ej: Mediana">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Precio</label>
                                <input type="number" name="price_3" min="0" step="0.01"
                                       value="{{ $item->price_3 ?? '' }}"
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors"
                                       placeholder="Vacío = no mostrar">
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-800">

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Unidad de venta</label>
                        <input type="text" name="unit" value="{{ $item->unit ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="Ej: c/u · docena · kg · porción">
                    </div>

                </div>
            </div>

            {{-- ── EXTRAS ── --}}
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-circle-plus text-amber-400 text-base"></i> Extras
                    </h3>
                </div>
                <div class="p-6 space-y-5">

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Sabores / Variedades</label>
                        <input type="text" name="flavors" value="{{ $item->flavors ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="Acelga y bechamel, Calabaza y champiñones, Espinaca, Caprese">
                        <p class="text-xs text-slate-500 mt-1">Separados por coma.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Nota de regalo / promoción</label>
                        <input type="text" name="gift_note" value="{{ $item->gift_note ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="Ej: con salsa de regalo · incluye guarnición">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Imagen del plato</label>
                        @if($item->image_url)
                            <div class="mb-3 flex items-start gap-4">
                                <img src="{{ $item->image_url }}" alt="{{ $item->title }}"
                                     class="w-32 h-24 object-cover rounded-xl border border-slate-700">
                                <div class="flex items-center gap-2 mt-2">
                                    <input type="checkbox" name="clear_image" value="1" id="clear_image"
                                           class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-red-500 focus:ring-0">
                                    <label for="clear_image" class="text-sm text-slate-400 cursor-pointer">Quitar imagen actual</label>
                                </div>
                            </div>
                        @endif
                        <input type="file" name="image_file" accept="image/*" id="image_file_input"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-slate-400 focus:outline-none focus:border-amber-500 transition-colors file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-500/10 file:text-amber-400">
                        <p class="text-xs text-slate-500 mt-1">Subí una imagen nueva para reemplazar la actual. Opcional.</p>
                        <div id="image_preview" class="mt-3 hidden">
                            <img id="preview_img" src="" alt="Vista previa" class="w-32 h-24 object-cover rounded-xl border border-slate-700">
                        </div>
                    </div>

                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-6 py-2.5 rounded-xl transition-all">
                    Guardar Cambios
                </button>
                <a href="{{ url('admin/menu/items') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-6 py-2.5 rounded-xl transition-all">
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
    document.getElementById('image_file_input').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('preview_img').src = e.target.result;
            document.getElementById('image_preview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });

    var radios = document.querySelectorAll('[name="badge_type"]');
    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            document.querySelectorAll('.badge-radio').forEach(function (lbl) {
                lbl.classList.remove('ring-2', 'ring-amber-400', 'bg-slate-700');
            });
            this.closest('label').classList.add('ring-2', 'ring-amber-400', 'bg-slate-700');
        });
    });
})();
</script>
@endpush
