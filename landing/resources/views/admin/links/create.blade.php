@extends('admin/layout')

@section('title', 'Nuevo Enlace')
@section('header', 'Crear Enlace')
@section('subheader', 'Agrega un nuevo link a tu perfil digital.')

@section('content')
<div class="max-w-2xl">
    <form action="{{ url('admin/links/create') }}" method="POST" class="bg-slate-900 border border-slate-800 p-5 sm:p-8 rounded-3xl shadow-sm space-y-6">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Título del Enlace</label>
                <input type="text" name="title" required placeholder="Ej: Mi Instagram"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
            </div>
            
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">URL / Destino</label>
                <input type="url" name="url" required placeholder="https://..."
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
            </div>

            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Icono</label>
                <input type="hidden" name="icon" id="icon-value" value="fa-solid fa-link">
                <button type="button" id="icon-picker-btn"
                        class="flex items-center gap-3 w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white hover:border-blue-500/50 transition-all">
                    <span id="icon-preview" class="w-6 h-6 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-link"></i>
                    </span>
                    <span id="icon-label" class="flex-1 text-left text-sm text-slate-300">Link</span>
                    <i class="fa-solid fa-chevron-down text-slate-500 text-xs"></i>
                </button>
                <!-- Panel selector de iconos -->
                <div id="icon-panel" class="hidden mt-2 bg-slate-950 border border-slate-800 rounded-xl p-4 shadow-xl">
                    <input type="text" id="icon-search" placeholder="Buscar icono..."
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-blue-500/50 mb-3">
                    <div id="icon-grid" class="grid grid-cols-4 sm:grid-cols-6 gap-2 max-h-48 overflow-y-auto"></div>
                </div>
            </div>

            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-medium text-slate-400 mb-2">Color de Acento</label>
                <div class="flex gap-2">
                    <input type="color" name="color" value="#3b82f6"
                           class="w-12 h-12 bg-slate-950 border border-slate-800 rounded-xl p-1 focus:border-blue-500/50 outline-none transition-all cursor-pointer">
                    <input type="text" value="#3b82f6" readonly
                           class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-500 text-sm">
                </div>
            </div>

            <div class="col-span-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700 shrink-0">
                        <input type="checkbox" name="bg_color_enabled" id="bg_color_enabled" class="sr-only peer">
                        <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                    </div>
                    <span class="text-sm font-medium text-slate-400">Color de fondo del icono</span>
                </label>
                <div id="bg-color-picker" class="mt-3 hidden">
                    <div class="flex gap-2">
                        <input type="color" name="bg_color" value="#fec771"
                               class="w-12 h-12 bg-slate-950 border border-slate-800 rounded-xl p-1 focus:border-blue-500/50 outline-none transition-all cursor-pointer">
                        <input type="text" value="#fec771" readonly id="bg_color_hex"
                               class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-500 text-sm">
                    </div>
                </div>
            </div>

            <div class="col-span-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700">
                        <input type="checkbox" name="active" checked class="sr-only peer">
                        <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                    </div>
                    <span class="text-sm font-medium text-slate-400">Mostrar enlace inmediatamente</span>
                </label>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex flex-wrap gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                Guardar Enlace
            </button>
            <a href="{{ url('admin/links') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-3 rounded-xl font-bold transition-all">
                Cancelar
            </a>
        </div>
    </form>
