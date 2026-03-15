<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings['landing_title'] ?? 'Landing' }} | {{ $settings['site_name'] ?? 'Digital Profile' }}</title>
    
    <!-- SEO -->
    <meta name="description" content="{{ $settings['seo_description'] ?? 'Mi perfil digital y enlaces importantes.' }}">
    <meta name="keywords" content="{{ $settings['seo_keywords'] ?? 'perfil, enlaces, bio' }}">
    <meta name="author" content="{{ $settings['seo_author'] ?? $settings['site_name'] ?? 'Landing' }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Utilities -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --accent: {{ $settings['landing_accent_color'] ?? '#fec771' }};
            --bg-base: {{ $settings['landing_bg_color'] ?? '#020617' }};
            --text: {{ $settings['landing_text_color'] ?? '#ffffff' }};
            --overlay: {{ $settings['landing_bg_overlay'] ?? '#000000' }};
            --opacity: {{ ($settings['landing_bg_overlay_opacity'] ?? 50) / 100 }};
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-base);
            color: var(--text);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .main-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            @if(!empty($settings['landing_bg_image_url']))
                background-image: url('{{ $settings['landing_bg_image_url'] }}');
                background-size: cover;
                background-position: center;
                background-attachment: fixed;
            @endif
        }
        .background-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--overlay);
            opacity: var(--opacity);
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .link-hover:hover {
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
        }
        .accent-text { color: var(--accent); }
        .accent-bg { background-color: var(--accent); }
        .accent-border { border-color: var(--accent); }
        /* Estilos para contenido rich text de la bio */
        .bio-content ul { list-style: disc; padding-left: 1.5rem; text-align: left; }
        .bio-content ol { list-style: decimal; padding-left: 1.5rem; text-align: left; }
        .bio-content strong { font-weight: 700; }
        .bio-content em { font-style: italic; }
        .bio-content a { color: var(--accent); text-decoration: underline; }
        .bio-content p { margin-bottom: 0.5rem; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center py-20 px-6 overflow-x-hidden relative">
    <!-- Background System -->
    <div class="main-background">
        <div class="background-overlay"></div>
    </div>

    <!-- Decorative gradients -->
    <div class="fixed top-[-10%] right-[-10%] w-[400px] h-[400px] rounded-full opacity-20 blur-[100px]" style="background: var(--accent);"></div>
    <div class="fixed bottom-[-10%] left-[-10%] w-[300px] h-[300px] rounded-full opacity-10 blur-[80px]" style="background: var(--accent);"></div>

    <div class="max-w-md w-full flex flex-col items-center relative z-10">
        <!-- Logo Section -->
        @if(!empty($settings['landing_logo_url']))
            <div class="mb-8 flex justify-center">
                <img src="{{ $settings['landing_logo_url'] }}" alt="{{ $settings['site_name'] ?? 'Logo' }}" class="max-h-16 w-auto object-contain">
            </div>
        @endif

        <!-- Profile Section: solo mostrar si no hay logo -->
        @if(empty($settings['landing_logo_url']))
        <div class="relative mb-8 group">
            <div class="absolute -inset-1 opacity-20 rounded-full blur transition duration-1000 group-hover:opacity-40" style="background: var(--accent);"></div>
            <div class="relative w-28 h-28 rounded-full bg-slate-900/50 border-2 border-white/10 flex items-center justify-center overflow-hidden shadow-2xl glass">
                @if(!empty($settings['landing_avatar_url']))
                    <img src="{{ $settings['landing_avatar_url'] }}" alt="Avatar" class="w-full h-full object-cover">
                @else
                    <i class="fa-solid fa-user text-5xl opacity-20"></i>
                @endif
            </div>
        </div>
        @endif

        <h1 class="text-4xl font-black mb-2 tracking-tighter text-center">
            {{ $settings['landing_title'] ?? 'My Profile' }}
        </h1>
        <p class="text-white/60 font-medium mb-12 text-center tracking-tight text-lg">{{ $settings['landing_subtitle'] ?? 'Digital Creator' }}</p>

        <!-- Links Section -->
        <div class="w-full flex flex-col space-y-5">
            @forelse($links as $link)
                <a href="{{ $link->url }}" 
                   class="glass link-hover p-4 px-6 rounded-2xl flex items-center transition-all duration-300 group"
                   target="_blank">
                    <div class="w-10 h-10 flex items-center justify-center text-xl accent-text group-hover:scale-110 transition-transform">
                        {!! $link->getIconHtml() !!}
                    </div>
                    <span class="flex-1 font-semibold text-lg ml-3">{{ $link->title }}</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-white/20 text-xs group-hover:text-accent group-hover:opacity-100 transition-all"></i>
                </a>
            @empty
                <div class="glass p-12 rounded-3xl text-center border-dashed border-white/10">
                    <i class="fa-solid fa-link-slash text-white/20 text-4xl mb-4 block"></i>
                    <p class="text-white/40 text-base">No links available yet.</p>
                </div>
            @endforelse
        </div>

        <!-- Bio Section -->
        @if(!empty($settings['landing_bio']))
            <div class="mt-14 text-center px-4">
                <div class="bio-content text-white/50 text-base leading-relaxed max-w-sm mx-auto font-light">
                    {!! $settings['landing_bio'] !!}
                </div>
            </div>
        @endif

        <!-- Ubicación Google Maps -->
        @if(!empty($settings['landing_maps_url']) && ($settings['landing_maps_mode'] ?? 'none') !== 'none')
            @if($settings['landing_maps_mode'] === 'button')
                <a href="{{ $settings['landing_maps_url'] }}" target="_blank" rel="noopener noreferrer"
                   class="glass link-hover mt-6 p-4 px-6 rounded-2xl flex items-center transition-all duration-300 group w-full">
                    <div class="w-10 h-10 flex items-center justify-center text-xl accent-text group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <span class="flex-1 font-semibold text-lg ml-3">Ver ubicación</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-white/20 text-xs group-hover:text-accent group-hover:opacity-100 transition-all"></i>
                </a>
            @elseif($settings['landing_maps_mode'] === 'embed')
                <div class="glass rounded-2xl overflow-hidden w-full mt-6">
                    <iframe src="{{ $settings['landing_maps_url'] }}" width="100%" height="300"
                            style="border:0;" allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            @endif
        @endif

        <!-- Footer -->
        <footer class="mt-24 pt-8 border-t border-white/5 w-full text-center">
            <p class="text-[11px] text-white/20 uppercase tracking-[0.2em] font-bold">
                Created with <span class="accent-text">♥</span> by {{ $settings['site_name'] ?? 'Landing' }}
            </p>
        </footer>
    </div>
</body>
</html>
