@extends('admin/layout')

@section('title', 'Configuración del Menú')
@section('header', 'Configuración del Menú')
@section('subheader', 'Activá el módulo y personalizá los textos de la página pública.')

@section('content')
@php $menuEnabled = ($settings['menu_enabled'] ?? '0') === '1'; @endphp

<div class="flex justify-end mb-4 gap-3">
    @if($menuEnabled)
        <a href="{{ url('menu') }}" target="_blank" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i> Ver Menú
        </a>
    @endif
    <a href="{{ url('admin/menu/sections') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-layer-group"></i> Secciones
    </a>
    <a href="{{ url('admin/menu/items') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-list"></i> Ítems
    </a>
</div>

@if(!$menuEnabled)
<div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-4 mb-6 flex items-start gap-3">
    <i class="fa-solid fa-circle-info text-amber-400 mt-0.5 flex-shrink-0"></i>
    <p class="text-amber-300 text-sm">
        El menú está <strong>desactivado</strong>. La página <code class="bg-amber-500/20 px-1 rounded">/menu</code> retornará un 404 hasta que lo actives.
    </p>
</div>
@endif

<div class="max-w-xl">
    <form action="{{ url('admin/menu/settings') }}" method="POST">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white">Ajustes del Módulo</h3>
            </div>
            <div class="p-6 space-y-6">

                {{-- Toggle activar --}}
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white font-bold text-sm">Activar página pública</p>
                        <p class="text-slate-500 text-xs mt-0.5">Hace visible la carta en <code class="bg-slate-800 px-1 rounded">/menu</code></p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="menu_enabled" value="1" class="sr-only peer" {{ $menuEnabled ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                </div>

                <hr class="border-slate-800">

                {{-- Texto de cabecera --}}
                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Texto de cabecera</label>
                    <input type="text" name="menu_header_text"
                           value="{{ $settings['menu_header_text'] ?? 'Nuestra Carta' }}"
                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                           placeholder="Nuestra Carta">
                    <p class="text-xs text-slate-500 mt-1">Se muestra como título principal al inicio del menú.</p>
                </div>

                {{-- Texto de footer --}}
                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Texto de pie de página</label>
                    <textarea name="menu_footer_text" rows="2"
                              class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors resize-none"
                              placeholder="Ej: Los precios incluyen IVA. Consulte por alérgenos.">{{ $settings['menu_footer_text'] ?? '' }}</textarea>
                    <p class="text-xs text-slate-500 mt-1">Opcional. Se muestra al final de la página del menú.</p>
                </div>

            </div>
            <div class="p-6 border-t border-slate-800">
                <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-6 py-2.5 rounded-xl transition-all">
                    Guardar Configuración
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
