@extends('admin/layout')

@section('title', 'Logs de errores')
@section('header', 'Logs de errores')
@section('subheader', 'Errores y eventos de la master y de cada sitio. Se borran solos a los ' . $retention . ' días.')

@section('content')
@php
    $badge = [
        'critical' => 'bg-red-500/10 text-red-400 border-red-500/20',
        'error'    => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
        'warning'  => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        'security' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        'audit'    => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
    ];
    $input = 'bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:border-blue-500 outline-none';
@endphp
<div class="space-y-4">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ url('admin/system') }}" class="text-slate-400 hover:text-white text-sm"><i class="fa-solid fa-arrow-left text-xs mr-1"></i> Sistema</a>
        <form action="{{ url('admin/system/logs/clear') }}" method="POST" class="flex items-center gap-2"
              onsubmit="return confirm(this.source.value ? '¿Vaciar los logs de la base elegida?' : '¿Vaciar los logs de TODAS las bases (master + sitios)?')">
            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="source" value="{{ $filters['source'] }}">
            <button class="bg-red-600/90 hover:bg-red-500 text-white px-4 py-2 rounded-xl font-bold text-sm transition-all">
                <i class="fa-solid fa-trash text-xs mr-1"></i>
                {{ $filters['source'] !== '' ? 'Vaciar logs de ' . ($sources[$filters['source']] ?? 'esta base') : 'Vaciar todos los logs' }}
            </button>
        </form>
    </div>

    <form method="GET" action="{{ url('admin/system/logs') }}" class="bg-slate-900 border border-slate-800 rounded-2xl p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <label class="sr-only" for="f-q">Buscar</label>
        <input id="f-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar en mensaje, URL o clase" class="{{ $input }} lg:col-span-2">
        <label class="sr-only" for="f-level">Nivel</label>
        <select id="f-level" name="level" class="{{ $input }}">
            <option value="">Todos los niveles</option>
            @foreach($levels as $lvl)
                <option value="{{ $lvl }}" @selected($filters['level'] === $lvl)>{{ ucfirst($lvl) }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="f-source">Base</label>
        <select id="f-source" name="source" class="{{ $input }}">
            <option value="">Todas las bases</option>
            @foreach($sources as $key => $label)
                <option value="{{ $key }}" @selected($filters['source'] === (string) $key)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <label class="sr-only" for="f-from">Desde</label>
            <input id="f-from" type="date" name="from" value="{{ $filters['from'] }}" class="{{ $input }} flex-1 min-w-0">
            <button class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl font-bold text-sm" aria-label="Filtrar"><i class="fa-solid fa-filter text-xs"></i></button>
        </div>
    </form>

    @php $problems = collect($status)->filter(fn ($s) => $s['error']); @endphp
    @if($problems->isNotEmpty())
        <div class="bg-yellow-950/40 border border-yellow-800/50 rounded-2xl px-4 py-3 text-sm text-yellow-200 space-y-1">
            @foreach($problems as $p)
                <p><i class="fa-solid fa-triangle-exclamation mr-1"></i><strong>{{ $p['label'] }}:</strong> {{ $p['error'] }}</p>
            @endforeach
        </div>
    @endif

    @if($truncated)
        <p class="text-slate-500 text-xs">Se muestran los {{ count($rows) }} más recientes. Usá los filtros para acotar.</p>
    @endif

    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                        <th class="px-3 py-3 md:px-5 font-bold">Fecha</th>
                        <th class="px-3 py-3 md:px-5 font-bold">Nivel</th>
                        <th class="px-3 py-3 md:px-5 font-bold">Mensaje</th>
                        <th class="px-3 py-3 md:px-5 font-bold hidden md:table-cell">Base</th>
                        <th class="px-3 py-3 md:px-5 font-bold hidden lg:table-cell">Request</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($rows as $r)
                        @php $href = url('admin/system/logs/' . $r->source . '/' . $r->id); @endphp
                        <tr class="hover:bg-slate-800/30 transition-colors cursor-pointer align-top" onclick="location.href='{{ $href }}'">
                            <td class="px-3 py-3 md:px-5 text-slate-400 text-xs whitespace-nowrap font-mono">{{ date('d/m H:i:s', strtotime($r->created_at)) }}</td>
                            <td class="px-3 py-3 md:px-5">
                                <span class="px-2 py-1 text-[10px] font-bold rounded-full uppercase border {{ $badge[$r->level] ?? '' }}">{{ $r->level }}</span>
                            </td>
                            <td class="px-3 py-3 md:px-5 min-w-[16rem]">
                                <a href="{{ $href }}" class="text-white text-sm hover:text-blue-400 break-words line-clamp-2">{{ $r->message }}</a>
                                @if($r->exception_class)
                                    <p class="text-slate-500 text-xs font-mono mt-0.5 break-all">{{ $r->exception_class }} · {{ $r->file }}:{{ $r->line }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 md:px-5 text-slate-400 text-xs hidden md:table-cell whitespace-nowrap">{{ $r->source_label }}</td>
                            <td class="px-3 py-3 md:px-5 text-slate-500 text-xs font-mono hidden lg:table-cell break-all max-w-xs">
                                {{ $r->method }} {{ $r->uri }}@if($r->ip)<br>{{ $r->ip }}@endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">No hay errores registrados{{ array_filter($filters) ? ' con esos filtros' : '' }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
