@extends('admin/layout')

@section('title', 'Gestión de Favicons')
@section('header', 'Favicons')
@section('subheader', 'Subí una imagen cuadrada y se generarán todos los tamaños automáticamente.')

@section('content')
<div class="max-w-3xl">

    {{-- Mensajes flash --}}
    @php
        $flashError   = $_GET['error'] ?? null;
        $flashSuccess = $_GET['success'] ?? null;
    @endphp

    @if($flashError)
        <div class="bg-red-950/50 border border-red-800 rounded-2xl p-4 mb-6 flex items-start gap-3">
            <i class="fa-solid fa-circle-exclamation text-red-400 mt-0.5"></i>
            <p class="text-sm text-red-300">{{ $flashError }}</p>
        </div>
    @endif

    @if($flashSuccess)
        <div class="bg-emerald-950/50 border border-emerald-800 rounded-2xl p-4 mb-6 flex items-start gap-3">
            <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5"></i>
            <p class="text-sm text-emerald-300">{{ $flashSuccess }}</p>
        </div>
    @endif

    {{-- Formulario de subida --}}
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-800 bg-slate-900/50">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-image text-amber-500"></i> Subir Favicon
            </h3>
            <p class="text-sm text-slate-500 mt-1">La imagen debe ser cuadrada (1:1). Se recomienda al menos 512x512 px para mejor calidad.</p>
        </div>
        <div class="p-8">
            <form id="favicon-form" action="{{ url('admin/favicon') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

                {{-- Preview de la imagen seleccionada --}}
                <div id="preview-zone" class="hidden">
                    <label class="block text-sm font-medium text-slate-400 mb-2">Vista previa</label>
                    <div class="flex items-center gap-6">
                        <img id="preview-img" src="" alt="Preview" class="w-32 h-32 rounded-2xl border border-slate-700 object-cover bg-slate-950">
                        <div id="preview-info" class="text-sm text-slate-400 space-y-1"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Imagen fuente</label>
                    <input type="file" name="favicon_source" id="favicon_source" accept="image/png,image/jpeg,image/gif,image/webp"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-slate-400
                                  file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold
                                  file:bg-amber-600 file:text-white hover:file:bg-amber-500 cursor-pointer"
                           required>
                    <p class="text-[10px] text-slate-500 mt-2 italic">Formatos: PNG, JPG, GIF, WebP. Tamaño mínimo recomendado: 512x512 px.</p>
                </div>

                {{-- Validación visual --}}
                <div id="validation-msg" class="hidden bg-red-950/50 border border-red-800 rounded-xl p-3 text-sm text-red-300">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    <span id="validation-text"></span>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" id="submit-btn"
                            class="bg-amber-600 hover:bg-amber-500 text-white px-8 py-3 rounded-2xl font-bold transition-all shadow-xl shadow-amber-500/20 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                            disabled>
                        <i class="fa-solid fa-wand-magic-sparkles mr-2"></i> Generar Favicons
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Favicons generados --}}
    @if($hasGenerated)
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm mt-8">
        <div class="p-6 border-b border-slate-800 bg-slate-900/50 flex items-center justify-between">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-check-circle text-emerald-500"></i> Favicons Generados
            </h3>
            <form action="{{ url('admin/favicon/delete') }}" method="POST" data-no-loading
                  onsubmit="return confirm('¿Eliminar todos los favicons generados?')">
                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="text-xs text-red-400 border border-red-400/40 px-3 py-1.5 rounded-lg hover:bg-red-400/10 transition-colors">
                    <i class="fa-solid fa-trash-can mr-1"></i> Eliminar todos
                </button>
            </form>
        </div>
        <div class="p-8">
            @php $v = !empty($settings['favicon_version']) ? '?v=' . $settings['favicon_version'] : ''; @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-6">
                @foreach($sizes as $filename => [$w, $h])
                    <div class="flex flex-col items-center gap-3">
                        <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 flex items-center justify-center"
                             style="width: {{ min($w, 80) + 32 }}px; height: {{ min($h, 80) + 32 }}px;">
                            <img src="{{ url('favicons/' . $filename) }}{{ $v }}"
                                 alt="{{ $filename }}"
                                 class="object-contain"
                                 style="width: {{ min($w, 80) }}px; height: {{ min($h, 80) }}px;">
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-slate-400 font-mono break-all">{{ $filename }}</p>
                            <p class="text-[10px] text-slate-600">{{ $w }}x{{ $h }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Webmanifest --}}
            <div class="mt-6 pt-6 border-t border-slate-800">
                <p class="text-sm text-slate-400">
                    <i class="fa-solid fa-file-code text-blue-400 mr-2"></i>
                    <span class="font-mono text-xs">site.webmanifest</span> generado en
                    <code class="bg-slate-950 px-2 py-0.5 rounded text-xs text-slate-500">{{ url('favicons/site.webmanifest') }}</code>
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Información de etiquetas HTML --}}
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm mt-8">
        <div class="p-6 border-b border-slate-800 bg-slate-900/50">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-code text-blue-400"></i> Integración automática
            </h3>
        </div>
        <div class="p-8">
            <p class="text-sm text-slate-400">
                Las etiquetas <code class="bg-slate-950 px-2 py-0.5 rounded text-xs">&lt;link&gt;</code> se incluyen automáticamente
                en el <code class="bg-slate-950 px-2 py-0.5 rounded text-xs">&lt;head&gt;</code> de tu landing page.
                No necesitás modificar ningún archivo.
            </p>
            <div class="mt-4 bg-slate-950 border border-slate-800 rounded-xl p-4 overflow-x-auto">
                <pre class="text-xs text-slate-500 font-mono leading-relaxed"><code>&lt;link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png"&gt;
