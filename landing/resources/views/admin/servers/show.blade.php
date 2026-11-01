@extends('admin/layout')

@section('title', $server->name)
@section('header', $server->name)
@section('subheader', $server->username . '@' . $server->host . ':' . $server->port . ' · ' . $server->remote_app_path)

@section('content')
@php
    $tok = $_SESSION['csrf_token'] ?? '';
    $cls = 'w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors';
    $icons = ['ok' => 'fa-circle-check text-emerald-400', 'warn' => 'fa-triangle-exclamation text-yellow-400', 'error' => 'fa-circle-xmark text-red-400'];
    $pinned = (bool) $server->host_key_fingerprint;
@endphp
<div class="space-y-6 max-w-5xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ url('admin/servers') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-white transition-colors">
            <i class="fa-solid fa-arrow-left text-xs"></i> Servidores
        </a>
        <div class="flex gap-2">
            <a href="{{ url('admin/servers/edit/' . $server->id) }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm border border-slate-700"><i class="fa-solid fa-pen text-xs"></i> Editar</a>
            <form action="{{ url('admin/servers/delete/' . $server->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este servidor del panel? No se toca nada remoto.')">
                <input type="hidden" name="_token" value="{{ $tok }}">
                <button class="bg-slate-800 hover:bg-red-900 text-slate-400 hover:text-red-300 px-4 py-2 rounded-xl text-sm border border-slate-700"><i class="fa-solid fa-trash text-xs"></i></button>
            </form>
        </div>
    </div>

    {{-- Huella --}}
    <div class="bg-slate-900 border {{ $pinned ? 'border-slate-800' : 'border-yellow-700' }} rounded-2xl p-6 space-y-3">
        <h3 class="text-white font-bold flex items-center gap-2"><i class="fa-solid fa-fingerprint text-slate-500"></i> Huella del servidor</h3>
        @if($pinned)
            <p class="text-sm text-slate-400">Confirmada: <code class="text-emerald-300 break-all">{{ $server->host_key_fingerprint }}</code>. Si el servidor presenta otra, la conexión se rechaza.</p>
        @else
            <p class="text-sm text-yellow-300">Antes de conectar, verificá la huella. Comparala con la que muestra el hosting (cPanel → Acceso SSH, o soporte) y confirmala.</p>
        @endif
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" onclick="fetchFingerprint()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm border border-slate-700">
                <i class="fa-solid fa-magnifying-glass text-xs"></i> Verificar huella
            </button>
            <span id="fp-result" class="text-sm"></span>
        </div>
        <form id="fp-pin" action="{{ url('admin/servers/' . $server->id . '/pin') }}" method="POST" class="hidden">
            <input type="hidden" name="_token" value="{{ $tok }}">
            <input type="hidden" name="fingerprint" id="fp-value">
            <button class="bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-xl text-sm font-bold"><i class="fa-solid fa-lock text-xs"></i> Confirmar esta huella</button>
        </form>
    </div>

    @if($pinned)
    {{-- Estado --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-white font-bold flex items-center gap-2"><i class="fa-solid fa-heart-pulse text-slate-500"></i> Estado</h3>
            <button type="button" id="btn-health" onclick="runHealth()" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-bold">
                <i class="fa-solid fa-play text-xs"></i> Revisar
            </button>
        </div>
        <ul id="health" class="divide-y divide-slate-800 text-sm"></ul>
        <div id="remote-sites" class="hidden">
            <h4 class="text-white font-bold mt-4 mb-2">Sitios en producción</h4>
            <table class="w-full text-sm"><tbody id="remote-sites-body" class="divide-y divide-slate-800"></tbody></table>
        </div>
    </div>

    {{-- Logs --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <h3 class="text-white font-bold flex items-center gap-2 mr-auto"><i class="fa-solid fa-scroll text-slate-500"></i> Logs</h3>
            <button type="button" onclick="loadLogFiles()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm border border-slate-700">Listar</button>
            <select id="log-file" class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm"></select>
            <select id="log-lines" class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm">
                <option>50</option><option selected>100</option><option>200</option><option>500</option>
            </select>
            <button type="button" onclick="tailLog()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm border border-slate-700">Ver</button>
        </div>
        <pre id="log-content" class="text-xs text-slate-300 bg-slate-950 rounded-xl p-4 max-h-[32rem] overflow-auto whitespace-pre-wrap break-all hidden"></pre>
    </div>

    {{-- Provisionar --}}
    <div id="provision" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
        <h3 class="text-white font-bold flex items-center gap-2"><i class="fa-solid fa-rocket text-slate-500"></i> Provisionar sitio</h3>
        <p class="text-sm text-slate-400">
            Crea en el servidor la base <code>{{ $server->dbPrefix() }}…</code> y su usuario (contraseña aleatoria, vía <code>uapi</code>),
            registra el sitio en el gestor de producción e inicializa su esquema y su administrador con <code>bin/console</code> remoto.
            Se puede re-ejecutar si algún paso falla.
        </p>

        @if($provision)
            <ul class="bg-slate-950 rounded-xl p-4 space-y-1 text-sm">
                @foreach($provision['steps'] as $st)
                    <li class="flex gap-2"><i class="fa-solid {{ $icons[$st['status']] ?? $icons['warn'] }} mt-1"></i><span class="text-slate-300 break-words">{{ $st['msg'] }}</span></li>
                @endforeach
            </ul>
        @endif

        <form action="{{ url('admin/servers/' . $server->id . '/provision') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4"
              onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Provisionando… (puede tardar)';">
            <input type="hidden" name="_token" value="{{ $tok }}">
            <input type="text" name="name" value="{{ $old['name'] ?? '' }}" placeholder="Nombre del sitio" required class="{{ $cls }}">
            <input type="text" name="domain" value="{{ $old['domain'] ?? '' }}" placeholder="cliente.com.ar" required class="{{ $cls }}">
            <div>
                <div class="flex items-center"><span class="text-slate-500 text-sm pr-2">{{ $server->dbPrefix() }}</span>
                <input type="text" name="db_suffix" value="{{ $old['db_suffix'] ?? '' }}" placeholder="cliente" required pattern="[A-Za-z0-9_]+" class="{{ $cls }}"></div>
                <p class="text-xs text-slate-600 mt-1">Nombre de la base</p>
            </div>
            <div>
                <div class="flex items-center"><span class="text-slate-500 text-sm pr-2">{{ $server->dbPrefix() }}</span>
                <input type="text" name="user_suffix" value="{{ $old['user_suffix'] ?? '' }}" placeholder="cliente" required pattern="[A-Za-z0-9_]+" class="{{ $cls }}"></div>
                <p class="text-xs text-slate-600 mt-1">Usuario MySQL</p>
            </div>
            <input type="text" name="admin_name" value="{{ $old['admin_name'] ?? '' }}" placeholder="Nombre del admin del sitio" class="{{ $cls }}">
            <input type="email" name="admin_email" value="{{ $old['admin_email'] ?? '' }}" placeholder="Email del admin del sitio" required class="{{ $cls }}">
            <input type="password" name="admin_password" placeholder="Contraseña del admin (mín. 8)" minlength="8" required autocomplete="new-password" class="{{ $cls }}">
            <input type="number" name="plan_days" value="{{ $old['plan_days'] ?? 365 }}" min="1" class="{{ $cls }}" title="Días de plan">
            <div class="sm:col-span-2 flex justify-end">
                <button class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-xl font-bold text-sm">
                    <i class="fa-solid fa-rocket text-xs"></i> Provisionar en {{ $server->name }}
                </button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
var CSRF = '{{ $tok }}';
var BASE = '{{ url("admin/servers/" . $server->id) }}';
var ICONS = { ok: 'fa-circle-check text-emerald-400', warn: 'fa-triangle-exclamation text-yellow-400', error: 'fa-circle-xmark text-red-400' };

function post(path, body) {
    return fetch(BASE + path, {
        method: 'POST',
        headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(body || {})
    }).then(function (r) { return r.json(); });
}
function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text !== undefined) e.textContent = text; return e; }

function fetchFingerprint() {
    var out = document.getElementById('fp-result');
    out.textContent = 'Conectando…'; out.className = 'text-sm text-slate-400';
    post('/fingerprint').then(function (d) {
        if (!d.success) { out.textContent = d.message; out.className = 'text-sm text-red-400'; return; }
        if (d.matches) { out.textContent = 'Coincide con la confirmada: ' + d.fingerprint; out.className = 'text-sm text-emerald-400'; return; }
        out.textContent = (d.pinned ? 'DISTINTA de la confirmada (' + d.pinned + '): ' : 'Huella presentada: ') + d.fingerprint;
        out.className = 'text-sm ' + (d.pinned ? 'text-red-400' : 'text-yellow-300');
        document.getElementById('fp-value').value = d.fingerprint;
        document.getElementById('fp-pin').classList.remove('hidden');
    }).catch(function () { out.textContent = 'Error de red.'; out.className = 'text-sm text-red-400'; });
}

function runHealth() {
    var list = document.getElementById('health'), btn = document.getElementById('btn-health');
    list.innerHTML = ''; list.appendChild(el('li', 'py-2 text-slate-400', 'Revisando… (puede tardar unos segundos)'));
    btn.disabled = true;
    post('/health').then(function (d) {
        btn.disabled = false; list.innerHTML = '';
        if (!d.success) { list.appendChild(el('li', 'py-2 text-red-400', d.message)); return; }
        d.checks.forEach(function (c) {
            var li = el('li', 'py-2 flex gap-3');
            li.appendChild(el('i', 'fa-solid ' + (ICONS[c.status] || ICONS.warn) + ' mt-1'));
            var box = el('div', 'min-w-0');
            box.appendChild(el('p', 'text-white font-medium', c.label));
            box.appendChild(el('p', 'text-slate-400 whitespace-pre-wrap break-words', c.detail));
            li.appendChild(box); list.appendChild(li);
        });
        var tbody = document.getElementById('remote-sites-body'); tbody.innerHTML = '';
        (d.sites || []).forEach(function (s) {
            var tr = el('tr');
            tr.appendChild(el('td', 'py-2 text-white', s.name));
            tr.appendChild(el('td', 'py-2 text-slate-400', s.domain));
            tr.appendChild(el('td', 'py-2 text-slate-500 font-mono text-xs', s.db_name));
            tr.appendChild(el('td', 'py-2 text-right ' + (s.status === 'active' ? 'text-emerald-400' : 'text-red-400'), s.status));
            tbody.appendChild(tr);
        });
        document.getElementById('remote-sites').classList.toggle('hidden', !(d.sites || []).length);
    }).catch(function () { btn.disabled = false; list.innerHTML = ''; list.appendChild(el('li', 'py-2 text-red-400', 'Error de red.')); });
}

function loadLogFiles() {
    var sel = document.getElementById('log-file');
    sel.innerHTML = ''; sel.appendChild(el('option', '', 'Cargando…'));
    post('/logs').then(function (d) {
        sel.innerHTML = '';
        if (!d.success) { sel.appendChild(el('option', '', 'Error: ' + d.message)); return; }
        if (!d.files.length) { sel.appendChild(el('option', '', 'Sin logs')); return; }
        d.files.forEach(function (f) { var o = el('option', '', f); o.value = f; sel.appendChild(o); });
    });
}

function tailLog() {
    var f = document.getElementById('log-file').value, pre = document.getElementById('log-content');
    if (!f) { loadLogFiles(); return; }
    pre.classList.remove('hidden'); pre.textContent = 'Cargando…';
    post('/logs', { file: f, lines: document.getElementById('log-lines').value }).then(function (d) {
        pre.textContent = d.success ? (d.content || '(vacío)') : 'Error: ' + d.message;
        pre.scrollTop = pre.scrollHeight;
    });
}
</script>
@endpush
