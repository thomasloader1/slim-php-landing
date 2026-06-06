@extends('admin/layout')

@section('title', 'FAQ')
@section('header', 'Preguntas Frecuentes')
@section('subheader', 'Administra las FAQ que se inyectan como Schema.org en la landing.')

@section('content')
<div class="max-w-4xl">
    <div class="mb-4">
        <a href="{{ url('admin/seo') }}" class="text-slate-400 hover:text-white text-sm transition-colors">
            <i class="fa-solid fa-chevron-left text-xs mr-1"></i> Volver a SEO
        </a>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
        <div class="p-4 sm:p-6 border-b border-slate-800 flex flex-wrap justify-between items-center gap-2 bg-slate-900/50">
            <h3 class="text-lg font-bold text-white">Lista de Preguntas</h3>
            <a href="{{ url('admin/seo/faq/create') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Nueva Pregunta
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                        <th class="px-3 py-3 md:px-6 md:py-4 font-bold w-8"><i class="fa-solid fa-grip-vertical"></i></th>
                        <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Pregunta</th>
                        <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden md:table-cell">Respuesta</th>
                        <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Estado</th>
                        <th class="px-3 py-3 md:px-6 md:py-4 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="sortable-faq" class="divide-y divide-slate-800/50">
                    @if(count($items) > 0)
                        @foreach($items as $item)
                            <tr class="hover:bg-slate-800/20 transition-colors group" data-id="{{ $item->id }}">
                                <td class="px-3 py-3 md:px-6 md:py-4 cursor-grab drag-handle">
                                    <i class="fa-solid fa-grip-vertical text-slate-600"></i>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4">
                                    <p class="text-white font-bold">{{ $item->question }}</p>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 hidden md:table-cell">
                                    <span class="text-slate-400 text-sm truncate max-w-[300px] block">{{ \Illuminate\Support\Str::limit(strip_tags($item->answer), 80) }}</span>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 hidden sm:table-cell">
                                    @if($item->active)
                                        <span class="px-2 py-1 bg-green-500/10 text-green-500 text-[10px] font-bold rounded-full uppercase border border-green-500/10">Activo</span>
                                    @else
                                        <span class="px-2 py-1 bg-slate-800 text-slate-500 text-[10px] font-bold rounded-full uppercase border border-slate-700">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ url('admin/seo/faq/edit/' . $item->id) }}" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-all">
                                            <i class="fa-solid fa-pen-to-square text-sm"></i>
                                        </a>
                                        <form action="{{ url('admin/seo/faq/delete/' . $item->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta pregunta?')" data-no-loading>
                                            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" class="p-2 bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-500 rounded-lg transition-all">
                                                <i class="fa-solid fa-trash-can text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">
                                No hay preguntas frecuentes todavía. ¡Crea la primera!
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function() {
    var el = document.getElementById('sortable-faq');
    if (!el || !el.children.length) return;
    Sortable.create(el, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function() {
            var order = [];
            el.querySelectorAll('tr[data-id]').forEach(function(row) {
                order.push(parseInt(row.dataset.id));
            });
            fetch('{{ url("admin/seo/faq/reorder") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order: order })
            });
        }
    });
})();
</script>
@endpush
@endsection
