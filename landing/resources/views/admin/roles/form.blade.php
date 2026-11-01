@extends('admin/layout')

@section('title', $role ? 'Editar rol' : 'Nuevo rol')
@section('header', $role ? 'Editar rol: ' . $role->name : 'Nuevo rol')
@section('subheader', 'Tildá las secciones del panel que pueden ver los usuarios con este rol.')

@section('content')
<div class="max-w-3xl">
    <form action="{{ url($role ? 'admin/roles/edit/' . $role->id : 'admin/roles/create') }}" method="POST" class="bg-slate-900 border border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm space-y-6">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

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
                <legend class="text-sm font-medium text-slate-400">Secciones visibles</legend>
                <div class="flex gap-3 text-xs">
                    <button type="button" class="text-blue-400 hover:text-blue-300" data-check="all">Todas</button>
                    <button type="button" class="text-slate-500 hover:text-slate-300" data-check="none">Ninguna</button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($catalog as $key => $perm)
                    <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-800 bg-slate-950 cursor-pointer hover:border-slate-700 transition-colors has-[:checked]:border-blue-500/40 has-[:checked]:bg-blue-500/5">
                        <input type="checkbox" name="permissions[]" value="{{ $key }}" {{ in_array($key, $granted, true) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-0">
                        <i class="fa-solid {{ $perm['icon'] }} w-4 text-slate-400"></i>
                        <span class="text-sm text-white font-medium">{{ $perm['label'] }}</span>
                        @if($perm['module'])
                            <span class="ml-auto text-[10px] text-slate-500" title="Además tiene que estar encendido en Módulos">módulo</span>
                        @endif
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-slate-500 mt-3">El Dashboard siempre es visible. "Usuarios" permite crear usuarios con este u otros roles no administradores.</p>
        </fieldset>

        <div class="pt-4 border-t border-slate-800 flex flex-wrap gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all">
                {{ $role ? 'Guardar permisos' : 'Crear rol' }}
            </button>
            <a href="{{ url('admin/roles') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-3 rounded-xl font-bold transition-all">Cancelar</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-check]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var on = btn.dataset.check === 'all';
        document.querySelectorAll('input[name="permissions[]"]').forEach(function (cb) { cb.checked = on; });
    });
});
</script>
@endpush
@endsection
