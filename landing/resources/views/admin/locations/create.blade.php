@extends('admin/layout')

@section('title', 'Nueva Ubicación')
@section('header', 'Nueva Ubicación')
@section('subheader', 'Agrega una sucursal o punto de encuentro a tu landing.')

@section('content')
<div class="max-w-2xl">
    <form action="{{ url('admin/locations/create') }}" method="POST"
          class="bg-slate-900 border border-slate-800 p-5 sm:p-8 rounded-3xl shadow-sm space-y-6">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Nombre --}}
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">
                    Nombre de la ubicación <span class="text-red-400">*</span>
                </label>
                <input type="text" name="name" required placeholder="Ej: Casa Central"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-amber-500/50 outline-none transition-all text-white">
            </div>

            {{-- Dirección --}}
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Dirección</label>
                <input type="text" name="address" placeholder="Ej: Av. Corrientes 1234, CABA"
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
                    <input type="tel" name="whatsapp"
                           placeholder="Ej: +54 9 11 1234-5678"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-11 pr-4 py-3 focus:border-green-500/50 outline-none transition-all text-white font-mono">
                </div>
                <p class="text-xs text-slate-500 mt-1.5">
                    Con código de país. Ej: <code class="bg-slate-800 px-1 rounded">+54911XXXXXXXX</code>.
                    Se mostrará como botón en la tarjeta de la ubicación.
                </p>
            </div>

            {{-- Mensaje de WhatsApp --}}
            <div class="col-span-2" id="whatsapp-message-group" style="display:none">
                <label class="block text-sm font-medium text-slate-400 mb-2">
                    Mensaje pre-cargado
                    <span class="text-slate-600 font-normal ml-1">(WhatsApp API)</span>
                </label>
                <textarea name="whatsapp_message" rows="3" id="whatsapp-message-input"
                          placeholder="Ej: Hola! Me comunico desde la web, quisiera consultar sobre..."
                          class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-green-500/50 outline-none transition-all text-white resize-none text-sm"></textarea>
                <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-slate-600"></i>
                    Al hacer clic en WhatsApp, este texto se pre-cargará en el chat.
                    <span id="char-counter" class="ml-auto font-mono text-slate-600">0 / 500</span>
                </p>
            </div>

            {{-- Modo --}}
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-400 mb-2">Modo de visualización del mapa</label>
                <select name="mode" id="location-mode"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-amber-500/50 outline-none transition-all text-white">
                    <option value="embed">Mapa embebido (iframe)</option>
                    <option value="button">Botón con enlace externo</option>
                </select>
            </div>

            {{-- URL (botón) --}}
            <div class="col-span-2" id="url-group">
                <label class="block text-sm font-medium text-slate-400 mb-2">URL de Google Maps (para botón)</label>
                <input type="text" name="url" placeholder="https://maps.google.com/..."
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-amber-500/50 outline-none transition-all text-white">
            </div>

            {{-- Embed code --}}
            <div class="col-span-2" id="embed-group">
                <label class="block text-sm font-medium text-slate-400 mb-2">
                    Código de Mapa Embebido
                    <span class="ml-2 text-xs font-normal text-slate-500">
                        (Google Maps &rarr; Compartir &rarr; Insertar mapa &rarr; copiar el &lt;iframe&gt;)
                    </span>
                </label>
                <textarea name="embed_code" rows="4"
                          class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-amber-500/50 outline-none transition-all text-white resize-none font-mono text-xs"
                          placeholder='<iframe src="https://www.google.com/maps/embed?pb=..." ...></iframe>'></textarea>
                <p class="text-[10px] text-slate-500 mt-2 italic">
                    Pegá el código &lt;iframe&gt; completo. Se extraerá automáticamente la URL de embed.
                </p>
            </div>

            {{-- Activo --}}
            <div class="col-span-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="active" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </div>
                    <span class="text-sm font-medium text-slate-400">Mostrar ubicación</span>
                </label>
            </div>

        </div>

        <div class="pt-4 border-t border-slate-800 flex flex-wrap gap-3">
            <button type="submit"
                    class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-8 py-3 rounded-xl transition-all">
                Guardar Ubicación
            </button>
            <a href="{{ url('admin/locations') }}"
               class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-3 rounded-xl font-bold transition-all">
                Cancelar
            </a>
        </div>

    </form>
</div>

@push('scripts')
<script>
(function () {
    var modeSelect   = document.getElementById('location-mode');
    var embedGroup   = document.getElementById('embed-group');
    var urlGroup     = document.getElementById('url-group');
    function toggle() {
        var isEmbed = modeSelect.value === 'embed';
        embedGroup.style.display = isEmbed ? '' : 'none';
        urlGroup.style.display   = isEmbed ? 'none' : '';
    }
    modeSelect.addEventListener('change', toggle);
    toggle();

    /* Mostrar campo de mensaje al poner número WhatsApp */
    var wspInput   = document.querySelector('[name="whatsapp"]');
    var msgGroup   = document.getElementById('whatsapp-message-group');
    var msgInput   = document.getElementById('whatsapp-message-input');
    var charCounter = document.getElementById('char-counter');
    function toggleMsg() {
        msgGroup.style.display = wspInput.value.trim() ? '' : 'none';
    }
    wspInput.addEventListener('input', toggleMsg);
    toggleMsg();

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
