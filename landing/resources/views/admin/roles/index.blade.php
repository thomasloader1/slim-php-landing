@extends('admin/layout')

@section('title', 'Roles y permisos')
@section('header', 'Roles y permisos')
@section('subheader', $inMaster
    ? 'Definí qué secciones del panel ve cada rol de la master. Para un sitio cliente, usá "Roles y permisos" desde Sitios.'
    : 'Definí qué secciones del panel ve cada rol de este sitio.')

@section('content')
@include('admin/partials/section-tabs', ['group' => 'users'])
<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
    <div class="p-4 sm:p-6 border-b border-slate-800 flex flex-wrap justify-between items-center gap-2 bg-slate-900/50">
        <div>
            <h3 class="text-lg font-bold text-white">Roles</h3>
            <p class="text-xs text-slate-500">El root ve todo y no aparece acá. Los módulos apagados se ocultan para todos.</p>
        </div>
        <a href="{{ url('admin/roles/create') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nuevo rol
        </a>
    </div>

    <div class="divide-y divide-slate-800/50">
        @foreach($roles as $role)
            @php $granted = $role->permissions(); $users = $role->usersCount(); @endphp
            <div class="p-4 sm:p-6 flex flex-col md:flex-row md:items-start gap-4">
                <div class="md:w-56 shrink-0">
                    <p class="text-white font-bold flex items-center gap-2">
                        {{ $role->name }}
                        @if($role->is_system)
                            <span class="px-1.5 py-0.5 bg-slate-800 text-slate-500 text-[9px] font-bold rounded uppercase border border-slate-700">Sistema</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-500 font-mono">{{ $role->slug }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $users }} {{ $users === 1 ? 'usuario' : 'usuarios' }}</p>
                </div>
                <div class="flex-1 flex flex-wrap gap-1.5">
                    @forelse($catalog as $key => $perm)
                        @php
                            $__features = \App\Support\Permissions::FEATURES[$key] ?? [];
                            $__partial = array_values(array_intersect(array_keys($__features), $granted));
                        @endphp
                        @if(in_array($key, $granted, true))
                            <span class="px-2 py-1 bg-blue-500/10 text-blue-300 text-xs font-bold rounded-lg border border-blue-500/20">
                                <i class="fa-solid {{ $perm['icon'] }} text-[10px]"></i> {{ $perm['label'] }}
                            </span>
                        @elseif($__partial)
                            <span class="px-2 py-1 bg-slate-800 text-slate-300 text-xs font-bold rounded-lg border border-slate-700"
                                  title="{{ implode(', ', array_map(fn ($f) => $__features[$f]['label'], $__partial)) }}">
                                <i class="fa-solid {{ $perm['icon'] }} text-[10px]"></i> {{ $perm['label'] }}
                                <span class="font-normal text-slate-500">· {{ count($__partial) }}/{{ count($__features) }}</span>
                            </span>
                        @endif
                    @empty
                    @endforelse
                    @if(!$granted)
                        <span class="text-xs text-slate-500 italic">Solo ve el Dashboard</span>
                    @endif
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ url('admin/roles/edit/' . $role->id) }}" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-all" title="Editar permisos" aria-label="Editar permisos de {{ $role->name }}">
                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                    </a>
                    @unless($role->is_system)
                        <form action="{{ url('admin/roles/delete/' . $role->id) }}" method="POST" onsubmit="return confirm('¿Eliminar el rol {{ $role->name }}?')" data-no-loading>
                            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="p-2 bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-500 rounded-lg transition-all" title="Eliminar" aria-label="Eliminar rol {{ $role->name }}">
                                <i class="fa-solid fa-trash-can text-sm"></i>
                            </button>
                        </form>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
