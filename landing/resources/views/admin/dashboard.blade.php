@extends('admin/layout')

@section('title', 'Dashboard')
@section('header', 'Bienvenido de nuevo')
@section('subheader', 'Resumen general de tu actividad y contenido.')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Stat Card -->
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
        <a href="/admin/links" class="text-blue-500 text-xs font-bold uppercase tracking-wider hover:text-blue-400 transition-colors">Gestionar Links →</a>
    </div>

    <!-- Stat Card -->
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

    <!-- Stat Card -->
    <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-sm">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-green-500/10 rounded-2xl flex items-center justify-center text-green-500">
                <i class="fa-solid fa-palette text-xl"></i>
            </div>
            <div>
                <p class="text-slate-500 text-sm font-medium">Diseño Activo</p>
                <h3 class="text-xl font-bold text-white">{{ $settings['site_name'] ?? 'Landing V2' }}</h3>
            </div>
        </div>
        <a href="/admin/settings" class="text-green-500 text-xs font-bold uppercase tracking-wider hover:text-green-400 transition-colors">Editar Estilo →</a>
    </div>
</div>

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
                    Podés reordenar los enlaces en la sección "Enlaces" usando el campo de orden. Los enlaces con un número menor aparecerán primero en la landing page.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