&lt;link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png"&gt;
&lt;link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png"&gt;
&lt;link rel="manifest" href="/favicons/site.webmanifest"&gt;</code></pre>
            </div>
        </div>
    </div>

</div>
@push('scripts')
<script>
(function() {
    var input     = document.getElementById('favicon_source');
    var preview   = document.getElementById('preview-zone');
    var previewImg = document.getElementById('preview-img');
    var previewInfo = document.getElementById('preview-info');
    var submitBtn  = document.getElementById('submit-btn');
    var valMsg     = document.getElementById('validation-msg');
    var valText    = document.getElementById('validation-text');

    input.addEventListener('change', function() {
        valMsg.classList.add('hidden');
        preview.classList.add('hidden');
        submitBtn.disabled = true;

        if (!this.files || !this.files[0]) return;

        var file = this.files[0];

        // Validar tipo
        var allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        if (allowed.indexOf(file.type) === -1) {
            showError('Formato no permitido. Usá PNG, JPG, GIF o WebP.');
            return;
        }

        // Validar tamaño (máx 10MB)
        if (file.size > 10 * 1024 * 1024) {
            showError('El archivo es demasiado grande. Máximo 10 MB.');
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                // Validar aspecto cuadrado (tolerancia 5%)
                var ratio = img.width / img.height;
                if (ratio < 0.95 || ratio > 1.05) {
                    showError('La imagen debe ser cuadrada (1:1). Actual: ' + img.width + 'x' + img.height + ' (' + ratio.toFixed(2) + ':1)');
                    return;
                }

                // Advertencia si es menor a 512px
                var warning = '';
                if (img.width < 512) {
                    warning = '<p class="text-yellow-400"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Menor a 512px, se recomienda al menos 512x512.</p>';
                }

                previewImg.src = e.target.result;
                previewInfo.innerHTML = '<p>Dimensiones: <strong class="text-white">' + img.width + 'x' + img.height + '</strong></p>'
                    + '<p>Tamaño: <strong class="text-white">' + (file.size / 1024).toFixed(1) + ' KB</strong></p>'
                    + '<p>Formato: <strong class="text-white">' + file.type.split('/')[1].toUpperCase() + '</strong></p>'
                    + warning;

                preview.classList.remove('hidden');
                submitBtn.disabled = false;
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    function showError(msg) {
        valText.textContent = msg;
        valMsg.classList.remove('hidden');
        submitBtn.disabled = true;
    }
})();
</script>
@endpush
@endsection
