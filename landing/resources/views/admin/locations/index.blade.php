@extends('admin/layout')

@section('title', 'Ubicaciones')
@section('header', 'Ubicaciones')
@section('subheader', 'Administra las sucursales y ubicaciones que se muestran en tu landing.')

@section('content')

{{-- ── Panel de visualización ───────────────────────────────── --}}
<form action="{{ url('admin/locations/display') }}" method="POST"
      class="bg-slate-900 border border-slate-800 rounded-3xl p-5 mb-6 flex flex-wrap items-end gap-5">

    <div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Modo de visualización</p>
        <div class="flex gap-2">
            @foreach(['list' => ['Lista', 'fa-list'], 'grid' => ['Grilla', 'fa-table-cells-large']] as $val => [$lbl, $ico])
            <label class="flex items-center gap-2 px-4 py-2.5 rounded-xl border cursor-pointer transition-all text-sm font-semibold
                          {{ ($settings['locations_display'] ?? 'list') === $val
                              ? 'bg-amber-500/10 border-amber-500/40 text-amber-300'
                              : 'border-slate-700 text-slate-400 hover:border-slate-600' }}">
                <input type="radio" name="locations_display" value="{{ $val }}" class="sr-only"
                       {{ ($settings['locations_display'] ?? 'list') === $val ? 'checked' : '' }}>
                <i class="fa-solid {{ $ico }} text-sm"></i> {{ $lbl }}
            </label>
            @endforeach
        </div>
    </div>

    <div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Columnas (modo grilla)</p>
        <div class="flex gap-2">
            @foreach([1 => '1 col', 2 => '2 col', 3 => '3 col'] as $val => $lbl)
            <label class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl border cursor-pointer transition-all text-sm font-semibold
                          {{ (int)($settings['locations_columns'] ?? 2) === $val
                              ? 'bg-amber-500/10 border-amber-500/40 text-amber-300'
                              : 'border-slate-700 text-slate-400 hover:border-slate-600' }}">
                <input type="radio" name="locations_columns" value="{{ $val }}" class="sr-only"
                       {{ (int)($settings['locations_columns'] ?? 2) === $val ? 'checked' : '' }}>
                {{ $lbl }}
            </label>
            @endforeach
        </div>
    </div>

    <button type="submit"
            class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-5 py-2.5 rounded-xl transition-all text-sm">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Guardar
    </button>
</form>

{{-- ── Lista de ubicaciones ─────────────────────────────────── --}}
<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
    <div class="p-4 sm:p-6 border-b border-slate-800 flex flex-wrap justify-between items-center gap-2 bg-slate-900/50">
        <h3 class="text-lg font-bold text-white">Lista de Ubicaciones</h3>
        <a href="{{ url('admin/locations/create') }}"
           class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nueva Ubicación
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold w-8"><i class="fa-solid fa-grip-vertical"></i></th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Nombre</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden md:table-cell">Dirección</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">WhatsApp</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Modo</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Estado</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="sortable-locations" class="divide-y divide-slate-800/50">
                @if(count($locations) > 0)
                    @foreach($locations as $loc)
                    <tr class="hover:bg-slate-800/20 transition-colors group" data-id="{{ $loc->id }}">
                        <td class="px-3 py-3 md:px-6 md:py-4 cursor-grab drag-handle">
                            <i class="fa-solid fa-grip-vertical text-slate-600"></i>
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4">
                            <p class="text-white font-bold">{{ $loc->name }}</p>
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4 hidden md:table-cell">
                            <span class="text-slate-400 text-sm truncate max-w-[200px] block">{{ $loc->address ?: '—' }}</span>
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                            @if($loc->whatsapp)
                                <a href="{{ $loc->whatsapp_url }}" target="_blank"
                                   class="inline-flex items-center gap-1.5 text-green-400 hover:text-green-300 text-sm transition-colors">
                                    <i class="fa-brands fa-whatsapp"></i>
                                    <span class="font-mono text-xs">{{ $loc->whatsapp }}</span>
                                </a>
                            @else
                                <span class="text-slate-600 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                            <span class="px-2 py-1 bg-slate-800 text-slate-400 text-[10px] font-bold rounded-full uppercase border border-slate-700">
                                {{ $loc->mode === 'button' ? 'Botón' : 'Embed' }}
                            </span>
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                            @if($loc->active)
                                <span class="px-2 py-1 bg-green-500/10 text-green-500 text-[10px] font-bold rounded-full uppercase border border-green-500/10">Activo</span>
                            @else
                                <span class="px-2 py-1 bg-slate-800 text-slate-500 text-[10px] font-bold rounded-full uppercase border border-slate-700">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ url('admin/locations/edit/' . $loc->id) }}"
                                   class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-all">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </a>
                                <form action="{{ url('admin/locations/delete/' . $loc->id) }}" method="POST"
                                      onsubmit="return confirm('¿Estás seguro de eliminar esta ubicación?')" data-no-loading>
                                    <button type="submit"
                                            class="p-2 bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-500 rounded-lg transition-all">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500 italic">
                            No hay ubicaciones creadas todavía. ¡Crea la primera!
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    /* Drag & drop reorder */
    var el = document.getElementById('sortable-locations');
    if (el && el.querySelector('tr[data-id]')) {
        Sortable.create(el, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function () {
                var order = [];
                el.querySelectorAll('tr[data-id]').forEach(function (row) {
                    order.push(parseInt(row.dataset.id));
                });
                fetch('{{ url("admin/locations/reorder") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order: order })
                });
            }
        });
    }

    /* Radio buttons visuales para el panel de display */
    document.querySelectorAll('[name="locations_display"], [name="locations_columns"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var name = this.name;
            document.querySelectorAll('[name="' + name + '"]').forEach(function (r) {
                var lbl = r.closest('label');
                if (r.checked) {
                    lbl.classList.add('bg-amber-500/10', 'border-amber-500/40', 'text-amber-300');
                    lbl.classList.remove('border-slate-700', 'text-slate-400');
                } else {
                    lbl.classList.remove('bg-amber-500/10', 'border-amber-500/40', 'text-amber-300');
                    lbl.classList.add('border-slate-700', 'text-slate-400');
                }
            });
        });
    });
})();
</script>
@endpush
@endsection
