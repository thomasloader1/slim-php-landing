@extends('admin/layout')

@section('title', 'Importar Productos')
@section('header', 'Importar Productos')
@section('subheader', 'Cargá o actualizá muchos productos a la vez desde una planilla de Excel.')

@section('content')
<div class="flex justify-end mb-4 gap-3">
    <a href="{{ url('admin/menu/items') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-700">
        <i class="fa-solid fa-arrow-left"></i> Volver a Productos
    </a>
</div>

@if(!$preview)
{{-- ── Pasos ─────────────────────────────────────────────────── --}}
<div class="grid gap-4 md:grid-cols-3 mb-6">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5">
        <p class="text-xs font-bold text-amber-400 uppercase tracking-wide mb-2">Paso 1</p>
        <h3 class="text-white font-bold mb-2">Descargá la plantilla</h3>
        <p class="text-sm text-slate-400 mb-4">Una planilla con las columnas y dos filas de ejemplo. Si ya tenés productos, exportalos para editarlos.</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ url('admin/menu/items/template') }}" data-no-loading
               class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-xl text-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-file-arrow-down"></i> Plantilla
            </a>
            <a href="{{ url('admin/menu/items/export') }}" data-no-loading
               class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-4 py-2 rounded-xl text-sm transition-all flex items-center gap-2 border border-slate-700">
                <i class="fa-solid fa-file-export"></i> Exportar actuales
            </a>
        </div>
    </div>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5">
        <p class="text-xs font-bold text-amber-400 uppercase tracking-wide mb-2">Paso 2</p>
        <h3 class="text-white font-bold mb-2">Completala en Excel</h3>
        <p class="text-sm text-slate-400">Un producto por fila. No cambies los encabezados. Al terminar, guardala como <strong class="text-slate-200">CSV UTF-8</strong> (Archivo → Guardar como).</p>
    </div>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5">
        <p class="text-xs font-bold text-amber-400 uppercase tracking-wide mb-2">Paso 3</p>
        <h3 class="text-white font-bold mb-2">Subila y revisá</h3>
        <form action="{{ url('admin/menu/items/import') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="file" name="csv_file" accept=".csv,text/csv" required
                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-slate-400 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-500/10 file:text-amber-400">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold px-4 py-2.5 rounded-xl text-sm transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-magnifying-glass"></i> Ver vista previa
            </button>
            <p class="text-xs text-slate-500">Nada se guarda hasta que confirmes.</p>
        </form>
    </div>
</div>

