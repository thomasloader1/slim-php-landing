@extends('admin/layout')

@section('title', $site ? 'Editar Sitio' : 'Nuevo Sitio')
@section('header', $site ? 'Editar Sitio' : 'Nuevo Sitio')
@section('subheader', $site ? 'Modifica los datos del sitio gestionado.' : 'Registra una nueva landing para gestionar.')

@section('content')
<div class="max-w-3xl">
    <a href="{{ url('admin/sites') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-white transition-colors mb-6">
        <i class="fa-solid fa-arrow-left text-xs"></i> Volver a Sitios
    </a>

    <form action="{{ url($site ? 'admin/sites/edit/' . $site->id : 'admin/sites/create') }}" method="POST" class="space-y-8">

        {{-- Información del Sitio --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
            <h3 class="text-white font-bold flex items-center gap-2">
                <i class="fa-solid fa-globe text-slate-500 text-sm"></i> Información del Sitio
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Nombre <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ $site->name ?? '' }}" required
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Dominio <span class="text-red-400">*</span></label>
                    <input type="text" name="domain" value="{{ $site->domain ?? '' }}" required placeholder="ejemplo.com"
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        {{-- Conexión a Base de Datos --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
            <h3 class="text-white font-bold flex items-center gap-2">
                <i class="fa-solid fa-database text-slate-500 text-sm"></i> Conexión a Base de Datos
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Host <span class="text-red-400">*</span></label>
                    <input type="text" name="db_host" value="{{ $site->db_host ?? 'localhost' }}" required
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Puerto</label>
                    <input type="number" name="db_port" value="{{ $site->db_port ?? 3306 }}"
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Base de Datos <span class="text-red-400">*</span></label>
                    <input type="text" name="db_name" value="{{ $site->db_name ?? '' }}" required
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Usuario <span class="text-red-400">*</span></label>
                    <input type="text" name="db_user" value="{{ $site->db_user ?? '' }}" required
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm text-slate-400 mb-2">
                        Contraseña {{ $site ? '(dejar vacío para no cambiar)' : '' }} <span class="{{ $site ? '' : 'text-red-400' }}">{{ $site ? '' : '*' }}</span>
                    </label>
                    <input type="password" name="db_pass" {{ $site ? '' : 'required' }}
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                </div>
            </div>
            @if($site)
            <div class="pt-2">
                <button type="button" id="btn-test-conn"
                        onclick="testConnectionForm({{ $site->id }})"
                        class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl transition-all flex items-center gap-2 text-sm border border-slate-700">
                    <i class="fa-solid fa-plug text-xs"></i> Probar Conexión
                </button>
                <span id="test-conn-result" class="text-sm ml-3"></span>
            </div>
            @endif
        </div>

        {{-- Plan y Contrato --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
            <h3 class="text-white font-bold flex items-center gap-2">
                <i class="fa-solid fa-file-contract text-slate-500 text-sm"></i> Plan y Contrato
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Plan</label>
                    <select name="plan_name"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                        @foreach(['basico', 'profesional', 'premium'] as $plan)
                            <option value="{{ $plan }}" {{ ($site->plan_name ?? 'basico') === $plan ? 'selected' : '' }}>
                                {{ ucfirst($plan) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Fecha Inicio <span class="text-red-400">*</span></label>
                    <input type="date" name="plan_start" value="{{ $site ? $site->plan_start->format('Y-m-d') : date('Y-m-d') }}" required
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Fecha Fin <span class="text-red-400">*</span></label>
                    <input type="date" name="plan_end" value="{{ $site ? $site->plan_end->format('Y-m-d') : '' }}" required
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                </div>
            </div>
        </div>

        {{-- Dominio --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
            <h3 class="text-white font-bold flex items-center gap-2">
                <i class="fa-solid fa-earth-americas text-slate-500 text-sm"></i> Dominio
            </h3>
            <div>
                <label class="block text-sm text-slate-400 mb-2">Fecha de Vencimiento del Dominio</label>
                <input type="date" name="domain_expiry" value="{{ $site && $site->domain_expiry ? $site->domain_expiry->format('Y-m-d') : '' }}"
                       class="w-full sm:w-1/2 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                <p class="text-xs text-slate-600 mt-1">Informativo. No afecta la suspensión automática.</p>
            </div>
        </div>

        {{-- Bloqueo --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
            <h3 class="text-white font-bold flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-slate-500 text-sm"></i> Bloqueo y Redirección
            </h3>
            <div>
                <label class="block text-sm text-slate-400 mb-2">URL de Redirección al Suspender</label>
                <input type="url" name="redirect_url" value="{{ $site->redirect_url ?? $defaultRedirect ?? '' }}" placeholder="https://..."
                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                <p class="text-xs text-slate-600 mt-1">URL a la que se redirige el tráfico público cuando el sitio está suspendido.</p>
            </div>
            <div>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="hidden" name="auto_suspend" value="0">
                    <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700 shrink-0">
                        <input type="checkbox" name="auto_suspend" value="1"
                               {{ ($site->auto_suspend ?? 1) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                    </div>
                    <div>
                        <span class="text-sm text-white font-bold">Auto-suspensión</span>
                        <p class="text-xs text-slate-500">Suspender automáticamente cuando vence el plan (vía cron).</p>
                    </div>
                </label>
            </div>
        </div>

        {{-- Notas --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
            <h3 class="text-white font-bold flex items-center gap-2">
                <i class="fa-solid fa-note-sticky text-slate-500 text-sm"></i> Notas
            </h3>
            <textarea name="notes" rows="3" placeholder="Notas internas sobre este sitio..."
                      class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors resize-y">{{ $site->notes ?? '' }}</textarea>
        </div>

        {{-- Guardar --}}
        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                {{ $site ? 'Actualizar Sitio' : 'Crear Sitio' }}
            </button>
        </div>
    </form>
</div>
@endsection

@if($site)
@push('scripts')
<script>
function testConnectionForm(siteId) {
    var btn = document.getElementById('btn-test-conn');
    var result = document.getElementById('test-conn-result');
    var icon = btn.querySelector('i');
    icon.className = 'fa-solid fa-circle-notch fa-spin text-xs';
    btn.disabled = true;
    result.textContent = '';

    fetch('{{ url("admin/sites") }}/' + siteId + '/test', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        icon.className = 'fa-solid fa-plug text-xs';
        btn.disabled = false;
        result.textContent = data.message;
        result.className = 'text-sm ml-3 ' + (data.success ? 'text-emerald-400' : 'text-red-400');
    })
    .catch(function() {
        icon.className = 'fa-solid fa-plug text-xs';
        btn.disabled = false;
        result.textContent = 'Error de red.';
        result.className = 'text-sm ml-3 text-red-400';
    });
}
</script>
@endpush
@endif
