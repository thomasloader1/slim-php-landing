@extends('admin/layout')

@section('title', 'Log #' . $entry->id)
@section('header', 'Log #' . $entry->id)
@section('subheader', $source_label . ' · ' . date('d/m/Y H:i:s', strtotime($entry->created_at)))

@section('content')
@php
    $fields = [
        'Nivel'      => strtoupper($entry->level),
        'Excepción'  => $entry->exception_class ? $entry->exception_class . ($entry->code ? ' (código ' . $entry->code . ')' : '') : null,
        'Archivo'    => $entry->file ? $entry->file . ':' . $entry->line : null,
        'Request'    => trim(($entry->method ?? '') . ' ' . ($entry->uri ?? '')) ?: null,
        'IP'         => $entry->ip,
        'User-Agent' => $entry->user_agent,
    ];
@endphp
<div class="space-y-4">
    <a href="{{ url('admin/system/logs') }}" class="text-slate-400 hover:text-white text-sm"><i class="fa-solid fa-arrow-left text-xs mr-1"></i> Logs</a>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
        <p class="text-white whitespace-pre-wrap break-words">{{ $entry->message }}</p>
        <dl class="mt-4 grid grid-cols-1 sm:grid-cols-[8rem_1fr] gap-x-4 gap-y-2 text-sm">
            @foreach($fields as $label => $value)
                @if($value)
                    <dt class="text-slate-500">{{ $label }}</dt>
                    <dd class="text-slate-300 font-mono text-xs break-all sm:pt-0.5">{{ $value }}</dd>
                @endif
            @endforeach
        </dl>
    </div>

    @if($trace)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-white font-bold mb-3">Stack trace</h3>
            <pre class="text-xs text-slate-400 whitespace-pre-wrap break-all">{{ $trace }}</pre>
        </div>
    @endif

    @if($context && $context !== '[]')
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-white font-bold mb-3">Contexto</h3>
            <pre class="text-xs text-slate-300 whitespace-pre-wrap break-all">{{ $context }}</pre>
        </div>
    @endif
</div>
@endsection