</div>
@push('scripts')
<script>
(function() {
    var icons = [
        { name: 'fa-brands fa-instagram', label: 'Instagram' },
        { name: 'fa-brands fa-twitter', label: 'Twitter/X' },
        { name: 'fa-brands fa-x-twitter', label: 'X' },
        { name: 'fa-brands fa-facebook', label: 'Facebook' },
        { name: 'fa-brands fa-youtube', label: 'YouTube' },
        { name: 'fa-brands fa-tiktok', label: 'TikTok' },
        { name: 'fa-brands fa-linkedin', label: 'LinkedIn' },
        { name: 'fa-brands fa-github', label: 'GitHub' },
        { name: 'fa-brands fa-whatsapp', label: 'WhatsApp' },
        { name: 'fa-brands fa-telegram', label: 'Telegram' },
        { name: 'fa-brands fa-discord', label: 'Discord' },
        { name: 'fa-brands fa-pinterest', label: 'Pinterest' },
        { name: 'fa-brands fa-snapchat', label: 'Snapchat' },
        { name: 'fa-brands fa-twitch', label: 'Twitch' },
        { name: 'fa-brands fa-spotify', label: 'Spotify' },
        { name: 'fa-brands fa-soundcloud', label: 'SoundCloud' },
        { name: 'fa-brands fa-patreon', label: 'Patreon' },
        { name: 'fa-brands fa-paypal', label: 'PayPal' },
        { name: 'fa-brands fa-etsy', label: 'Etsy' },
        { name: 'fa-brands fa-shopify', label: 'Shopify' },
        { name: 'fa-solid fa-link', label: 'Link' },
        { name: 'fa-solid fa-globe', label: 'Web' },
        { name: 'fa-solid fa-envelope', label: 'Email' },
        { name: 'fa-solid fa-phone', label: 'Teléfono' },
        { name: 'fa-solid fa-store', label: 'Tienda' },
        { name: 'fa-solid fa-map-location-dot', label: 'Ubicación' },
        { name: 'fa-solid fa-cart-shopping', label: 'Compras' },
        { name: 'fa-solid fa-bag-shopping', label: 'Bolsa' },
        { name: 'fa-solid fa-star', label: 'Estrella' },
        { name: 'fa-solid fa-heart', label: 'Corazón' },
        { name: 'fa-solid fa-calendar', label: 'Calendario' },
        { name: 'fa-solid fa-camera', label: 'Cámara' },
        { name: 'fa-solid fa-video', label: 'Video' },
        { name: 'fa-solid fa-music', label: 'Música' },
        { name: 'fa-solid fa-podcast', label: 'Podcast' },
        { name: 'fa-solid fa-book', label: 'Libro' },
        { name: 'fa-solid fa-graduation-cap', label: 'Educación' },
        { name: 'fa-solid fa-briefcase', label: 'Portafolio' },
        { name: 'fa-solid fa-newspaper', label: 'Blog' },
        { name: 'fa-solid fa-rss', label: 'RSS' },
        { name: 'fa-solid fa-fire', label: 'Fire' },
        { name: 'fa-solid fa-bolt', label: 'Bolt' },
        { name: 'fa-solid fa-crown', label: 'Corona' },
        { name: 'fa-solid fa-gift', label: 'Regalo' },
        { name: 'fa-solid fa-handshake', label: 'Contacto' },
        { name: 'fa-solid fa-comment', label: 'Chat' },
        { name: 'fa-solid fa-address-card', label: 'Tarjeta' },
        { name: 'fa-solid fa-qrcode', label: 'QR Code' },
    ];

    var btn = document.getElementById('icon-picker-btn');
    var panel = document.getElementById('icon-panel');
    var grid = document.getElementById('icon-grid');
    var search = document.getElementById('icon-search');
    var iconValue = document.getElementById('icon-value');
    var iconPreview = document.getElementById('icon-preview');
    var iconLabel = document.getElementById('icon-label');

    function renderGrid(filter) {
        filter = (filter || '').toLowerCase();
        var filtered = filter ? icons.filter(function(ic) {
            return ic.label.toLowerCase().includes(filter) || ic.name.toLowerCase().includes(filter);
        }) : icons;
        grid.innerHTML = '';
        filtered.forEach(function(ic) {
            var btn2 = document.createElement('button');
            btn2.type = 'button';
            btn2.title = ic.label;
            btn2.className = 'flex flex-col items-center justify-center gap-1 p-2 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-700 hover:border-blue-500/50 transition-all group';
            btn2.innerHTML = '<i class="' + ic.name + ' text-slate-300 group-hover:text-blue-400 text-base"></i>' +
                             '<span class="text-[9px] text-slate-500 truncate w-full text-center">' + ic.label + '</span>';
            btn2.addEventListener('click', function() {
                iconValue.value = ic.name;
                iconPreview.innerHTML = '<i class="' + ic.name + '"></i>';
                iconLabel.textContent = ic.label;
                panel.classList.add('hidden');
            });
            grid.appendChild(btn2);
        });
    }

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            renderGrid('');
            search.value = '';
            search.focus();
        }
    });

    search.addEventListener('input', function() { renderGrid(this.value); });

    document.addEventListener('click', function(e) {
        if (!panel.contains(e.target) && e.target !== btn) {
            panel.classList.add('hidden');
        }
    });
})();

/* ── Toggle bg_color picker ── */
(function() {
    var toggle = document.getElementById('bg_color_enabled');
    var picker = document.getElementById('bg-color-picker');
    var colorInput = document.querySelector('input[name="bg_color"]');
    var hexDisplay = document.getElementById('bg_color_hex');
    if (!toggle || !picker) return;
    toggle.addEventListener('change', function() {
        picker.classList.toggle('hidden', !this.checked);
    });
    if (colorInput && hexDisplay) {
        colorInput.addEventListener('input', function() {
            hexDisplay.value = this.value;
        });
    }
})();
</script>
@endpush
@endsection
