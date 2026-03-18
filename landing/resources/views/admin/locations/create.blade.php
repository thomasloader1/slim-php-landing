@extends('admin/layout')

@section('title', 'Nueva Ubicación')
@section('header', 'Nueva Ubicación')
@section('subheader', 'Agrega una sucursal o punto de encuentro a tu landing.')

@section('content')
<div class="max-w-2xl">
    <form action="{{ url('admin/locations/create') }}" method="POST" class="bg-slate-900 border border-slate-800 p-5 sm:p-8 rounded-3xl shadow-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Nombre de la ubicación</label>
                <input type="text" name="name" required placeholder="Ej: Casa Central"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
            </div>

            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Dirección</label>
                <input type="text" name="address" placeholder="Ej: Av. Corrientes 1234, CABA"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-400 mb-2">Modo de visualización</label>
                <select name="mode" id="location-mode"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                    <option value="embed">Mapa embebido</option>
                    <option value="button">Botón con enlace</option>
                </select>
            </div>

            <div class="col-span-2" id="url-group">
                <label class="block text-sm font-medium text-slate-400 mb-2">URL de Google Maps (para botón)</label>
                <input type="text" name="url" placeholder="https://maps.google.com/..."
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
            </div>

            <div class="col-span-2" id="embed-group">
                <label class="block text-sm font-medium text-slate-400 mb-2">
                    Código de Mapa Embebido
                    <span class="ml-2 text-xs font-normal text-slate-500">(Google Maps &rarr; Compartir &rarr; Insertar mapa &rarr; copiar el &lt;iframe&gt;)</span>
                </label>
                <textarea name="embed_code" rows="4"
                          class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white resize-none font-mono text-xs"
                          placeholder='<iframe src="https://www.google.com/maps/embed?pb=..." ...></iframe>'></textarea>
                <p class="text-[10px] text-slate-500 mt-2 italic">Pegá el código &lt;iframe&gt; completo. Se extraerá automáticamente la URL de embed.</p>
            </div>

            <div class="col-span-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700">
                        <input type="checkbox" name="active" checked class="sr-only peer">
                        <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                    </div>
                    <span class="text-sm font-medium text-slate-400">Mostrar ubicación</span>
                </label>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex flex-wrap gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                Guardar Ubicación
            </button>
            <a href="{{ url('admin/locations') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-3 rounded-xl font-bold transition-all">
                Cancelar
            </a>
        </div>
    </form>
</div>
@push('scripts')
<script>
(function() {
    var modeSelect  = document.getElementById('location-mode');
    var embedGroup  = document.getElementById('embed-group');
    var urlGroup    = document.getElementById('url-group');
    function toggle() {
        var isEmbed = modeSelect.value === 'embed';
        embedGroup.style.display = isEmbed ? '' : 'none';
        urlGroup.style.display   = isEmbed ? 'none' : '';
    }
    modeSelect.addEventListener('change', toggle);
    toggle();
})();
</script>
@endpush
@endsection
