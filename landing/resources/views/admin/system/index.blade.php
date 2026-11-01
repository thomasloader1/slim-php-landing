@extends('admin/layout')

@section('title', 'Sistema')
@section('header', 'Sistema')
@section('subheader', 'Diagnóstico de la instalación y reparaciones sin tocar la base de datos a mano.')

@section('content')
@php
    $icons  = ['ok' => 'fa-circle-check text-emerald-400', 'warn' => 'fa-triangle-exclamation text-yellow-400', 'error' => 'fa-circle-xmark text-red-400'];
    $groups = collect($checks)->groupBy('group');
    $errors = collect($checks)->where('status', 'error')->count();
    $warns  = collect($checks)->where('status', 'warn')->count();
    $migLabels = ['applied' => 'Aplicada', 'pending' => 'Pendiente', 'changed' => 'Modificada', 'missing_file' => 'Falta archivo'];
@endphp
<div class="space-y-6 max-w-5xl">

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <p class="text-slate-500 text-sm">Errores</p>
            <p class="text-3xl font-bold {{ $errors ? 'text-red-400' : 'text-white' }}">{{ $errors }}</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <p class="text-slate-500 text-sm">Advertencias</p>
            <p class="text-3xl font-bold {{ $warns ? 'text-yellow-400' : 'text-white' }}">{{ $warns }}</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col gap-2">
            <form action="{{ url('admin/system/migrate') }}" method="POST">
                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <button class="w-full bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl font-bold text-sm transition-all">
                    <i class="fa-solid fa-database text-xs mr-1"></i> Aplicar migraciones
                </button>
            </form>
            <form action="{{ url('admin/system/rotate-keys') }}" method="POST"
                  onsubmit="return confirm('¿Re-cifrar todas las credenciales con la clave activa?')">
                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <button class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 px-4 py-2 rounded-xl text-sm transition-all">
                    <i class="fa-solid fa-key text-xs mr-1"></i> Re-cifrar con clave activa
                </button>
            </form>
        </div>
    </div>

    @if(!empty($log))
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-white font-bold mb-3">Resultado</h3>
            <pre class="text-xs text-slate-300 whitespace-pre-wrap">@foreach($log as $line){{ $line }}
@endforeach</pre>
        </div>
    @endif

    @foreach($groups as $group => $items)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <h3 class="text-white font-bold px-6 py-4 border-b border-slate-800">{{ $group }}</h3>
            <ul class="divide-y divide-slate-800">
                @foreach($items as $c)
                    <li class="px-6 py-3 flex gap-3 items-start">
                        <i class="fa-solid {{ $icons[$c['status']] }} mt-1"></i>
                        <div class="min-w-0">
                            <p class="text-white text-sm font-medium">{{ $c['label'] }}</p>
                            <p class="text-slate-400 text-sm break-words">{{ $c['detail'] }}</p>
                            @if($c['fix'])
                                <p class="text-xs text-slate-500 mt-1"><i class="fa-solid fa-wrench mr-1"></i>{{ $c['fix'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach

    @if(!empty($migrations))
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <h3 class="text-white font-bold px-6 py-4 border-b border-slate-800">Archivos de esquema (DB master)</h3>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-800">
                    @foreach($migrations as $m)
                        <tr>
                            <td class="px-6 py-3 text-slate-300 font-mono text-xs">db/{{ $m['file'] }}</td>
                            <td class="px-6 py-3 text-right">
                                <span class="text-xs px-2 py-1 rounded-lg {{ $m['status'] === 'applied' ? 'bg-emerald-950 text-emerald-300' : ($m['status'] === 'missing_file' ? 'bg-red-950 text-red-300' : 'bg-yellow-950 text-yellow-300') }}">
                                    {{ $migLabels[$m['status']] ?? $m['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
