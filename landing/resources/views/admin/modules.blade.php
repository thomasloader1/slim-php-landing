@extends('admin/layout')

@section('title', 'Módulos')
@section('header', 'Módulos')
@section('subheader', 'Qué secciones existen en este sitio. Apagada, una sección desaparece para todos los usuarios; vos (root) la seguís viendo marcada "Off".')

@section('content')
<div>
    <form action="{{ url('admin/modules') }}" method="POST" class="space-y-8">
        <input type="hidden" name="_token" value="{{ $_SESSION['csrf_token'] ?? '' }}">

        @foreach($groups as $groupKey => $group)
            <section>
                <h3 class="text-xs uppercase tracking-widest text-slate-500 font-bold mb-3 flex items-center gap-2">
                    <i class="fa-solid {{ $group['icon'] }}"></i> {{ $group['label'] }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($group['modules'] as $flag => $mod)
                        <label class="flex items-start gap-4 cursor-pointer group bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition-all has-[:checked]:border-blue-500/30">
                            <input type="hidden" name="{{ $flag }}" value="0">
                            <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700 shrink-0 mt-1">
                                <input type="checkbox" name="{{ $flag }}" value="1"
                                       {{ ($settings[$flag] ?? '1') === '1' ? 'checked' : '' }}
                                       @if($mod['requires']) data-requires="{{ $mod['requires'] }}" @endif
                                       class="sr-only peer">
                                <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fa-solid {{ $mod['icon'] }} text-slate-500 text-sm"></i>
                                    <span class="text-sm font-bold text-white">{{ $mod['label'] }}</span>
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">{{ $mod['desc'] }}</p>
                                @if($mod['requires'])
                                    <p class="text-[11px] text-amber-400/80 mt-1 hidden" data-requires-note="{{ $mod['requires'] }}">
                                        <i class="fa-solid fa-link-slash"></i> Necesita el Catálogo encendido.
                                    </p>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section>
            <h3 class="text-xs uppercase tracking-widest text-slate-500 font-bold mb-3 flex items-center gap-2">
                <i class="fa-solid fa-copyright"></i> Footer de la landing
            </h3>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="footer-text" class="block text-sm text-slate-400 mb-2">Texto</label>
                        <input id="footer-text" type="text" name="landing_footer_text" maxlength="120" value="{{ $settings['landing_footer_text'] }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500/50 outline-none">
                    </div>
                    <div>
                        <label for="footer-label" class="block text-sm text-slate-400 mb-2">Texto del link</label>
                        <input id="footer-label" type="text" name="landing_footer_link_label" maxlength="80" value="{{ $settings['landing_footer_link_label'] }}"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500/50 outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label for="footer-url" class="block text-sm text-slate-400 mb-2">Link</label>
                        <input id="footer-url" type="url" name="landing_footer_url" maxlength="300" value="{{ $settings['landing_footer_url'] }}" placeholder="https://gomepab.com.ar"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-blue-500/50 outline-none">
                    </div>
                </div>
                <p class="text-xs text-slate-500">
                    Vista previa:
                    <span class="text-slate-300" id="footer-preview">{{ $settings['landing_footer_text'] }} <u>{{ $settings['landing_footer_link_label'] }}</u></span>
                    · Solo el root puede cambiarlo; el resto de los usuarios no ve esta pantalla.
                </p>
            </div>
        </section>

        {{-- Barra de guardado: queda visible al hacer scroll --}}
        <div class="sticky bottom-0 z-20 -mx-4 sm:-mx-6 lg:-mx-10 px-4 sm:px-6 lg:px-10 py-4 bg-slate-950/90 backdrop-blur border-t border-slate-800 flex justify-end gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                Guardar Módulos
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Aviso cuando una sección depende de otra apagada (ej. Pedidos sin Catálogo)
    (function () {
        function sync() {
            document.querySelectorAll('[data-requires-note]').forEach(function (note) {
                var dep = document.querySelector('input[type=checkbox][name="' + note.dataset.requiresNote + '"]');
                note.classList.toggle('hidden', !dep || dep.checked);
            });
        }
        document.querySelectorAll('input[type=checkbox]').forEach(function (cb) { cb.addEventListener('change', sync); });
        sync();

        // Vista previa del footer
        var t = document.getElementById('footer-text'), l = document.getElementById('footer-label'), pv = document.getElementById('footer-preview');
        function preview() { pv.textContent = ''; pv.append(t.value + ' '); var u = document.createElement('u'); u.textContent = l.value; pv.append(u); }
        [t, l].forEach(function (el) { el.addEventListener('input', preview); });
    })();
</script>
@endpush
