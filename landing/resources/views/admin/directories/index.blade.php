@extends('admin/layout')

@section('title', 'Directorios')
@section('header', 'Directorios')
@section('subheader', 'Carpetas de primer nivel de una ruta del servidor (ej. public_html). Solo lectura.')

@section('content')
<form action="{{ url('admin/directories') }}" method="POST" class="bg-slate-900 border border-slate-800 rounded-3xl p-4 sm:p-6 flex flex-col sm:flex-row gap-3 sm:items-end">
    <input type="hidden" name="_token" value="{{ $_SESSION['csrf_token'] ?? '' }}">
    <div class="flex-1 min-w-0">
        <label for="dir-root" class="block text-sm font-medium text-slate-400 mb-2">Ruta a listar</label>
        <input id="dir-root" type="text" name="root" pattern="/.*" title="Ruta absoluta: tiene que empezar con /" value="{{ $root }}" placeholder="{{ $default ?: '/home/cuenta/public_html' }}"
               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white font-mono text-sm focus:outline-none focus:border-blue-500">
        <p class="text-xs text-slate-500 mt-1">Ruta absoluta. Vacía = valor por defecto{{ $default ? ' (' . $default . ')' : '' }}.</p>
    </div>
    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2">
        <i class="fa-solid fa-floppy-disk"></i> Guardar
    </button>
</form>

@if($error)
    <div class="bg-red-950/50 border border-red-800 rounded-2xl p-4 flex items-start gap-3">
        <i class="fa-solid fa-circle-exclamation text-red-400 mt-0.5"></i>
        <p class="text-sm text-red-300">{{ $error }}</p>
    </div>
@elseif($root === '')
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 text-center text-slate-500">
        <i class="fa-solid fa-folder-open text-3xl mb-3"></i>
        <p>Configurá una ruta para ver sus carpetas.</p>
    </div>
@else
<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
    <div class="p-4 sm:p-6 border-b border-slate-800 flex flex-wrap justify-between items-center gap-2 bg-slate-900/50">
        <h3 class="text-lg font-bold text-white font-mono break-all">{{ $resolved }}/*</h3>
        <span class="text-xs text-slate-500">{{ count($dirs) }} {{ count($dirs) === 1 ? 'carpeta' : 'carpetas' }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Carpeta</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Sitio</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">App</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Modificado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($dirs as $dir)
                    <tr class="hover:bg-slate-800/30 transition-colors">
                        <td class="px-3 py-3 md:px-6 md:py-4">
                            <span class="text-white font-mono text-sm flex items-center gap-2">
                                <i class="fa-solid {{ $dir['symlink'] ? 'fa-link' : 'fa-folder' }} text-amber-400"></i> {{ $dir['name'] }}
                            </span>
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4 text-sm">
                            @if($dir['domain'])
                                <a href="https://{{ $dir['domain'] }}" target="_blank" rel="noopener" class="text-blue-400 hover:underline">{{ $dir['domain'] }}</a>
                            @else
                                <span class="text-slate-600">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4">
                            @if($dir['app'])
                                <span class="px-2 py-1 bg-green-500/10 text-green-400 text-xs font-bold rounded-lg border border-green-500/20">Instalada</span>
                            @else
                                <span class="text-slate-600 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4 text-sm text-slate-400 hidden sm:table-cell">
                            {{ $dir['modified'] ? date('d/m/Y H:i', $dir['modified']) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">La carpeta está vacía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
