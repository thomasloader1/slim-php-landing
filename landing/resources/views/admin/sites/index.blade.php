@extends('admin/layout')

@section('title', 'Sitios')
@section('header', 'Gestor de Sitios')
@section('subheader', 'Administra las landings gestionadas desde este panel.')

@section('content')
<div class="space-y-6">
    <div class="flex justify-end">
        <a href="{{ url('admin/sites/create') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-xl font-bold transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus text-sm"></i> Nuevo Sitio
        </a>
    </div>

    @if($sites->isEmpty())
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center">
            <i class="fa-solid fa-server text-4xl text-slate-700 mb-4"></i>
            <p class="text-slate-500">No hay sitios registrados.</p>
        </div>
    @else
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-500 text-left">
                            <th class="px-6 py-4 font-medium">Nombre</th>
                            <th class="px-6 py-4 font-medium hidden sm:table-cell">Dominio</th>
                            <th class="px-6 py-4 font-medium hidden md:table-cell">Plan</th>
                            <th class="px-6 py-4 font-medium hidden lg:table-cell">Venc. Plan</th>
                            <th class="px-6 py-4 font-medium hidden lg:table-cell">Venc. Dominio</th>
                            <th class="px-6 py-4 font-medium">Estado</th>
                            <th class="px-6 py-4 font-medium text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($sites as $site)
                            @php
                                $daysPlan = $site->daysUntilPlanExpiry();
                                $rowClass = '';
                                if ($site->status === 'suspended' || $site->status === 'cancelled') {
                                    $rowClass = 'bg-red-950/20';
                                } elseif ($daysPlan < 0) {
                                    $rowClass = 'bg-red-950/20';
                                } elseif ($daysPlan <= 30) {
                                    $rowClass = 'bg-yellow-950/20';
                                }
                            @endphp
                            <tr class="{{ $rowClass }} hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-white">{{ $site->name }}</span>
                                    <span class="block sm:hidden text-xs text-slate-500 mt-0.5">{{ $site->domain }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-400 hidden sm:table-cell">{{ $site->domain }}</td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span class="text-xs bg-slate-800 text-slate-300 px-2 py-1 rounded-lg capitalize">{{ $site->plan_name }}</span>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <span class="text-slate-400">{{ $site->plan_end->format('d/m/Y') }}</span>
                                    @if($daysPlan < 0)
                                        <span class="block text-xs text-red-400">Vencido</span>
                                    @elseif($daysPlan <= 30)
                                        <span class="block text-xs text-yellow-400">{{ $daysPlan }} días</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    @if($site->domain_expiry)
                                        <span class="text-slate-400">{{ $site->domain_expiry->format('d/m/Y') }}</span>
                                        @php $daysDomain = $site->daysUntilDomainExpiry(); @endphp
                                        @if($daysDomain !== null && $daysDomain < 0)
                                            <span class="block text-xs text-red-400">Vencido</span>
                                        @elseif($daysDomain !== null && $daysDomain <= 30)
                                            <span class="block text-xs text-yellow-400">{{ $daysDomain }} días</span>
                                        @endif
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($site->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-400 bg-emerald-400/10 px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span> Activo
                                        </span>
                                    @elseif($site->status === 'suspended')
                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-red-400 bg-red-400/10 px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span> Suspendido
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 bg-slate-400/10 px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span> Cancelado
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Probar Conexión -->
                                        <button type="button" onclick="testConnection({{ $site->id }}, this)"
                                                class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-cyan-400 transition-colors"
                                                title="Probar conexión">
                                            <i class="fa-solid fa-plug text-xs"></i>
                                        </button>
                                        <!-- Editar -->
                                        <a href="{{ url('admin/sites/edit/' . $site->id) }}"
                                           class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors"
                                           title="Editar">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </a>
                                        <!-- Suspender / Activar -->
                                        @if($site->status === 'active')
                                            <form action="{{ url('admin/sites/' . $site->id . '/suspend') }}" method="POST" class="inline" data-no-loading>
                                                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <button type="submit" class="p-2 rounded-lg bg-slate-800 hover:bg-red-900 text-slate-400 hover:text-red-400 transition-colors"
                                                        title="Suspender" onclick="return confirm('¿Suspender este sitio?')">
                                                    <i class="fa-solid fa-ban text-xs"></i>
                                                </button>
                                            </form>
                                        @elseif($site->status === 'suspended')
                                            <form action="{{ url('admin/sites/' . $site->id . '/activate') }}" method="POST" class="inline" data-no-loading>
                                                <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <button type="submit" class="p-2 rounded-lg bg-slate-800 hover:bg-emerald-900 text-slate-400 hover:text-emerald-400 transition-colors"
                                                        title="Activar">
                                                    <i class="fa-solid fa-check text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <!-- Eliminar -->
                                        <form action="{{ url('admin/sites/delete/' . $site->id) }}" method="POST" class="inline" data-no-loading>
                                            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" class="p-2 rounded-lg bg-slate-800 hover:bg-red-900 text-slate-400 hover:text-red-400 transition-colors"
                                                    title="Eliminar" onclick="return confirm('¿Eliminar este sitio del registro? Esta acción no afecta la base de datos remota.')">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function testConnection(siteId, btn) {
    var icon = btn.querySelector('i');
    var origClass = icon.className;
    icon.className = 'fa-solid fa-circle-notch fa-spin text-xs';
    btn.disabled = true;

    fetch('{{ url("admin/sites") }}/' + siteId + '/test', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        icon.className = origClass;
        btn.disabled = false;
        if (data.success) {
            btn.classList.add('text-emerald-400');
            icon.className = 'fa-solid fa-check text-xs';
        } else {
            btn.classList.add('text-red-400');
            icon.className = 'fa-solid fa-xmark text-xs';
        }
        alert(data.message);
        setTimeout(function() {
            icon.className = origClass;
            btn.classList.remove('text-emerald-400', 'text-red-400');
        }, 3000);
    })
    .catch(function() {
        icon.className = origClass;
        btn.disabled = false;
        alert('Error al probar la conexión.');
    });
}
</script>
@endpush
