{{-- Diapositiva editable de un carrusel. Variables: $s (image, alt, title, subtitle, cta_label, cta_url) --}}
@php
    $input = 'w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-blue-500/50 transition-all';
    $label = 'block text-xs font-medium text-slate-400 mb-1.5';
@endphp
<li class="slide-item bg-slate-950/60 border border-slate-800 rounded-2xl p-3 sm:p-4 space-y-3">
    <div class="flex items-center gap-2">
        <button type="button" class="slide-handle cursor-grab text-slate-600 hover:text-slate-400 px-1" aria-label="Arrastrar para ordenar la diapositiva">
            <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
        </button>
        <span class="slide-number text-xs font-bold text-slate-400 flex-1">Diapositiva</span>
        <button type="button" class="slide-remove p-2 rounded-lg text-slate-500 hover:bg-red-500/10 hover:text-red-400 transition-all" aria-label="Eliminar diapositiva">
            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
        </button>
    </div>
    @include('admin/builder/_image-field', ['name' => 'image', 'value' => $s['image'] ?? '', 'label' => 'Imagen', 'attr' => 'data-slide-field', 'hint' => 'Ideal 1920×820'])
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="{{ $label }}">Título</label>
            <input type="text" data-slide-field="title" maxlength="150" value="{{ $s['title'] ?? '' }}" class="{{ $input }}">
        </div>
        <div>
            <label class="{{ $label }}">Bajada</label>
            <input type="text" data-slide-field="subtitle" maxlength="300" value="{{ $s['subtitle'] ?? '' }}" class="{{ $input }}">
        </div>
        <div>
            <label class="{{ $label }}">Texto del botón <span class="text-slate-600">(opcional)</span></label>
            <input type="text" data-slide-field="cta_label" maxlength="60" value="{{ $s['cta_label'] ?? '' }}" class="{{ $input }}" placeholder="Ej: Ver ofertas">
        </div>
        <div>
            <label class="{{ $label }}">Enlace <span class="text-slate-600">(opcional)</span></label>
            <input type="text" data-slide-field="cta_url" maxlength="500" value="{{ $s['cta_url'] ?? '' }}" class="{{ $input }}" placeholder="/shop o https://…">
        </div>
        <div class="sm:col-span-2">
            <label class="{{ $label }}">Descripción de la imagen <span class="text-slate-600">(accesibilidad)</span></label>
            <input type="text" data-slide-field="alt" maxlength="150" value="{{ $s['alt'] ?? '' }}" class="{{ $input }}" placeholder="Ej: Tortas de cumpleaños decoradas">
        </div>
    </div>
</li>
