<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') | {{ \Illuminate\Database\Capsule\Manager::table('settings')->where('setting_key', 'site_name')->value('setting_value') ?? $_ENV['APP_NAME'] }}</title>
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
    <div class="flex min-h-screen">

        <!-- Backdrop (mobile overlay) -->
        <div id="sidebar-backdrop"
             class="fixed inset-0 z-30 bg-slate-950/60 backdrop-blur-sm hidden md:hidden">
        </div>

        <!-- Sidebar -->
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 border-r border-slate-800 flex flex-col
                      -translate-x-full md:translate-x-0 transition-transform duration-300
                      md:relative md:z-auto">
            <div class="p-6">
                <h1 class="text-xl font-bold text-white flex items-center gap-2">
                    {{ \Illuminate\Database\Capsule\Manager::table('settings')->where('setting_key', 'site_name')->value('setting_value') ?? 'Backoffice' }}
                <span class="w-8 h-8 bg-accent rounded-lg flex items-center justify-center text-slate-950">
                        <i class="fa-solid fa-bolt text-sm"></i>
                    </span>
                </h1>
            </div>

            @php
                $__modules = \Illuminate\Database\Capsule\Manager::table('settings')
                    ->whereIn('setting_key', ['module_links_enabled','module_locations_enabled','module_menu_enabled','module_site_manager_enabled'])
                    ->pluck('setting_value', 'setting_key')->toArray();
                $__linksEnabled        = ($__modules['module_links_enabled'] ?? '1') === '1';
                $__locationsEnabled    = ($__modules['module_locations_enabled'] ?? '1') === '1';
                $__menuEnabled         = ($__modules['module_menu_enabled'] ?? '1') === '1';
                $__siteManagerEnabled  = ($__modules['module_site_manager_enabled'] ?? '0') === '1';
                $__menuConfigured   = \Illuminate\Database\Capsule\Manager::table('settings')
                    ->where('setting_key', 'menu_enabled')->value('setting_value') ?? '0';
            @endphp
            <nav class="flex-1 px-4 space-y-1 mt-4">
                <a href="{{ url('admin') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin') || request_is('admin/') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-chart-line w-5"></i> Dashboard
                </a>
                @if(($_SESSION['user_role'] ?? '') === 'admin')
                    <a href="{{ url('admin/modules') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/modules*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                        <i class="fa-solid fa-puzzle-piece w-5"></i> Módulos
                    </a>
                @endif
                @if($__linksEnabled)
                <a href="{{ url('admin/links') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/links*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-link w-5"></i> Enlaces
                </a>
                @endif
                @if($__locationsEnabled)
                <a href="{{ url('admin/locations') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/locations*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-map-location-dot w-5"></i> Ubicaciones
                </a>
                @endif
                @if(($_SESSION['user_role'] ?? '') === 'admin')
                    <a href="{{ url('admin/users') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/users*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                        <i class="fa-solid fa-users w-5"></i> Usuarios
                    </a>
                @endif
                <a href="{{ url('admin/settings') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/settings*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-gears w-5"></i> Ajustes
                </a>
                <a href="{{ url('admin/seo') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/seo*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-magnifying-glass w-5"></i> SEO
                </a>
                <a href="{{ url('admin/favicon') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/favicon*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-icons w-5"></i> Favicons
                </a>
                @if($__menuEnabled)
                <a href="{{ url($__menuConfigured === '1' ? 'admin/menu/items' : 'admin/menu/settings') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/menu*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-utensils w-5"></i> Menú
                    @if($__menuConfigured !== '1')
                        <span class="ml-auto text-[9px] bg-slate-700 text-slate-500 px-1.5 py-0.5 rounded uppercase tracking-wide">Off</span>
                    @endif
                </a>
                @endif
                @if($__siteManagerEnabled && ($_SESSION['user_role'] ?? '') === 'admin')
                <a href="{{ url('admin/sites') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ request_is('admin/sites*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/50 text-slate-400' }}">
                    <i class="fa-solid fa-server w-5"></i> Sitios
                </a>
                @endif
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
                    <form action="{{ url('admin/logout') }}" method="POST" data-no-loading>
                        <button type="submit" class="text-slate-500 hover:text-red-400 transition-colors">
                            <i class="fa-solid fa-power-off text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 p-4 sm:p-6 lg:p-10">
            <header class="flex flex-wrap items-center justify-between gap-3 mb-6 md:mb-10">
                <div class="flex items-center gap-3 min-w-0">
                    <!-- Hamburger (mobile only) -->
                    <button id="menu-btn"
                            class="md:hidden p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white transition-colors flex-shrink-0">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-xl md:text-3xl font-bold text-white truncate">@yield('header')</h2>
                        <p class="text-slate-500 mt-0.5 text-sm hidden sm:block">@yield('subheader')</p>
                    </div>
                </div>
                <div class="flex gap-3 flex-shrink-0">
                    <a href="{{ url() }}" target="_blank" class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-xl transition-all flex items-center gap-2 text-sm border border-slate-700">
                        <i class="fa-solid fa-eye text-xs"></i> <span class="hidden sm:inline">Ver Sitio</span>
                    </a>
                </div>
            </header>

            @php
                $__siteSuspended = $_REQUEST['_site_suspended'] ?? false;
                // Verificar si el sitio está suspendido (módulo site_client)
                try {
                    $__siteStatus = \Illuminate\Database\Capsule\Manager::table('settings')
                        ->where('setting_key', 'site_status')->value('setting_value');
                    $__siteSuspended = $__siteStatus === 'suspended';
                } catch (\Exception $e) {}
            @endphp
            @if($__siteSuspended)
            <div class="bg-red-600 text-white p-4 text-center font-bold rounded-xl mb-6">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                Este sitio está suspendido. Contacte al administrador para renovar su plan.
            </div>
            @endif

            <div class="space-y-8">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
    <script>
        // Botón de loading al guardar formularios
        document.querySelectorAll('form:not([data-no-loading])').forEach(function(form) {
            form.addEventListener('submit', function() {
                var btn = this.querySelector('[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-2"></i>Guardando...';
                }
            });
        });

        // Sidebar toggle (mobile)
        (function() {
            var sidebar  = document.getElementById('sidebar');
            var backdrop = document.getElementById('sidebar-backdrop');
            var menuBtn  = document.getElementById('menu-btn');
            function openSidebar()  {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
                document.body.style.overflow = '';
            }
            if (menuBtn)  menuBtn.addEventListener('click', function() {
                sidebar.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
            });
            if (backdrop) backdrop.addEventListener('click', closeSidebar);
            // Cerrar al navegar en mobile
            sidebar.querySelectorAll('a').forEach(function(a) {
                a.addEventListener('click', function() { if (window.innerWidth < 768) closeSidebar(); });
            });
        })();
    </script>
</body>
</html>
