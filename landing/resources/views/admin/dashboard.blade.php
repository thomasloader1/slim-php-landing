@extends('admin/layout')

@section('title', 'Dashboard')
@section('header', 'Bienvenido de nuevo')
@section('subheader', 'Resumen general de tu actividad y contenido.')

@section('content')
@php $__cards = array_filter(['links', 'users', 'settings'], 'can'); @endphp
@if(!$__cards)
<div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-sm mb-6">
    <h4 class="text-lg font-bold text-white mb-1">Tus secciones</h4>
    <p class="text-sm text-slate-500 mb-4">Estas son las partes del panel a las que tenés acceso.</p>
    <div class="flex flex-wrap gap-2">
        @forelse(\App\Services\AccessService::visibleSections() as $__s)
            <a href="{{ url($__s['url']) }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                <i class="fa-solid {{ $__s['icon'] }}"></i> {{ $__s['label'] }}
            </a>
        @empty
            <p class="text-sm text-slate-500">Todavía no tenés secciones asignadas. Pedile acceso a un administrador.</p>
        @endforelse
    </div>
</div>
@endif
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Stat Card -->
    @if(can('links'))
    <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-sm">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center text-blue-500">
                <i class="fa-solid fa-link text-xl"></i>
            </div>
            <div>
                <p class="text-slate-500 text-sm font-medium">Total Enlaces</p>
                <h3 class="text-3xl font-bold text-white">{{ $linksCount }}</h3>
            </div>
        </div>
        <a href="{{ url('admin/links') }}" class="text-blue-500 text-xs font-bold uppercase tracking-wider hover:text-blue-400 transition-colors">Gestionar Links →</a>
    </div>
    @endif

    <!-- Stat Card -->
    @if(can('users'))
    <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-sm">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500">
                <i class="fa-solid fa-users text-xl"></i>
            </div>
            <div>
                <p class="text-slate-500 text-sm font-medium">Usuarios</p>
                <h3 class="text-3xl font-bold text-white">{{ $usersCount }}</h3>
            </div>
        </div>
        <p class="text-slate-500 text-xs">Usuarios con acceso al panel.</p>
    </div>
    @endif

    <!-- Stat Card -->
    @if(can('settings'))
    <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-sm">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-green-500/10 rounded-2xl flex items-center justify-center text-green-500">
                <i class="fa-solid fa-palette text-xl"></i>
            </div>
            <div>
                <p class="text-slate-500 text-sm font-medium">Diseño Activo</p>
                <h3 class="text-xl font-bold text-white">{{ $settings['site_name'] ?? 'Landing' }}</h3>
            </div>
        </div>
        <a href="{{ url('admin/settings') }}" class="text-green-500 text-xs font-bold uppercase tracking-wider hover:text-green-400 transition-colors">Editar Estilo →</a>
    </div>
    @endif
</div>

@if(can('settings'))
<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden p-8">
    <div class="flex items-center justify-between mb-8">
        <h4 class="text-xl font-bold text-white">Configuración Rápida</h4>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="space-y-4">
            <div class="flex justify-between items-center p-4 bg-slate-950 border border-slate-800 rounded-2xl">
                <div>
                    <p class="text-sm font-bold text-white">Título del Sitio</p>
                    <p class="text-xs text-slate-500">{{ $settings['landing_title'] }}</p>
                </div>
            </div>
            <div class="flex justify-between items-center p-4 bg-slate-950 border border-slate-800 rounded-2xl">
                <div>
                    <p class="text-sm font-bold text-white">Color de Acento</p>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="w-3 h-3 rounded-full" style="background: {{ $settings['landing_accent_color'] }}"></div>
                        <p class="text-xs text-slate-500">{{ $settings['landing_accent_color'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-blue-500/5 border border-blue-500/10 p-6 rounded-2xl relative overflow-hidden">
            <div class="relative z-10">
                <i class="fa-solid fa-circle-info text-blue-500 text-xl mb-4 block"></i>
                <h5 class="font-bold text-white mb-2">Tip de Administración</h5>
                <p class="text-slate-400 text-sm leading-relaxed">
                    Arrastrá los enlaces, productos y ubicaciones desde su ícono <i class="fa-solid fa-grip-vertical"></i> para cambiar el orden en que aparecen en la landing.
                </p>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
