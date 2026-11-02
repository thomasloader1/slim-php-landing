@extends('admin/layout')

@section('title', $role ? 'Editar rol' : 'Nuevo rol')
@section('header', $role ? 'Editar rol: ' . $role->name : 'Nuevo rol')
@section('subheader', 'Elegí, módulo por módulo, qué puede ver y hacer este rol: todo el módulo o solo algunas funciones.')

@section('content')
    <form action="{{ url($role ? 'admin/roles/edit/' . $role->id : 'admin/roles/create') }}" method="POST">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-slate-900 border border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm space-y-6">
                    <div>
                        <label for="role-name" class="block text-sm font-medium text-slate-400 mb-2">Nombre del rol</label>
                        <input id="role-name" type="text" name="name" required maxlength="100" value="{{ $role->name ?? '' }}" placeholder="Ej: Vendedor"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                        @if($role)
                            <p class="text-xs text-slate-500 mt-1">Identificador: <code class="font-mono">{{ $role->slug }}</code> (no cambia)</p>
                        @endif
                    </div>

                    <fieldset>
                        <div class="flex items-center justify-between mb-3">
                            <legend class="text-sm font-medium text-slate-400">Qué puede hacer este rol</legend>
                            <div class="flex gap-3 text-xs">
                                <button type="button" class="text-blue-400 hover:text-blue-300" data-check="all">Todo</button>
                                <button type="button" class="text-slate-500 hover:text-slate-300" data-check="none">Nada</button>
                            </div>
                        </div>
                        <div class="space-y-6">
                            @forelse($groups as $groupKey => $group)
                                <div>
                                    <p class="text-[11px] uppercase tracking-widest text-slate-500 font-bold mb-2 flex items-center gap-2">
                                        <i class="fa-solid {{ $group['icon'] }}"></i> {{ $group['label'] }}
                                    </p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 md:[&>*:last-child:nth-child(odd)]:col-span-2 gap-3">
                                        @foreach($group['sections'] as $key => $section)
                                            @php
                                                $full = in_array($key, $granted, true);
                                                $features = $section['features'];
                                            @endphp
                                            <div class="rounded-2xl border border-slate-800 bg-slate-950 has-[[data-section]:checked]:border-blue-500/40" data-perm-card>
                                                <label class="flex items-center gap-3 p-3 cursor-pointer">
                                                    <input type="checkbox" name="permissions[]" value="{{ $key }}" data-section="{{ $key }}" {{ $full ? 'checked' : '' }}
                                                           class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-0">
                                                    <i class="fa-solid {{ $section['icon'] }} w-4 text-slate-400"></i>
                                                    <span class="text-sm text-white font-bold flex-1">{{ $section['label'] }}</span>
                                                    <span class="text-[10px] uppercase tracking-wide text-slate-500">{{ $features ? 'Todas' : 'Acceso' }}</span>
                                                </label>
                                                @if($features)
                                                    <div class="border-t border-slate-800 px-3 py-2 grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-1">
                                                        @foreach($features as $fKey => $feature)
                                                            <label class="flex items-center gap-2 py-1 text-xs text-slate-300 cursor-pointer">
                                                                <input type="checkbox" name="permissions[]" value="{{ $fKey }}" data-feature-of="{{ $key }}"
                                                                       data-implies="{{ implode(' ', $feature['implies'] ?? []) }}"
                                                                       {{ $full || in_array($fKey, $granted, true) ? 'checked' : '' }}
                                                                       class="w-3.5 h-3.5 rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-0">
                                                                {{ $feature['label'] }}
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">No hay módulos encendidos para asignar. Activalos en Módulos.</p>
                            @endforelse
                        </div>
                    </fieldset>
                </div>
            </div>

            <aside class="space-y-6 xl:sticky xl:top-6">
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-sm space-y-3 text-xs text-slate-400">
                    <p class="text-sm font-bold text-white flex items-center gap-2"><i class="fa-solid fa-circle-info text-blue-400"></i> Cómo funciona</p>
                    <p>Solo aparecen los módulos encendidos en <strong class="text-slate-300">Módulos</strong>. "Todas" incluye también las funciones que se agreguen a ese módulo más adelante.</p>
                    <p>Crear, editar o eliminar incluyen <em>ver</em>. Lo que no esté tildado desaparece del menú y del panel para este rol.</p>
                    <p>El Dashboard siempre es visible. "Usuarios" permite crear usuarios con este u otros roles no administradores. El root no se configura acá: siempre tiene acceso total.</p>
                </div>
                @if($off)
                    <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-sm text-xs text-slate-400 space-y-2">
                        <p class="text-sm font-bold text-white flex items-center gap-2"><i class="fa-solid fa-power-off text-slate-500"></i> Apagados en Módulos</p>
                        <p>{{ implode(' · ', $off) }}</p>
                        <p class="text-slate-500">No se pueden asignar mientras estén apagados. Si este rol ya tenía permisos ahí, se conservan para cuando se vuelvan a encender.</p>
                    </div>
                @endif
            </aside>
        </div>

        {{-- Barra de guardado: queda visible al hacer scroll --}}
        <div class="sticky bottom-0 z-20 -mx-4 sm:-mx-6 lg:-mx-10 px-4 sm:px-6 lg:px-10 py-4 bg-slate-950/90 backdrop-blur border-t border-slate-800 flex justify-end gap-3">
            <a href="{{ url('admin/roles') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-3 rounded-xl font-bold transition-all">Cancelar</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all">
                {{ $role ? 'Guardar permisos' : 'Crear rol' }}
            </button>
        </div>
    </form>

@push('scripts')
<script>
(function () {
    var all = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); };
    var features = function (section) { return all('[data-feature-of="' + section + '"]'); };

    // "Todas" refleja el estado de sus funciones (todas / algunas = indeterminado / ninguna)
    function syncSection(section) {
        var master = document.querySelector('[data-section="' + section + '"]');
        var fs = features(section);
        if (!master || !fs.length) return;
        var on = fs.filter(function (f) { return f.checked; }).length;
        master.checked = on === fs.length;
        master.indeterminate = on > 0 && on < fs.length;
    }

    all('[data-section]').forEach(function (master) {
        master.addEventListener('change', function () {
            features(master.dataset.section).forEach(function (f) { f.checked = master.checked; });
            master.indeterminate = false;
        });
        syncSection(master.dataset.section);
    });

    all('[data-feature-of]').forEach(function (f) {
        f.addEventListener('change', function () {
            var section = f.dataset.featureOf;
            if (f.checked) {
                // Editar / eliminar necesitan "ver"
                (f.dataset.implies || '').split(' ').filter(Boolean).forEach(function (k) {
                    var dep = document.querySelector('[data-feature-of="' + section + '"][value="' + k + '"]');
                    if (dep) dep.checked = true;
                });
            } else {
                // Sin "ver" no tiene sentido editar / eliminar
                features(section).forEach(function (other) {
                    if ((other.dataset.implies || '').split(' ').indexOf(f.value) !== -1) other.checked = false;
                });
            }
            syncSection(section);
        });
    });

    all('[data-check]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var on = btn.dataset.check === 'all';
            all('input[name="permissions[]"]').forEach(function (cb) { cb.checked = on; cb.indeterminate = false; });
        });
    });
})();
</script>
@endpush
@endsection
