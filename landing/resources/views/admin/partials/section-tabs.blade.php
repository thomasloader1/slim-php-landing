{{-- Tabs de un grupo del sidebar (Shop, Usuarios...). Uso: @include('admin/partials/section-tabs', ['group' => 'shop']) --}}
@php
    $__tabsNav = \App\Services\AccessService::visibleNav(\App\Services\AccessService::currentPath());
    $__tabs = $__tabsNav[$group]['items'] ?? [];
@endphp
@if(count($__tabs) > 1)
<nav class="mb-6 -mx-1 overflow-x-auto" aria-label="{{ $__tabsNav[$group]['label'] }}">
    <div class="flex gap-1 px-1 border-b border-slate-800 min-w-max">
        @foreach($__tabs as $__tab)
            <a href="{{ url($__tab['url']) }}" @if($__tab['active']) aria-current="page" @endif
               class="px-4 py-2.5 text-sm font-bold flex items-center gap-2 border-b-2 -mb-px transition-colors
                      {{ $__tab['active'] ? 'border-accent text-white' : 'border-transparent text-slate-500 hover:text-slate-300' }}">
                <i class="fa-solid {{ $__tab['icon'] }} text-xs"></i> {{ $__tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
@endif
