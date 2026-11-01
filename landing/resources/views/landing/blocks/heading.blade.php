@php
    $size  = ['sm' => 'text-xl', 'md' => 'text-2xl', 'lg' => 'text-3xl'][$data['size'] ?? 'md'] ?? 'text-2xl';
    $align = 'text-' . ($data['align'] ?? 'center');
@endphp
@if(($data['text'] ?? '') !== '')
    <h2 class="w-full {{ $size }} {{ $align }} font-bold tracking-tight">{{ $data['text'] }}</h2>
@endif
