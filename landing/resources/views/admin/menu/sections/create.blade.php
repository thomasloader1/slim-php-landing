@extends('admin/layout')

@section('title', 'Nueva Sección')
@section('header', 'Nueva Sección')
@section('subheader', 'Creá una categoría para agrupar ítems del menú.')

@section('content')
<div class="max-w-xl">
    <form action="{{ url('admin/menu/sections/create') }}" method="POST">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-800 bg-slate-900/50">
                <h3 class="text-lg font-bold text-white">Datos de la Sección</h3>
            </div>
            <div class="p-6 space-y-5">

                <div>
                    <label class="block text-sm font-bold text-slate-300 mb-2">Nombre <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required
                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition-colors"
                           placeholder="Ej: Entradas, Bebidas, Postres...">
                </div>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="active" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                    <span class="text-sm text-slate-300">Sección activa</span>
                </div>

            </div>
            <div class="p-6 border-t border-slate-800 flex gap-3">
                <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-6 py-2.5 rounded-xl transition-all">
                    Guardar Sección
                </button>
                <a href="{{ url('admin/menu/sections') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-6 py-2.5 rounded-xl transition-all">
                    Cancelar
                </a>
            </div>
        </div>
    </form>
</div>
@endsection
