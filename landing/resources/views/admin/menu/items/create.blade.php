@extends('admin/layout')

@section('title', 'Nuevo Producto')
@section('header', 'Nuevo Producto')
@section('subheader', 'Agregá un producto a tu catálogo.')

@section('content')
    <form action="{{ url('admin/menu/items/create') }}" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            <div class="xl:col-span-2 space-y-6">

            {{-- ── DATOS BÁSICOS ── --}}
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                    <h3 class="text-lg font-bold text-white">Datos del Producto</h3>
                </div>
                <div class="p-6 space-y-5">

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Nombre <span class="text-red-400">*</span></label>
                        <input type="text" name="title" required
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="Ej: Focaccia Simple, Tarta Grande, Buñuelos de Acelga...">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-300 mb-2">Código (SKU)</label>
                            <input type="text" name="sku" maxlength="64"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                                   placeholder="Ej: A-001">
                            <p class="text-xs text-slate-500 mt-1">Opcional. Se usa para actualizar desde Excel.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-300 mb-2">Stock</label>
                            <input type="number" name="stock" min="0" step="1" max="999999999"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                                   placeholder="Sin control">
                            <p class="text-xs text-slate-500 mt-1">Vacío = no se controla. 0 = "Sin stock".</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Categoría</label>
                        <select name="section_id"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition-colors">
                            <option value="">Sin categoría</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Descripción</label>
                        <textarea name="description" rows="2"
                                  class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors resize-none"
                                  placeholder="Ingredientes, preparación, alérgenos..."></textarea>
                    </div>

                </div>
            </div>

            {{-- ── PRECIOS ── --}}
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-tag text-amber-400 text-base"></i> Precios
                    </h3>
                    <p class="text-slate-500 text-sm mt-0.5">Podés definir hasta 3 precios (ej: Chica / Grande / Mediana).</p>
                </div>
                <div class="p-6 space-y-4">

                    {{-- Precio 1 --}}
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Precio principal</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Etiqueta (tamaño)</label>
                                <input type="text" name="price_label_1"
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                                       placeholder="Ej: Chica (opcional)">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Precio <span class="text-red-400">*</span></label>
                                <input type="number" name="price" min="0" step="0.01" value="0" required
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors">
                            </div>
                        </div>
                    </div>

                    {{-- Precio 2 --}}
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Precio 2 <span class="text-slate-600 font-normal">(opcional)</span></p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Etiqueta</label>
                                <input type="text" name="price_label_2"
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                                       placeholder="Ej: Grande">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Precio</label>
                                <input type="number" name="price_2" min="0" step="0.01"
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors"
                                       placeholder="Vacío = no mostrar">
                            </div>
                        </div>
                    </div>

                    {{-- Precio 3 --}}
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Precio 3 <span class="text-slate-600 font-normal">(opcional)</span></p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Etiqueta</label>
                                <input type="text" name="price_label_3"
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                                       placeholder="Ej: Mediana">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Precio</label>
                                <input type="number" name="price_3" min="0" step="0.01"
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors"
                                       placeholder="Vacío = no mostrar">
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-800">

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Unidad de venta</label>
                        <input type="text" name="unit"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="Ej: c/u · docena · kg · porción">
                        <p class="text-xs text-slate-500 mt-1">Se muestra junto al precio. Ej: "$6.500 c/u" o "$35.000 docena".</p>
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
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Sabores / Variedades</label>
                        <input type="text" name="flavors"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="Acelga y bechamel, Calabaza y champiñones, Espinaca, Caprese">
                        <p class="text-xs text-slate-500 mt-1">Separados por coma. Se muestran como chips debajo de la descripción.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Nota de regalo / promoción</label>
                        <input type="text" name="gift_note"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                               placeholder="Ej: con salsa de regalo · incluye guarnición">
                        <p class="text-xs text-slate-500 mt-1">Se muestra como chip verde. Opcional.</p>
                    </div>

                </div>
            </div>

            </div>

            <aside class="space-y-6 xl:sticky xl:top-6">

                {{-- ── FOTO ── --}}
                <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-image text-amber-400 text-base"></i> Foto
                        </h3>
                    </div>
                    <div class="p-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-300 mb-2">Foto del producto</label>
                            <input type="file" name="image_file" accept=".png,.jpg,.jpeg,.gif,.webp" data-max-mb="2" id="image_file_input"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-slate-400 focus:outline-none focus:border-amber-500 transition-colors file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-500/10 file:text-amber-400">
                            <p class="text-xs text-slate-500 mt-1">Subí un archivo (JPG, PNG o WebP, máx 2 MB) o pegá un link. Opcional.</p>
                            <input type="url" name="image_link" maxlength="500"
                                   class="mt-2 w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                                   placeholder="https://… (link a la foto)">
                            <div id="image_preview" class="mt-3 hidden">
                                <img id="preview_img" src="" alt="Vista previa" class="w-32 h-24 object-cover rounded-xl border border-slate-700">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── VISIBILIDAD ── --}}
                <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-eye text-amber-400 text-base"></i> Visibilidad
                        </h3>
                    </div>
                    <div class="p-6 space-y-5">
                        <div>
                            <label class="block text-sm font-bold text-slate-300 mb-2">Insignia especial</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach([
                                    'none'     => ['Sin insignia', 'text-slate-500', 'border-slate-700'],
                                    'premium'  => ['Premium', 'text-amber-300', 'border-amber-500/30'],
                                    'preorder' => ['Pedido previo', 'text-orange-300', 'border-orange-500/30'],
                                    'special'  => ['Especial', 'text-purple-300', 'border-purple-500/30'],
                                ] as $val => [$lbl, $textCls, $borderCls])
                                <label class="flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-all hover:bg-slate-800 {{ $val === 'none' ? 'bg-slate-800 '.$borderCls : $borderCls }}">
                                    <input type="radio" name="badge_type" value="{{ $val }}" {{ $val === 'none' ? 'checked' : '' }}
                                           class="sr-only">
                                    <span class="text-sm font-medium {{ $textCls }}">{{ $lbl }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="active" value="1" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                            </label>
                            <span class="text-sm text-slate-300">Producto activo</span>
                        </div>
                    </div>
                </div>

                {{-- ── ACCIONES ── --}}

            </aside>
        </div>

        {{-- Barra de guardado: queda visible al hacer scroll --}}
        <div class="sticky bottom-0 z-20 -mx-4 sm:-mx-6 lg:-mx-10 px-4 sm:px-6 lg:px-10 py-4 bg-slate-950/90 backdrop-blur border-t border-slate-800 flex justify-end gap-3">
            <a href="{{ url('admin/menu/items') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-6 py-2.5 rounded-xl transition-all">
                Cancelar
            </a>
            <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-6 py-2.5 rounded-xl transition-all">
                Guardar Producto
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    /* Preview imagen */
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

    /* Radio badge_type → estilos visuales */
    var radios = document.querySelectorAll('[name="badge_type"]');
    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            radios.forEach(function (rb) {
                var lbl = rb.closest('label');
                lbl.classList.remove('ring-2', 'ring-amber-400', 'bg-slate-700');
            });
            this.closest('label').classList.add('ring-2', 'ring-amber-400', 'bg-slate-700');
        });
    });
    // Marcar inicial
    var checked = document.querySelector('[name="badge_type"]:checked');
    if (checked) checked.closest('label').classList.add('ring-2', 'ring-amber-400', 'bg-slate-700');
})();
</script>
@endpush
