@extends('admin/layout')

@section('title', 'Ajustes del Sitio')
@section('header', 'Configuración')
@section('subheader', 'Personaliza el contenido y apariencia de tu landing page.')

@section('content')
<div class="max-w-4xl">
    <form action="{{ url('admin/settings') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        <!-- Site Content -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-pen-nib text-blue-500"></i> Contenido Principal
                </h3>
            </div>
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Nombre del Sitio</label>
                        <input type="text" name="site_name" value="{{ $settings['site_name'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Título de la Landing</label>
                        <input type="text" name="landing_title" value="{{ $settings['landing_title'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-2">Subtítulo / Profesión</label>
                        <input type="text" name="landing_subtitle" value="{{ $settings['landing_subtitle'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                    </div>
                    <div class="col-span-2 mb-16">
                        <label class="block text-sm font-medium text-slate-400 mb-2">Biografía / Descripción</label>
                        <div id="bio-editor" class="bg-slate-950 border border-slate-800 rounded-xl text-white min-h-[120px]" style="font-size:1rem; line-height:1.5;">{!! $settings['landing_bio'] ?? '' !!}</div>
                        <textarea name="landing_bio" id="bio-hidden" class="hidden">{{ $settings['landing_bio'] ?? '' }}</textarea>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-2">Avatar (Subir Imagen)</label>
                        <input type="file" name="avatar_file" accept="image/*"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                        @if(!empty($settings['landing_avatar_url']))
                            <div class="mt-3 flex items-center gap-3 flex-wrap">
                                <img src="{{ $settings['landing_avatar_url'] }}" class="w-12 h-12 rounded-full border border-slate-700 object-cover">
                                <span class="text-[10px] text-slate-500 truncate max-w-[200px]">{{ $settings['landing_avatar_url'] }}</span>
                                <label class="flex items-center gap-2 cursor-pointer ml-2 select-none">
                                    <input type="checkbox" name="clear_avatar" value="1" class="sr-only peer">
                                    <span class="text-xs text-red-400 peer-checked:line-through border border-red-400/40 px-2 py-1 rounded-lg hover:bg-red-400/10 transition-colors">Quitar imagen</span>
                                </label>
                            </div>
                        @endif
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-2">O usar URL externa</label>
                        <input type="text" name="landing_avatar_url" value="{{ $settings['landing_avatar_url'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                               placeholder="https://...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Appearance -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i> Apariencia y Colores
                </h3>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="flex gap-4">
                        <div>
                        <label class="block text-sm font-medium text-slate-400 mb-4">Color de Acento</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="landing_accent_color" value="{{ $settings['landing_accent_color'] ?? '#f59e0b' }}"
                                   class="w-16 h-16 bg-slate-950 border border-slate-800 rounded-2xl p-2 cursor-pointer transition-transform hover:scale-105">
                            <span class="text-slate-500 font-mono text-sm uppercase">{{ $settings['landing_accent_color'] ?? '#F59E0B' }}</span>
                        </div>
                    </div>

                        <div class="mt-3 flex flex-col items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="hidden" name="landing_accent_force" value="0">
                                <input type="checkbox" name="landing_accent_force" value="1"
                                       {{ ($settings['landing_accent_force'] ?? '1') === '1' ? 'checked' : '' }}
                                       class="w-4 h-4 rounded cursor-pointer accent-checkbox">
                                <span class="text-sm text-slate-400">Forzar en links</span>
                            </label>
                            <span class="text-xs text-slate-500 italic">Desactivado: cada link usa su propio color.</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-4">Fondo (Base)</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="landing_bg_color" value="{{ $settings['landing_bg_color'] ?? '#020617' }}"
                                   class="w-16 h-16 bg-slate-950 border border-slate-800 rounded-2xl p-2 cursor-pointer transition-transform hover:scale-105">
                            <span class="text-slate-500 font-mono text-sm uppercase">{{ $settings['landing_bg_color'] ?? '#111827' }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-4">Color Overlay (Sobre Imagen)</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="landing_bg_overlay" value="{{ $settings['landing_bg_overlay'] ?? '#000000' }}"
                                   class="w-16 h-16 bg-slate-950 border border-slate-800 rounded-2xl p-2 cursor-pointer transition-transform hover:scale-105">
                            <span class="text-slate-500 font-mono text-sm uppercase">{{ $settings['landing_bg_overlay'] ?? '#000000' }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-4">Texto</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="landing_text_color" value="{{ $settings['landing_text_color'] ?? '#ffffff' }}"
                                   class="w-16 h-16 bg-slate-950 border border-slate-800 rounded-2xl p-2 cursor-pointer transition-transform hover:scale-105">
                            <span class="text-slate-500 font-mono text-sm uppercase">{{ $settings['landing_text_color'] ?? '#FFFFFF' }}</span>
                        </div>
                    </div>

                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-slate-400 mb-2">Opacidad Overlay (0-100)</label>
                        <input type="number" name="landing_bg_overlay_opacity" value="{{ $settings['landing_bg_overlay_opacity'] ?? '50' }}" min="0" max="100"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                    </div>

                    <div class="md:col-span-2 lg:col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-2">Imagen de Fondo (Subir)</label>
                        <input type="file" name="bg_file" accept="image/*"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                        @if(!empty($settings['landing_bg_image_url']))
                            <div class="mt-3 flex items-center gap-3 flex-wrap">
                                <img src="{{ $settings['landing_bg_image_url'] }}" class="h-12 w-20 object-cover border border-slate-700 rounded-lg">
                                <span class="text-[10px] text-slate-500 truncate max-w-[200px]">{{ $settings['landing_bg_image_url'] }}</span>
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" name="clear_bg" value="1" class="sr-only peer">
                                    <span class="text-xs text-red-400 peer-checked:line-through border border-red-400/40 px-2 py-1 rounded-lg hover:bg-red-400/10 transition-colors">Quitar imagen de fondo</span>
                                </label>
                            </div>
                        @endif
                    </div>

                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-slate-400 mb-2">O usar URL de fondo externa</label>
                        <input type="text" name="landing_bg_image_url" value="{{ $settings['landing_bg_image_url'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                               placeholder="https://...">
                    </div>

                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-slate-400 mb-2">Logo (Subir)</label>
                        <input type="file" name="logo_file" accept="image/*"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                        @if(!empty($settings['landing_logo_url']))
                            <div class="mt-3 flex items-center gap-3 flex-wrap">
                                <img src="{{ $settings['landing_logo_url'] }}" class="h-8 w-auto border border-slate-700 rounded object-contain bg-slate-950 px-2">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" name="clear_logo" value="1" class="sr-only peer">
                                    <span class="text-xs text-red-400 peer-checked:line-through border border-red-400/40 px-2 py-1 rounded-lg hover:bg-red-400/10 transition-colors">Quitar logo</span>
                                </label>
                            </div>
                        @endif
                    </div>

                    <div class="md:col-span-1 lg:col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-2">O usar URL de logo externa</label>
                        <input type="text" name="landing_logo_url" value="{{ $settings['landing_logo_url'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                               placeholder="https://...">
                    </div>

                    
                </div>
            </div>
        </div>

        <!-- SEO -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-blue-400"></i> SEO & Buscadores
                </h3>
                <p class="text-sm text-slate-500 mt-1">Configurá cómo aparece tu sitio en Google y redes sociales.</p>
            </div>
            <div class="p-8 space-y-8">

                {{-- ── 1. Básico ───────────────────────────────────────── --}}
                <div class="space-y-6">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Básico</h4>

                    {{-- Preview SERP --}}
                    <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5">
                        <p class="text-xs text-slate-500 mb-3 font-medium uppercase tracking-wider">Vista previa en Google</p>
                        <div class="space-y-1">
                            <p id="serp-title"   class="text-[#8ab4f8] text-lg font-medium leading-tight truncate">{{ $settings['landing_title'] ?? 'Título del sitio' }}</p>
                            <p id="serp-url"     class="text-[#bdc1c6] text-xs">{{ rtrim($settings['seo_site_url'] ?? 'https://tusitio.com', '/') . '/' }}</p>
                            <p id="serp-desc"    class="text-[#bdc1c6] text-sm leading-relaxed line-clamp-2">{{ $settings['seo_description'] ?? 'Aquí aparecerá tu meta descripción. Escribí algo que invite a hacer clic. Máximo 160 caracteres.' }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">
                            Meta Descripción
                            <span class="ml-2 text-xs font-normal text-slate-500">(aparece debajo del título en Google)</span>
                        </label>
                        <textarea name="seo_description" id="seo_description" rows="3"
                                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white resize-none"
                                  placeholder="Ej: Cerrajero profesional en Buenos Aires. Apertura de puertas 24hs, instalación de cerraduras, cajas de seguridad. ☎ (11) 1234-5678">{{ $settings['seo_description'] ?? '' }}</textarea>
                        <div class="flex justify-between mt-1">
                            <p class="text-xs text-slate-500">Recomendado: 120–160 caracteres. Más importante que las keywords para que la gente haga clic.</p>
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
                               placeholder="Ej: cerrajero Buenos Aires, apertura de puertas 24hs, cerrajería urgente Palermo">
                        <div class="mt-3 bg-slate-950/60 border border-slate-800 rounded-xl p-4 space-y-2 text-xs text-slate-400">
                            <p class="font-semibold text-slate-300">Guía de palabras clave:</p>
                            <p><span class="text-yellow-400 font-medium">Negocio local:</span> <code class="bg-slate-800 px-1 rounded">[servicio] [ciudad]</code>, <code class="bg-slate-800 px-1 rounded">[servicio] urgente</code>, <code class="bg-slate-800 px-1 rounded">[servicio] 24 horas</code><br>
                               <span class="text-slate-500">Ej: cerrajero Buenos Aires, cerrajería urgente, abrir puertas Palermo</span></p>
                            <p><span class="text-blue-400 font-medium">Profesional/Freelancer:</span> <code class="bg-slate-800 px-1 rounded">[profesión] [ciudad]</code>, <code class="bg-slate-800 px-1 rounded">[profesión] freelance</code><br>
                               <span class="text-slate-500">Ej: fotógrafo de bodas Buenos Aires, fotografía corporativa CABA</span></p>
                            <p><span class="text-green-400 font-medium">Marca/Emprendimiento:</span> <code class="bg-slate-800 px-1 rounded">nombre de marca</code>, <code class="bg-slate-800 px-1 rounded">[categoría] [diferenciador]</code><br>
                               <span class="text-slate-500">Ej: Tienda Sol, ropa sustentable Argentina, indumentaria ecológica</span></p>
                            <p class="text-slate-500 pt-1 border-t border-slate-800">Tip: usá las palabras que <em>tus clientes</em> tipearían en Google. Incluí siempre la ciudad si es un negocio local.</p>
                        </div>
                    </div>
                </div>

                {{-- ── 2. Social & Open Graph ───────────────────────────── --}}
                <div class="space-y-6 pt-6 border-t border-slate-800">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Social & Open Graph</h4>
                    <p class="text-xs text-slate-500 -mt-4">Controla cómo se ve tu sitio cuando alguien lo comparte en WhatsApp, Facebook o Twitter.</p>

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

                {{-- ── 3. Schema.org ────────────────────────────────────── --}}
                <div class="space-y-6 pt-6 border-t border-slate-800">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Schema.org (Datos Estructurados)</h4>
                    <p class="text-xs text-slate-500 -mt-4">Ayuda a Google a entender qué tipo de entidad es tu sitio. Puede mejorar la presentación en resultados de búsqueda.</p>

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

                        {{-- Tipo de negocio (solo LocalBusiness) --}}
                        <div id="business-type-group" class="{{ ($settings['seo_schema_type'] ?? 'Person') !== 'LocalBusiness' ? 'hidden' : '' }}">
                            <label class="block text-sm font-medium text-slate-400 mb-2">
                                Tipo de Negocio
                                <span class="ml-2 text-xs font-normal text-slate-500">(subtipo para Google)</span>
                            </label>
                            <input type="text" name="seo_business_type" value="{{ $settings['seo_business_type'] ?? '' }}"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                                   placeholder="Locksmith, Plumber, Restaurant, HairSalon, ElectricianBusiness...">
                            <p class="text-xs text-slate-500 mt-1">Usá el tipo en inglés de <a href="https://schema.org/LocalBusiness" target="_blank" class="text-blue-400 hover:underline">schema.org/LocalBusiness</a></p>
                        </div>

                        {{-- Dirección (solo LocalBusiness) --}}
                        <div id="address-group" class="col-span-2 {{ ($settings['seo_schema_type'] ?? 'Person') !== 'LocalBusiness' ? 'hidden' : '' }}">
                            <label class="block text-sm font-medium text-slate-400 mb-2">Dirección del negocio</label>
                            <input type="text" name="seo_address" value="{{ $settings['seo_address'] ?? '' }}"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                                   placeholder="Av. Corrientes 1234, Buenos Aires">
                        </div>
                    </div>
                </div>

                {{-- ── 4. Avanzado ──────────────────────────────────────── --}}
                <div class="pt-6 border-t border-slate-800">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Avanzado</h4>
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="hidden" name="seo_noindex" value="0">
                        <input type="checkbox" name="seo_noindex" value="1"
                               {{ ($settings['seo_noindex'] ?? '0') === '1' ? 'checked' : '' }}
                               id="seo_noindex"
                               class="mt-0.5 w-4 h-4 rounded cursor-pointer">
                        <div>
                            <span class="text-sm font-medium text-red-400">Ocultar de buscadores (noindex)</span>
                            <p class="text-xs text-slate-500 mt-0.5">Agrega <code class="bg-slate-800 px-1 rounded">noindex, nofollow</code> para que Google <strong>no indexe</strong> este sitio. Útil para entornos de prueba/staging. <strong>Desactivar en producción.</strong></p>
                        </div>
                    </label>
                    <div id="noindex-warning" class="{{ ($settings['seo_noindex'] ?? '0') === '1' ? '' : 'hidden' }} mt-3 bg-red-950/50 border border-red-800 rounded-xl p-4 text-sm text-red-300">
                        <i class="fa-solid fa-triangle-exclamation mr-2"></i> <strong>Atención:</strong> Tu sitio está oculto para Google. Desactivá esta opción cuando estés listo para publicar.
                    </div>
                </div>

            </div>
        </div>

        <!-- Ubicación Google Maps -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-map-location-dot text-emerald-500"></i> Ubicación
                </h3>
            </div>
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Modo de visualización</label>
                        <select name="landing_maps_mode"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                            <option value="none" {{ ($settings['landing_maps_mode'] ?? 'none') === 'none' ? 'selected' : '' }}>Ocultar</option>
                            <option value="button" {{ ($settings['landing_maps_mode'] ?? '') === 'button' ? 'selected' : '' }}>Mostrar botón</option>
                            <option value="embed" {{ ($settings['landing_maps_mode'] ?? '') === 'embed' ? 'selected' : '' }}>Mapa embebido</option>
                        </select>
                        <p class="text-[10px] text-slate-500 mt-2 italic">Para "Mapa embebido" usa la URL de Google Maps → Compartir → Insertar mapa.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">URL de Google Maps</label>
                        <input type="text" name="landing_maps_url" value="{{ $settings['landing_maps_url'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                               placeholder="https://maps.google.com/...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end pt-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-10 py-4 rounded-2xl font-bold transition-all shadow-xl shadow-blue-500/20 active:scale-95">
                Guardar Cambios Globales
            </button>
        </div>
    </form>
</div>
@push('scripts')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    /* Adaptar Quill al tema oscuro */
    #bio-editor .ql-editor { min-height: 120px; color: #fff; }
    .ql-toolbar.ql-snow { border-color: #1e293b; background: #0f172a; border-radius: 0.75rem 0.75rem 0 0; }
    .ql-container.ql-snow { border-color: #1e293b; border-radius: 0 0 0.75rem 0.75rem; }
    .ql-snow .ql-stroke { stroke: #94a3b8; }
    .ql-snow .ql-fill { fill: #94a3b8; }
    .ql-snow .ql-picker { color: #94a3b8; }
    #bio-editor { border-radius: 0.75rem; overflow: hidden; border: 1px solid #1e293b; }
    #bio-editor .ql-toolbar { border-bottom: 1px solid #1e293b; }
</style>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
    var quill = new Quill('#bio-editor', {
        theme: 'snow',
        formats: ['bold', 'italic', 'list', 'link'],
        modules: {
            toolbar: [
                ['bold', 'italic'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });
    // Al enviar, copiar HTML al textarea oculto
    document.querySelector('form').addEventListener('submit', function() {
        document.getElementById('bio-hidden').value = quill.root.innerHTML;
    });
</script>

<script>
/* ── Preview SERP en tiempo real ── */
(function() {
    var descEl    = document.getElementById('seo_description');
    var siteUrlEl = document.getElementById('seo_site_url');
    var counter   = document.getElementById('desc-counter');
    var serpTitle = document.getElementById('serp-title');
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

    // Actualizar título del preview con el campo landing_title
    var titleInput = document.querySelector('input[name="landing_title"]');
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            serpTitle.textContent = this.value || 'Título del sitio';
        });
    }
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
