<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') | {{ \Illuminate\Database\Capsule\Manager::table('settings')->where('setting_key', 'site_name')->value('setting_value') ?? 'Backoffice V2' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        adminbg: '#020617',
                        panels: '#0f172a',
                        accent: '#f59e0b'
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #020617; }
        .glass { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="text-slate-200 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 h-screen sticky top-0 border-r border-slate-800 flex flex-col">
            <div class="p-6">
                <h1 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-8 h-8 bg-accent rounded-lg flex items-center justify-center text-slate-950">
                        <i class="fa-solid fa-bolt text-sm"></i>
                    </span>
                    {{ \Illuminate\Database\Capsule\Manager::table('settings')->where('setting_key', 'site_name')->value('setting_value') ?? 'Backoffice' }} <span class="text-accent">V2</span>
                </h1>
            </div>

            <nav class="flex-1 px-4 space-y-1 mt-4">
                <a href="/admin" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin') || request_is('admin/') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-chart-line w-5"></i> Dashboard
                </a>
                <a href="/admin/links" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/links*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-link w-5"></i> Enlaces
                </a>
                @if(($_SESSION['user_role'] ?? '') === 'admin')
                    <a href="/admin/users" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/users*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                        <i class="fa-solid fa-users w-5"></i> Usuarios
                    </a>
                @endif
                <a href="/admin/settings" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/settings*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-gears w-5"></i> Ajustes
                </a>
            </nav>

            <div class="p-4 mt-auto border-t border-slate-800">
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center border border-slate-700">
                        <i class="fa-solid fa-user text-slate-500"></i>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <p class="text-sm font-bold text-white truncate">{{ $_SESSION['user_name'] ?? 'Admin' }}</p>
                        <p class="text-xs text-slate-500 truncate capitalize">{{ $_SESSION['user_role'] ?? 'Editor' }}</p>
                    </div>
                    <form action="/admin/logout" method="POST">
                        <button type="submit" class="text-slate-500 hover:text-red-400 transition-colors">
                            <i class="fa-solid fa-power-off text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-10">
            <header class="flex items-center justify-between mb-10">
                <div>
                    <h2 class="text-3xl font-bold text-white">@yield('header')</h2>
                    <p class="text-slate-500 mt-1">@yield('subheader')</p>
                </div>
                <div class="flex gap-4">
                    <a href="/" target="_blank" class="bg-slate-800 hover:bg-slate-700 text-white px-5 py-2.5 rounded-xl transition-all flex items-center gap-2 text-sm border border-slate-700">
                        <i class="fa-solid fa-eye text-xs"></i> Ver Sitio
                    </a>
                </div>
            </header>

            <div class="space-y-8">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
