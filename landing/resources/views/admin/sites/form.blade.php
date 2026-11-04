@extends('admin/layout')

@section('title', $site ? 'Editar Sitio' : 'Nuevo Sitio')
@section('header', $site ? 'Editar Sitio' : 'Nuevo Sitio')
@section('subheader', $site ? 'Modifica los datos del sitio gestionado.' : 'Registra una nueva landing para gestionar.')

@section('content')
    <a href="{{ url('admin/sites') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-white transition-colors mb-6">
        <i class="fa-solid fa-arrow-left text-xs"></i> Volver a Sitios
    </a>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <form action="{{ url($site ? 'admin/sites/edit/' . $site->id : 'admin/sites/create') }}" method="POST" class="{{ $site ? 'xl:col-span-2' : 'xl:col-span-3' }}">
            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="grid grid-cols-1 {{ $site ? '' : 'xl:grid-cols-3' }} gap-6 items-start">
                <div class="{{ $site ? '' : 'xl:col-span-2' }} space-y-6">
                    {{-- Información del Sitio --}}
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
                        <h3 class="text-white font-bold flex items-center gap-2">
                            <i class="fa-solid fa-globe text-slate-500 text-sm"></i> Información del Sitio
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm text-slate-400 mb-2">Nombre <span class="text-red-400">*</span></label>
                                <input type="text" name="name" value="{{ $old['name'] ?? $site->name ?? '' }}" required
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-400 mb-2">Dominio <span class="text-red-400">*</span></label>
                                <input type="text" name="domain" value="{{ $old['domain'] ?? $site->domain ?? '' }}" required maxlength="253" pattern="(?=.{1,253}$)([A-Za-z0-9]([A-Za-z0-9\-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,63}" title="Dominio sin www ni https, ej: cliente.com.ar" autocapitalize="off" placeholder="ejemplo.com"
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm text-slate-400 mb-2">Carpeta en public_html <span class="text-slate-600">(opcional)</span></label>
                                <input type="text" name="folder" value="{{ $old['folder'] ?? $site->folder ?? '' }}" maxlength="190" pattern="[A-Za-z0-9][A-Za-z0-9._\- ]*" title="Solo un nombre de carpeta, sin / ni .. (ej: cliente)" placeholder="ej: cliente"
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                                <p class="text-xs text-slate-600 mt-1">Carpeta donde está la copia de esta app para el sitio (public_html/<em>cliente</em>/). La usa la sección Directorios para asociar carpeta ↔ sitio.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Conexión a Base de Datos --}}
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
                        <h3 class="text-white font-bold flex items-center gap-2">
                            <i class="fa-solid fa-database text-slate-500 text-sm"></i> Conexión a Base de Datos
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm text-slate-400 mb-2">Host <span class="text-red-400">*</span></label>
                                <input type="text" name="db_host" value="{{ $old['db_host'] ?? $site->db_host ?? 'localhost' }}" required maxlength="255" pattern="[A-Za-z0-9.\-_]+" title="Solo letras, números, punto, guion y guion bajo (ej: localhost)"
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-400 mb-2">Puerto</label>
                                <input type="number" name="db_port" min="1" max="65535" step="1" value="{{ $old['db_port'] ?? $site->db_port ?? 3306 }}"
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-400 mb-2">Base de Datos <span class="text-red-400">*</span></label>
                                <input type="text" name="db_name" value="{{ $old['db_name'] ?? $site->db_name ?? '' }}" required maxlength="64" pattern="[A-Za-z0-9_]+" title="Solo letras, números y guion bajo (ej: cuenta_cliente)"
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-400 mb-2">Usuario <span class="text-red-400">*</span></label>
                                <input type="text" name="db_user" value="{{ $old['db_user'] ?? $site->db_user ?? '' }}" required maxlength="64" pattern="[A-Za-z0-9_]+" title="Solo letras, números y guion bajo (ej: cuenta_cliente)"
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm text-slate-400 mb-2">
                                    Contraseña {{ $site ? '(dejar vacío para no cambiar)' : '' }} <span class="{{ $site ? '' : 'text-red-400' }}">{{ $site ? '' : '*' }}</span>
                                </label>
                                <input type="password" name="db_pass" {{ $site ? '' : 'required' }}
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            </div>
                        </div>
                        @if($site)
                        <div class="pt-2">
                            <button type="button" id="btn-test-conn"
                                    onclick="testConnectionForm({{ $site->id }})"
                                    class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl transition-all flex items-center gap-2 text-sm border border-slate-700">
                                <i class="fa-solid fa-plug text-xs"></i> Probar Conexión
                            </button>
                            <span id="test-conn-result" class="text-sm ml-3"></span>
                        </div>
                        @endif
                    </div>

                    {{-- Plan y Contrato --}}
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
                        <h3 class="text-white font-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-contract text-slate-500 text-sm"></i> Plan y Contrato
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="sm:col-span-2">
                                <label class="block text-sm text-slate-400 mb-2">Plan</label>
                                <select name="plan_name"
                                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                                    @foreach(['basico', 'profesional', 'premium'] as $plan)
                                        <option value="{{ $plan }}" {{ ($site->plan_name ?? 'basico') === $plan ? 'selected' : '' }}>
                                            {{ ucfirst($plan) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-slate-400 mb-2">Fecha Inicio <span class="text-red-400">*</span></label>
                                <input type="date" name="plan_start" value="{{ $site ? $site->plan_start->format('Y-m-d') : date('Y-m-d') }}" required
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-400 mb-2">Fecha Fin <span class="text-red-400">*</span></label>
                                <input type="date" name="plan_end" data-min-field="plan_start" data-error-min="El fin del plan no puede ser anterior al inicio." value="{{ $site ? $site->plan_end->format('Y-m-d') : '' }}" required
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="space-y-6 {{ $site ? '' : 'xl:sticky xl:top-6' }}">
                    {{-- Dominio --}}
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
                        <h3 class="text-white font-bold flex items-center gap-2">
                            <i class="fa-solid fa-earth-americas text-slate-500 text-sm"></i> Dominio
                        </h3>
                        <div>
                            <label class="block text-sm text-slate-400 mb-2">Fecha de Vencimiento del Dominio</label>
                            <input type="date" name="domain_expiry" value="{{ $site && $site->domain_expiry ? $site->domain_expiry->format('Y-m-d') : '' }}"
                                   class="w-full sm:w-1/2 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            <p class="text-xs text-slate-600 mt-1">Informativo. No afecta la suspensión automática.</p>
                        </div>
                    </div>

                    {{-- Bloqueo --}}
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
                        <h3 class="text-white font-bold flex items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-slate-500 text-sm"></i> Bloqueo y Redirección
                        </h3>
                        <div>
                            <label class="block text-sm text-slate-400 mb-2">URL de Redirección al Suspender</label>
                            <input type="text" name="redirect_url" maxlength="500" pattern="(/|https?://).*" title="Una ruta que empiece con / o una URL con http(s)://" value="{{ $site->redirect_url ?? $defaultRedirect ?? '' }}" placeholder="https://..."
                                   class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors">
                            <p class="text-xs text-slate-600 mt-1">URL a la que se redirige el tráfico público cuando el sitio está suspendido.</p>
                        </div>
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="hidden" name="auto_suspend" value="0">
                                <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700 shrink-0">
                                    <input type="checkbox" name="auto_suspend" value="1"
                                           {{ ($site->auto_suspend ?? 1) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                                </div>
                                <div>
                                    <span class="text-sm text-white font-bold">Auto-suspensión</span>
                                    <p class="text-xs text-slate-500">Suspender automáticamente cuando vence el plan (vía cron).</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Notas --}}
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
                        <h3 class="text-white font-bold flex items-center gap-2">
                            <i class="fa-solid fa-note-sticky text-slate-500 text-sm"></i> Notas
                        </h3>
                        <textarea name="notes" rows="3" placeholder="Notas internas sobre este sitio..."
                                  class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors resize-y">{{ $site->notes ?? '' }}</textarea>
                    </div>
                </aside>
            </div>

            {{-- Barra de guardado: queda visible al hacer scroll --}}
            <div class="sticky bottom-0 z-20 -mx-4 sm:-mx-6 lg:-mx-10 px-4 sm:px-6 lg:px-10 py-4 bg-slate-950/90 backdrop-blur border-t border-slate-800 flex justify-end gap-3 {{ $site ? 'xl:mx-0 xl:px-0' : '' }}">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                    {{ $site ? 'Actualizar Sitio' : 'Crear Sitio' }}
                </button>
            </div>
        </form>

        @if($site)
        {{-- ─── Puesta en marcha ─────────────────────────────────── --}}
        @php
            $tok = $_SESSION['csrf_token'] ?? '';
            $inputCls = 'w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors';
        @endphp
        <aside class="mt-10 xl:mt-0 space-y-6">
            <h2 class="text-xl font-bold text-white flex items-center gap-2"><i class="fa-solid fa-rocket text-slate-500"></i> Puesta en marcha</h2>

            @if(!empty($log))
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                    <pre class="text-xs text-slate-300 whitespace-pre-wrap">@foreach($log as $line){{ $line }}
@endforeach</pre>
                </div>
            @endif

            {{-- 1. Base de datos --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
                <h3 class="text-white font-bold">1. Base de datos propia</h3>
                <p class="text-sm text-slate-400">
                    <strong class="text-slate-300">En cPanel:</strong> creá la base <code class="text-cyan-300">{{ $site->db_name }}</code> y el usuario
                    <code class="text-cyan-300">{{ $site->db_user }}</code> en <em>Bases de datos MySQL</em>, asigná el usuario con TODOS los privilegios
                    y cargá esos datos en «Conexión a Base de Datos» (en cPanel llevan el prefijo de la cuenta, ej. <code>cuenta_cliente</code>).
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="btn-test-conn2" onclick="testConnectionForm({{ $site->id }})"
                            class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm border border-slate-700">
                        <i class="fa-solid fa-plug text-xs"></i> Probar conexión
                    </button>
                    <form action="{{ url('admin/sites/' . $site->id . '/create-db') }}" method="POST"
                          onsubmit="return confirm('Crear la base y el usuario con la cuenta MySQL master? (solo Docker/VPS, no cPanel)')">
                        <input type="hidden" name="_token" value="{{ $tok }}">
                        <button class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm border border-slate-700">
                            <i class="fa-solid fa-database text-xs"></i> Crear DB y usuario (local/VPS)
                        </button>
                    </form>
                </div>
            </div>

            {{-- 2. Inicializar --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
                <h3 class="text-white font-bold flex items-center gap-3">2. Inicializar sitio
                    @if($site->initialized_at)
                        <span class="text-xs font-normal text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-full">Inicializado {{ $site->initialized_at->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="text-xs font-normal text-yellow-400 bg-yellow-400/10 px-2 py-1 rounded-full">Pendiente</span>
                    @endif
                </h3>
                <p class="text-sm text-slate-400">Crea las tablas y la configuración base en la DB del sitio (es seguro repetirlo) y crea o actualiza su administrador.</p>
                <form action="{{ url('admin/sites/' . $site->id . '/initialize') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-4">
                    <input type="hidden" name="_token" value="{{ $tok }}">
                    <input type="text" name="admin_name" aria-label="Nombre del admin" placeholder="Nombre del admin" class="{{ $inputCls }} sm:col-span-2 xl:col-span-1">
                    <input type="email" name="admin_email" aria-label="Email del admin" placeholder="admin@{{ $site->domain }} *" required maxlength="150" class="{{ $inputCls }}">
                    <input type="password" name="admin_password" aria-label="Contraseña del admin" placeholder="Contraseña (mín. 8) *" minlength="8" required autocomplete="new-password" class="{{ $inputCls }}">
                    <div class="sm:col-span-2 xl:col-span-1 flex justify-end">
                        <button class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-xl font-bold text-sm">
                            <i class="fa-solid fa-wand-magic-sparkles text-xs"></i> Inicializar
                        </button>
                    </div>
                </form>
            </div>

            {{-- 3. Dominio --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
                <h3 class="text-white font-bold">3. Dominio</h3>
                <ol class="text-sm text-slate-400 list-decimal list-inside space-y-1">
                    <li>En el registrador, apuntá <code class="text-cyan-300">{{ $site->domain }}</code> (y <code>www</code>) a la IP del hosting, o usá los nameservers del hosting.</li>
                    <li>En cPanel → <em>Dominios</em> → <em>Crear un nuevo dominio</em>: <code class="text-cyan-300">{{ $site->domain }}</code>.</li>
                    <li><strong>Una raíz de documentos para todos</strong> (misma instalación): la misma carpeta <code>public/</code> de esta app (ej. <code>landing/public</code>). No copiás archivos: la app elige la DB por dominio.</li>
                    <li><strong>Carpeta propia por sitio</strong> (copia en <code>public_html/{{ $site->folder ?: '<em>cliente</em>' }}/</code>): la raíz de documentos es <code>public_html/{{ $site->folder ?: '<em>cliente</em>' }}/public/</code> y la copia lleva su <code>.env</code> con <code>SITE_ID={{ $site->id }}</code> y <code>MASTER_DB_*</code> (ver <code>docs/multisitio-y-servidores.md</code> §2b).</li>
                    <li>Emití el SSL (cPanel → <em>SSL/TLS Status</em> → AutoSSL).</li>
                    <li>Verificá:</li>
                </ol>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="btn-verify" onclick="verifyDomain({{ $site->id }})"
                            class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm border border-slate-700">
                        <i class="fa-solid fa-satellite-dish text-xs"></i> Verificar dominio
                    </button>
                    <span id="verify-result" class="text-sm"></span>
                </div>
            </div>

            {{-- 4. Entrar --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-white font-bold">4. Administrar el sitio</h3>
                    <p class="text-sm text-slate-400">Entrás a <code>{{ $site->domain }}/admin</code> como root, sin conocer ninguna contraseña (acceso de un solo uso, queda registrado). Desde ahí configurás qué ve cada rol del sitio.</p>
                </div>
                <form action="{{ url('admin/sites/' . $site->id . '/impersonate') }}" method="POST" target="_blank" data-no-loading>
                    <input type="hidden" name="_token" value="{{ $tok }}">
                    <button class="bg-amber-600 hover:bg-amber-500 text-white px-6 py-2.5 rounded-xl font-bold text-sm" {{ $site->status !== 'active' ? 'disabled' : '' }}>
                        <i class="fa-solid fa-right-to-bracket text-xs"></i> Entrar como root
                    </button>
                </form>
                <form action="{{ url('admin/sites/' . $site->id . '/impersonate') }}" method="POST" target="_blank" data-no-loading>
                    <input type="hidden" name="_token" value="{{ $tok }}">
                    <input type="hidden" name="next" value="admin/roles">
                    <button class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-6 py-2.5 rounded-xl font-bold text-sm" {{ $site->status !== 'active' ? 'disabled' : '' }}>
                        <i class="fa-solid fa-user-shield text-xs"></i> Roles y permisos
                    </button>
                </form>
            </div>
        </aside>
        @endif
    </div>
@endsection

@if($site)
@push('scripts')
<script>
function testConnectionForm(siteId) {
    var btn = document.getElementById('btn-test-conn');
    var result = document.getElementById('test-conn-result');
    var icon = btn.querySelector('i');
    icon.className = 'fa-solid fa-circle-notch fa-spin text-xs';
    btn.disabled = true;
    result.textContent = '';

    fetch('{{ url("admin/sites") }}/' + siteId + '/test', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '{{ $_SESSION['csrf_token'] ?? '' }}' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        icon.className = 'fa-solid fa-plug text-xs';
        btn.disabled = false;
        result.textContent = data.message;
        result.className = 'text-sm ml-3 ' + (data.success ? 'text-emerald-400' : 'text-red-400');
    })
    .catch(function() {
        icon.className = 'fa-solid fa-plug text-xs';
        btn.disabled = false;
        result.textContent = 'Error de red.';
        result.className = 'text-sm ml-3 text-red-400';
    });
}
function verifyDomain(siteId) {
    var out = document.getElementById('verify-result');
    out.textContent = 'Verificando…';
    out.className = 'text-sm text-slate-400';
    fetch('{{ url("admin/sites") }}/' + siteId + '/verify-domain', {
        method: 'POST',
        headers: { 'X-CSRF-Token': '{{ $_SESSION['csrf_token'] ?? '' }}', 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        out.textContent = d.message + (d.dns && d.dns.length ? ' DNS: ' + d.dns.join(', ') : '') + (d.master_ips && d.master_ips.length ? ' · IP master: ' + d.master_ips.join(', ') : '');
        out.className = 'text-sm ' + (d.success ? 'text-emerald-400' : 'text-yellow-400');
    })
    .catch(function() { out.textContent = 'Error de red.'; out.className = 'text-sm text-red-400'; });
}
</script>
@endpush
@endif
