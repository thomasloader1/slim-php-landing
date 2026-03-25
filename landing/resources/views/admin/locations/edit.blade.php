@extends('admin/layout')

@section('title', 'Editar Ubicación')
@section('header', 'Editar Ubicación')
@section('subheader', 'Modifica la información de esta ubicación.')

@section('content')
<div class="max-w-2xl">
    <form action="{{ url('admin/locations/edit/' . $location->id) }}" method="POST" class="bg-slate-900 border border-slate-800 p-5 sm:p-8 rounded-3xl shadow-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Nombre de la ubicación <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ $location->name }}" required
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-amber-500/50 outline-none transition-all text-white">
            </div>

            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Dirección</label>
                <input type="text" name="address" value="{{ $location->address }}"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-amber-500/50 outline-none transition-all text-white">
            </div>

            {{-- WhatsApp --}}
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-400 mb-2">
                    Número de WhatsApp
                    <span class="text-slate-600 font-normal ml-1">(opcional)</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-400 pointer-events-none">
                        <i class="fa-brands fa-whatsapp text-lg"></i>
                    </span>
                    <input type="tel" name="whatsapp" value="{{ $location->whatsapp ?? '' }}"
                           placeholder="Ej: +54 9 11 1234-5678"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-11 pr-4 py-3 focus:border-green-500/50 outline-none transition-all text-white font-mono">
                </div>
                <p class="text-xs text-slate-500 mt-1.5">
                    Con código de país. Ej: <code class="bg-slate-800 px-1 rounded">+54911XXXXXXXX</code>.
                    Se mostrará como botón en la tarjeta de la ubicación.
                </p>
            </div>

            {{-- Mensaje de WhatsApp --}}
            <div class="col-span-2" id="whatsapp-message-group" style="{{ $location->whatsapp ? '' : 'display:none' }}">
                <label class="block text-sm font-medium text-slate-400 mb-2">
                    Mensaje pre-cargado
                    <span class="text-slate-600 font-normal ml-1">(WhatsApp API)</span>
                </label>
                <textarea name="whatsapp_message" rows="3" id="whatsapp-message-input"
                          placeholder="Ej: Hola! Me comunico desde la web, quisiera consultar sobre..."
                          class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-green-500/50 outline-none transition-all text-white resize-none text-sm">{{ $location->whatsapp_message ?? '' }}</textarea>
                <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-slate-600"></i>
                    Al hacer clic en WhatsApp, este texto se pre-cargará en el chat.
                    <span id="char-counter" class="ml-auto font-mono text-slate-600">{{ strlen($location->whatsapp_message ?? '') }} / 500</span>
                </p>
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-400 mb-2">Modo de visualización</label>
                <select name="mode" id="location-mode"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-amber-500/50 outline-none transition-all text-white">
                    <option value="embed" {{ $location->mode === 'embed' ? 'selected' : '' }}>Mapa embebido</option>
                    <option value="button" {{ $location->mode === 'button' ? 'selected' : '' }}>Botón con enlace</option>
                </select>
            </div>

            <div class="col-span-2" id="url-group">
                <label class="block text-sm font-medium text-slate-400 mb-2">URL de Google Maps (para botón)</label>
                <input type="text" name="url" value="{{ $location->url }}"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-amber-500/50 outline-none transition-all text-white">
            </div>

            <div class="col-span-2" id="embed-group">
                <label class="block text-sm font-medium text-slate-400 mb-2">
                    Código de Mapa Embebido
                    <span class="ml-2 text-xs font-normal text-slate-500">(Google Maps &rarr; Compartir &rarr; Insertar mapa &rarr; copiar el &lt;iframe&gt;)</span>
                </label>
                <textarea name="embed_code" rows="4"
                          class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-amber-500/50 outline-none transition-all text-white resize-none font-mono text-xs">{{ $location->embed_code }}</textarea>
            </div>

            <div class="col-span-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700">
                        <input type="checkbox" name="active" {{ $location->active ? 'checked' : '' }} class="sr-only peer">
                        <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-amber-500"></div>
                    </div>
                    <span class="text-sm font-medium text-slate-400">Mostrar ubicación</span>
                </label>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex flex-wrap gap-3">
            <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-8 py-3 rounded-xl transition-all">
                Guardar Cambios
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

    /* Mostrar campo de mensaje al poner número WhatsApp */
    var wspInput    = document.querySelector('[name="whatsapp"]');
    var msgGroup    = document.getElementById('whatsapp-message-group');
    var msgInput    = document.getElementById('whatsapp-message-input');
    var charCounter = document.getElementById('char-counter');
    function toggleMsg() {
        msgGroup.style.display = wspInput.value.trim() ? '' : 'none';
    }
    wspInput.addEventListener('input', toggleMsg);

    /* Contador de caracteres */
    msgInput.addEventListener('input', function () {
        charCounter.textContent = this.value.length + ' / 500';
        charCounter.className = this.value.length > 450
            ? 'ml-auto font-mono text-amber-500'
            : 'ml-auto font-mono text-slate-600';
    });
})();
</script>
@endpush
@endsection
