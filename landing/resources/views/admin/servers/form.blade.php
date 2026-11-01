@extends('admin/layout')

@section('title', $server ? 'Editar servidor' : 'Nuevo servidor')
@section('header', $server ? 'Editar servidor' : 'Nuevo servidor')
@section('subheader', 'Conexión SSH con clave a un hosting cPanel.')

@section('content')
@php
    $v = fn ($k, $def = '') => $old[$k] ?? ($server->$k ?? $def);
    $cls = 'w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-blue-500 focus:outline-none transition-colors';
@endphp
<div class="max-w-3xl">
    <a href="{{ url($server ? 'admin/servers/' . $server->id : 'admin/servers') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-white transition-colors mb-6">
        <i class="fa-solid fa-arrow-left text-xs"></i> Volver
    </a>

    <form action="{{ url($server ? 'admin/servers/edit/' . $server->id : 'admin/servers/create') }}" method="POST" class="space-y-8">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
            <h3 class="text-white font-bold flex items-center gap-2"><i class="fa-solid fa-terminal text-slate-500 text-sm"></i> Conexión SSH</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm text-slate-400 mb-2">Nombre <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ $v('name') }}" required placeholder="Producción cPanel" class="{{ $cls }}">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Host <span class="text-red-400">*</span></label>
                    <input type="text" name="host" value="{{ $v('host') }}" required placeholder="servidor.hosting.com" class="{{ $cls }}">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Puerto</label>
                    <input type="number" name="port" value="{{ $v('port', 22) }}" class="{{ $cls }}">
                    <p class="text-xs text-slate-600 mt-1">Muchos hostings usan un puerto distinto de 22.</p>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Usuario SSH <span class="text-red-400">*</span></label>
                    <input type="text" name="username" value="{{ $v('username') }}" required placeholder="usuario de cPanel" class="{{ $cls }}">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Clave privada <span class="text-red-400">*</span></label>
                    @if(count($keys))
                        <select name="key_path" class="{{ $cls }}">
                            @foreach($keys as $k)
                                <option value="{{ $k }}" {{ $v('key_path') === $k ? 'selected' : '' }}>{{ basename($k) }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="key_path" value="{{ $v('key_path') }}" placeholder="{{ $keysDir }}/id_ed25519" class="{{ $cls }}">
                        <p class="text-xs text-yellow-500 mt-1">No hay claves en {{ $keysDir }}. Ver instrucciones abajo.</p>
                    @endif
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm text-slate-400 mb-2">Passphrase de la clave {{ $server && $server->key_passphrase_encrypted ? '(guardada; dejar vacío para no cambiar)' : '(si tiene)' }}</label>
                    <input type="password" name="key_passphrase" autocomplete="new-password" class="{{ $cls }}">
                    @if($server && $server->key_passphrase_encrypted)
                        <label class="text-xs text-slate-500 mt-2 flex items-center gap-2"><input type="checkbox" name="clear_passphrase" value="1"> Quitar passphrase guardada</label>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
            <h3 class="text-white font-bold flex items-center gap-2"><i class="fa-solid fa-cubes text-slate-500 text-sm"></i> Instalación remota</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Ruta de la app <span class="text-red-400">*</span></label>
                    <input type="text" name="remote_app_path" value="{{ $v('remote_app_path', 'landing') }}" required class="{{ $cls }}">
                    <p class="text-xs text-slate-600 mt-1">Carpeta <code>landing/</code> relativa al home (donde está <code>bin/console</code>).</p>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Binario PHP (CLI)</label>
                    <input type="text" name="php_bin" value="{{ $v('php_bin', 'php') }}" class="{{ $cls }}">
                    <p class="text-xs text-slate-600 mt-1">Ej: <code>/opt/cpanel/ea-php81/root/usr/bin/php</code> si <code>php</code> no es 8.1+.</p>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Usuario cPanel (prefijo de DBs)</label>
                    <input type="text" name="cpanel_user" value="{{ $v('cpanel_user') }}" placeholder="igual al usuario SSH" class="{{ $cls }}">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Host MySQL de los sitios</label>
                    <input type="text" name="mysql_host" value="{{ $v('mysql_host', 'localhost') }}" class="{{ $cls }}">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all">
                {{ $server ? 'Actualizar' : 'Guardar' }}
            </button>
        </div>
    </form>

    <div class="mt-8 bg-slate-900/50 border border-slate-800 rounded-2xl p-6 text-sm text-slate-400 space-y-2">
        <h3 class="text-white font-bold">Configurar la clave (una sola vez)</h3>
        <ol class="list-decimal list-inside space-y-1">
            <li>En tu máquina: <code class="text-cyan-300">ssh-keygen -t ed25519 -f secrets/ssh/landing_deploy -C landing-deploy</code> (en la raíz del repo).</li>
            <li>En cPanel → <em>Acceso SSH</em> → <em>Administrar claves SSH</em> → <em>Importar clave</em>: pegá el contenido de <code>landing_deploy.pub</code> y <strong>Autorizala</strong>.</li>
            <li>La carpeta <code>secrets/ssh</code> se monta en el contenedor como <code>{{ $keysDir }}</code> (solo lectura) y está en <code>.gitignore</code>.</li>
        </ol>
    </div>
</div>
@endsection
