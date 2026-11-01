@extends('admin/layout')

@section('title', 'Servidores')
@section('header', 'Servidores')
@section('subheader', 'Servidores de producción (cPanel) administrados por SSH con clave desde este panel.')

@section('content')
<div class="space-y-6 max-w-5xl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-500">Las claves privadas se leen de <code class="text-slate-300">{{ $keysDir }}</code> (montada solo lectura). Nunca se guardan en la base de datos.</p>
        <a href="{{ url('admin/servers/create') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-xl font-bold transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus text-sm"></i> Nuevo servidor
        </a>
    </div>

    @if($servers->isEmpty())
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center">
            <i class="fa-solid fa-terminal text-4xl text-slate-700 mb-4"></i>
            <p class="text-slate-500">No hay servidores configurados.</p>
        </div>
    @else
        <div class="bg-slate-900 border border-slate-800 rounded-2xl divide-y divide-slate-800">
            @foreach($servers as $s)
                <a href="{{ url('admin/servers/' . $s->id) }}" class="flex flex-wrap items-center gap-4 px-6 py-4 hover:bg-slate-800/50 transition-colors">
                    <i class="fa-solid fa-server text-slate-500"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-bold">{{ $s->name }}</p>
                        <p class="text-sm text-slate-500 truncate">{{ $s->username }}@{{ $s->host }}:{{ $s->port }} · {{ $s->remote_app_path }}</p>
                    </div>
                    @if(!$s->host_key_fingerprint)
                        <span class="text-xs text-yellow-400 bg-yellow-400/10 px-2 py-1 rounded-full">Huella sin confirmar</span>
                    @elseif($s->last_check_at)
                        <span class="text-xs {{ $s->last_check_ok ? 'text-emerald-400 bg-emerald-400/10' : 'text-red-400 bg-red-400/10' }} px-2 py-1 rounded-full">
                            {{ $s->last_check_ok ? 'OK' : 'Con errores' }} · {{ $s->last_check_at->format('d/m H:i') }}
                        </span>
                    @endif
                    <i class="fa-solid fa-chevron-right text-slate-600 text-xs"></i>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
