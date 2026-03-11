@extends('admin/layout')

@section('title', 'Nuevo Enlace')
@section('header', 'Crear Enlace')
@section('subheader', 'Agrega un nuevo link a tu perfil digital.')

@section('content')
<div class="max-w-2xl">
    <form action="{{ url('admin/links/create') }}" method="POST" class="bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Título del Enlace</label>
                <input type="text" name="title" required placeholder="Ej: Mi Instagram"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
            </div>
            
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">URL / Destino</label>
                <input type="url" name="url" required placeholder="https://..."
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
            </div>

            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Icono (FontAwesome Class)</label>
                <div class="flex gap-2">
                    <input type="text" name="icon" value="fa-link" required
                           class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                    <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center border border-slate-700">
                        <i class="fa-solid fa-link text-slate-400"></i>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 mt-2 italic">Ej: fa-brands fa-instagram, fa-globe, etc.</p>
            </div>

            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Color de Acento</label>
                <div class="flex gap-2">
                    <input type="color" name="color" value="#3b82f6"
                           class="w-12 h-12 bg-slate-950 border border-slate-800 rounded-xl p-1 focus:border-blue-500/50 outline-none transition-all cursor-pointer">
                    <input type="text" value="#3b82f6" readonly
                           class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-500 text-sm">
                </div>
            </div>

            <div class="col-span-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700">
                        <input type="checkbox" name="active" checked class="sr-only peer">
                        <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                    </div>
                    <span class="text-sm font-medium text-slate-400">Mostrar enlace inmediatamente</span>
                </label>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                Guardar Enlace
            </button>
            <a href="{{ url('admin/links') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-3 rounded-xl font-bold transition-all">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
