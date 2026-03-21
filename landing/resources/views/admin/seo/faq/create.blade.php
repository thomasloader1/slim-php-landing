@extends('admin/layout')

@section('title', 'Nueva Pregunta')
@section('header', 'Crear Pregunta')
@section('subheader', 'Agrega una nueva pregunta frecuente al schema FAQ.')

@section('content')
<div class="max-w-2xl">
    <div class="mb-4">
        <a href="{{ url('admin/seo/faq') }}" class="text-slate-400 hover:text-white text-sm transition-colors">
            <i class="fa-solid fa-chevron-left text-xs mr-1"></i> Volver a FAQ
        </a>
    </div>

    <form action="{{ url('admin/seo/faq/create') }}" method="POST" class="bg-slate-900 border border-slate-800 p-5 sm:p-8 rounded-3xl shadow-sm space-y-6">
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-2">Pregunta</label>
            <input type="text" name="question" required placeholder="Ej: ¿Cuál es el horario de atención?"
                   class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-400 mb-2">Respuesta</label>
            <textarea name="answer" rows="4" required placeholder="Escribí la respuesta a esta pregunta..."
                      class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white resize-none"></textarea>
        </div>

        <div>
            <label class="flex items-center gap-3 cursor-pointer group">
                <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700 shrink-0">
                    <input type="checkbox" name="active" checked class="sr-only peer">
                    <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                </div>
                <span class="text-sm font-medium text-slate-400">Activa (visible en la landing)</span>
            </label>
        </div>

        <div class="pt-4 border-t border-slate-800 flex flex-wrap gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                Guardar Pregunta
            </button>
            <a href="{{ url('admin/seo/faq') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-3 rounded-xl font-bold transition-all">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
