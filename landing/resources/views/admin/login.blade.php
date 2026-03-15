<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold">Backoffice {{ $_ENV['APP_NAME'] }}</h1>
            <p class="text-slate-400 mt-2">Ingresá tus credenciales para administrar</p>
        </div>

        <form action="{{ url('admin/login') }}" method="POST" class="bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl">
            @if(isset($error))
                <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 text-sm">
                    {{ $error }}
                </div>
            @endif

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Email</label>
                    <input type="email" name="email" required 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-yellow-500/50 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Contraseña</label>
                    <input type="password" name="password" required 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 focus:border-yellow-500/50 outline-none transition-all">
                </div>

                <button type="submit" 
                        class="w-full bg-yellow-500 hover:bg-yellow-400 text-slate-950 font-bold py-4 rounded-xl transition-all shadow-lg shadow-yellow-500/20">
                    Iniciar Sesión
                </button>
            </div>
        </form>
        
        <p class="text-center mt-8 text-slate-500 text-xs tracking-widest uppercase">
            Powered by Slim 4 + Eloquent
        </p>
    </div>
</body>
</html>
