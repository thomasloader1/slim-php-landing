@extends('admin/layout')

@section('title', 'Pedidos')
@section('header', 'Pedidos')
@section('subheader', 'Comandas que llegan desde la tienda. El stock se reserva al pedir y se devuelve si rechazás.')

@section('content')
@include('admin/partials/section-tabs', ['group' => 'shop'])
@php
    $badge = [
        'pending'  => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'accepted' => 'bg-green-500/10 text-green-400 border-green-500/20',
        'rejected' => 'bg-red-500/10 text-red-400 border-red-500/20',
    ];
@endphp

<div class="flex flex-wrap gap-2 mb-4" role="tablist" aria-label="Filtrar pedidos">
    @foreach($filters as $key => $label)
        @php $n = $key === 'all' ? array_sum($counts) : ($counts[$key] ?? 0); @endphp
        <a href="{{ url('admin/shop/orders') }}?status={{ $key }}" role="tab" aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
           class="px-4 py-2 rounded-xl text-sm font-bold border transition-all flex items-center gap-2
                  {{ $filter === $key ? 'bg-slate-800 text-white border-slate-600' : 'text-slate-400 border-slate-800 hover:border-slate-700' }}">
            {{ $label }}
            <span class="text-xs {{ $key === 'pending' && $n > 0 ? 'bg-amber-500 text-slate-950' : 'bg-slate-700 text-slate-300' }} px-1.5 rounded-md">{{ $n }}</span>
        </a>
    @endforeach
</div>

<div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-widest border-b border-slate-800 bg-slate-950/30">
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Pedido</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Cliente</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden md:table-cell">Fecha</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold hidden sm:table-cell">Ítems</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Total</th>
                    <th class="px-3 py-3 md:px-6 md:py-4 font-bold">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/50">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-800/30 transition-colors cursor-pointer" onclick="location.href='{{ url('admin/shop/orders/' . $order->id) }}'">
                        <td class="px-3 py-3 md:px-6 md:py-4">
                            <a href="{{ url('admin/shop/orders/' . $order->id) }}" class="text-white font-bold font-mono hover:text-blue-400">{{ $order->code }}</a>
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4">
                            <p class="text-white text-sm font-bold">{{ $order->customer_name }}</p>
                            <p class="text-slate-500 text-xs">{{ $order->customer_phone }}</p>
                        </td>
                        <td class="px-3 py-3 md:px-6 md:py-4 text-slate-400 text-sm hidden md:table-cell">{{ $order->created_at?->format('d/m H:i') }}</td>
                        <td class="px-3 py-3 md:px-6 md:py-4 text-slate-400 text-sm hidden sm:table-cell">{{ $order->items_count }}</td>
                        <td class="px-3 py-3 md:px-6 md:py-4 text-amber-400 font-bold text-sm whitespace-nowrap">{{ $order->total_formatted }}</td>
                        <td class="px-3 py-3 md:px-6 md:py-4">
                            <span class="px-2 py-1 text-[10px] font-bold rounded-full uppercase border {{ $badge[$order->status] ?? '' }}">{{ $order->status_label }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                            {{ $filter === 'pending' ? 'No hay pedidos pendientes.' : 'No hay pedidos.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
