@extends('admin/layout')

@section('title', 'Editar Usuario')
@section('header', 'Editar Usuario')
@section('subheader', $isSelf ? 'Tu cuenta: tu rol y estado solo los cambia otro root/administrador.' : 'Modificá los datos y el rol del usuario.')

@section('content')
    <form action="{{ url('admin/users/edit/' . $user->id) }}" method="POST">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-slate-900 border border-slate-800 p-5 sm:p-8 rounded-3xl shadow-sm space-y-6">
                    <h3 class="text-lg font-bold text-white">Datos del Usuario</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-2">Nombre Completo</label>
                            <input type="text" name="name" value="{{ $user->name }}" required maxlength="100"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-2">Correo Electrónico</label>
                            <input type="email" name="email" value="{{ $user->email }}" required maxlength="150"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-400 mb-2">Nueva Contraseña</label>
                            <input type="password" name="password" minlength="8" placeholder="Dejar en blanco para no cambiar"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white">
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-6 xl:sticky xl:top-6">
                <div class="bg-slate-900 border border-slate-800 p-5 sm:p-8 rounded-3xl shadow-sm space-y-6">
                    <h3 class="text-lg font-bold text-white">Acceso</h3>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Rol de Usuario</label>
                        <select name="role" {{ $isSelf ? 'disabled' : 'required' }} class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-blue-500/50 outline-none transition-all text-white disabled:opacity-60">
                            @unless(array_key_exists($user->role, $roles))
                                <option value="{{ $user->role }}" selected>{{ $user->role === 'root' ? 'Root' : $user->role }}</option>
                            @endunless
                            @foreach($roles as $slug => $name)
                                <option value="{{ $slug }}" {{ $user->role === $slug ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative w-12 h-6 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700">
                                <input type="checkbox" name="active" {{ $user->active ? 'checked' : '' }} {{ $isSelf ? 'disabled' : '' }} class="sr-only peer">
                                <div class="absolute left-1 top-1 w-4 h-4 bg-slate-400 rounded-full transition-all peer-checked:left-7 peer-checked:bg-blue-500"></div>
                            </div>
                            <span class="text-sm font-medium text-slate-400">Usuario activo</span>
                        </label>
                    </div>
                    <p class="text-xs text-slate-500">El rol define qué secciones del panel ve el usuario. Un usuario inactivo no puede iniciar sesión.</p>
                </div>
            </aside>
        </div>

        {{-- Barra de guardado: queda visible al hacer scroll --}}
        <div class="sticky bottom-0 z-20 -mx-4 sm:-mx-6 lg:-mx-10 px-4 sm:px-6 lg:px-10 py-4 bg-slate-950/90 backdrop-blur border-t border-slate-800 flex justify-end gap-3">
            <a href="{{ url('admin/users') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-3 rounded-xl font-bold transition-all">
                Cancelar
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-500/20">
                Guardar Cambios
            </button>
        </div>
    </form>
@endsection
