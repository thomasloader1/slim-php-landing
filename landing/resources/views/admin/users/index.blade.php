@extends('admin/layout')

@section('title', 'Gestionar Usuarios')
@section('header', 'Usuarios')
@section('subheader', 'Administra los accesos al panel de control.')

@section('content')
<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
    <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
        <h3 class="text-lg font-bold text-white">Administradores</h3>
        <a href="/admin/users/create" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Nuevo Usuario
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                    <th class="px-6 py-4 font-bold">Nombre</th>
                    <th class="px-6 py-4 font-bold">Email</th>
                    <th class="px-6 py-4 font-bold">Rol</th>
                    <th class="px-6 py-4 font-bold">Estado</th>
                    <th class="px-6 py-4 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/50">
                @foreach($users as $user)
                    <tr class="hover:bg-slate-800/20 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-500/10 text-blue-500 flex items-center justify-center font-bold text-xs uppercase">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <span class="text-white font-bold">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-400 text-sm">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 bg-slate-800 text-slate-400 text-[10px] font-bold rounded-full uppercase border border-slate-700">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->active)
                                <span class="px-2 py-1 bg-green-500/10 text-green-500 text-[10px] font-bold rounded-full uppercase border border-green-500/10">Activo</span>
                            @else
                                <span class="px-2 py-1 bg-slate-800 text-slate-500 text-[10px] font-bold rounded-full uppercase border border-slate-700">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="/admin/users/edit/{{ $user->id }}" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-all">
                                    <i class="fa-solid fa-user-gear text-sm"></i>
                                </a>
                                @if($user->id != 1)
                                <form action="/admin/users/delete/{{ $user->id }}" method="POST" onsubmit="return confirm('¿Eliminar este administrador?')">
                                    <button type="submit" class="p-2 bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-500 rounded-lg transition-all">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
