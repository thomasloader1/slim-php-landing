@extends('admin/layout')

@section('title', 'Ajustes del Sitio')
@section('header', 'Configuración')
@section('subheader', 'Personaliza el contenido y apariencia de tu landing page.')

@section('content')
<div class="max-w-4xl">
    <form id="settings-form" action="{{ url('admin/settings') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
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
                        <label class="block text-sm font-medium text-slate-400 mb-2">Favicon (Subir Imagen)</label>
                        <input type="file" name="favicon_file" accept="image/png,image/x-icon,image/svg+xml"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                        @if(!empty($settings['landing_favicon_url']))
                            <div class="mt-3 flex items-center gap-3 flex-wrap">
                                <img src="{{ $settings['landing_favicon_url'] }}" class="w-8 h-8 border border-slate-700 rounded object-contain bg-slate-950 p-1">
                                <span class="text-[10px] text-slate-500 truncate max-w-[200px]">{{ $settings['landing_favicon_url'] }}</span>
                                <label class="flex items-center gap-2 cursor-pointer ml-2 select-none">
                                    <input type="checkbox" name="clear_favicon" value="1" class="sr-only peer">
                                    <span class="text-xs text-red-400 peer-checked:line-through border border-red-400/40 px-2 py-1 rounded-lg hover:bg-red-400/10 transition-colors">Quitar favicon</span>
                                </label>
                            </div>
                        @endif
                        <p class="text-[10px] text-slate-500 mt-2 italic">Formatos: .png, .ico, .svg — Tamaño recomendado: 32x32 o 64x64 px.</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-2">O usar URL de favicon externa</label>
                        <input type="text" name="landing_favicon_url" value="{{ $settings['landing_favicon_url'] ?? '' }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white"
                               placeholder="https://...">
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
                        <label class="block text-sm font-medium text-slate-400 mb-2">Tamaño del Logo</label>
                        <select name="landing_logo_size"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                            <option value="sm" {{ ($settings['landing_logo_size'] ?? 'sm') === 'sm' ? 'selected' : '' }}>Pequeño (64px)</option>
                            <option value="md" {{ ($settings['landing_logo_size'] ?? '') === 'md' ? 'selected' : '' }}>Mediano (96px)</option>
                            <option value="lg" {{ ($settings['landing_logo_size'] ?? '') === 'lg' ? 'selected' : '' }}>Grande (128px)</option>
                            <option value="xl" {{ ($settings['landing_logo_size'] ?? '') === 'xl' ? 'selected' : '' }}>Extra grande (160px)</option>
                        </select>
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

        <!-- Modo de Visualización de Links -->
        @if(($settings['module_links_enabled'] ?? '1') === '1')
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-grip text-cyan-500"></i> Visualización de Enlaces
                </h3>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Modo de visualización</label>
                        <select name="landing_links_display"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                            <option value="list" {{ ($settings['landing_links_display'] ?? 'list') === 'list' ? 'selected' : '' }}>Lista (texto + icono)</option>
                            <option value="grid" {{ ($settings['landing_links_display'] ?? '') === 'grid' ? 'selected' : '' }}>Grilla de iconos</option>
                        </select>
                        <p class="text-[10px] text-slate-500 mt-2 italic">Grilla: muestra solo los iconos en cuadrados, ideal para redes sociales.</p>
                    </div>
                </div>
            </div>
        </div>

        @endif

        <!-- SEO (enlace a página dedicada) -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 bg-slate-900/50 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-magnifying-glass text-blue-400 text-lg"></i>
                    <div>
                        <h3 class="text-lg font-bold text-white">SEO & Buscadores</h3>
                        <p class="text-sm text-slate-500">Meta tags, Open Graph, Schema.org y FAQ.</p>
                    </div>
                </div>
                <a href="{{ url('admin/seo') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                    <i class="fa-solid fa-arrow-right"></i> Ir a SEO
                </a>
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
    document.getElementById('settings-form').addEventListener('submit', function() {
        document.getElementById('bio-hidden').value = quill.root.innerHTML;
    });
</script>

@endpush
@endsection