{{-- ── Referencia de columnas ────────────────────────────────── --}}
<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden">
    <div class="p-4 sm:p-6 border-b border-slate-800">
        <h3 class="text-lg font-bold text-white">Columnas de la planilla</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <tbody class="divide-y divide-slate-800/50">
                @foreach([
                    ['codigo', false, 'Código único (SKU). Si ya existe un producto con ese código, se actualiza; si no, se crea uno nuevo. Sin código, siempre se crea.'],
                    ['categoria', false, 'Nombre de la categoría. Si no existe, se crea.'],
                    ['nombre', true, 'Nombre del producto.'],
                    ['precio', true, 'Número, ej: 1500 o 1500,50 (también acepta 1.500,50).'],
                    ['descripcion', false, 'Texto libre.'],
                    ['foto_url', false, 'Link a la foto (https://…). Vacío no borra la foto actual. Para subir fotos desde tu compu, editá el producto.'],
                    ['stock', false, 'Unidades disponibles. 0 muestra "Sin stock"; vacío = no se controla stock.'],
                    ['activo', false, 'si / no. Vacío = si.'],
                ] as [$col, $req, $desc])
                <tr>
                    <td class="px-4 py-3 md:px-6 align-top whitespace-nowrap">
                        <code class="text-amber-300 font-mono">{{ $col }}</code>
                        @if($req)<span class="ml-1 text-[10px] text-red-400 font-bold uppercase">obligatorio</span>@endif
                    </td>
                    <td class="px-4 py-3 md:px-6 text-slate-400">{{ $desc }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@else
{{-- ── Vista previa ──────────────────────────────────────────── --}}
@php $c = $preview['counts']; $valid = $c['create'] + $c['update']; @endphp

<div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 mb-6">
    <p class="text-sm text-slate-400 mb-3">Archivo: <span class="text-white font-bold">{{ $preview['filename'] }}</span></p>
    <div class="flex flex-wrap gap-3 mb-4">
        <span class="px-3 py-1.5 bg-green-500/10 text-green-400 text-sm font-bold rounded-xl border border-green-500/20">{{ $c['create'] }} nuevos</span>
        <span class="px-3 py-1.5 bg-blue-500/10 text-blue-400 text-sm font-bold rounded-xl border border-blue-500/20">{{ $c['update'] }} a actualizar</span>
        @if($c['error'])
            <span class="px-3 py-1.5 bg-red-500/10 text-red-400 text-sm font-bold rounded-xl border border-red-500/20">{{ $c['error'] }} con errores (se omiten)</span>
        @endif
    </div>
    @if($preview['new_categories'])
        <div class="mb-4">
            <p class="text-sm text-slate-400 mb-2">Se crearán {{ count($preview['new_categories']) === 1 ? 'esta categoría nueva' : 'estas ' . count($preview['new_categories']) . ' categorías nuevas' }}:</p>
            <div class="flex flex-wrap gap-2">
                @foreach($preview['new_categories'] as $cat)
                    <span class="px-2 py-1 bg-purple-500/10 text-purple-300 text-xs font-bold rounded-full border border-purple-500/20"><i class="fa-solid fa-plus text-[10px]"></i> {{ $cat }}</span>
                @endforeach
            </div>
        </div>
    @endif
    @foreach($preview['errors'] as $err)
        <p class="text-sm text-amber-400 mb-2"><i class="fa-solid fa-triangle-exclamation"></i> {{ $err }}</p>
    @endforeach
    @if(!in_array('codigo', $preview['columns']))
        <p class="text-sm text-amber-400 mb-2"><i class="fa-solid fa-triangle-exclamation"></i> El archivo no tiene columna "codigo": todos los productos se crearán como nuevos.</p>
    @endif

    <div class="flex flex-wrap gap-3">
        @if($valid > 0)
        <form action="{{ url('admin/menu/items/import/confirm') }}" method="POST">
            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="import_token" value="{{ $preview['token'] }}">
            <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-5 py-2.5 rounded-xl text-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-check"></i>
                Importar {{ $valid }} {{ $valid === 1 ? 'producto' : 'productos' }}{{ $c['error'] ? ' válidos' : '' }}
            </button>
        </form>
        @endif
        <a href="{{ url('admin/menu/items/import') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-5 py-2.5 rounded-xl text-sm transition-all border border-slate-700">
            Subir otro archivo
        </a>
    </div>
</div>

<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                    <th class="px-3 py-3 md:px-4 font-bold">Fila</th>
                    <th class="px-3 py-3 md:px-4 font-bold">Acción</th>
                    <th class="px-3 py-3 md:px-4 font-bold">Código</th>
                    <th class="px-3 py-3 md:px-4 font-bold">Nombre</th>
                    <th class="px-3 py-3 md:px-4 font-bold hidden md:table-cell">Categoría</th>
                    <th class="px-3 py-3 md:px-4 font-bold">Precio</th>
                    <th class="px-3 py-3 md:px-4 font-bold hidden sm:table-cell">Stock</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/50">
                @foreach($preview['rows'] as $row)
                @php $d = $row['data']; @endphp
                <tr class="{{ $row['action'] === 'error' ? 'bg-red-500/5' : '' }}">
                    <td class="px-3 py-3 md:px-4 text-slate-500 align-top">{{ $row['line'] }}</td>
                    <td class="px-3 py-3 md:px-4 align-top whitespace-nowrap">
                        @if($row['action'] === 'create')
                            <span class="px-2 py-1 bg-green-500/10 text-green-400 text-[10px] font-bold rounded-full uppercase">Nuevo</span>
                        @elseif($row['action'] === 'update')
                            <span class="px-2 py-1 bg-blue-500/10 text-blue-400 text-[10px] font-bold rounded-full uppercase">Actualiza</span>
                        @else
                            <span class="px-2 py-1 bg-red-500/10 text-red-400 text-[10px] font-bold rounded-full uppercase">Error</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 md:px-4 text-slate-400 font-mono align-top">{{ $d['codigo'] ?? '—' }}</td>
                    <td class="px-3 py-3 md:px-4 align-top">
                        <p class="text-white font-bold">{{ $d['nombre'] !== '' ? $d['nombre'] : '—' }}</p>
                        @foreach($row['errors'] as $err)
                            <p class="text-xs text-red-400 mt-1">{{ $err }}</p>
                        @endforeach
                    </td>
                    <td class="px-3 py-3 md:px-4 text-slate-400 align-top hidden md:table-cell">{{ $d['categoria'] ?? '—' }}</td>
                    <td class="px-3 py-3 md:px-4 text-amber-400 font-bold align-top whitespace-nowrap">
                        {{ $d['precio'] !== null ? \App\Models\MenuItem::formatMoney($d['precio']) : '—' }}
                    </td>
                    <td class="px-3 py-3 md:px-4 text-slate-400 align-top hidden sm:table-cell">
                        {{ ($d['stock'] ?? null) === null ? '—' : $d['stock'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
