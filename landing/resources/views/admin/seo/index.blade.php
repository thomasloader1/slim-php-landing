@extends('admin/layout')

@section('title', 'SEO')
@section('header', 'SEO & Buscadores')
@section('subheader', 'Configurá cómo aparece tu sitio en Google y redes sociales.')

@section('content')
<div class="max-w-5xl">
    <form id="seo-form" action="{{ url('admin/seo') }}" method="POST" class="space-y-8">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

        {{-- ── Básico + SERP Preview ── --}}
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-blue-400"></i> Básico
                </h3>
            </div>
            <div class="p-8 space-y-6">
                {{-- Preview SERP --}}
                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5">
                    <p class="text-xs text-slate-500 mb-3 font-medium uppercase tracking-wider">Vista previa en Google</p>
                    <div class="space-y-1">
                        <p id="serp-title" class="text-[#8ab4f8] text-lg font-medium leading-tight truncate">{{ $settings['landing_title'] ?? 'Título del sitio' }}</p>
                        <p id="serp-url" class="text-[#bdc1c6] text-xs">{{ rtrim($settings['seo_site_url'] ?? 'https://tusitio.com', '/') . '/' }}</p>
                        <p id="serp-desc" class="text-[#bdc1c6] text-sm leading-relaxed line-clamp-2">{{ $settings['seo_description'] ?? 'Aquí aparecerá tu meta descripción.' }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">
                        Meta Descripción
                        <span class="ml-2 text-xs font-normal text-slate-500">(aparece debajo del título en Google)</span>
                    </label>
                    <textarea name="seo_description" id="seo_description" rows="3"
                              class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white resize-none"
                              placeholder="Ej: Cerrajero profesional en Buenos Aires. Apertura de puertas 24hs.">{{ $settings['seo_description'] ?? '' }}</textarea>
                    <div class="flex justify-between mt-1">
                        <p class="text-xs text-slate-500">Recomendado: 120–160 caracteres.</p>
                        <span id="desc-counter" class="text-xs text-slate-500 shrink-0 ml-4">0/160</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">
                        Keywords
                        <span class="ml-2 text-xs font-normal text-slate-500">(separadas por coma, máximo 10)</span>
                    </label>
                    <input type="text" name="seo_keywords" id="seo_keywords" value="{{ $settings['seo_keywords'] ?? '' }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                           placeholder="Ej: cerrajero Buenos Aires, apertura de puertas 24hs">
                    <div class="mt-3 bg-slate-950/60 border border-slate-800 rounded-xl p-4 space-y-2 text-xs text-slate-400">
                        <p class="font-semibold text-slate-300">Guía de palabras clave:</p>
                        <p><span class="text-yellow-400 font-medium">Negocio local:</span> <code class="bg-slate-800 px-1 rounded">[servicio] [ciudad]</code>, <code class="bg-slate-800 px-1 rounded">[servicio] urgente</code></p>
                        <p><span class="text-blue-400 font-medium">Profesional:</span> <code class="bg-slate-800 px-1 rounded">[profesión] [ciudad]</code>, <code class="bg-slate-800 px-1 rounded">[profesión] freelance</code></p>
                        <p><span class="text-green-400 font-medium">Marca:</span> <code class="bg-slate-800 px-1 rounded">nombre de marca</code>, <code class="bg-slate-800 px-1 rounded">[categoría] [diferenciador]</code></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Social & Open Graph ── --}}
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-share-nodes text-purple-400"></i> Social & Open Graph
                </h3>
                <p class="text-sm text-slate-500 mt-1">Controla cómo se ve tu sitio cuando alguien lo comparte en WhatsApp, Facebook o Twitter.</p>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-2">
                            URL del sitio
                            <span class="ml-2 text-xs font-normal text-slate-500">(necesario para canonical tag y sitemap)</span>
                        </label>
                        <input type="url" name="seo_site_url" id="seo_site_url" value="{{ $settings['seo_site_url'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                               placeholder="https://tusitio.com">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-2">
                            Imagen para redes sociales
                            <span class="ml-2 text-xs font-normal text-slate-500">(recomendado: 1200×630 px — si está vacío se usa el avatar)</span>
                        </label>
                        <input type="url" name="seo_og_image" value="{{ $settings['seo_og_image'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                               placeholder="https://tusitio.com/imagen-share.jpg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Idioma / Locale</label>
                        <select name="seo_locale"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                            @php
                                $locales = ['es_AR' => 'Español (Argentina)', 'es_MX' => 'Español (México)', 'es_ES' => 'Español (España)', 'es_CL' => 'Español (Chile)', 'es_UY' => 'Español (Uruguay)', 'es_CO' => 'Español (Colombia)', 'pt_BR' => 'Português (Brasil)', 'en_US' => 'English (US)'];
                                $currentLocale = $settings['seo_locale'] ?? 'es_AR';
                            @endphp
                            @foreach($locales as $code => $label)
                                <option value="{{ $code }}" {{ $currentLocale === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">
                            Twitter / X handle
                            <span class="ml-2 text-xs font-normal text-slate-500">(sin @)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-medium">@</span>
                            <input type="text" name="seo_twitter_handle" value="{{ $settings['seo_twitter_handle'] ?? '' }}"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-8 pr-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                                   placeholder="tuusuario">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Schema.org ── --}}
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-code text-emerald-400"></i> Schema.org (Datos Estructurados)
                </h3>
                <p class="text-sm text-slate-500 mt-1">Ayuda a Google a entender qué tipo de entidad es tu sitio.</p>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Tipo de Schema</label>
                        <select name="seo_schema_type" id="seo_schema_type"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                            <option value="Person"        {{ ($settings['seo_schema_type'] ?? 'Person') === 'Person' ? 'selected' : '' }}>Persona / Profesional</option>
                            <option value="LocalBusiness" {{ ($settings['seo_schema_type'] ?? '') === 'LocalBusiness' ? 'selected' : '' }}>Negocio Local</option>
                            <option value="Organization"  {{ ($settings['seo_schema_type'] ?? '') === 'Organization' ? 'selected' : '' }}>Organización / Marca</option>
                        </select>
                    </div>

                    <div id="business-type-group" class="{{ ($settings['seo_schema_type'] ?? 'Person') !== 'LocalBusiness' ? 'hidden' : '' }}">
                        <label class="block text-sm font-medium text-slate-400 mb-2">
                            Tipo de Negocio
                            <span class="ml-2 text-xs font-normal text-slate-500">(subtipo para Google)</span>
                        </label>
                        <input type="text" name="seo_business_type" value="{{ $settings['seo_business_type'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                               placeholder="Locksmith, Restaurant, HairSalon...">
                        <p class="text-xs text-slate-500 mt-1">Usá el tipo en inglés de <a href="https://schema.org/LocalBusiness" target="_blank" class="text-blue-400 hover:underline">schema.org/LocalBusiness</a></p>
                    </div>

                    <div id="address-group" class="col-span-2 {{ ($settings['seo_schema_type'] ?? 'Person') !== 'LocalBusiness' ? 'hidden' : '' }}">
                        <label class="block text-sm font-medium text-slate-400 mb-2">Dirección del negocio</label>
                        <input type="text" name="seo_address" value="{{ $settings['seo_address'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                               placeholder="Av. Corrientes 1234, Buenos Aires">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Avanzado ── --}}
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-orange-400"></i> Avanzado
                </h3>
            </div>
            <div class="p-8">
                <label class="flex items-start gap-3 cursor-pointer group">
                    <input type="hidden" name="seo_noindex" value="0">
                    <input type="checkbox" name="seo_noindex" value="1"
                           {{ ($settings['seo_noindex'] ?? '0') === '1' ? 'checked' : '' }}
                           id="seo_noindex"
                           class="mt-0.5 w-4 h-4 rounded cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-red-400">Ocultar de buscadores (noindex)</span>
                        <p class="text-xs text-slate-500 mt-0.5">Agrega <code class="bg-slate-800 px-1 rounded">noindex, nofollow</code> para que Google <strong>no indexe</strong> este sitio. <strong>Desactivar en producción.</strong></p>
                    </div>
                </label>
                <div id="noindex-warning" class="{{ ($settings['seo_noindex'] ?? '0') === '1' ? '' : 'hidden' }} mt-3 bg-red-950/50 border border-red-800 rounded-xl p-4 text-sm text-red-300">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i> <strong>Atención:</strong> Tu sitio está oculto para Google. Desactivá esta opción cuando estés listo para publicar.
                </div>
            </div>
        </div>

        {{-- ── FAQ Schema ── --}}
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-circle-question text-cyan-400"></i> Preguntas Frecuentes (FAQ Schema)
                </h3>
                <p class="text-sm text-slate-500 mt-1">Las FAQ aparecen como datos estructurados en Google y pueden mejorar tu visibilidad.</p>
            </div>
            <div class="p-8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white font-medium">{{ $faqCount }} pregunta{{ $faqCount !== 1 ? 's' : '' }} cargada{{ $faqCount !== 1 ? 's' : '' }}</p>
                        <p class="text-xs text-slate-500 mt-1">Gestioná las preguntas frecuentes que se inyectan como JSON-LD en la landing.</p>
                    </div>
                    <a href="{{ url('admin/seo/faq') }}" class="bg-cyan-600 hover:bg-cyan-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                        <i class="fa-solid fa-list"></i> Gestionar FAQ
                    </a>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end pt-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-10 py-4 rounded-2xl font-bold transition-all shadow-xl shadow-blue-500/20 active:scale-95">
                Guardar SEO
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
/* ── Preview SERP en tiempo real ── */
(function() {
    var descEl    = document.getElementById('seo_description');
    var siteUrlEl = document.getElementById('seo_site_url');
    var counter   = document.getElementById('desc-counter');
    var serpUrl   = document.getElementById('serp-url');
    var serpDesc  = document.getElementById('serp-desc');

    function updateCounter() {
        var len = (descEl.value || '').length;
        counter.textContent = len + '/160';
        counter.className   = 'text-xs shrink-0 ml-4 ' + (len > 160 ? 'text-red-400' : (len > 120 ? 'text-yellow-400' : 'text-slate-500'));
        serpDesc.textContent = descEl.value || 'Aquí aparecerá tu meta descripción.';
    }

    function updateUrl() {
        var u = (siteUrlEl.value || 'https://tusitio.com').replace(/\/$/, '') + '/';
        serpUrl.textContent = u;
    }

    if (descEl)    descEl.addEventListener('input', updateCounter);
    if (siteUrlEl) siteUrlEl.addEventListener('input', updateUrl);
    updateCounter();
    updateUrl();
})();

/* ── Mostrar/ocultar campos LocalBusiness ── */
(function() {
    var schemaSelect      = document.getElementById('seo_schema_type');
    var businessTypeGroup = document.getElementById('business-type-group');
    var addressGroup      = document.getElementById('address-group');

    if (!schemaSelect) return;

    function toggle() {
        var isLocal = schemaSelect.value === 'LocalBusiness';
        businessTypeGroup.classList.toggle('hidden', !isLocal);
        addressGroup.classList.toggle('hidden', !isLocal);
    }

    schemaSelect.addEventListener('change', toggle);
})();

/* ── Warning noindex ── */
(function() {
    var noindexCb      = document.getElementById('seo_noindex');
    var noindexWarning = document.getElementById('noindex-warning');
    if (!noindexCb) return;
    noindexCb.addEventListener('change', function() {
        noindexWarning.classList.toggle('hidden', !this.checked);
    });
})();
</script>
@endpush
@endsection
