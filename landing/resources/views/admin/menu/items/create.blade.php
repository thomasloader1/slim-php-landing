@extends('admin/layout')

@section('title', 'Nuevo Ítem')
@section('header', 'Nuevo Ítem')
@section('subheader', 'Agregá un plato o producto a la carta.')

@section('content')
<div class="max-w-2xl">
    <form action="{{ url('admin/menu/items/create') }}" method="POST" enctype="multipart/form-data">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white">Datos del Ítem</h3>
            </div>
            <div class="p-6 space-y-5">

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Título <span class="text-red-400">*</span></label>
                    <input type="text" name="title" required
                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                           placeholder="Ej: Pizza Margherita, Limonada, Brownie...">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Precio <span class="text-red-400">*</span></label>
                        <input type="number" name="price" min="0" step="0.01" value="0.00" required
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-300 mb-2">Sección</label>
                        <select name="section_id"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition-colors">
                            <option value="">Sin sección</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Descripción</label>
                    <textarea name="description" rows="3"
                              class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors resize-none"
                              placeholder="Ingredientes, preparación, alérgenos..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Imagen del plato</label>
                    <input type="file" name="image_file" accept="image/*" id="image_file_input"
                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-slate-400 focus:outline-none focus:border-amber-500 transition-colors file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-500/10 file:text-amber-400">
                    <p class="text-xs text-slate-500 mt-1">JPG, PNG o WebP. Opcional.</p>
                    <div id="image_preview" class="mt-3 hidden">
                        <img id="preview_img" src="" alt="Vista previa" class="w-32 h-24 object-cover rounded-xl border border-slate-700">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="active" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                    <span class="text-sm text-slate-300">Ítem activo</span>
                </div>

            </div>
            <div class="p-6 border-t border-slate-800 flex gap-3">
                <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-6 py-2.5 rounded-xl transition-all">
                    Guardar Ítem
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
</script>
@endpush
