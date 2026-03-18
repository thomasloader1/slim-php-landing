@extends('admin/layout')

@section('title', 'Módulos')
@section('header', 'Módulos')
@section('subheader', 'Activa o desactiva las funcionalidades de tu landing page.')

@section('content')
<div class="max-w-3xl">
    <form action="{{ url('admin/modules') }}" method="POST" class="space-y-6">
        @php
            $modules = [
                ['key' => 'module_title_enabled',     'label' => 'Título y Subtítulo',  'icon' => 'fa-heading',          'desc' => 'Muestra el título y subtítulo en la landing page.'],
                ['key' => 'module_avatar_enabled',    'label' => 'Logo / Avatar',        'icon' => 'fa-image',            'desc' => 'Muestra el logo o avatar en la landing page.'],
                ['key' => 'module_links_enabled',     'label' => 'Enlaces',              'icon' => 'fa-link',             'desc' => 'Sección de enlaces estilo Linktree. Oculta también la gestión de enlaces en el sidebar y ajustes.'],
                ['key' => 'module_bio_enabled',       'label' => 'Biografía',            'icon' => 'fa-align-left',       'desc' => 'Muestra la sección de biografía/descripción en la landing page.'],
                ['key' => 'module_locations_enabled', 'label' => 'Ubicaciones',          'icon' => 'fa-map-location-dot', 'desc' => 'CRUD de sucursales/ubicaciones con mapa embebido o botón.'],
                ['key' => 'module_menu_enabled',      'label' => 'Menú / Carta',         'icon' => 'fa-utensils',         'desc' => 'Carta digital con secciones e ítems. Oculta la gestión del menú en el sidebar.'],
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($modules as $mod)
                <label class="flex items-start gap-4 cursor-pointer group bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition-all">
                    <input type="hidden" name="{{ $mod['key'] }}" value="0">
                    <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700 shrink-0 mt-1">
                        <input type="checkbox" name="{{ $mod['key'] }}" value="1"
                               {{ ($settings[$mod['key']] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fa-solid {{ $mod['icon'] }} text-slate-500 text-sm"></i>
                            <span class="text-sm font-bold text-white">{{ $mod['label'] }}</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">{{ $mod['desc'] }}</p>
                    </div>
                </label>
            @endforeach
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                Guardar Módulos
            </button>
        </div>
    </form>
</div>
@endsection
