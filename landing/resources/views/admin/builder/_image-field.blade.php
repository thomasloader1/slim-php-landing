{{--
    Campo de imagen del UX Builder: link (https:// o /uploads/…) + botón para subir archivo.
    La subida va por fetch a admin/builder/upload y deja la URL en el input.
    Variables: $name (campo), $value, $label, $hint?, $attr ('data-field' | 'data-slide-field')
--}}
@php
    $attr  = $attr ?? 'data-field';
    $value = $value ?? '';
@endphp
<div class="img-field">
    <span class="block text-xs font-medium text-slate-400 mb-1.5">{{ $label }}</span>
    <div class="flex gap-3 items-start">
        <div class="w-24 h-16 rounded-xl bg-slate-950 border border-slate-800 overflow-hidden shrink-0 flex items-center justify-center">
            <img class="img-thumb w-full h-full object-cover {{ $value === '' ? 'hidden' : '' }}" src="{{ $value }}" alt="">
            <i class="img-empty fa-regular fa-image text-slate-700 {{ $value === '' ? '' : 'hidden' }}" aria-hidden="true"></i>
        </div>
        <div class="flex-1 min-w-0 space-y-2">
            <input type="text" {{ $attr }}="{{ $name }}" value="{{ $value }}" maxlength="1000" aria-label="{{ $label }} (link)"
                   class="img-url w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-blue-500/50 transition-all"
                   placeholder="https://… o subí un archivo">
            <div class="flex flex-wrap items-center gap-2">
                <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 cursor-pointer transition-all focus-within:ring-2 focus-within:ring-blue-500">
                    <i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i> Subir imagen
                    <input type="file" class="img-file sr-only" accept="image/png,image/jpeg,image/webp,image/gif">
                </label>
                <button type="button" class="img-clear text-xs text-slate-500 hover:text-red-400 {{ $value === '' ? 'hidden' : '' }}">Quitar</button>
                <span class="img-status text-xs text-slate-500" aria-live="polite">{{ $hint ?? '' }}</span>
            </div>
        </div>
    </div>
</div>
