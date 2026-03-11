@extends('admin/layout')

@section('title', 'Nuevo Usuario')
@section('header', 'Crear Usuario')
@section('subheader', 'Agrega un nuevo administrador al panel.')

@section('content')
<div class="max-w-2xl">
    <form action="/admin/users/create" method="POST" class="bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Nombre Completo</label>
                <input type="text" name="name" required placeholder="Ej: Juan Pérez"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
            </div>
            
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Correo Electrónico</label>
                <input type="email" name="email" required placeholder="juan@ejemplo.com"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
            </div>

            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Contraseña</label>
                <input type="password" name="password" required
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
            </div>

            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Rol de Usuario</label>
                <select name="role" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                    <option value="admin">Administrador</option>
                    <option value="editor">Editor</option>
                </select>
            </div>

            <div class="col-span-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700">
                        <input type="checkbox" name="active" checked class="sr-only peer">
                        <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                    </div>
                    <span class="text-sm font-medium text-slate-400">Usuario activo (puede loguearse)</span>
                </label>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                Crear Administrador
            </button>
            <a href="/admin/users" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-3 rounded-xl font-bold transition-all">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
