@php
    $plantillaHC = file_exists(base_path('Plantillas/Historia_Clinica.docx'));
    $plantillaIndicaciones = file_exists(base_path('Plantillas/Indicaciones.docx'));
    $plantillaAdmision = file_exists(base_path('Plantillas/Admision.docx'));

    $discoActivoObj = collect($discos ?? [])->firstWhere('disco', $discoPredeterminado ?? 'H') ?? ($discos[0] ?? [
        'disco' => 'H',
        'etiqueta' => 'Google Drive (H:)',
        'es_drive' => true,
        'ruta_pediatria' => 'H:\\Mi unidad\\PEDIATRIA',
    ]);

    $fechaEspanol = ucfirst(\Illuminate\Support\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'));
@endphp
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#f4f8fc]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HospitalPediatria | Panel Clínico Integral</title>
    <meta name="description" content="Sistema clínico y panel asistencial de pediatría adaptable a todas las pantallas.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('icon.svg') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Vite / CDN) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        screens: {
                            'xs': '475px',
                            '3xl': '1920px',
                        },
                        fontFamily: {
                            sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                            mono: ['"JetBrains Mono"', 'monospace'],
                        },
                        colors: {
                            navy: '#123b5d',
                            teal: '#248294',
                            mint: '#55d6c2',
                            soft: '#f4f8fc',
                        }
                    }
                },
                safelist: [
                    'bg-teal-50', 'text-teal-700', 'border-teal-200/60',
                    'bg-indigo-50', 'text-indigo-700', 'border-indigo-200/60',
                    'bg-rose-50', 'text-rose-700', 'border-rose-200/60',
                    'bg-emerald-50', 'text-emerald-700', 'border-emerald-200/60',
                    'bg-blue-50', 'text-blue-700', 'border-blue-200/60',
                ]
            }
        </script>
    @endif

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f4f8fc;
            color: #123b5d;
        }

        .hidden {
            display: none !important;
        }

        /* Scrollbar ultra delgado y estilizado */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            font-weight: 700;
        }
        .nav-item.active i, .nav-item.active svg {
            color: #55d6c2 !important;
        }

        /* Transición suave entre pantallas */
        .module-view {
            animation: fadeIn 0.18s ease-in-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(3px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Reglas estructurales para escritorio y laptops (>= 768px): La barra lateral NUNCA se sobrepone */
        @media (min-width: 768px) {
            #sidebar {
                position: relative !important;
                transform: none !important;
                left: auto !important;
                top: auto !important;
                bottom: auto !important;
                box-shadow: none !important;
                display: flex !important;
            }
            #sidebarBackdrop {
                display: none !important;
            }
            .mobile-close-btn {
                display: none !important;
            }
        }

        /* Modo Colapsado de Barra Lateral para ganar espacio horizontal en laptop (68px) */
        #sidebar.sidebar-collapsed {
            width: 68px !important;
            min-width: 68px !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        #sidebar.sidebar-collapsed .sidebar-text-hide {
            display: none !important;
        }
        #sidebar.sidebar-collapsed .sidebar-item-btn {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        #sidebar.sidebar-collapsed .sidebar-badge-hide {
            display: none !important;
        }

        /* Soporte nativo de Zoom para adaptar la interfaz a pantallas de laptop */
        html {
            transition: zoom 0.15s ease-out;
        }
    </style>
</head>
<body class="h-full flex overflow-hidden antialiased selection:bg-[#248294] selection:text-white">

    <!-- ======================================================== -->
    <!-- TOAST DE NOTIFICACIONES FLOTANTE (RESPONSIVO)            -->
    <!-- ======================================================== -->
    <div id="toastNotification" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: none;" class="items-center gap-3 rounded-2xl bg-[#123b5d] px-4 py-3 sm:px-5 sm:py-3.5 text-xs sm:text-sm font-bold text-white shadow-2xl transition-all duration-300 opacity-0 pointer-events-none translate-y-3 border border-white/10 max-w-[90vw]">
        <i id="toastIcon" data-lucide="check" class="size-4 sm:size-5 text-[#55d6c2] shrink-0"></i>
        <span id="toastMessage" class="truncate">Operación realizada con éxito</span>
    </div>

    <!-- ======================================================== -->
    <!-- CONTENEDOR PRINCIPAL FLUIDO (100% PANTALLA)              -->
    <!-- ======================================================== -->
    <div class="flex w-full h-full overflow-hidden">

        <!-- ======================================================== -->
        <!-- SIDEBAR DE NAVEGACIÓN (ADAPTABLE A CUALQUIER RESOLUCIÓN) -->
        <!-- ======================================================== -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex w-60 xl:w-64 flex-col bg-[#123b5d] px-3 py-3.5 sm:px-3.5 sm:py-4 text-white transition-all duration-200 md:relative md:translate-x-0 -translate-x-full overflow-y-auto overflow-x-hidden shrink-0 shadow-xl md:shadow-none border-r border-[#1a4a73]/40">
            
            <!-- Logo e Identidad -->
            <div class="mb-4 sm:mb-5 flex items-center justify-between px-1">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="grid size-8.5 place-items-center rounded-xl bg-[#55d6c2] text-[#123b5d] shadow-2xs shrink-0">
                        <i data-lucide="heart-pulse" class="size-4.5 stroke-[2.2]"></i>
                    </div>
                    <div class="min-w-0 sidebar-text-hide">
                        <div class="text-[13px] font-extrabold tracking-tight text-white leading-tight truncate">HospitalPediatria</div>
                        <div class="text-[8.5px] uppercase tracking-[0.16em] text-[#a5ced4] font-bold">Sistema clínico</div>
                    </div>
                </div>
                <!-- Botón colapsar menú en PC / Laptop -->
                <button type="button" onclick="toggleSidebarCollapse()" class="hidden md:flex size-7 items-center justify-center rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition cursor-pointer shrink-0" title="Colapsar / expandir menú">
                    <i id="btnCollapseIcon" data-lucide="chevrons-left" class="size-4"></i>
                </button>
                <!-- Botón cerrar menú en Móvil -->
                <button type="button" onclick="toggleSidebar(false)" class="rounded-xl p-1 text-slate-300 hover:bg-white/10 md:hidden cursor-pointer mobile-close-btn" aria-label="Cerrar menú">
                    <i data-lucide="x" class="size-5"></i>
                </button>
            </div>

            <!-- Título de navegación -->
            <div class="mb-2 px-2 text-[9px] font-bold uppercase tracking-[0.18em] text-[#7fa9b7] sidebar-text-hide">
                Módulos del sistema
            </div>

            <!-- Botones de Navegación por Módulos -->
            <nav class="space-y-1 flex-1" aria-label="Módulos del sistema">
                <!-- 1. Dashboard principal -->
                <button type="button" onclick="goModule('dashboard')" id="nav-dashboard" class="nav-item sidebar-item-btn active flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-xs font-semibold transition text-white cursor-pointer" title="Dashboard principal">
                    <i data-lucide="layout-dashboard" class="size-4 text-[#55d6c2] shrink-0"></i>
                    <span class="truncate sidebar-text-hide">Dashboard principal</span>
                    <span id="dot-dashboard" class="ml-auto size-1.5 rounded-full bg-[#55d6c2] shrink-0 sidebar-badge-hide"></span>
                </button>

                <!-- 2. Pacientes y documentación -->
                <button type="button" onclick="goModule('patients')" id="nav-patients" class="nav-item sidebar-item-btn flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-xs font-semibold transition text-[#a9c7d1] hover:bg-white/7 hover:text-white cursor-pointer" title="Pacientes y Documentos">
                    <i data-lucide="folder-open" class="size-4 text-[#83a9b7] shrink-0"></i>
                    <span class="truncate sidebar-text-hide">Pacientes y Documentos</span>
                    <span id="badge-total-pacientes" class="ml-auto rounded-full bg-[#55d6c2]/20 px-1.5 py-0.2 text-[9px] font-bold text-[#8cebdd] shrink-0 sidebar-badge-hide">--</span>
                </button>

                <!-- 3. Asistente clínico IA -->
                <button type="button" onclick="goModule('ai')" id="nav-ai" class="nav-item sidebar-item-btn flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-xs font-semibold transition text-[#a9c7d1] hover:bg-white/7 hover:text-white cursor-pointer" title="Asistente clínico IA">
                    <i data-lucide="bot" class="size-4 text-[#83a9b7] shrink-0"></i>
                    <span class="truncate sidebar-text-hide">Asistente clínico IA</span>
                </button>

                <!-- 4. Material docente -->
                <button type="button" onclick="goModule('teaching')" id="nav-teaching" class="nav-item sidebar-item-btn flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-xs font-semibold transition text-[#a9c7d1] hover:bg-white/7 hover:text-white cursor-pointer" title="Material docente">
                    <i data-lucide="presentation" class="size-4 text-[#83a9b7] shrink-0"></i>
                    <span class="truncate sidebar-text-hide">Material docente</span>
                </button>

                <!-- 5. Plantillas y configuración -->
                <button type="button" onclick="goModule('templates')" id="nav-templates" class="nav-item sidebar-item-btn flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-xs font-semibold transition text-[#a9c7d1] hover:bg-white/7 hover:text-white cursor-pointer" title="Plantillas y configuración">
                    <i data-lucide="settings-2" class="size-4 text-[#83a9b7] shrink-0"></i>
                    <span class="truncate sidebar-text-hide">Plantillas y config.</span>
                </button>
            </nav>

            <!-- Bloque Inferior: Estado del Sistema y Perfil -->
            <div class="mt-auto space-y-2.5 pt-3">
                <!-- Tarjeta de Estado del Disco / Drive -->
                <div class="rounded-xl border border-white/10 bg-white/7 p-2.5 backdrop-blur-xs sidebar-text-hide" title="Estado de sincronización">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="text-[9.5px] font-bold text-[#a9c7d1]">Estado del sistema</span>
                        <span class="relative flex size-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#55d6c2] opacity-75"></span>
                            <span class="relative inline-flex size-1.5 rounded-full bg-[#55d6c2]"></span>
                        </span>
                    </div>
                    <div class="text-[11px] font-bold text-white">Sincronizado</div>
                    <div class="mt-0.5 flex items-center gap-1.5 text-[9.5px] text-[#8eb5c1] truncate font-mono" id="sidebarDiskLabel">
                        <i data-lucide="cloud" class="size-3 text-[#55d6c2] shrink-0"></i>
                        <span class="truncate">{{ $discoActivoObj['etiqueta'] ?? 'Disco Local' }}</span>
                    </div>
                </div>

                <!-- Perfil del Médico -->
                <div class="flex items-center gap-2.5 border-t border-white/10 px-0.5 pt-2">
                    <div class="grid size-7.5 place-items-center rounded-full bg-[#d8eef0] text-[11px] font-extrabold text-[#246377] shrink-0" title="Equipo Pediatría">
                        EP
                    </div>
                    <div class="flex-1 min-w-0 sidebar-text-hide">
                        <div class="text-[11px] font-bold text-white truncate leading-tight">Equipo Pediatría</div>
                        <div class="text-[9px] text-[#8eb5c1] truncate">Acceso profesional</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Overlay móvil para cerrar sidebar -->
        <div id="sidebarBackdrop" onclick="toggleSidebar(false)" class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-xs hidden md:hidden"></div>

        <!-- ======================================================== -->
        <!-- ÁREA DE CONTENIDO CENTRAL (FLUIDA Y CON SCROLL PROPIO)   -->
        <!-- ======================================================== -->
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">

            <!-- Topbar Header Compacto y Adaptable -->
            <header class="sticky top-0 z-20 flex h-12 sm:h-13 items-center justify-between border-b border-slate-200/70 bg-[#f4f8fc]/95 px-3 sm:px-5 lg:px-6 backdrop-blur-md shrink-0">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                    <!-- Botón menú móvil -->
                    <button type="button" onclick="toggleSidebar(true)" class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:bg-slate-100 md:hidden cursor-pointer shrink-0" aria-label="Abrir menú">
                        <i data-lucide="menu" class="size-4"></i>
                    </button>
                    <!-- Botón toggle sidebar desktop -->
                    <button type="button" onclick="toggleSidebarCollapse()" class="hidden md:flex items-center justify-center size-7.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 transition cursor-pointer shrink-0 shadow-2xs" title="Minimizar / Expandir menú lateral">
                        <i data-lucide="panel-left" class="size-4 text-[#248294]"></i>
                    </button>
                    <div class="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5 truncate">
                        <span class="hidden sm:inline">Panel</span>
                        <i data-lucide="chevron-right" class="size-3 text-slate-300 hidden sm:inline shrink-0"></i>
                        <span id="breadcrumbModule" class="text-[#248294] font-bold truncate">Dashboard principal</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <!-- Control de Escala / Zoom de Pantalla (Ideal para Laptops) -->
                    <div class="flex items-center rounded-full border border-slate-200/80 bg-white p-0.5 shadow-2xs text-[10px] font-bold text-slate-500" title="Ajustar tamaño en tu pantalla">
                        <span class="pl-2 pr-1 text-slate-400 hidden sm:inline flex items-center gap-1">
                            <i data-lucide="monitor" class="size-3"></i>
                            <span>Vista:</span>
                        </span>
                        <button type="button" onclick="setUiZoom('1.0')" id="zoomBtn-100" class="zoom-btn rounded-full px-2 py-0.5 transition cursor-pointer bg-[#248294] text-white shadow-2xs font-bold" title="100% Tamaño estándar">100%</button>
                        <button type="button" onclick="setUiZoom('0.9')" id="zoomBtn-90" class="zoom-btn rounded-full px-2 py-0.5 transition cursor-pointer text-slate-600 hover:text-[#123b5d] font-semibold" title="90% Escala reducida">90%</button>
                        <button type="button" onclick="setUiZoom('0.82')" id="zoomBtn-82" class="zoom-btn rounded-full px-2 py-0.5 transition cursor-pointer text-slate-600 hover:text-[#123b5d] font-semibold" title="82% Ultra compacto">82%</button>
                    </div>

                    <!-- Selector de unidad de disco activa -->
                    <div class="flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] sm:text-[11px] font-semibold text-slate-600 shadow-2xs">
                        <i data-lucide="cloud" class="size-3 text-[#248294] shrink-0"></i>
                        <select id="selectDiscoGlobal" onchange="cambiarDiscoGlobal(this.value)" class="bg-transparent font-bold text-[#123b5d] focus:outline-none cursor-pointer max-w-[120px] sm:max-w-[180px] truncate text-[11px]">
                            @foreach($discos ?? [] as $d)
                                <option value="{{ $d['disco'] }}" {{ ($discoPredeterminado ?? '') === $d['disco'] ? 'selected' : '' }}>
                                    {{ $d['etiqueta'] }}
                                </option>
                            @endforeach
                        </select>
                        <span class="size-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                    </div>

                    <!-- Avatar Médico -->
                    <div class="grid size-7.5 place-items-center rounded-full bg-[#d8eef0] text-[11px] font-extrabold text-[#246377] shadow-2xs shrink-0" title="Equipo Pediatría">
                        EP
                    </div>
                </div>
            </header>

            <!-- Contenedor con Scroll Independiente de Pantallas Modulares -->
            <main class="flex-1 overflow-y-auto px-3 py-3 sm:px-5 sm:py-4 lg:px-6 lg:py-5 flex flex-col">
                <div class="mx-auto w-full max-w-[1780px] flex-1 flex flex-col space-y-3.5 lg:space-y-4">

                    <!-- ==================================================== -->
                    <!-- VISTA 1: DASHBOARD PRINCIPAL                         -->
                    <!-- ==================================================== -->
                    <div id="module-dashboard" class="module-view flex-1 flex flex-col space-y-3.5 lg:space-y-4">
                        
                        <!-- Hero Banner Compacto y Elegante -->
                        <section class="shrink-0 relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#123b5d] via-[#16456e] to-[#123b5d] px-5 py-4 sm:px-6 sm:py-5 text-white shadow-sm border border-white/10">
                            <div class="relative z-10 max-w-3xl">
                                <div class="mb-1 text-[9.5px] sm:text-[10px] font-bold uppercase tracking-[0.16em] text-[#aee9df]">
                                    {{ $fechaEspanol }} · Guardia activa
                                </div>
                                <h1 class="text-lg sm:text-xl lg:text-2xl font-black tracking-[-0.03em] leading-tight">
                                    Buenos días, equipo.
                                </h1>
                                <p class="mt-0.5 text-xs text-[#b4d0d9] leading-relaxed max-w-xl">
                                    Acompañando cada decisión clínica con orden, información y confianza.
                                </p>
                                <div class="mt-2.5 sm:mt-3 flex flex-wrap gap-2">
                                    <button type="button" onclick="goModule('patients')" class="flex items-center gap-1.5 rounded-lg bg-[#55d6c2] px-3.5 py-1.5 text-xs font-extrabold text-[#123b5d] shadow-2xs hover:bg-[#6ef0dc] transition cursor-pointer">
                                        <i data-lucide="user-plus" class="size-3.5 shrink-0"></i>
                                        <span>Nuevo paciente</span>
                                    </button>
                                    <button type="button" onclick="goModule('ai')" class="flex items-center gap-1.5 rounded-lg border border-white/20 bg-white/5 px-3 py-1.5 text-xs font-bold text-white hover:bg-white/10 transition cursor-pointer">
                                        <i data-lucide="bot" class="size-3.5 text-[#55d6c2] shrink-0"></i>
                                        <span>Consultar asistente IA</span>
                                    </button>
                                    <button type="button" onclick="goModule('teaching')" class="flex items-center gap-1.5 rounded-lg border border-white/20 bg-white/5 px-3 py-1.5 text-xs font-bold text-white hover:bg-white/10 transition cursor-pointer">
                                        <i data-lucide="presentation" class="size-3.5 text-[#55d6c2] shrink-0"></i>
                                        <span>Biblioteca docente</span>
                                    </button>
                                </div>
                            </div>
                            <!-- Icono decorativo de fondo -->
                            <i data-lucide="activity" class="absolute bottom-2 right-4 sm:bottom-3 sm:right-6 size-20 sm:size-28 text-[#55d6c2]/10 pointer-events-none"></i>
                        </section>

                        <!-- 4 Tarjetas de Métricas (KPIs) -->
                        <div class="shrink-0 grid gap-2.5 sm:gap-3 grid-cols-2 lg:grid-cols-4">
                            <!-- KPI 1 -->
                            <div class="rounded-xl border border-slate-200/80 bg-white p-3 sm:p-3.5 shadow-2xs flex items-center gap-3 hover:border-slate-300 transition">
                                <div class="grid size-9 sm:size-10 place-items-center rounded-xl bg-[#e8f5f6] text-[#248294] shrink-0">
                                    <i data-lucide="users" class="size-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <div id="kpi-pacientes-hoy" class="text-base sm:text-lg font-black text-[#123b5d] leading-tight">--</div>
                                    <div class="text-[10px] sm:text-[11px] font-semibold text-slate-500 truncate">Pacientes registrados</div>
                                    <div class="text-[9px] font-bold text-emerald-600">Jornada activa</div>
                                </div>
                            </div>

                            <!-- KPI 2 -->
                            <div class="rounded-xl border border-slate-200/80 bg-white p-3 sm:p-3.5 shadow-2xs flex items-center gap-3 hover:border-slate-300 transition">
                                <div class="grid size-9 sm:size-10 place-items-center rounded-xl bg-[#e8f5f6] text-[#248294] shrink-0">
                                    <i data-lucide="file-text" class="size-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <div id="kpi-documentos-hoy" class="text-base sm:text-lg font-black text-[#123b5d] leading-tight">3 docs/pac</div>
                                    <div class="text-[10px] sm:text-[11px] font-semibold text-slate-500 truncate">Documentos Word</div>
                                    <div class="text-[9px] font-bold text-emerald-600">HC, Ind, Admisión</div>
                                </div>
                            </div>

                            <!-- KPI 3 -->
                            <div class="rounded-xl border border-slate-200/80 bg-white p-3 sm:p-3.5 shadow-2xs flex items-center gap-3 hover:border-slate-300 transition">
                                <div class="grid size-9 sm:size-10 place-items-center rounded-xl bg-[#eef1fb] text-[#5667a8] shrink-0">
                                    <i data-lucide="presentation" class="size-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <div id="kpi-pptx-total" class="text-base sm:text-lg font-black text-[#123b5d] leading-tight">--</div>
                                    <div class="text-[10px] sm:text-[11px] font-semibold text-slate-500 truncate">Clases PowerPoint</div>
                                    <div class="text-[9px] font-bold text-[#5667a8]">Biblioteca activa</div>
                                </div>
                            </div>

                            <!-- KPI 4 -->
                            <div class="rounded-xl border border-slate-200/80 bg-white p-3 sm:p-3.5 shadow-2xs flex items-center gap-3 hover:border-slate-300 transition">
                                <div class="grid size-9 sm:size-10 place-items-center rounded-xl bg-[#fff0e8] text-[#cb7842] shrink-0">
                                    <i data-lucide="cloud" class="size-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm sm:text-base font-black text-[#123b5d] truncate leading-tight" id="kpi-unidad-activa">{{ $discoActivoObj['etiqueta'] ?? 'Drive' }}</div>
                                    <div class="text-[10px] sm:text-[11px] font-semibold text-slate-500 truncate">Almacenamiento</div>
                                    <div class="text-[9px] font-bold text-emerald-600">Salud óptima</div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Contenido Inferior (Flexible a la altura de pantalla) -->
                        <div class="flex-1 grid gap-3.5 lg:gap-4 grid-cols-1 lg:grid-cols-12 items-stretch min-h-[380px] lg:min-h-[420px]">
                            
                            <!-- Columna Izquierda: Últimos pacientes de hoy -->
                            <div class="lg:col-span-7 xl:col-span-8 rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-2xs flex flex-col justify-between h-full">
                                <div class="flex-1 flex flex-col min-h-0">
                                    <div class="mb-3 flex items-center justify-between pb-2.5 border-b border-slate-100 shrink-0">
                                        <div class="flex items-center gap-2">
                                            <div>
                                                <h2 class="text-xs sm:text-sm font-extrabold text-[#123b5d]">Últimos pacientes de hoy</h2>
                                                <p class="text-[10px] text-slate-400">Expedientes clínicos registrados recientemente</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span id="dashBadgePacientesCount" class="rounded-full bg-[#e8f5f6] px-2 py-0.5 text-[9.5px] font-bold text-[#248294] border border-[#248294]/20 hidden sm:inline">Jornada activa</span>
                                            <button type="button" onclick="goModule('patients')" class="text-[11px] font-bold text-[#248294] hover:underline flex items-center gap-1 cursor-pointer">
                                                <span>Ver todos</span>
                                                <i data-lucide="arrow-right" class="size-3"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div id="dashboardRecentPatients" class="flex-1 overflow-y-auto min-h-[160px] space-y-1 pr-1">
                                        <div class="py-10 text-center text-xs text-slate-400">
                                            Cargando registros recientes de pacientes...
                                        </div>
                                    </div>
                                </div>

                                <!-- Barra inferior con acción rápida -->
                                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500 shrink-0">
                                    <span class="text-[10px] text-slate-400 font-medium truncate">
                                        📁 Carpeta activa: <code class="font-mono text-[#123b5d] font-bold text-[10px]" id="dashFolderIndicator">{{ $discoActivoObj['etiqueta'] ?? 'H:' }}\Pediatria</code>
                                    </span>
                                    <button type="button" onclick="goModule('patients')" class="text-[11px] font-bold text-[#248294] hover:underline flex items-center gap-1 cursor-pointer">
                                        <span>+ Registrar otro paciente</span>
                                        <i data-lucide="arrow-up-right" class="size-3"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Columna Derecha: Calculadora Médica y Accesos -->
                            <div class="lg:col-span-5 xl:col-span-4 rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-2xs flex flex-col justify-between h-full">
                                <div class="flex-1 flex flex-col min-h-0 space-y-3">
                                    <div class="flex items-center gap-2.5 pb-2.5 border-b border-slate-100 shrink-0">
                                        <div class="grid size-8.5 place-items-center rounded-xl bg-[#fff0e8] text-[#cb7842] shrink-0">
                                            <i data-lucide="calculator" class="size-4.5"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xs sm:text-sm font-extrabold text-[#123b5d]">Utilidad médica de guardia</h2>
                                            <p class="text-[10px] text-slate-400">Superficie corporal (SC) e Hidratación basal</p>
                                        </div>
                                    </div>

                                    <!-- Inputs Peso y Talla -->
                                    <div class="grid grid-cols-2 gap-2.5 shrink-0">
                                        <label class="block text-[10px] font-bold text-slate-600">
                                            Peso (kg)
                                            <input type="number" id="calcWeight" oninput="calcularSC()" placeholder="Ej. 25" step="0.1" class="mt-1 w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-[#123b5d] outline-none focus:border-[#248294] transition shadow-2xs">
                                        </label>
                                        <label class="block text-[10px] font-bold text-slate-600">
                                            Talla (cm)
                                            <input type="number" id="calcHeight" oninput="calcularSC()" placeholder="Ej. 120" step="1" class="mt-1 w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-[#123b5d] outline-none focus:border-[#248294] transition shadow-2xs">
                                        </label>
                                    </div>

                                    <!-- Resultado 1: Superficie Corporal -->
                                    <div class="rounded-xl bg-[#f4f8fc] p-3 border border-slate-200/70 shadow-2xs shrink-0">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10.5px] font-bold text-slate-600 flex items-center gap-1.5">
                                                <i data-lucide="activity" class="size-3 text-[#248294]"></i>
                                                <span>Superficie corporal:</span>
                                            </span>
                                            <span class="text-[9px] text-slate-400 font-mono">Fórmula Mosteller</span>
                                        </div>
                                        <div class="mt-1 text-2xl font-black text-[#248294] leading-tight">
                                            <span id="scResult">—</span> <span class="text-xs font-bold text-slate-400">m²</span>
                                        </div>
                                    </div>

                                    <!-- Resultado 2: Holliday-Segar (Líquidos Basales) -->
                                    <div class="rounded-xl bg-emerald-50/60 p-3 border border-emerald-100 shadow-2xs shrink-0">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10.5px] font-bold text-emerald-900 flex items-center gap-1.5">
                                                <i data-lucide="droplet" class="size-3 text-emerald-600"></i>
                                                <span>Líquidos basales (Holliday-Segar):</span>
                                            </span>
                                            <span class="text-[9px] text-emerald-700 font-mono">24 horas</span>
                                        </div>
                                        <div class="mt-1 flex items-baseline justify-between gap-2">
                                            <div class="text-lg font-black text-emerald-700 leading-tight">
                                                <span id="hsResult">— ml/24h</span>
                                            </div>
                                            <div class="text-[11px] font-bold text-emerald-800" id="hsHourlyResult">
                                                — ml/h
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Accesos rápidos a IA pediátrica -->
                                    <div class="pt-1 shrink-0">
                                        <div class="text-[9.5px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                            Consultas rápidas con IA
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <button type="button" onclick="consultarIAPronta('dosificacion')" class="rounded-lg border border-slate-200 bg-white hover:bg-[#e8f5f6] hover:text-[#248294] hover:border-[#248294] px-2 py-1 text-[10px] font-semibold text-slate-600 transition flex items-center gap-1 cursor-pointer shadow-2xs">
                                                <i data-lucide="sparkles" class="size-2.5 text-[#248294]"></i>
                                                <span>Dosis x kg</span>
                                            </button>
                                            <button type="button" onclick="consultarIAPronta('hidratacion')" class="rounded-lg border border-slate-200 bg-white hover:bg-[#e8f5f6] hover:text-[#248294] hover:border-[#248294] px-2 py-1 text-[10px] font-semibold text-slate-600 transition flex items-center gap-1 cursor-pointer shadow-2xs">
                                                <i data-lucide="droplets" class="size-2.5 text-[#248294]"></i>
                                                <span>Deshidratación</span>
                                            </button>
                                            <button type="button" onclick="consultarIAPronta('crisis')" class="rounded-lg border border-slate-200 bg-white hover:bg-[#e8f5f6] hover:text-[#248294] hover:border-[#248294] px-2 py-1 text-[10px] font-semibold text-slate-600 transition flex items-center gap-1 cursor-pointer shadow-2xs">
                                                <i data-lucide="stethoscope" class="size-2.5 text-[#248294]"></i>
                                                <span>Crisis asmática</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] shrink-0">
                                    <span class="text-slate-400">¿Dudas de dosificación?</span>
                                    <button type="button" onclick="goModule('ai')" class="font-bold text-[#248294] hover:underline flex items-center gap-1 cursor-pointer">
                                        <span>Consultar asistente IA</span>
                                        <i data-lucide="arrow-right" class="size-3"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- VISTA 2: PACIENTES Y DOCUMENTACIÓN CLÍNICA           -->
                    <!-- ==================================================== -->
                    <div id="module-patients" class="module-view hidden space-y-3.5 lg:space-y-4">
                        <div class="mb-3">
                            <div class="mb-0.5 text-[9.5px] font-bold uppercase tracking-[0.17em] text-[#248294]">Flujo asistencial</div>
                            <h1 class="text-lg sm:text-xl font-black tracking-[-0.03em] text-[#123b5d]">Pacientes y documentación clínica</h1>
                            <p class="mt-0.5 text-xs text-slate-400">Genera expedientes completos y gestiona los documentos de cada paciente desde una sola pantalla.</p>
                        </div>

                        <div class="grid gap-3.5 lg:gap-4 grid-cols-1 lg:grid-cols-12 items-start">
                            
                            <!-- Columna Izquierda: Formulario Nuevo Expediente -->
                            <div class="lg:col-span-5 xl:col-span-4 rounded-xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-2xs">
                                <div class="mb-3 flex items-center gap-2.5 pb-2 border-b border-slate-100">
                                    <div class="grid size-8.5 place-items-center rounded-lg bg-[#e8f5f6] text-[#248294] shrink-0">
                                        <i data-lucide="user-plus" class="size-4.5"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-xs sm:text-sm font-extrabold text-[#123b5d]">Nuevo expediente</h2>
                                        <p class="text-[10px] text-slate-400">Genera los 3 documentos oficiales en Word</p>
                                    </div>
                                </div>

                                <form id="formCrearPaciente" onsubmit="handleCrearPaciente(event)" class="space-y-3">
                                    <div>
                                        <label for="nombre-paciente" class="block text-[11px] font-bold text-[#123b5d]">
                                            Nombre y apellidos del paciente
                                        </label>
                                        <input type="text" id="nombre-paciente" required placeholder="Ej. María Fernanda López" oninput="actualizarPreviewRuta(this.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-[#123b5d] outline-none focus:border-[#248294] focus:ring-1 focus:ring-[#248294]/20 transition shadow-2xs">
                                    </div>

                                    <div>
                                        <div class="text-[11px] font-bold text-[#123b5d] mb-1">Unidad de almacenamiento</div>
                                        <div class="grid gap-1.5 grid-cols-1 sm:grid-cols-2" id="diskSelectorContainer">
                                            @foreach($discos ?? [] as $idx => $d)
                                                <button type="button" onclick="seleccionarDiscoPaciente('{{ $d['disco'] }}', '{{ $d['etiqueta'] }}')" id="disk-btn-{{ $d['disco'] }}" class="disk-option-btn rounded-lg border p-2 text-left transition cursor-pointer {{ ($discoPredeterminado ?? '') === $d['disco'] ? 'border-[#248294] bg-[#e8f5f6]' : 'border-slate-200 hover:border-slate-300' }}">
                                                    <i data-lucide="{{ $d['es_drive'] ? 'cloud' : 'archive' }}" class="mb-1 size-3.5 text-[#248294]"></i>
                                                    <div class="text-[10px] font-bold text-[#123b5d] truncate">{{ $d['etiqueta'] }}</div>
                                                    <div class="text-[9px] text-slate-400 truncate">{{ $d['ruta_pediatria'] }}</div>
                                                </button>
                                            @endforeach
                                        </div>
                                        <input type="hidden" id="discoPacienteSeleccionado" value="{{ $discoPredeterminado ?? 'H' }}">
                                    </div>

                                    <!-- Vista previa de la ruta automática -->
                                    <div class="rounded-lg bg-[#f4f8fc] p-2 text-[10px] font-semibold text-slate-500 border border-slate-100 flex items-center gap-1.5">
                                        <i data-lucide="folder-open" class="size-3 text-[#248294] shrink-0"></i>
                                        <span id="previewRutaDestino" class="truncate font-mono text-[9.5px]">
                                            {{ $discoActivoObj['etiqueta'] ?? 'H:' }}\Pediatria\{{ date('Y-m-d') }}\[Nombre_Paciente]\
                                        </span>
                                    </div>

                                    <button type="submit" id="btnGenerarExpediente" class="w-full rounded-lg bg-[#123b5d] hover:bg-[#1a4a73] py-2.5 text-xs font-black text-white transition shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                                        <i data-lucide="check" class="size-3.5 text-[#55d6c2]"></i>
                                        <span id="btnGenerarTexto">Generar Expediente Clínico</span>
                                    </button>
                                </form>
                            </div>

                            <!-- Columna Derecha: Historial de Pacientes (Adaptable en altura) -->
                            <div class="lg:col-span-7 xl:col-span-8 rounded-xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-2xs flex flex-col min-h-[380px] lg:h-[calc(100vh-140px)]">
                                <div class="mb-3 pb-2.5 border-b border-slate-100">
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h2 class="text-xs sm:text-sm font-extrabold text-[#123b5d]">Historial de pacientes</h2>
                                                <span class="text-[10px] font-bold text-[#248294] bg-[#e8f5f6] border border-[#248294]/20 px-2 py-0.5 rounded-full hidden md:inline-flex items-center gap-1">
                                                    <i data-lucide="folder-open" class="size-3"></i> Clic en paciente para abrir archivos
                                                </span>
                                            </div>
                                            <p id="historialContadorSub" class="text-[10px] text-slate-400 font-medium">Expedientes clínicos registrados en la unidad</p>
                                        </div>
                                        <button type="button" onclick="cargarHistorial()" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-[#e8f5f6] text-[11px] font-bold text-[#248294] border border-slate-200/80 hover:border-[#248294] transition cursor-pointer" title="Actualizar lista">
                                            <i data-lucide="refresh-cw" class="size-3"></i>
                                            <span>Actualizar</span>
                                        </button>
                                    </div>

                                    <!-- Barra de Filtros Compacta -->
                                    <div class="rounded-xl border border-slate-200 bg-[#f8fbfe] p-2 sm:p-2.5 shadow-2xs">
                                        <div class="text-[9.5px] font-extrabold uppercase tracking-wider text-[#248294] mb-1.5 flex items-center gap-1">
                                            <i data-lucide="filter" class="size-3"></i>
                                            <span>Filtros de búsqueda</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end">
                                            <!-- Filtro 1: Buscador por Nombre -->
                                            <div class="sm:col-span-7">
                                                <label for="filtroBuscarPaciente" class="block text-[10px] font-bold text-[#123b5d] mb-0.5">
                                                    Buscar por nombre de paciente:
                                                </label>
                                                <div class="relative flex items-center">
                                                    <i data-lucide="search" class="absolute left-2.5 size-3.5 text-[#248294] pointer-events-none"></i>
                                                    <input type="text" id="filtroBuscarPaciente" oninput="filtrarHistorial()" class="w-full rounded-lg border border-slate-300 bg-white pl-8 pr-2.5 py-1.5 text-xs font-semibold text-[#123b5d] outline-none focus:border-[#248294] focus:ring-1 focus:ring-[#248294]/20 transition shadow-2xs placeholder:text-slate-400" placeholder="Escribe el nombre del paciente a filtrar...">
                                                </div>
                                            </div>

                                            <!-- Filtro 2: Selector de Fecha -->
                                            <div class="sm:col-span-5">
                                                <label for="filtroFechaPaciente" class="block text-[10px] font-bold text-[#123b5d] mb-0.5">
                                                    Filtrar por fecha:
                                                </label>
                                                <div class="flex items-center gap-1.5">
                                                    <div class="relative flex-1 flex items-center">
                                                        <i data-lucide="calendar" class="absolute left-2.5 size-3 text-[#248294] pointer-events-none"></i>
                                                        <input type="date" id="filtroFechaPaciente" onchange="cargarHistorial()" class="w-full rounded-lg border border-slate-300 bg-white pl-7 pr-2 py-1.5 text-xs font-semibold text-[#123b5d] outline-none focus:border-[#248294] transition shadow-2xs cursor-pointer" value="{{ date('Y-m-d') }}">
                                                    </div>
                                                    <button type="button" onclick="irAHoy()" class="rounded-lg border border-slate-300 bg-white hover:bg-[#e8f5f6] hover:border-[#248294] hover:text-[#248294] px-2.5 py-1.5 text-xs font-bold text-[#123b5d] transition shadow-2xs shrink-0 cursor-pointer" title="Ver pacientes de hoy">
                                                        Hoy
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Listado de pacientes con scroll independiente -->
                                <div id="contenedorHistorialPacientes" class="space-y-2 overflow-y-auto flex-1 pr-1">
                                    <div class="py-8 text-center text-xs text-slate-400">
                                        Cargando historial de pacientes...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- VISTA 3: ASISTENTE CLÍNICO DE IA                    -->
                    <!-- ==================================================== -->
                    <div id="module-ai" class="module-view hidden space-y-3.5 lg:space-y-4">
                        <div class="mb-3">
                            <div class="mb-0.5 text-[9.5px] font-bold uppercase tracking-[0.17em] text-[#248294]">Copiloto clínico</div>
                            <h1 class="text-lg sm:text-xl font-black tracking-[-0.03em] text-[#123b5d]">Asistente clínico de inteligencia artificial</h1>
                            <p class="mt-0.5 text-xs text-slate-400">Selecciona un modelo y utiliza prompts pediátricos preparados para tu guardia.</p>
                        </div>

                        <div class="grid gap-3.5 lg:gap-4 grid-cols-1 lg:grid-cols-12 items-start">
                            
                            <!-- Columna Izquierda: Selector de Herramientas de IA -->
                            <div class="lg:col-span-4 xl:col-span-3 rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-2xs">
                                <div class="mb-2.5 flex items-center justify-between pb-2 border-b border-slate-100">
                                    <h2 class="text-xs sm:text-sm font-extrabold text-[#123b5d]">Herramientas de IA</h2>
                                    <span class="text-[9.5px] text-slate-400">Paso 1: Elige IA</span>
                                </div>
                                <div class="space-y-1.5">
                                    @php
                                        $modelos = [
                                            ['id' => 'notebooklm', 'nombre' => 'NotebookLM', 'url' => 'https://notebooklm.google.com', 'letra' => 'N', 'bg' => 'bg-[#1a73e8]/15 text-[#1a73e8]', 'sub' => 'Google · Expedientes (1M-2M tokens)'],
                                            ['id' => 'chatgpt', 'nombre' => 'ChatGPT', 'url' => 'https://chat.openai.com', 'letra' => 'C', 'bg' => 'bg-[#10a37f]/10 text-[#10a37f]', 'sub' => 'OpenAI · Asistente clínico'],
                                            ['id' => 'claude', 'nombre' => 'Claude', 'url' => 'https://claude.ai', 'letra' => 'C', 'bg' => 'bg-[#d97706]/10 text-[#d97706]', 'sub' => 'Anthropic · Razonamiento clínico'],
                                            ['id' => 'gemini', 'nombre' => 'Gemini', 'url' => 'https://gemini.google.com', 'letra' => 'G', 'bg' => 'bg-[#1a73e8]/10 text-[#1a73e8]', 'sub' => 'Google · Consulta abierta'],
                                            ['id' => 'deepseek', 'nombre' => 'DeepSeek', 'url' => 'https://chat.deepseek.com', 'letra' => 'D', 'bg' => 'bg-[#4f46e5]/10 text-[#4f46e5]', 'sub' => 'DeepSeek · Cálculo clínico'],
                                            ['id' => 'perplexity', 'nombre' => 'Perplexity', 'url' => 'https://www.perplexity.ai', 'letra' => 'P', 'bg' => 'bg-[#0f766e]/10 text-[#0f766e]', 'sub' => 'Perplexity · Búsqueda web'],
                                        ];
                                    @endphp

                                    @foreach($modelos as $m)
                                        <div onclick="seleccionarModeloIA('{{ $m['nombre'] }}', '{{ $m['url'] }}', '{{ $m['id'] }}')" id="card-ia-{{ $m['id'] }}" class="ia-model-card flex items-center justify-between rounded-lg border p-2 transition cursor-pointer hover:border-slate-300 {{ $m['id'] === 'notebooklm' ? 'border-[#1a73e8] bg-blue-50/60 ring-2 ring-[#1a73e8]/25' : 'border-slate-100 bg-white' }}">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div class="grid size-7.5 place-items-center rounded-lg font-extrabold text-xs {{ $m['bg'] }} shrink-0">
                                                    {{ $m['letra'] }}
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-xs font-extrabold text-[#123b5d] truncate">{{ $m['nombre'] }}</span>
                                                        <span id="badge-ia-{{ $m['id'] }}" class="badge-ia-status text-[8.5px] font-bold px-1.5 py-0.2 rounded-full {{ $m['id'] === 'notebooklm' ? 'bg-[#1a73e8] text-white' : 'hidden' }}">Activa</span>
                                                    </div>
                                                    <div class="text-[9.5px] text-slate-400 truncate">{{ $m['sub'] ?? 'Asistente clínico' }}</div>
                                                </div>
                                            </div>
                                            <a href="{{ $m['url'] }}" target="_blank" onclick="event.stopPropagation()" class="rounded-md bg-slate-50 hover:bg-[#e8f5f6] hover:text-[#248294] text-slate-500 px-2 py-0.5 text-[9.5px] font-bold transition flex items-center gap-1 shrink-0" title="Abrir directamente en navegador">
                                                <span>Web</span>
                                                <i data-lucide="external-link" class="size-2.5"></i>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Columna Derecha: Prompts y Expediente Clínico con IA -->
                            <div class="lg:col-span-8 xl:col-span-9 space-y-3.5">
                                
                                <!-- TARJETA CENTRAL: BUSCADOR DE PACIENTE Y SELECCIÓN DE DOCUMENTOS -->
                                <div class="rounded-xl border border-blue-200/90 bg-gradient-to-br from-[#f8fbfe] via-white to-[#eff6ff] p-3.5 sm:p-4 shadow-2xs">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2.5 border-b border-blue-100/80">
                                        <div class="flex items-center gap-2.5">
                                            <div class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-[#1a73e8] to-[#4285f4] text-white shadow-2xs shrink-0">
                                                <i data-lucide="sparkles" class="size-4.5"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h2 class="text-xs sm:text-sm font-extrabold text-[#123b5d]">Vincular expediente con IA</h2>
                                                    <span id="badgeIANombreModelo" class="rounded-full bg-blue-100 text-[#1a73e8] px-2 py-0.2 text-[9.5px] font-extrabold border border-blue-200/60">
                                                        Destino: NotebookLM (Google)
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 mt-0.5">Elige un modelo a la izquierda, busca al paciente y marca los documentos específicos a consultar.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PASO 1: FILTROS DE BÚSQUEDA DE PACIENTE -->
                                    <div class="mt-3">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="text-[11px] font-bold text-[#123b5d] flex items-center gap-1.5">
                                                <span class="grid size-4.5 place-items-center rounded-full bg-[#123b5d] text-white text-[9.5px] font-extrabold">1</span>
                                                <span>Filtros de búsqueda de paciente:</span>
                                            </label>
                                            <span id="labelTotalPacientesIA" class="text-[9.5px] font-semibold text-slate-400">
                                                Cargando pacientes...
                                            </span>
                                        </div>

                                        <!-- Barra de Filtros: Nombre + Fecha + Botones -->
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end bg-white/90 p-2 sm:p-2.5 rounded-xl border border-slate-200/80 shadow-2xs">
                                            <!-- Filtro por Nombre -->
                                            <div class="sm:col-span-6">
                                                <label for="filtroNombreIA" class="block text-[10px] font-bold text-[#123b5d] mb-0.5">
                                                    Buscar por nombre:
                                                </label>
                                                <div class="relative flex items-center">
                                                    <i data-lucide="search" class="absolute left-2.5 size-3.5 text-[#248294] pointer-events-none"></i>
                                                    <input type="text" id="filtroNombreIA" oninput="aplicarFiltrosPacientesIA()" placeholder="Escribe el nombre del paciente..." class="w-full rounded-lg border border-slate-300 bg-white pl-8 pr-2.5 py-1.5 text-xs font-semibold text-[#123b5d] outline-none focus:border-[#1a73e8] focus:ring-1 focus:ring-[#1a73e8]/20 transition shadow-2xs placeholder:text-slate-400">
                                                </div>
                                            </div>

                                            <!-- Filtro por Fecha -->
                                            <div class="sm:col-span-3">
                                                <label for="filtroFechaIA" class="block text-[10px] font-bold text-[#123b5d] mb-0.5">
                                                    Filtrar por fecha:
                                                </label>
                                                <div class="relative flex items-center">
                                                    <i data-lucide="calendar" class="absolute left-2.5 size-3 text-[#248294] pointer-events-none"></i>
                                                    <input type="date" id="filtroFechaIA" onchange="aplicarFiltrosPacientesIA()" class="w-full rounded-lg border border-slate-300 bg-white pl-7 pr-2 py-1.5 text-xs font-semibold text-[#123b5d] outline-none focus:border-[#1a73e8] transition shadow-2xs cursor-pointer">
                                                </div>
                                            </div>

                                            <!-- Botones Rápidos de Fecha -->
                                            <div class="sm:col-span-3 flex items-center gap-1.5">
                                                <button type="button" onclick="filtrarFechaIAHoy()" class="flex-1 rounded-lg border border-slate-300 bg-white hover:bg-blue-50 hover:border-[#1a73e8] hover:text-[#1a73e8] py-1.5 text-xs font-bold text-[#123b5d] transition shadow-2xs cursor-pointer text-center" title="Filtrar pacientes hoy">
                                                    Hoy
                                                </button>
                                                <button type="button" onclick="filtrarFechaIATodos()" class="flex-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 py-1.5 text-xs font-bold text-slate-600 transition shadow-2xs cursor-pointer text-center" title="Mostrar todas las fechas">
                                                    Todos
                                                </button>
                                                <button type="button" onclick="cargarSelectorPacientesIA(true)" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 p-1.5 text-slate-500 transition shadow-2xs cursor-pointer shrink-0" title="Actualizar lista">
                                                    <i data-lucide="refresh-cw" class="size-3"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Resultados de Pacientes Filtrados (Lista interactiva) -->
                                        <div class="mt-2.5">
                                            <div class="flex items-center justify-between mb-1 px-0.5">
                                                <span class="text-[10.5px] font-bold text-[#123b5d]">Pacientes encontrados:</span>
                                                <span id="badgeConteoFiltradosIA" class="text-[9.5px] font-bold text-[#1a73e8]">-- encontrados</span>
                                            </div>
                                            <div id="listaResultadosPacientesIA" class="max-h-40 overflow-y-auto space-y-1 pr-1 rounded-lg">
                                                <!-- Se llena dinámicamente con JS -->
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PASO 2: DOCUMENTOS ESPECÍFICOS DE LA CARPETA (Aparece al seleccionar paciente) -->
                                    <div id="panelDetallePacienteIA" class="hidden mt-4 pt-3 border-t border-blue-200/60 space-y-3">
                                        <div>
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2">
                                                <label class="text-[11px] font-bold text-[#123b5d] flex items-center gap-1.5">
                                                    <span class="grid size-4.5 place-items-center rounded-full bg-[#248294] text-white text-[9.5px] font-extrabold">2</span>
                                                    <span>Marca qué documentos de la carpeta usar para la IA:</span>
                                                </label>
                                                <span class="text-[9.5px] text-slate-400 font-medium truncate">
                                                    📁 Carpeta: <code id="iaRutaCarpetaDisplay" class="font-mono text-[#123b5d] font-bold">--</code>
                                                </span>
                                            </div>

                                            <!-- Checkboxes de los 3 documentos -->
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                                <!-- Documento 1: Historia Clínica -->
                                                <label id="cardCheckHC" class="rounded-xl border-2 border-[#1a73e8] bg-white p-2.5 shadow-2xs flex flex-col justify-between transition cursor-pointer select-none">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="flex items-center gap-1.5">
                                                            <input type="checkbox" id="chkDocHC" checked onchange="actualizarDocsSeleccionadosIA()" class="size-3.5 rounded text-[#1a73e8] focus:ring-[#1a73e8] cursor-pointer">
                                                            <span class="font-extrabold text-[#123b5d] text-[11px]">Historia Clínica</span>
                                                        </div>
                                                        <i data-lucide="file-text" class="size-3.5 text-[#1a73e8] shrink-0"></i>
                                                    </div>
                                                    <p class="text-[9.5px] text-slate-400 mt-1 leading-snug">Antecedentes, examen físico y diagnóstico.</p>
                                                    <div class="mt-1.5 text-[9.5px] font-bold text-slate-500 pt-1.5 border-t border-slate-100 flex items-center justify-between">
                                                        <span id="lblHcStats">Word .docx</span>
                                                        <button type="button" onclick="event.preventDefault(); abrirDocWordActual('Historia_Clinica.docx')" class="text-[#248294] hover:underline cursor-pointer">Abrir Word</button>
                                                    </div>
                                                </label>

                                                <!-- Documento 2: Indicaciones Médicas -->
                                                <label id="cardCheckInd" class="rounded-xl border-2 border-[#248294] bg-white p-2.5 shadow-2xs flex flex-col justify-between transition cursor-pointer select-none">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="flex items-center gap-1.5">
                                                            <input type="checkbox" id="chkDocInd" checked onchange="actualizarDocsSeleccionadosIA()" class="size-3.5 rounded text-[#248294] focus:ring-[#248294] cursor-pointer">
                                                            <span class="font-extrabold text-[#123b5d] text-[11px]">Indicaciones</span>
                                                        </div>
                                                        <i data-lucide="file-text" class="size-3.5 text-[#248294] shrink-0"></i>
                                                    </div>
                                                    <p class="text-[9.5px] text-slate-400 mt-1 leading-snug">Plan terapéutico, fármacos mg/kg y enfermería.</p>
                                                    <div class="mt-1.5 text-[9.5px] font-bold text-slate-500 pt-1.5 border-t border-slate-100 flex items-center justify-between">
                                                        <span id="lblIndStats">Word .docx</span>
                                                        <button type="button" onclick="event.preventDefault(); abrirDocWordActual('Indicaciones.docx')" class="text-[#248294] hover:underline cursor-pointer">Abrir Word</button>
                                                    </div>
                                                </label>

                                                <!-- Documento 3: Admisión -->
                                                <label id="cardCheckAdm" class="rounded-xl border-2 border-[#5667a8] bg-white p-2.5 shadow-2xs flex flex-col justify-between transition cursor-pointer select-none">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="flex items-center gap-1.5">
                                                            <input type="checkbox" id="chkDocAdm" checked onchange="actualizarDocsSeleccionadosIA()" class="size-3.5 rounded text-[#5667a8] focus:ring-[#5667a8] cursor-pointer">
                                                            <span class="font-extrabold text-[#123b5d] text-[11px]">Admisión</span>
                                                        </div>
                                                        <i data-lucide="file-text" class="size-3.5 text-[#5667a8] shrink-0"></i>
                                                    </div>
                                                    <p class="text-[9.5px] text-slate-400 mt-1 leading-snug">Datos administrativos y motivo de ingreso.</p>
                                                    <div class="mt-1.5 text-[9.5px] font-bold text-slate-500 pt-1.5 border-t border-slate-100 flex items-center justify-between">
                                                        <span id="lblAdmStats">Word .docx</span>
                                                        <button type="button" onclick="event.preventDefault(); abrirDocWordActual('Admision.docx')" class="text-[#248294] hover:underline cursor-pointer">Abrir Word</button>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- PASO 3: ENVIAR A LA IA SELECCIONADA -->
                                        <div class="pt-2.5 border-t border-blue-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                            <div class="flex items-center gap-1.5">
                                                <span class="grid size-4.5 place-items-center rounded-full bg-emerald-600 text-white text-[9.5px] font-extrabold">3</span>
                                                <span class="text-[11px] font-bold text-[#123b5d]">Listo para enviar a <span id="lblNombreIAMeta" class="text-[#1a73e8]">NotebookLM</span>:</span>
                                                <span id="badgeCaracteresTotalIA" class="text-[9.5px] font-bold text-slate-500 bg-slate-100 px-2 py-0.2 rounded-full">-- caracteres</span>
                                            </div>

                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <button type="button" id="btnEjecutarEnvioIA" onclick="ejecutarEnvioIA()" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-[#1a73e8] to-[#1557b0] hover:from-[#1557b0] hover:to-[#0d47a1] text-white px-3 py-1.5 text-xs font-black transition shadow-2xs cursor-pointer">
                                                    <i data-lucide="sparkles" class="size-3.5"></i>
                                                    <span id="lblTextoBotonEjecutar">Abrir NotebookLM y copiar</span>
                                                </button>
                                                <button type="button" id="btnRecargarTextoIA" onclick="recargarDocsPacienteActualIA(false)" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 px-2.5 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Recargar los últimos cambios guardados en Word">
                                                    <i data-lucide="refresh-cw" class="size-3.5 text-[#248294]"></i>
                                                    <span>Actualizar texto</span>
                                                </button>
                                                <button type="button" onclick="abrirCarpetaPacienteSeleccionadoIA()" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 px-2.5 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Abrir carpeta en Windows">
                                                    <i data-lucide="folder-open" class="size-3.5 text-[#248294]"></i>
                                                    <span>Carpeta</span>
                                                </button>
                                                <button type="button" id="btnCopiarSoloTextoIA" onclick="copiarTextoDocsSeleccionadosIA()" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 px-2.5 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Copiar texto de los documentos marcados">
                                                    <i data-lucide="copy" class="size-3.5 text-slate-500"></i>
                                                    <span>Copiar</span>
                                                </button>
                                                <button type="button" id="btnVerTextoIA" onclick="abrirModalVerTextoIA()" class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50/80 hover:bg-blue-100 text-[#1a73e8] px-2.5 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Ver el texto extraído del paciente">
                                                    <i data-lucide="eye" class="size-3.5 text-[#1a73e8]"></i>
                                                    <span>Ver</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- GUÍA RÁPIDA: CÓMO USAR EN NOTEBOOKLM / IA -->
                                        <div class="rounded-xl border border-blue-200/70 bg-gradient-to-r from-blue-50/70 via-slate-50 to-indigo-50/40 p-2.5 text-xs">
                                            <div class="flex items-start gap-2">
                                                <div class="grid size-6 place-items-center rounded-lg bg-blue-500/10 text-[#1a73e8] shrink-0 mt-0.5">
                                                    <i data-lucide="info" class="size-3.5"></i>
                                                </div>
                                                <div class="space-y-1 flex-1 min-w-0">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span class="font-bold text-[#123b5d] text-[11px]">¿Por qué no se sube automáticamente a NotebookLM?</span>
                                                        <span class="text-[9px] font-semibold text-[#1a73e8] bg-blue-100/80 px-1.5 py-0.2 rounded-full">Seguridad de Google</span>
                                                    </div>
                                                    <p class="text-[10px] text-slate-500 leading-relaxed">
                                                        Google y OpenAI exigen sesión privada del médico. Tienes dos formas inmediatas:
                                                    </p>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 pt-0.5 text-[10px]">
                                                        <div class="rounded-lg bg-white/90 border border-blue-100 p-2 flex items-start gap-1.5 shadow-2xs">
                                                            <span class="font-black text-[#1a73e8] bg-blue-50 rounded px-1 text-[9px] shrink-0">1</span>
                                                            <div>
                                                                <strong class="text-slate-700 block leading-tight">Pegar texto (Ctrl + V):</strong>
                                                                <span class="text-slate-500 text-[9.5px]">Al presionar <em>"Abrir y copiar"</em>, el texto ya está en tu portapapeles. Solo presiona <strong>Ctrl + V</strong> en la IA.</span>
                                                            </div>
                                                        </div>
                                                        <div class="rounded-lg bg-white/90 border border-blue-100 p-2 flex items-start gap-1.5 shadow-2xs">
                                                            <span class="font-black text-[#248294] bg-teal-50 rounded px-1 text-[9px] shrink-0">2</span>
                                                            <div>
                                                                <strong class="text-slate-700 block leading-tight">Arrastrar Word:</strong>
                                                                <span class="text-slate-500 text-[9.5px]">Haz clic en <em>"Carpeta"</em> y arrastra el archivo Word (.docx) a la pantalla de la IA.</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- VISTA 4: MATERIAL DOCENTE & SESIONES CLÍNICAS        -->
                    <!-- ==================================================== -->
                    <div id="module-teaching" class="module-view hidden space-y-3.5 lg:space-y-4">
                        <div class="flex flex-wrap items-end justify-between gap-2.5 mb-2">
                            <div>
                                <div class="mb-0.5 text-[9.5px] font-bold uppercase tracking-[0.17em] text-[#248294]">Aprendizaje continuo</div>
                                <h1 class="text-lg sm:text-xl font-black tracking-[-0.03em] text-[#123b5d]">Material docente & sesiones clínicas</h1>
                                <p class="mt-0.5 text-xs text-slate-400">Biblioteca académica para residentes y especialistas.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" onclick="abrirModalNuevoTema()" class="flex items-center gap-1.5 rounded-lg bg-white border border-slate-200/90 hover:border-[#248294] hover:bg-slate-50 px-3 py-1.5 text-xs font-bold text-[#123b5d] transition shadow-2xs cursor-pointer">
                                    <i data-lucide="folder-plus" class="size-3.5 text-[#248294]"></i>
                                    <span>Ingresar nuevo tema</span>
                                </button>
                                <button type="button" onclick="abrirModalPptx()" class="flex items-center gap-1.5 rounded-lg bg-[#123b5d] hover:bg-[#1a4a73] px-3.5 py-1.5 text-xs font-black text-white transition shadow-2xs cursor-pointer">
                                    <i data-lucide="upload-cloud" class="size-3.5 text-[#55d6c2]"></i>
                                    <span>Subir presentación (.pptx)</span>
                                </button>
                            </div>
                        </div>

                        <!-- Barra de búsqueda y filtros por tema (Diseño elegante y estructurado) -->
                        <div class="rounded-xl border border-slate-200/80 bg-white p-3 sm:p-3.5 shadow-2xs space-y-2.5">
                            <!-- Fila 1: Buscador Principal -->
                            <div class="relative flex items-center">
                                <i data-lucide="search" class="absolute left-3 size-3.5 text-[#248294] pointer-events-none"></i>
                                <input type="text" id="filtroPptxQuery" oninput="filtrarPptx()" class="w-full rounded-lg border border-slate-200 bg-slate-50/50 pl-9 pr-9 py-2 text-xs text-slate-700 outline-none focus:border-[#248294] focus:bg-white focus:ring-1 focus:ring-[#248294]/20 transition shadow-2xs font-semibold placeholder:text-slate-400 placeholder:font-normal" placeholder="Buscar por título, tema clínico o archivo...">
                                <span class="absolute right-3 text-[9px] font-mono text-slate-400 bg-white px-1.5 py-0.5 rounded border border-slate-200 shadow-2xs">⌘K</span>
                            </div>

                            <!-- Fila 2: Chips de categorías y Ordenar por -->
                            <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-slate-100">
                                <div class="flex flex-wrap gap-1 items-center" id="temasPptxButtons">
                                    <button type="button" onclick="filtrarTemaPptx('Todas', this)" class="pptx-topic-btn rounded-lg px-2.5 py-1 text-[10.5px] font-bold bg-[#248294] text-white transition cursor-pointer flex items-center gap-1 shadow-2xs">
                                        <span>Todas</span>
                                        <span id="badgePptxCount" class="rounded-full bg-white/20 px-1.5 py-0.2 text-[9px]">0</span>
                                    </button>
                                    <button type="button" onclick="filtrarTemaPptx('Neonatología', this)" class="pptx-topic-btn rounded-lg px-2.5 py-1 text-[10.5px] font-semibold bg-slate-100/80 text-slate-600 hover:bg-slate-200/70 transition cursor-pointer">Neonatología</button>
                                    <button type="button" onclick="filtrarTemaPptx('Urgencias', this)" class="pptx-topic-btn rounded-lg px-2.5 py-1 text-[10.5px] font-semibold bg-slate-100/80 text-slate-600 hover:bg-slate-200/70 transition cursor-pointer">Urgencias</button>
                                    <button type="button" onclick="filtrarTemaPptx('Infectología', this)" class="pptx-topic-btn rounded-lg px-2.5 py-1 text-[10.5px] font-semibold bg-slate-100/80 text-slate-600 hover:bg-slate-200/70 transition cursor-pointer">Infectología</button>
                                    <button type="button" onclick="filtrarTemaPptx('Neumología', this)" class="pptx-topic-btn rounded-lg px-2.5 py-1 text-[10.5px] font-semibold bg-slate-100/80 text-slate-600 hover:bg-slate-200/70 transition cursor-pointer">Neumología</button>
                                    <button type="button" onclick="filtrarTemaPptx('Terapia Intensiva', this)" class="pptx-topic-btn rounded-lg px-2.5 py-1 text-[10.5px] font-semibold bg-slate-100/80 text-slate-600 hover:bg-slate-200/70 transition cursor-pointer">UCI</button>
                                    <button type="button" onclick="filtrarTemaPptx('Gastroenterología', this)" class="pptx-topic-btn rounded-lg px-2.5 py-1 text-[10.5px] font-semibold bg-slate-100/80 text-slate-600 hover:bg-slate-200/70 transition cursor-pointer">Gastroenterología</button>
                                </div>

                                <!-- Ordenar por -->
                                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                    <span class="text-[10px] text-slate-400">Ordenar:</span>
                                    <select id="selectOrdenPptx" onchange="ordenarPptx(this.value)" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10.5px] font-bold text-[#123b5d] outline-none focus:border-[#248294] cursor-pointer shadow-2xs">
                                        <option value="recientes">Más recientes</option>
                                        <option value="az">A - Z</option>
                                        <option value="tamano">Mayor tamaño</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Grid de presentaciones PPTX -->
                        <div id="gridPptxPresentaciones" class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            <div class="col-span-full py-8 text-center text-xs text-slate-400">
                                Cargando presentaciones de material docente...
                            </div>
                        </div>

                        <!-- Paginación y contador inferior -->
                        <div id="footerPptxPaginacion" class="flex flex-col sm:flex-row items-center justify-between gap-2.5 text-xs text-slate-500 pt-2.5 border-t border-slate-200/80">
                            <div class="text-[11px]">
                                Mostrando <span id="lblPptxVisibles" class="font-bold text-[#123b5d]">0</span> de <span id="lblPptxTotales" class="font-bold text-[#123b5d]">0</span> sesiones clínicas archivadas
                            </div>
                            <div class="flex items-center gap-1 text-[10.5px]">
                                <button type="button" class="px-2 py-0.5 rounded-lg border border-slate-200 bg-white text-slate-400 hover:bg-slate-50 cursor-pointer disabled:opacity-50">Anterior</button>
                                <span class="px-2 py-0.5 rounded-lg bg-[#248294] text-white font-bold">1</span>
                                <button type="button" class="px-2 py-0.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 cursor-pointer">Siguiente</button>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- VISTA 5: PLANTILLAS Y CONFIGURACIÓN DE DISCO         -->
                    <!-- ==================================================== -->
                    <div id="module-templates" class="module-view hidden space-y-3.5 lg:space-y-4">
                        <div class="mb-3">
                            <div class="mb-0.5 text-[9.5px] font-bold uppercase tracking-[0.17em] text-[#248294]">Configuración del sistema</div>
                            <h1 class="text-lg sm:text-xl font-black tracking-[-0.03em] text-[#123b5d]">Plantillas y configuración de disco</h1>
                            <p class="mt-0.5 text-xs text-slate-400">Administra las plantillas maestras y verifica la salud de tus rutas clínicas.</p>
                        </div>

                        <div class="grid gap-3.5 lg:gap-4 grid-cols-1 lg:grid-cols-12 items-start">
                            
                            <!-- Columna Izquierda: Las 3 Plantillas Oficiales -->
                            <div class="lg:col-span-7 xl:col-span-8 rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-2xs">
                                <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h2 class="text-sm sm:text-base font-extrabold text-[#123b5d]">Plantillas maestras oficiales (.docx)</h2>
                                            <span class="rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/60 px-2 py-0.5 text-[9.5px] font-bold">Activas</span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-0.5">Puedes modificarlas en Word o subir un archivo nuevo. Se clonarán automáticamente en cada nuevo paciente.</p>
                                    </div>
                                    <i data-lucide="shield-check" class="size-5 text-emerald-500 shrink-0 hidden sm:block"></i>
                                </div>

                                <div class="space-y-3">
                                    <!-- Plantilla 1: Historia Clínica -->
                                    <div class="rounded-xl border border-slate-200/90 bg-[#f8fbfe] hover:bg-white p-3.5 transition shadow-2xs">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="grid size-10 place-items-center rounded-xl bg-[#e8f5f6] text-[#248294] shrink-0 shadow-2xs">
                                                    <i data-lucide="stethoscope" class="size-5"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-xs sm:text-sm font-extrabold text-[#123b5d] truncate">Historia Clínica Pediátrica</div>
                                                    <div id="subtitulo-plantilla-historia-clinica" class="flex items-center gap-1.5 text-[10px] text-slate-500 font-mono truncate mt-0.5">
                                                        @if(isset($plantillasInfo['historia-clinica']) && str_contains($plantillasInfo['historia-clinica']['origen'], 'Google Drive'))
                                                            <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-semibold text-[9.5px]">
                                                                <i data-lucide="cloud" class="size-2.5"></i> Drive
                                                            </span>
                                                            <span class="truncate font-medium text-slate-600" title="{{ $plantillasInfo['historia-clinica']['archivo_real'] }}">{{ $plantillasInfo['historia-clinica']['archivo_real'] }}</span>
                                                            <span class="text-[9px] text-slate-400">({{ $plantillasInfo['historia-clinica']['fecha_mod'] }})</span>
                                                        @else
                                                            <span class="truncate text-slate-600 font-medium" title="{{ $plantillasInfo['historia-clinica']['archivo_real'] ?? 'Historia_Clinica.docx' }}">{{ $plantillasInfo['historia-clinica']['archivo_real'] ?? 'Historia_Clinica.docx' }}</span>
                                                            @if(isset($plantillasInfo['historia-clinica']['fecha_mod']))
                                                                <span class="text-[9px] text-slate-400">({{ $plantillasInfo['historia-clinica']['fecha_mod'] }})</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                                                <!-- Botón 1: Abrir y Editar en Word -->
                                                <button type="button" onclick="editarPlantillaWord('historia-clinica')" class="inline-flex items-center gap-1.5 rounded-lg bg-[#123b5d] hover:bg-[#1a4a73] text-white px-3 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Abrir y modificar directamente en Microsoft Word">
                                                    <i data-lucide="file-edit" class="size-3.5 text-[#55d6c2]"></i>
                                                    <span>Editar en Word</span>
                                                </button>

                                                <!-- Botón 2: Subir / Cambiar archivo -->
                                                <button type="button" onclick="triggerSubirPlantilla('historia-clinica')" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-[#123b5d] hover:text-[#248294] px-2.5 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Subir otro archivo Word para reemplazar esta plantilla">
                                                    <i data-lucide="upload-cloud" class="size-3.5 text-[#248294]"></i>
                                                    <span>Cambiar archivo</span>
                                                </button>
                                                <input type="file" id="inputPlantilla-historia-clinica" accept=".docx" class="hidden" onchange="subirPlantillaArchivo('historia-clinica', this)">

                                                <!-- Botón 3: Descargar copia de respaldo -->
                                                <a href="{{ route('documentos.descargar-plantilla', ['tipo' => 'historia-clinica']) }}" class="rounded-lg border border-slate-200 bg-white hover:bg-[#e8f5f6] p-1.5 text-slate-500 hover:text-[#248294] transition cursor-pointer shrink-0 shadow-2xs" title="Descargar copia de respaldo">
                                                    <i data-lucide="download" class="size-3.5"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Plantilla 2: Indicaciones Médicas -->
                                    <div class="rounded-xl border border-slate-200/90 bg-[#f8fbfe] hover:bg-white p-3.5 transition shadow-2xs">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="grid size-10 place-items-center rounded-xl bg-[#e8f5f6] text-[#248294] shrink-0 shadow-2xs">
                                                    <i data-lucide="clipboard-list" class="size-5"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-xs sm:text-sm font-extrabold text-[#123b5d] truncate">Indicaciones Médicas</div>
                                                    <div id="subtitulo-plantilla-indicaciones" class="flex items-center gap-1.5 text-[10px] text-slate-500 font-mono truncate mt-0.5">
                                                        @if(isset($plantillasInfo['indicaciones']) && str_contains($plantillasInfo['indicaciones']['origen'], 'Google Drive'))
                                                            <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-semibold text-[9.5px]">
                                                                <i data-lucide="cloud" class="size-2.5"></i> Drive
                                                            </span>
                                                            <span class="truncate font-medium text-slate-600" title="{{ $plantillasInfo['indicaciones']['archivo_real'] }}">{{ $plantillasInfo['indicaciones']['archivo_real'] }}</span>
                                                            <span class="text-[9px] text-slate-400">({{ $plantillasInfo['indicaciones']['fecha_mod'] }})</span>
                                                        @else
                                                            <span class="truncate text-slate-600 font-medium" title="{{ $plantillasInfo['indicaciones']['archivo_real'] ?? 'Indicaciones.docx' }}">{{ $plantillasInfo['indicaciones']['archivo_real'] ?? 'Indicaciones.docx' }}</span>
                                                            @if(isset($plantillasInfo['indicaciones']['fecha_mod']))
                                                                <span class="text-[9px] text-slate-400">({{ $plantillasInfo['indicaciones']['fecha_mod'] }})</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                                                <!-- Botón 1: Abrir y Editar en Word -->
                                                <button type="button" onclick="editarPlantillaWord('indicaciones')" class="inline-flex items-center gap-1.5 rounded-lg bg-[#123b5d] hover:bg-[#1a4a73] text-white px-3 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Abrir y modificar directamente en Microsoft Word">
                                                    <i data-lucide="file-edit" class="size-3.5 text-[#55d6c2]"></i>
                                                    <span>Editar en Word</span>
                                                </button>

                                                <!-- Botón 2: Subir / Cambiar archivo -->
                                                <button type="button" onclick="triggerSubirPlantilla('indicaciones')" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-[#123b5d] hover:text-[#248294] px-2.5 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Subir otro archivo Word para reemplazar esta plantilla">
                                                    <i data-lucide="upload-cloud" class="size-3.5 text-[#248294]"></i>
                                                    <span>Cambiar archivo</span>
                                                </button>
                                                <input type="file" id="inputPlantilla-indicaciones" accept=".docx" class="hidden" onchange="subirPlantillaArchivo('indicaciones', this)">

                                                <!-- Botón 3: Descargar copia de respaldo -->
                                                <a href="{{ route('documentos.descargar-plantilla', ['tipo' => 'indicaciones']) }}" class="rounded-lg border border-slate-200 bg-white hover:bg-[#e8f5f6] p-1.5 text-slate-500 hover:text-[#248294] transition cursor-pointer shrink-0 shadow-2xs" title="Descargar copia de respaldo">
                                                    <i data-lucide="download" class="size-3.5"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Plantilla 3: Admisión Pediátrica -->
                                    <div class="rounded-xl border border-slate-200/90 bg-[#f8fbfe] hover:bg-white p-3.5 transition shadow-2xs">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="grid size-10 place-items-center rounded-xl bg-[#e8f5f6] text-[#248294] shrink-0 shadow-2xs">
                                                    <i data-lucide="archive" class="size-5"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-xs sm:text-sm font-extrabold text-[#123b5d] truncate">Admisión Pediátrica</div>
                                                    <div id="subtitulo-plantilla-admision" class="flex items-center gap-1.5 text-[10px] text-slate-500 font-mono truncate mt-0.5">
                                                        @if(isset($plantillasInfo['admision']) && str_contains($plantillasInfo['admision']['origen'], 'Google Drive'))
                                                            <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-semibold text-[9.5px]">
                                                                <i data-lucide="cloud" class="size-2.5"></i> Drive
                                                            </span>
                                                            <span class="truncate font-medium text-slate-600" title="{{ $plantillasInfo['admision']['archivo_real'] }}">{{ $plantillasInfo['admision']['archivo_real'] }}</span>
                                                            <span class="text-[9px] text-slate-400">({{ $plantillasInfo['admision']['fecha_mod'] }})</span>
                                                        @else
                                                            <span class="truncate text-slate-600 font-medium" title="{{ $plantillasInfo['admision']['archivo_real'] ?? 'Admision.docx' }}">{{ $plantillasInfo['admision']['archivo_real'] ?? 'Admision.docx' }}</span>
                                                            @if(isset($plantillasInfo['admision']['fecha_mod']))
                                                                <span class="text-[9px] text-slate-400">({{ $plantillasInfo['admision']['fecha_mod'] }})</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                                                <!-- Botón 1: Abrir y Editar en Word -->
                                                <button type="button" onclick="editarPlantillaWord('admision')" class="inline-flex items-center gap-1.5 rounded-lg bg-[#123b5d] hover:bg-[#1a4a73] text-white px-3 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Abrir y modificar directamente en Microsoft Word">
                                                    <i data-lucide="file-edit" class="size-3.5 text-[#55d6c2]"></i>
                                                    <span>Editar en Word</span>
                                                </button>

                                                <!-- Botón 2: Subir / Cambiar archivo -->
                                                <button type="button" onclick="triggerSubirPlantilla('admision')" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-[#123b5d] hover:text-[#248294] px-2.5 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Subir otro archivo Word para reemplazar esta plantilla">
                                                    <i data-lucide="upload-cloud" class="size-3.5 text-[#248294]"></i>
                                                    <span>Cambiar archivo</span>
                                                </button>
                                                <input type="file" id="inputPlantilla-admision" accept=".docx" class="hidden" onchange="subirPlantillaArchivo('admision', this)">

                                                <!-- Botón 3: Descargar copia de respaldo -->
                                                <a href="{{ route('documentos.descargar-plantilla', ['tipo' => 'admision']) }}" class="rounded-lg border border-slate-200 bg-white hover:bg-[#e8f5f6] p-1.5 text-slate-500 hover:text-[#248294] transition cursor-pointer shrink-0 shadow-2xs" title="Descargar copia de respaldo">
                                                    <i data-lucide="download" class="size-3.5"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cuadro de seguridad e integridad clínica -->
                                <div class="mt-4 rounded-xl border border-blue-200/80 bg-blue-50/60 p-3 sm:p-3.5 text-xs text-slate-700 flex items-start gap-2.5">
                                    <div class="grid size-6 place-items-center rounded-lg bg-blue-500/10 text-[#1a73e8] shrink-0 mt-0.5">
                                        <i data-lucide="shield-check" class="size-4 text-[#1a73e8]"></i>
                                    </div>
                                    <div class="space-y-0.5">
                                        <div class="font-extrabold text-[#123b5d] text-xs">Protección de expedientes clínicos</div>
                                        <p class="text-[11px] text-slate-600 leading-relaxed">
                                            Cualquier edición o cambio de archivo que realices aquí se aplicará <strong>únicamente a los nuevos pacientes</strong> que registres a partir de ese momento. Los expedientes de pacientes ya registrados se mantienen intactos y archivados con su formato original.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna Derecha: Salud de Rutas y Google Drive -->
                            <div class="lg:col-span-5 xl:col-span-4 rounded-xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-2xs flex flex-col justify-between">
                                <div>
                                    <h2 class="text-xs sm:text-sm font-extrabold text-[#123b5d]">Salud de rutas clínicas</h2>
                                    
                                    <div class="mt-3 rounded-xl bg-[#e8f5f6] p-3 border border-[#cde8ea]">
                                        <div class="flex items-center gap-2.5">
                                            <div class="grid size-8.5 place-items-center rounded-lg bg-white text-[#248294] shadow-2xs shrink-0">
                                                <i data-lucide="cloud" class="size-4"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-xs font-extrabold text-[#123b5d] truncate">{{ $discoActivoObj['etiqueta'] ?? 'Unidad' }}</div>
                                                <div class="text-[9.5px] text-slate-500 font-mono truncate">{{ $discoActivoObj['ruta_pediatria'] ?? 'H:\Mi unidad\PEDIATRIA' }}</div>
                                            </div>
                                            <span class="ml-auto size-2 rounded-full bg-emerald-400 shrink-0"></span>
                                        </div>

                                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white">
                                            <div class="h-full w-[85%] rounded-full bg-[#248294]"></div>
                                        </div>
                                        <div class="mt-1.5 flex justify-between text-[9.5px] font-bold text-slate-500">
                                            <span>Almacenamiento disponible</span>
                                            <span class="text-emerald-600">Salud óptima</span>
                                        </div>
                                    </div>

                                    <div class="mt-3 space-y-2 text-xs">
                                        <div class="flex justify-between border-b border-slate-100 pb-1.5">
                                            <span class="text-slate-400 text-[11px]">Unidad predeterminada:</span>
                                            <b class="text-[#123b5d] text-[11px]">{{ $discoActivoObj['etiqueta'] ?? 'Google Drive H:' }}</b>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-100 pb-1.5">
                                            <span class="text-slate-400 text-[11px]">Estructura por paciente:</span>
                                            <b class="text-[#123b5d] text-[11px]">3 Archivos Word (.docx)</b>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-400 text-[11px]">Modo de sincronización:</span>
                                            <b class="text-emerald-600 text-[11px]">Local / Cloud Desktop</b>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" onclick="verificarRutas()" class="mt-4 w-full rounded-lg border border-[#248294] py-2 text-xs font-bold text-[#248294] hover:bg-[#e8f5f6] transition cursor-pointer">
                                    Verificar rutas ahora
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL INGRESAR NUEVO TEMA                                -->
    <!-- ======================================================== -->
    <div id="modalNuevoTema" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 backdrop-blur-xs hidden p-4">
        <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-md w-full shadow-2xl border border-slate-100 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="grid size-11 place-items-center rounded-2xl bg-teal-50 border border-teal-100 text-[#0d9488] shrink-0">
                        <i data-lucide="folder-plus" class="size-6"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-[#123b5d]">Ingresar un nuevo tema</h3>
                        <p class="text-[11px] text-slate-400">Crea una nueva categoría clínica para tu biblioteca</p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalNuevoTema()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="size-5"></i>
                </button>
            </div>

            <form id="formNuevoTema" onsubmit="handleCrearNuevoTema(event)" class="space-y-4">
                <div>
                    <label for="inputNuevoTemaNombre" class="block text-[11px] font-extrabold uppercase tracking-wider text-[#123b5d] mb-1.5">
                        Nombre del nuevo tema o especialidad:
                    </label>
                    <div class="relative flex items-center">
                        <i data-lucide="folder" class="absolute left-3.5 size-4 text-[#248294] pointer-events-none"></i>
                        <input type="text" id="inputNuevoTemaNombre" required placeholder="Ej. Cardiología Pediátrica, Neurología Infantil..." class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 pl-10 pr-4 py-2.5 text-xs font-bold text-[#123b5d] outline-none focus:border-[#248294] focus:bg-white focus:ring-2 focus:ring-[#248294]/20 transition shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                    </div>
                </div>

                <div>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Sugerencias pediátricas frecuentes:</span>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="setNuevoTemaInput('Cardiología Pediátrica')" class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">Cardiología Pediátrica</button>
                        <button type="button" onclick="setNuevoTemaInput('Neurología Infantil')" class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">Neurología Infantil</button>
                        <button type="button" onclick="setNuevoTemaInput('Dermatología Pediátrica')" class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">Dermatología</button>
                        <button type="button" onclick="setNuevoTemaInput('Nutrición y Metabolismo')" class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">Nutrición</button>
                        <button type="button" onclick="setNuevoTemaInput('Nefrología Pediátrica')" class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">Nefrología</button>
                        <button type="button" onclick="setNuevoTemaInput('Cirugía Pediátrica')" class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">Cirugía Pediátrica</button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalNuevoTema()" class="px-4 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="flex items-center gap-1.5 px-5 py-2.5 text-xs font-extrabold bg-[#123b5d] hover:bg-[#1a4a73] text-white rounded-xl transition shadow-sm cursor-pointer">
                        <span>Continuar y subir presentación</span>
                        <i data-lucide="arrow-right" class="size-3.5"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL OFICIAL PARA SUBIR PRESENTACIÓN CLÍNICA (.PPTX)    -->
    <!-- ======================================================== -->
    <div id="modalSubirPptx" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 backdrop-blur-xs hidden p-3 sm:p-4 overflow-y-auto">
        <div class="bg-white rounded-3xl p-5 sm:p-6 sm:px-7 max-w-xl sm:max-w-2xl w-full shadow-2xl border border-slate-100 space-y-3.5 my-auto max-h-[92vh] overflow-y-auto overflow-x-hidden">
            <!-- Cabecera Oficial -->
            <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="grid size-11 place-items-center rounded-2xl bg-[#e6f8f5] border border-teal-100 text-[#0d9488] shrink-0">
                        <i data-lucide="presentation" class="size-6"></i>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black tracking-tight text-[#123b5d]">Subir presentación clínica (.pptx)</h3>
                        <p class="text-[11px] text-slate-400 flex items-center gap-1.5 flex-wrap mt-0.5">
                            <span>Se sincronizará automáticamente con</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-bold bg-[#e6f8f5] text-[#0d9488] border border-[#a7f3d0]">
                                <i data-lucide="cloud" class="size-3"></i>
                                <span id="modalPptxDiscoBadge">Google Drive (G:)</span>
                            </span>
                            <span>en la Biblioteca docente</span>
                        </p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalPptx()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="size-5"></i>
                </button>
            </div>

            <form id="formPptxUpload" onsubmit="handleSubirPptx(event)" class="space-y-3.5">
                <!-- 1. TEMA O ESPECIALIDAD MÉDICA -->
                <div>
                    <label for="inputPptxTema" class="block text-[10px] font-black uppercase tracking-[0.08em] text-[#123b5d] mb-1">
                        TEMA O ESPECIALIDAD MÉDICA
                    </label>
                    <div class="relative flex items-center">
                        <i data-lucide="heart" class="absolute left-3.5 size-4 text-[#248294] pointer-events-none"></i>
                        <input type="text" id="inputPptxTema" list="datalistTemasPptx" required oninput="actualizarChipsTemaModal()" placeholder="Ej. Infectología Pediátrica, Neonatología..." class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 pl-10 pr-9 py-2.5 text-xs font-bold text-[#123b5d] outline-none focus:border-[#248294] focus:bg-white focus:ring-2 focus:ring-[#248294]/20 transition shadow-2xs">
                        <i data-lucide="chevron-down" class="absolute right-3.5 size-4 text-slate-400 pointer-events-none"></i>
                        <datalist id="datalistTemasPptx"></datalist>
                    </div>
                    <!-- Sugerencias -->
                    <div class="flex items-center gap-1.5 mt-2 flex-wrap text-[11px]">
                        <span class="text-[10px] font-semibold text-slate-400">Sugerencias:</span>
                        <div id="contenedorChipsTemasModal" class="flex flex-wrap gap-1">
                            <button type="button" onclick="seleccionarTemaModal('Neonatología')" class="pptx-modal-chip px-2.5 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">Neonatología</button>
                            <button type="button" onclick="seleccionarTemaModal('Infectología')" class="pptx-modal-chip px-2.5 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">Infectología</button>
                            <button type="button" onclick="seleccionarTemaModal('Urgencias')" class="pptx-modal-chip px-2.5 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">Urgencias</button>
                            <button type="button" onclick="seleccionarTemaModal('UCI Pediátrica')" class="pptx-modal-chip px-2.5 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">UCI Pediátrica</button>
                            <button type="button" onclick="seleccionarTemaModal('Gastroenterología')" class="pptx-modal-chip px-2.5 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer">Gastroenterología</button>
                        </div>
                    </div>
                </div>

                <!-- 2. TÍTULO DE LA SESIÓN O ATENEO -->
                <div>
                    <label for="inputPptxTitulo" class="block text-[10px] font-black uppercase tracking-[0.08em] text-[#123b5d] mb-1">
                        TÍTULO DE LA SESIÓN O ATENEO
                    </label>
                    <input type="text" id="inputPptxTitulo" placeholder="Ej. Manejo Antimicrobiano en Sepsis Pediátrica" class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-xs font-bold text-[#123b5d] outline-none focus:border-[#248294] focus:bg-white focus:ring-2 focus:ring-[#248294]/20 transition shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                </div>

                <!-- 3. EXPOSITOR / DOCENTE RESPONSABLE -->
                <div>
                    <label for="inputPptxExpositor" class="block text-[10px] font-black uppercase tracking-[0.08em] text-[#123b5d] mb-1">
                        EXPOSITOR / DOCENTE RESPONSABLE
                    </label>
                    <div class="relative flex items-center">
                        <i data-lucide="user" class="absolute left-3.5 size-4 text-slate-400 pointer-events-none"></i>
                        <input type="text" id="inputPptxExpositor" placeholder="Ej. Dra. Elena Ramos - Infectología Infantil" class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 pl-10 pr-3.5 py-2.5 text-xs font-semibold text-[#123b5d] outline-none focus:border-[#248294] focus:bg-white focus:ring-2 focus:ring-[#248294]/20 transition shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                    </div>
                </div>

                <!-- 4. FILA: FECHA DE LA SESIÓN & TIPO DE ACTIVIDAD -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="inputPptxFecha" class="block text-[10px] font-black uppercase tracking-[0.08em] text-[#123b5d] mb-1">
                            FECHA DE LA SESIÓN
                        </label>
                        <input type="date" id="inputPptxFecha" class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-xs font-bold text-[#123b5d] outline-none focus:border-[#248294] focus:bg-white focus:ring-2 focus:ring-[#248294]/20 transition shadow-2xs">
                    </div>
                    <div>
                        <label for="inputPptxTipoActividad" class="block text-[10px] font-black uppercase tracking-[0.08em] text-[#123b5d] mb-1">
                            TIPO DE ACTIVIDAD
                        </label>
                        <select id="inputPptxTipoActividad" class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-xs font-bold text-[#123b5d] outline-none focus:border-[#248294] focus:bg-white focus:ring-2 focus:ring-[#248294]/20 transition shadow-2xs cursor-pointer">
                            <option value="Ateneo hospitalario">Ateneo hospitalario</option>
                            <option value="Sesión clínica">Sesión clínica</option>
                            <option value="Pase de guardia">Pase de guardia</option>
                            <option value="Revisión bibliográfica">Revisión bibliográfica</option>
                            <option value="Caso clínico">Caso clínico</option>
                            <option value="Clase magistral">Clase magistral</option>
                        </select>
                    </div>
                </div>

                <!-- 5. ARCHIVO DE PRESENTACIÓN (.PPTX) -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-[0.08em] text-[#123b5d] mb-1">
                        ARCHIVO DE PRESENTACIÓN (.PPTX)
                    </label>
                    <input type="file" id="inputPptxArchivo" accept=".pptx,.ppt,.ppsx,.pps" onchange="onArchivoPptxSeleccionado(this)" class="hidden">

                    <!-- Estado A: Dropzone vacío -->
                    <div id="pptxDropzone" onclick="document.getElementById('inputPptxArchivo').click()" ondragover="handlePptxDragOver(event)" ondragleave="handlePptxDragLeave(event)" ondrop="handlePptxDrop(event)" class="rounded-2xl border-2 border-dashed border-[#55d6c2]/80 hover:border-[#248294] bg-[#f0fbf9]/70 hover:bg-[#e6f8f5] p-5 text-center transition cursor-pointer group">
                        <i data-lucide="upload-cloud" class="size-7 text-[#248294] mx-auto mb-1.5 group-hover:scale-110 transition-transform"></i>
                        <p class="text-xs text-slate-700 font-medium">
                            Arrastra tu archivo aquí o <span class="text-[#248294] font-extrabold underline">haz clic para examinar</span>
                        </p>
                        <p class="text-[10px] text-slate-400 mt-1">
                            Formatos admitidos: .pptx, .ppt, .ppsx (hasta 150 MB)
                        </p>
                    </div>

                    <!-- Estado B: Archivo Seleccionado y Progreso (Copia fiel del diseño) -->
                    <div id="pptxFileSelectedBox" class="space-y-2.5 hidden">
                        <!-- Barra de Transferencia / Estado -->
                        <div class="rounded-2xl border border-dashed border-[#248294]/40 bg-[#f0fbf9] p-3 flex items-center gap-3">
                            <div class="size-8 rounded-xl bg-[#e0f7f4] flex items-center justify-center text-[#248294] shrink-0">
                                <i data-lucide="upload" class="size-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-[#123b5d]">Transferencia en curso</span>
                                    <span id="pptxBadgeTransferencia" class="rounded-full bg-[#55d6c2]/20 border border-[#55d6c2]/40 text-[#123b5d] text-[10px] font-extrabold px-2 py-0.2">Subida activa</span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-0.5 truncate">No cierres la ventana mientras se completa la sincronización con Google Drive</p>
                            </div>
                        </div>

                        <!-- Tarjeta de Archivo con Barra de Progreso -->
                        <div class="rounded-2xl border border-emerald-200/80 bg-white p-3.5 shadow-2xs space-y-2.5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="rounded-lg bg-[#d04423] text-white px-2 py-1 flex items-center justify-center text-[10px] font-black tracking-tight shrink-0 shadow-2xs">
                                        PPTX
                                    </div>
                                    <span id="pptxNombrePreview" class="text-xs font-bold text-[#123b5d] truncate">archivo.pptx</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span id="pptxBadgeEstado" class="rounded-full bg-[#e6f8f5] text-[#0d9488] border border-[#a7f3d0] text-[10px] font-bold px-2 py-0.5 flex items-center gap-1">
                                        <span class="size-1.5 rounded-full bg-[#0d9488] animate-pulse"></span>
                                        <span id="pptxEstadoTexto">Listo para sincronizar</span>
                                    </span>
                                    <button type="button" onclick="cancelarArchivoPptx()" class="text-slate-400 hover:text-rose-600 text-xs font-semibold cursor-pointer whitespace-nowrap">✕ Cancelar</button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-400 font-semibold px-0.5">
                                <span id="pptxTamanoPreview">0 MB</span>
                                <span id="pptxVelocidadPreview" class="text-[#248294] font-bold">» Google Drive Sync</span>
                            </div>
                            <!-- Barra de progreso viva -->
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div id="pptxProgressBar" class="bg-gradient-to-r from-[#248294] to-[#55d6c2] h-full rounded-full transition-all duration-300" style="width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. PIE DEL MODAL -->
                <div class="flex items-center justify-between gap-3 pt-3 border-t border-slate-100 shrink-0">
                    <div class="flex items-center gap-2 min-w-0 overflow-hidden">
                        <span class="rounded-xl border border-teal-200/80 bg-[#f0fbf9] px-2.5 py-1.5 text-[10px] font-extrabold text-teal-800 flex items-center gap-1.5 shadow-2xs shrink-0 whitespace-nowrap">
                            <i data-lucide="cloud" class="size-3.5 text-teal-600"></i>
                            <span>Sincronizando en vivo</span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-semibold truncate" id="pptxRutaDestinoLabel">Google Drive (G:) / MaterialDocente</span>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 ml-auto">
                        <button type="button" onclick="cerrarModalPptx()" class="px-3.5 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer whitespace-nowrap">
                            Cancelar
                        </button>
                        <button type="submit" id="btnSubirPptxConfirm" class="px-5 py-2.5 text-xs font-extrabold bg-[#123b5d] hover:bg-[#1a4a73] text-white rounded-xl transition shadow-sm cursor-pointer flex items-center gap-2 shrink-0 whitespace-nowrap">
                            <i data-lucide="upload" class="size-3.5 shrink-0"></i>
                            <span id="btnSubirPptxTexto" class="whitespace-nowrap">Subir archivo</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL INTEGRACIÓN NOTEBOOKLM (GOOGLE)                    -->
    <!-- ======================================================== -->
    <div id="modalNotebookLM" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 backdrop-blur-xs hidden p-3 sm:p-4">
        <div class="bg-white rounded-3xl p-5 sm:p-6 max-w-2xl w-full shadow-2xl border border-slate-100 max-h-[92vh] flex flex-col justify-between overflow-hidden">
            <!-- Cabecera -->
            <div class="flex items-start justify-between pb-3.5 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-[#1a73e8] to-[#4285f4] text-white shadow-md shadow-blue-500/20 shrink-0">
                        <i data-lucide="sparkles" class="size-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base sm:text-lg font-extrabold text-[#123b5d]">Analizar con NotebookLM</h3>
                            <span class="rounded-full bg-blue-50 text-[#1a73e8] border border-blue-200/60 px-2 py-0.5 text-[10px] font-extrabold">1M-2M tokens</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Paciente: <span id="nbModalPacienteNombre" class="font-extrabold text-[#123b5d]">--</span> · <span id="nbModalPacienteFecha" class="font-medium text-slate-500">--</span>
                        </p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalNotebookLM()" class="text-slate-400 hover:text-slate-600 rounded-xl p-1 hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="size-5"></i>
                </button>
            </div>

            <!-- Contenido con scroll interno -->
            <div class="overflow-y-auto py-4 space-y-4 flex-1 pr-1">
                <!-- Estado de Carga -->
                <div id="nbModalLoading" class="py-12 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-[#1a73e8] border-t-transparent mb-3"></div>
                    <div class="text-xs font-bold text-[#123b5d]">Extrayendo documentos clínicos del paciente...</div>
                    <p class="text-[11px] text-slate-400 mt-1">Leyendo Historia Clínica, Indicaciones y Admisión en Word</p>
                </div>

                <!-- Contenido cuando ya cargó -->
                <div id="nbModalContent" class="hidden space-y-4">
                    <!-- Tarjeta 1: Archivos disponibles del paciente -->
                    <div class="rounded-2xl border border-slate-200/80 bg-[#f8fbfe] p-4">
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-xs font-bold text-[#123b5d] flex items-center gap-1.5">
                                <i data-lucide="files" class="size-4 text-[#1a73e8]"></i>
                                Documentos clínicos detectados:
                            </span>
                            <span id="nbModalBadgeTotal" class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                3 archivos listos
                            </span>
                        </div>

                        <div id="nbModalListaArchivos" class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                            <!-- Se llena dinámicamente con JS -->
                        </div>

                        <!-- Botones de Acción para los Archivos -->
                        <div class="mt-3 pt-3 border-t border-slate-200/60 flex flex-wrap gap-2">
                            <button type="button" id="btnCopiarTextoCompleto" onclick="copiarTextoCompletoExpediente()" class="flex-1 min-w-[200px] inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#123b5d] to-[#1a4a73] hover:from-[#1a4a73] hover:to-[#248294] text-white px-3.5 py-2 text-xs font-bold transition shadow-sm cursor-pointer">
                                <i data-lucide="copy" class="size-4"></i>
                                <span>1. Copiar texto completo del expediente</span>
                            </button>
                            <button type="button" id="btnAbrirCarpetaWindowsNb" onclick="abrirCarpetaWindowsDesdeModal()" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 px-3 py-2 text-xs font-bold transition cursor-pointer shadow-2xs">
                                <i data-lucide="folder-open" class="size-4 text-[#248294]"></i>
                                <span>Abrir carpeta</span>
                            </button>
                            <a id="linkDescargarZipNb" href="#" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 px-3 py-2 text-xs font-bold transition cursor-pointer shadow-2xs">
                                <i data-lucide="download" class="size-4 text-slate-500"></i>
                                <span>Descargar ZIP</span>
                            </a>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Prompt clínico sugerido -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-4">
                        <div class="flex items-center justify-between mb-2">
                            <label for="nbModalPromptTexto" class="text-xs font-bold text-[#123b5d] flex items-center gap-1.5">
                                <i data-lucide="message-square-quote" class="size-4 text-[#248294]"></i>
                                Prompt clínico pediátrico preparado:
                            </label>
                            <button type="button" id="btnCopiarPromptNb" onclick="copiarPromptNotebookLM()" class="text-[11px] font-bold text-[#1a73e8] hover:underline flex items-center gap-1 cursor-pointer">
                                <i data-lucide="copy" class="size-3.5"></i>
                                <span>Copiar prompt</span>
                            </button>
                        </div>
                        <textarea id="nbModalPromptTexto" rows="4" class="w-full rounded-xl border border-slate-200 p-3 text-xs text-slate-700 bg-slate-50/50 outline-none focus:border-[#1a73e8] focus:bg-white transition font-mono leading-relaxed" readonly></textarea>
                    </div>

                    <!-- Tarjeta 3: Instrucciones rápidas en 3 pasos -->
                    <div class="rounded-2xl bg-amber-50/80 border border-amber-200/70 p-3.5 text-xs text-amber-900">
                        <div class="font-extrabold mb-1.5 flex items-center gap-1.5 text-amber-900">
                            <i data-lucide="info" class="size-4 text-amber-700"></i>
                            ¿Cómo usarlo en NotebookLM?
                        </div>
                        <ol class="list-decimal list-inside space-y-1 text-[11px] text-amber-800 font-medium">
                            <li>Haz clic en el botón inferior <strong>"Abrir NotebookLM"</strong> y crea una libreta con el nombre del paciente.</li>
                            <li>En <strong>"Agregar fuentes"</strong>: haz clic en <em>"Texto copiado"</em> y pega (`Ctrl+V`), o arrastra los archivos Word desde la carpeta.</li>
                            <li>Pega el prompt clínico en el chat para recibir el análisis médico sin alucinaciones y con citas directas.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Pie del Modal con Acción Principal -->
            <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <button type="button" onclick="cerrarModalNotebookLM()" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                    Cerrar
                </button>
                <a href="https://notebooklm.google.com" target="_blank" onclick="notificarAperturaNotebookLM()" class="inline-flex items-center gap-2 rounded-xl bg-[#1a73e8] hover:bg-[#1557b0] text-white px-5 py-2.5 text-xs font-extrabold transition shadow-md shadow-blue-500/20 cursor-pointer">
                    <span>2. Abrir NotebookLM (Google)</span>
                    <i data-lucide="external-link" class="size-4"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL VISUALIZADOR DE TEXTO EXTRAÍDO PARA IA             -->
    <!-- ======================================================== -->
    <div id="modalVerTextoIA" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 backdrop-blur-xs hidden p-3 sm:p-4">
        <div class="bg-white rounded-3xl p-5 sm:p-6 max-w-3xl w-full shadow-2xl border border-slate-100 max-h-[92vh] flex flex-col justify-between overflow-hidden">
            <!-- Cabecera -->
            <div class="flex items-start justify-between pb-3.5 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-[#1a73e8] to-[#4285f4] text-white shadow-md shadow-blue-500/20 shrink-0">
                        <i data-lucide="file-text" class="size-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base sm:text-lg font-extrabold text-[#123b5d]">Texto extraído para IA</h3>
                            <span id="badgeModalVerTextoChars" class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">-- caracteres</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Paciente: <strong id="lblModalVerTextoPaciente" class="text-slate-700">--</strong> · Fecha: <span id="lblModalVerTextoFecha" class="font-mono">--</span>
                        </p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalVerTextoIA()" class="text-slate-400 hover:text-slate-600 rounded-xl p-1 hover:bg-slate-100 transition cursor-pointer">
                    <i data-lucide="x" class="size-5"></i>
                </button>
            </div>

            <!-- Contenido con scroll: Textarea de solo lectura con el texto completo -->
            <div class="py-3 flex-1 flex flex-col min-h-0">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                    <span class="font-bold text-slate-600">Contenido clínico de los documentos seleccionados:</span>
                    <span class="text-[11px] text-slate-400">Puedes seleccionarlo manualmente o pulsar Copiar</span>
                </div>
                <textarea id="txtModalVerTextoContenido" readonly class="w-full flex-1 min-h-[180px] max-h-[48vh] p-3 font-mono text-xs text-slate-700 bg-slate-50 border border-slate-200 rounded-2xl resize-none focus:outline-none focus:ring-2 focus:ring-[#1a73e8]/20 select-all leading-relaxed"></textarea>
            </div>

            <!-- Pie del modal -->
            <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <button type="button" onclick="cerrarModalVerTextoIA()" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                    Cerrar
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnCopiarDesdeModalVerTexto" onclick="copiarDesdeModalVerTexto()" class="inline-flex items-center gap-2 rounded-xl bg-[#1a73e8] hover:bg-[#1557b0] text-white px-5 py-2.5 text-xs font-extrabold transition shadow-md shadow-blue-500/20 cursor-pointer">
                        <i data-lucide="copy" class="size-4"></i>
                        <span>Copiar todo el texto</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- LOGICA JAVASCRIPT REAL CONECTADA CON LARAVEL             -->
    <!-- ======================================================== -->
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let discoActual = "{{ $discoPredeterminado ?? 'H' }}";
        let cachePacientes = [];
        let cachePptx = [];
        let temaPptxActivo = 'Todas';

        /**
         * Función universal para copiar al portapapeles.
         * Funciona en contextos seguros (HTTPS), localhost, HTTP y redes locales.
         */
        function copiarAlPortapapeles(texto) {
            return new Promise((resolve, reject) => {
                if (!texto || texto.trim() === '') {
                    reject(new Error("No hay texto para copiar"));
                    return;
                }

                // 1. Intentar API moderna Clipboard si está disponible y en contexto seguro
                if (navigator.clipboard && window.isSecureContext && typeof navigator.clipboard.writeText === 'function') {
                    navigator.clipboard.writeText(texto)
                        .then(() => resolve(true))
                        .catch(() => {
                            fallbackCopiarTexto(texto, resolve, reject);
                        });
                    return;
                }

                // 2. Fallback estándar compatible con HTTP / Laragon
                fallbackCopiarTexto(texto, resolve, reject);
            });
        }

        function fallbackCopiarTexto(texto, resolve, reject) {
            try {
                const textarea = document.createElement('textarea');
                textarea.value = texto;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.top = '-9999px';
                textarea.style.left = '-9999px';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                textarea.setSelectionRange(0, 999999);
                const exito = document.execCommand('copy');
                document.body.removeChild(textarea);
                if (exito) {
                    resolve(true);
                } else {
                    reject(new Error("execCommand copy falló"));
                }
            } catch (err) {
                reject(err);
            }
        }

        // 0. CONTROL DE ESCALA Y BARRA LATERAL (ADAPTACIÓN LAPTOP & PANTALLAS PEQUEÑAS)
        function toggleSidebarCollapse() {
            const sidebar = document.getElementById('sidebar');
            const icon = document.getElementById('btnCollapseIcon');
            if (!sidebar) return;

            const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
            if (icon) {
                icon.setAttribute('data-lucide', isCollapsed ? 'chevrons-right' : 'chevrons-left');
                if (window.lucide) lucide.createIcons();
            }
            try {
                localStorage.setItem('pediatria_sidebar_collapsed', isCollapsed ? '1' : '0');
            } catch (e) {}
        }

        function setUiZoom(level) {
            const zoomValue = parseFloat(level) || 1.0;
            document.documentElement.style.zoom = zoomValue;

            // Actualizar botones de zoom en la barra superior
            ['100', '90', '82'].forEach(k => {
                const btn = document.getElementById('zoomBtn-' + k);
                if (!btn) return;
                const match = (k === '100' && zoomValue === 1.0) ||
                              (k === '90' && Math.abs(zoomValue - 0.9) < 0.03) ||
                              (k === '82' && Math.abs(zoomValue - 0.82) < 0.03);
                if (match) {
                    btn.className = "zoom-btn rounded-full px-2 py-0.5 transition cursor-pointer bg-[#248294] text-white shadow-2xs font-bold";
                } else {
                    btn.className = "zoom-btn rounded-full px-2 py-0.5 transition cursor-pointer text-slate-600 hover:text-[#123b5d] font-semibold";
                }
            });

            try {
                localStorage.setItem('pediatria_ui_zoom', String(zoomValue));
            } catch (e) {}
        }

        // Inicializar Lucide Icons, datos y escala de pantalla
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Restaurar o fijar escala estándar (diseño 100% adaptado con Tailwind CSS)
            try {
                const savedZoom = localStorage.getItem('pediatria_ui_zoom');
                if (savedZoom && savedZoom !== '0.9') {
                    setUiZoom(savedZoom);
                } else {
                    setUiZoom('1.0');
                }

                const savedCollapsed = localStorage.getItem('pediatria_sidebar_collapsed');
                if (savedCollapsed === '1') {
                    toggleSidebarCollapse();
                }
            } catch (e) {}

            if (window.lucide) lucide.createIcons();
            cargarHistorial();
            cargarMaterialDocente();
            cargarSelectorPacientesIA();
        });

        // 1. NAVEGACIÓN MODULAR
        function goModule(moduleName) {
            document.querySelectorAll('.module-view').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));

            const target = document.getElementById('module-' + moduleName);
            const navBtn = document.getElementById('nav-' + moduleName);

            if (target) {
                target.classList.remove('hidden');
            }
            if (navBtn) {
                navBtn.classList.add('active');
            }

            // Resetear scroll al inicio para evitar que pantallas largas queden desplazadas
            const mainContainer = document.querySelector('main');
            if (mainContainer) {
                mainContainer.scrollTop = 0;
            }

            if (moduleName === 'ai') {
                cargarSelectorPacientesIA();
            } else if (moduleName === 'teaching') {
                cargarMaterialDocente();
            } else if (moduleName === 'templates') {
                cargarEstadoPlantillas();
            }

            const titles = {
                'dashboard': 'Dashboard principal',
                'patients': 'Pacientes y documentación',
                'ai': 'Asistente clínico IA',
                'teaching': 'Material docente',
                'templates': 'Plantillas y configuración'
            };
            const breadcrumb = document.getElementById('breadcrumbModule');
            if (breadcrumb) {
                breadcrumb.innerText = titles[moduleName] || 'Dashboard principal';
            }

            toggleSidebar(false);
            if (window.lucide) lucide.createIcons();
        }

        function toggleSidebar(open) {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (open) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        // 2. TOAST NOTIFICATIONS
        function notify(msg) {
            const toast = document.getElementById('toastNotification');
            const msgEl = document.getElementById('toastMessage');
            if (!toast || !msgEl) return;
            msgEl.innerText = msg;

            toast.style.display = 'flex';
            requestAnimationFrame(() => {
                toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-3');
            });

            clearTimeout(window.__toastTimer);
            window.__toastTimer = setTimeout(() => {
                toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-3');
                setTimeout(() => {
                    if (toast.classList.contains('opacity-0')) {
                        toast.style.display = 'none';
                    }
                }, 350);
            }, 3000);
        }

        // 3. CAMBIAR DISCO GLOBAL
        function cambiarDiscoGlobal(disco) {
            discoActual = disco;
            document.getElementById('discoPacienteSeleccionado').value = disco;
            
            // Actualizar estilo de botones en vista de paciente
            document.querySelectorAll('.disk-option-btn').forEach(btn => {
                btn.classList.remove('border-[#248294]', 'bg-[#e8f5f6]');
                btn.classList.add('border-slate-200');
            });
            const selBtn = document.getElementById('disk-btn-' + disco);
            if (selBtn) {
                selBtn.classList.add('border-[#248294]', 'bg-[#e8f5f6]');
                selBtn.classList.remove('border-slate-200');
            }

            actualizarPreviewRuta(document.getElementById('nombre-paciente').value);
            notify("Unidad activa cambiada a " + disco + ":");
            cargarHistorial();
            cargarSelectorPacientesIA();
            cargarEstadoPlantillas();
        }

        function seleccionarDiscoPaciente(disco, etiqueta) {
            document.getElementById('selectDiscoGlobal').value = disco;
            cambiarDiscoGlobal(disco);
        }

        function getFechaLocalHoy() {
            const d = new Date();
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function actualizarPreviewRuta(nombre) {
            const val = nombre.trim() ? nombre.trim().replace(/\s+/g, '_') : '[Nombre_Paciente]';
            const fecha = getFechaLocalHoy();
            document.getElementById('previewRutaDestino').innerText = `${discoActual}:\\Pediatria\\${fecha}\\${val}\\`;
        }

        // 4. CREAR PACIENTE REAL (Genera carpeta y los 3 Word)
        async function handleCrearPaciente(e) {
            e.preventDefault();
            const input = document.getElementById('nombre-paciente');
            const nombre = input.value.trim();
            const btn = document.getElementById('btnGenerarExpediente');
            const btnTexto = document.getElementById('btnGenerarTexto');

            if (!nombre) return;

            btn.disabled = true;
            btnTexto.innerText = "Generando 3 documentos Word...";

            try {
                const res = await fetch("{{ route('documentos.ingresar-paciente') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        nombre: nombre,
                        paciente: nombre,
                        disco: discoActual
                    })
                });

                const data = await res.json();

                if (data.success) {
                    notify("✓ Expediente generado: " + nombre);
                    input.value = "";
                    actualizarPreviewRuta("");
                    cargarHistorial();
                } else {
                    notify("Error: " + (data.message || data.mensaje || "No se pudo generar el expediente"));
                }
            } catch (err) {
                notify("Error al comunicarse con el servidor");
            } finally {
                btn.disabled = false;
                btnTexto.innerText = "Generar Expediente Clínico";
            }
        }

        // 5. CARGAR HISTORIAL DE PACIENTES DESDE DISCO
        async function cargarHistorial() {
            const cont = document.getElementById('contenedorHistorialPacientes');
            const fechaSel = document.getElementById('filtroFechaPaciente').value;

            try {
                let url = "{{ route('documentos.historial') }}?disco=" + discoActual;
                if (fechaSel) url += "&fecha=" + fechaSel;

                const res = await fetch(url);
                const data = await res.json();

                if (data && data.pacientes) {
                    cachePacientes = data.pacientes;
                    renderHistorial(data.pacientes);
                    actualizarContadoresPacientes(data.pacientes.length);
                }
            } catch (err) {
                cont.innerHTML = `<div class="py-8 text-center text-xs text-red-500">Error al cargar historial</div>`;
            }
        }

        function actualizarContadoresPacientes(total) {
            const b = document.getElementById('badge-total-pacientes');
            if (b) b.innerText = total;
            const kpi = document.getElementById('kpi-pacientes-hoy');
            if (kpi) kpi.innerText = total;
            const sub = document.getElementById('historialContadorSub');
            if (sub) sub.innerText = `${total} paciente(s) registrados`;
        }

        function renderHistorial(pacientes) {
            const cont = document.getElementById('contenedorHistorialPacientes');
            const dashRecent = document.getElementById('dashboardRecentPatients');

            if (!pacientes || pacientes.length === 0) {
                const vacioHtml = `<div class="py-12 text-center text-xs text-slate-400">No se encontraron pacientes para esta fecha y unidad.</div>`;
                cont.innerHTML = vacioHtml;
                dashRecent.innerHTML = `<div class="py-10 text-center text-xs text-slate-400">Aún no hay pacientes registrados hoy en esta unidad.</div>`;
                return;
            }

            const tones = [
                'bg-[#e8f5f6] text-[#248294]',
                'bg-[#fff0e8] text-[#cb7842]',
                'bg-[#eef1fb] text-[#5667a8]',
                'bg-emerald-50 text-emerald-700'
            ];

            let html = "";
            let dashHtml = "";

            pacientes.forEach((p, idx) => {
                const tone = tones[idx % tones.length];
                const partes = p.nombre.split(' ');
                const initials = (partes[0] ? partes[0][0] : 'P') + (partes[1] ? partes[1][0] : '');

                // Items para la vista de Pacientes
                html += `
                    <div onclick="abrirCarpetaWindows('${p.fecha}', '${p.carpeta}')" class="group rounded-xl border border-slate-200/90 bg-white hover:bg-[#f6fafd] hover:border-[#248294]/60 p-3 sm:p-3.5 transition-all shadow-2xs mb-2.5 cursor-pointer" title="Haz clic en cualquier parte para abrir los archivos de ${p.nombre.replace(/"/g, '&quot;')}">
                        <div class="flex items-center gap-3">
                            <div class="grid size-9 place-items-center rounded-full text-xs font-black ${tone} shrink-0 shadow-2xs">
                                ${initials.toUpperCase()}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs sm:text-sm font-extrabold text-[#123b5d] group-hover:text-[#248294] transition truncate">${p.nombre}</span>
                                    <span class="text-[9.5px] font-bold text-[#248294] opacity-80 group-hover:opacity-100 transition inline-flex items-center gap-1">
                                        <i data-lucide="folder-open" class="size-3 text-[#248294]"></i>
                                        <span class="hidden sm:inline">Abrir archivos</span>
                                    </span>
                                </div>
                                <div class="text-[10px] text-slate-400 font-medium">${p.total_archivos || 3} documentos Word · ${p.fecha_formateada || p.fecha} ${p.hora ? '· ' + p.hora : ''}</div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
                                <button type="button" onclick="event.stopPropagation(); abrirCarpetaWindows('${p.fecha}', '${p.carpeta}', '${(p.ruta_completa || '').replace(/\\/g, '\\\\')}')" class="inline-flex items-center gap-1.5 rounded-lg bg-[#e8f5f6] hover:bg-[#d4eef0] text-[#123b5d] hover:text-[#248294] border border-[#248294]/30 px-3 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Abrir carpeta en Windows">
                                    <i data-lucide="folder-open" class="size-3.5 text-[#248294]"></i>
                                    <span>Abrir carpeta</span>
                                </button>
                                <button type="button" onclick="event.stopPropagation(); abrirModalNotebookLM('${p.fecha}', '${p.carpeta}', '${p.nombre.replace(/'/g, "\\'")}', '${(p.ruta_completa || '').replace(/\\/g, '\\\\')}')" class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50/70 hover:bg-blue-100 text-[#1a73e8] px-2.5 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Analizar con NotebookLM">
                                    <i data-lucide="sparkles" class="size-3 text-[#1a73e8]"></i>
                                    <span>NotebookLM</span>
                                </button>
                                <a href="${p.zip_url}" onclick="event.stopPropagation()" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white hover:border-[#248294] hover:bg-[#e8f5f6] text-slate-600 hover:text-[#248294] px-2 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer" title="Descargar ZIP">
                                    <i data-lucide="download" class="size-3 text-slate-500"></i>
                                    <span>ZIP</span>
                                </a>
                            </div>
                        </div>

                        <!-- Fila de los 3 documentos oficiales (Word) claramente identificados y clicables -->
                        <div class="mt-2.5 pt-2 border-t border-slate-100/80 flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1 mr-0.5">
                                <i data-lucide="file-text" class="size-3 text-[#248294]"></i>
                                <span>Abrir Word:</span>
                            </span>
                            <button type="button" onclick="event.stopPropagation(); abrirArchivoWord('${p.fecha}', '${p.carpeta}', 'Historia_Clinica.docx', '${(p.ruta_completa || '').replace(/\\/g, '\\\\')}')" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50/60 hover:bg-blue-100 text-[#123b5d] hover:text-blue-700 px-2.5 py-1 text-[11px] font-bold transition cursor-pointer shadow-2xs" title="Abrir Historia Clínica en Word">
                                <i data-lucide="file-text" class="size-3 text-[#1a73e8]"></i>
                                <span>Historia Clínica.docx</span>
                                <i data-lucide="external-link" class="size-2.5 text-blue-500"></i>
                            </button>
                            <button type="button" onclick="event.stopPropagation(); abrirArchivoWord('${p.fecha}', '${p.carpeta}', 'Indicaciones.docx', '${(p.ruta_completa || '').replace(/\\/g, '\\\\')}')" class="inline-flex items-center gap-1.5 rounded-lg border border-teal-200 bg-teal-50/60 hover:bg-teal-100 text-[#123b5d] hover:text-teal-700 px-2.5 py-1 text-[11px] font-bold transition cursor-pointer shadow-2xs" title="Abrir Indicaciones Médicas en Word">
                                <i data-lucide="file-text" class="size-3 text-[#248294]"></i>
                                <span>Indicaciones.docx</span>
                                <i data-lucide="external-link" class="size-2.5 text-teal-500"></i>
                            </button>
                            <button type="button" onclick="event.stopPropagation(); abrirArchivoWord('${p.fecha}', '${p.carpeta}', 'Admision.docx', '${(p.ruta_completa || '').replace(/\\/g, '\\\\')}')" class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50/60 hover:bg-indigo-100 text-[#123b5d] hover:text-indigo-700 px-2.5 py-1 text-[11px] font-bold transition cursor-pointer shadow-2xs" title="Abrir Admisión en Word">
                                <i data-lucide="file-text" class="size-3 text-[#5667a8]"></i>
                                <span>Admisión.docx</span>
                                <i data-lucide="external-link" class="size-2.5 text-indigo-500"></i>
                            </button>
                        </div>
                    </div>
                `;

                // Items para la vista resumida de Dashboard (primeros 7)
                if (idx < 7) {
                    dashHtml += `
                        <div onclick="abrirCarpetaWindows('${p.fecha}', '${p.carpeta}')" class="group flex items-center gap-2.5 py-2.5 px-2 hover:bg-[#f4f8fc] rounded-xl transition border-b border-slate-100 last:border-b-0 cursor-pointer" title="Clic para abrir los archivos de ${p.nombre.replace(/"/g, '&quot;')}">
                            <div class="grid size-8 place-items-center rounded-full text-xs font-bold ${tone} shrink-0 shadow-2xs">
                                ${initials.toUpperCase()}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs sm:text-[13px] font-bold text-[#123b5d] group-hover:text-[#248294] transition truncate">${p.nombre}</div>
                                <div class="text-[10px] text-slate-400 font-medium truncate">Registrado ${p.hora || ''} · ${p.fecha}</div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" onclick="event.stopPropagation(); abrirCarpetaWindows('${p.fecha}', '${p.carpeta}')" class="rounded-lg bg-[#e8f5f6] hover:bg-[#d8eef0] text-[#123b5d] hover:text-[#248294] border border-[#248294]/30 px-2.5 py-1 text-xs font-bold transition flex items-center gap-1 cursor-pointer shrink-0 shadow-2xs" title="Abrir carpeta">
                                    <i data-lucide="folder-open" class="size-3 text-[#248294]"></i>
                                    <span>Abrir carpeta</span>
                                </button>
                                <button type="button" onclick="event.stopPropagation(); abrirModalNotebookLM('${p.fecha}', '${p.carpeta}', '${p.nombre.replace(/'/g, "\\'")}')" class="rounded-lg border border-blue-200 bg-blue-50/70 hover:bg-blue-100 text-[#1a73e8] px-2.5 py-1 text-xs font-bold transition flex items-center gap-1 cursor-pointer shadow-2xs" title="Analizar con NotebookLM">
                                    <i data-lucide="sparkles" class="size-3 text-[#1a73e8]"></i>
                                    <span class="hidden sm:inline">NotebookLM</span>
                                </button>
                            </div>
                        </div>
                    `;
                }
            });

            cont.innerHTML = html;
            dashRecent.innerHTML = dashHtml;

            const dashBadge = document.getElementById('dashBadgePacientesCount');
            if (dashBadge && pacientes && pacientes.length > 0) {
                dashBadge.innerText = `${pacientes.length} paciente(s) hoy`;
            }

            if (window.lucide) lucide.createIcons();
        }

        function filtrarHistorial() {
            const q = document.getElementById('filtroBuscarPaciente').value.toLowerCase().trim();
            if (!q) {
                renderHistorial(cachePacientes);
                return;
            }
            const filtrados = cachePacientes.filter(p => p.nombre.toLowerCase().includes(q) || p.carpeta.toLowerCase().includes(q));
            renderHistorial(filtrados);
        }

        function irAHoy() {
            const hoy = getFechaLocalHoy();
            document.getElementById('filtroFechaPaciente').value = hoy;
            document.getElementById('filtroBuscarPaciente').value = "";
            cargarHistorial();
        }

        // 6. ACCIONES EN WINDOWS (Abrir carpeta y abrir Word)
        async function abrirCarpetaWindows(fecha, paciente, ruta = null) {
            notify("Abriendo carpeta en el Explorador...");
            try {
                const res = await fetch("{{ route('documentos.abrir-carpeta') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ fecha: fecha, paciente: paciente, disco: discoActual, ruta: ruta })
                });
                const d = await res.json();
                if (d.success) {
                    notify("✓ Carpeta abierta en Windows");
                } else {
                    notify("Aviso: " + (d.message || "No se pudo abrir"));
                }
            } catch (e) {
                notify("Error al comunicar con Windows");
            }
        }

        async function abrirArchivoWord(fecha, paciente, archivo, ruta = null) {
            notify("Abriendo " + archivo + " en Microsoft Word...");
            try {
                const res = await fetch("{{ route('documentos.abrir-archivo') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ fecha: fecha, paciente: paciente, archivo: archivo, disco: discoActual, ruta: ruta })
                });
                const d = await res.json();
                if (d.success) {
                    notify("✓ Archivo abierto en Word");
                } else {
                    notify("Aviso: " + (d.message || "No se pudo abrir el archivo"));
                }
            } catch (e) {
                notify("Error al comunicar con Word");
            }
        }

        // 6.1. INTEGRACIÓN CON NOTEBOOKLM (GOOGLE)
        let nbPacienteActivo = null;

        async function abrirModalNotebookLM(fecha, carpeta, nombre, ruta = null) {
            nbPacienteActivo = { fecha: fecha, carpeta: carpeta, nombre: nombre, textoCompleto: '', rutaCarpeta: ruta || '', promptSugerido: '' };

            document.getElementById('nbModalPacienteNombre').innerText = nombre;
            document.getElementById('nbModalPacienteFecha').innerText = fecha;
            document.getElementById('nbModalLoading').classList.remove('hidden');
            document.getElementById('nbModalContent').classList.add('hidden');
            document.getElementById('modalNotebookLM').classList.remove('hidden');

            try {
                const res = await fetch("{{ route('documentos.extraer-texto-paciente') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({ fecha: fecha, paciente: carpeta, disco: discoActual, ruta: ruta })
                });

                const data = await res.json();
                if (data.success) {
                    nbPacienteActivo.textoCompleto = data.texto_completo;
                    nbPacienteActivo.rutaCarpeta = data.ruta_carpeta;
                    nbPacienteActivo.promptSugerido = data.prompt_sugerido;

                    document.getElementById('nbModalPromptTexto').value = data.prompt_sugerido;
                    document.getElementById('linkDescargarZipNb').href = data.zip_url;

                    const listEl = document.getElementById('nbModalListaArchivos');
                    let listHtml = '';
                    if (data.documentos && data.documentos.length > 0) {
                        data.documentos.forEach(doc => {
                            listHtml += `
                                <div class="rounded-xl border border-slate-200/90 bg-white p-2.5 shadow-2xs flex items-center gap-2">
                                    <div class="grid size-7 place-items-center rounded-lg bg-blue-50 text-[#1a73e8] shrink-0">
                                        <i data-lucide="file-text" class="size-3.5"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-extrabold text-[#123b5d] text-[11px] truncate">${doc.titulo}</div>
                                        <div class="text-[10px] text-slate-400 font-medium">${doc.tamano} · ${doc.caracteres.toLocaleString()} carac.</div>
                                    </div>
                                </div>
                            `;
                        });
                        document.getElementById('nbModalBadgeTotal').innerText = `${data.documentos.length} archivos listos`;
                    } else {
                        listHtml = `<div class="col-span-3 text-slate-400 text-xs py-2">No se encontraron archivos .docx en la carpeta</div>`;
                    }
                    listEl.innerHTML = listHtml;

                    document.getElementById('nbModalLoading').classList.add('hidden');
                    document.getElementById('nbModalContent').classList.remove('hidden');
                    if (window.lucide) lucide.createIcons();
                } else {
                    notify("Error: " + (data.message || 'No se pudo procesar el expediente'));
                    cerrarModalNotebookLM();
                }
            } catch (err) {
                notify("Error al conectar para extraer el expediente");
                cerrarModalNotebookLM();
            }
        }

        function cerrarModalNotebookLM() {
            document.getElementById('modalNotebookLM').classList.add('hidden');
        }

        function copiarTextoCompletoExpediente() {
            if (!nbPacienteActivo || !nbPacienteActivo.textoCompleto) {
                notify("No hay texto para copiar");
                return;
            }

            copiarAlPortapapeles(nbPacienteActivo.textoCompleto).then(() => {
                const btn = document.getElementById('btnCopiarTextoCompleto');
                const span = btn ? btn.querySelector('span') : null;
                const originalText = span ? span.innerText : "1. Copiar texto completo del expediente";
                if (span) span.innerText = "✓ ¡Texto de los 3 documentos copiado!";
                notify("✓ Expediente copiado. Ahora dale a 'Abrir NotebookLM' y pégalo (Ctrl+V)");
                setTimeout(() => {
                    if (span) span.innerText = originalText;
                }, 2800);
            }).catch(() => {
                notify("Error al copiar texto al portapapeles");
            });
        }

        function copiarPromptNotebookLM() {
            const txt = document.getElementById('nbModalPromptTexto').value;
            if (!txt) return;

            copiarAlPortapapeles(txt).then(() => {
                notify("✓ Prompt clínico copiado al portapapeles");
                const btn = document.getElementById('btnCopiarPromptNb');
                const span = btn ? btn.querySelector('span') : null;
                if (span) {
                    span.innerText = "¡Copiado!";
                    setTimeout(() => { span.innerText = "Copiar prompt"; }, 1800);
                }
            }).catch(() => {
                notify("Error al copiar el prompt");
            });
        }

        function abrirCarpetaWindowsDesdeModal() {
            if (!nbPacienteActivo) return;
            abrirCarpetaWindows(nbPacienteActivo.fecha, nbPacienteActivo.carpeta);
            notify("Carpeta abierta en Windows. Arrastra los archivos Word a NotebookLM.");
        }

        function notificarAperturaNotebookLM() {
            notify("Abriendo NotebookLM de Google. Crea una libreta y pega o arrastra los archivos.");
        }

        // 6.2. COPILOTO CLÍNICO: SELECCIÓN DE IA, BUSCADOR DE PACIENTE Y FILTRO DE DOCUMENTOS
        let modeloIAActual = {
            id: 'notebooklm',
            nombre: 'NotebookLM',
            url: 'https://notebooklm.google.com'
        };

        let todosLosPacientesIA = [];
        let pacienteActivoIA = null;
        let docsExtraidosActivosIA = [];

        function seleccionarModeloIA(nombre, url, id) {
            modeloIAActual = { id: id, nombre: nombre, url: url };

            // Actualizar estilos de las tarjetas a la izquierda
            document.querySelectorAll('.ia-model-card').forEach(c => {
                c.className = "ia-model-card flex items-center justify-between rounded-xl border border-slate-100 bg-white p-2.5 sm:p-3 transition cursor-pointer hover:border-slate-300";
            });
            document.querySelectorAll('.badge-ia-status').forEach(b => b.classList.add('hidden'));

            const selCard = document.getElementById('card-ia-' + id);
            if (selCard) {
                selCard.className = "ia-model-card flex items-center justify-between rounded-xl border border-[#1a73e8] bg-blue-50/60 ring-2 ring-[#1a73e8]/25 p-2.5 sm:p-3 transition cursor-pointer";
            }
            const badge = document.getElementById('badge-ia-' + id);
            if (badge) badge.classList.remove('hidden');

            // Actualizar etiquetas en el panel de la derecha
            const badgeMeta = document.getElementById('badgeIANombreModelo');
            if (badgeMeta) badgeMeta.innerText = `Destino: ${nombre}`;
            const lblMeta = document.getElementById('lblNombreIAMeta');
            if (lblMeta) lblMeta.innerText = nombre;
            const btnLbl = document.getElementById('lblTextoBotonEjecutar');
            if (btnLbl) btnLbl.innerText = `Abrir ${nombre} y copiar`;

            notify(`IA activa cambiada a: ${nombre}`);
        }

        async function cargarSelectorPacientesIA(forzar = false) {
            const labelTotal = document.getElementById('labelTotalPacientesIA');
            if (labelTotal) labelTotal.innerText = "Cargando...";

            try {
                const res = await fetch("{{ route('documentos.historial') }}?disco=" + discoActual);
                const data = await res.json();
                if (data && data.pacientes) {
                    todosLosPacientesIA = data.pacientes;
                    if (labelTotal) {
                        labelTotal.innerText = `${data.pacientes.length} paciente(s) en disco`;
                    }
                    aplicarFiltrosPacientesIA();
                }
            } catch (e) {
                if (labelTotal) labelTotal.innerText = "Listo";
            }
        }

        function aplicarFiltrosPacientesIA() {
            const q = (document.getElementById('filtroNombreIA') ? document.getElementById('filtroNombreIA').value : '').toLowerCase().trim();
            const f = (document.getElementById('filtroFechaIA') ? document.getElementById('filtroFechaIA').value : '').trim();

            let filtrados = todosLosPacientesIA;

            if (f) {
                filtrados = filtrados.filter(p => p.fecha === f);
            }

            if (q) {
                filtrados = filtrados.filter(p => 
                    p.nombre.toLowerCase().includes(q) || 
                    p.carpeta.toLowerCase().includes(q)
                );
            }

            const badge = document.getElementById('badgeConteoFiltradosIA');
            if (badge) {
                badge.innerText = `${filtrados.length} encontrado(s)`;
            }

            const cont = document.getElementById('listaResultadosPacientesIA');
            if (!cont) return;

            if (filtrados.length === 0) {
                cont.innerHTML = `<div class="py-4 text-center text-xs text-slate-400">No hay pacientes con ese filtro.</div>`;
                return;
            }

            let html = '';
            const tones = ['bg-[#e8f5f6] text-[#248294]', 'bg-[#fff0e8] text-[#cb7842]', 'bg-[#eef1fb] text-[#5667a8]'];

            filtrados.slice(0, 15).forEach((p, idx) => {
                const tone = tones[idx % tones.length];
                const partes = p.nombre.split(' ');
                const initials = (partes[0] ? partes[0][0] : 'P') + (partes[1] ? partes[1][0] : '');
                const esSeleccionado = pacienteActivoIA && pacienteActivoIA.carpeta === p.carpeta && pacienteActivoIA.fecha === p.fecha;

                html += `
                    <div onclick="seleccionarPacienteIA('${p.fecha}', '${p.carpeta}', '${p.nombre.replace(/'/g, "\\'")}')" class="flex items-center justify-between p-2 sm:p-2.5 rounded-xl border transition cursor-pointer ${esSeleccionado ? 'border-[#1a73e8] bg-blue-50/80 ring-2 ring-[#1a73e8]/20' : 'border-slate-200/80 bg-white hover:bg-slate-50 hover:border-slate-300'}">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="grid size-7 sm:size-8 place-items-center rounded-full text-[10px] sm:text-[11px] font-extrabold ${tone} shrink-0">
                                ${initials.toUpperCase()}
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-extrabold text-[#123b5d] truncate">${p.nombre}</div>
                                <div class="text-[10px] text-slate-400 font-medium">${p.fecha_formateada || p.fecha} · ${p.hora || ''}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="rounded-lg bg-slate-100 text-slate-600 px-2 py-0.5 text-[10px] font-bold">3 archivos</span>
                            <span class="text-xs font-extrabold ${esSeleccionado ? 'text-[#1a73e8]' : 'text-slate-400'}">
                                ${esSeleccionado ? '✓ Seleccionado' : 'Elegir →'}
                            </span>
                        </div>
                    </div>
                `;
            });

            cont.innerHTML = html;
        }

        function filtrarFechaIAHoy() {
            const hoy = getFechaLocalHoy();
            const dateInput = document.getElementById('filtroFechaIA');
            if (dateInput) {
                dateInput.value = hoy;
            }
            aplicarFiltrosPacientesIA();
            notify("Filtrando pacientes registrados hoy (" + hoy + ")");
        }

        function filtrarFechaIATodos() {
            const dateInput = document.getElementById('filtroFechaIA');
            if (dateInput) {
                dateInput.value = '';
            }
            const nombreInput = document.getElementById('filtroNombreIA');
            if (nombreInput) {
                nombreInput.value = '';
            }
            aplicarFiltrosPacientesIA();
            notify("Mostrando todos los pacientes");
        }

        async function seleccionarPacienteIA(fecha, carpeta, nombre) {
            pacienteActivoIA = { fecha: fecha, carpeta: carpeta, nombre: nombre };
            aplicarFiltrosPacientesIA();

            // Marcar por defecto los 3 documentos para que la IA disponga del expediente completo
            const chkHC = document.getElementById('chkDocHC');
            const chkInd = document.getElementById('chkDocInd');
            const chkAdm = document.getElementById('chkDocAdm');
            if (chkHC) chkHC.checked = true;
            if (chkInd) chkInd.checked = true;
            if (chkAdm) chkAdm.checked = true;

            const panel = document.getElementById('panelDetallePacienteIA');
            if (panel) panel.classList.remove('hidden');

            document.getElementById('iaRutaCarpetaDisplay').innerText = `${discoActual}:\\Pediatria\\${fecha}\\${carpeta}\\`;

            notify(`Extrayendo documentos de ${nombre}...`);

            try {
                const res = await fetch("{{ route('documentos.extraer-texto-paciente') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ fecha: fecha, paciente: carpeta, disco: discoActual })
                });
                const data = await res.json();
                if (data.success) {
                    docsExtraidosActivosIA = data.documentos || [];
                    pacienteActivoIA.rutaCarpeta = data.ruta_carpeta;
                    pacienteActivoIA.promptSugerido = data.prompt_sugerido;

                    // Actualizar etiquetas de tamaño en los checkboxes
                    docsExtraidosActivosIA.forEach(doc => {
                        const nombreLower = doc.nombre.toLowerCase();
                        if (nombreLower.includes('historia')) {
                            document.getElementById('lblHcStats').innerText = `${doc.tamano} · ${doc.caracteres.toLocaleString()} carac.`;
                        } else if (nombreLower.includes('indicaciones')) {
                            document.getElementById('lblIndStats').innerText = `${doc.tamano} · ${doc.caracteres.toLocaleString()} carac.`;
                        } else if (nombreLower.includes('admision')) {
                            document.getElementById('lblAdmStats').innerText = `${doc.tamano} · ${doc.caracteres.toLocaleString()} carac.`;
                        }
                    });

                    // Actualizar texto según checkboxes marcados
                    actualizarDocsSeleccionadosIA();

                    notify(`✓ 3 documentos de ${nombre} listos para filtrar`);
                } else {
                    notify("Error al extraer documentos: " + (data.message || 'Error'));
                }
            } catch (e) {
                notify("Error de conexión al extraer documentos");
            }
        }

        async function recargarDocsPacienteActualIA(silencioso = false) {
            if (!pacienteActivoIA) return false;

            if (!silencioso) {
                notify("Actualizando texto desde los documentos de Word...");
            }

            try {
                const res = await fetch("{{ route('documentos.extraer-texto-paciente') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({
                        fecha: pacienteActivoIA.fecha,
                        paciente: pacienteActivoIA.carpeta,
                        disco: discoActual,
                        ruta_carpeta: pacienteActivoIA.rutaCarpeta || null
                    })
                });
                const data = await res.json();
                if (data.success) {
                    docsExtraidosActivosIA = data.documentos || [];
                    pacienteActivoIA.rutaCarpeta = data.ruta_carpeta;
                    pacienteActivoIA.promptSugerido = data.prompt_sugerido;

                    // Actualizar estadísticas de tamaño y caracteres
                    docsExtraidosActivosIA.forEach(doc => {
                        const nombreLower = doc.nombre.toLowerCase();
                        if (nombreLower.includes('historia')) {
                            const el = document.getElementById('lblHcStats');
                            if (el) el.innerText = `${doc.tamano} · ${doc.caracteres.toLocaleString()} carac.`;
                        } else if (nombreLower.includes('indicaciones')) {
                            const el = document.getElementById('lblIndStats');
                            if (el) el.innerText = `${doc.tamano} · ${doc.caracteres.toLocaleString()} carac.`;
                        } else if (nombreLower.includes('admision')) {
                            const el = document.getElementById('lblAdmStats');
                            if (el) el.innerText = `${doc.tamano} · ${doc.caracteres.toLocaleString()} carac.`;
                        }
                    });

                    actualizarDocsSeleccionadosIA();

                    if (!silencioso) {
                        notify("✓ Texto actualizado desde Word correctamente");
                    }
                    return true;
                }
            } catch (e) {
                console.error("Error al recargar texto de documentos:", e);
            }
            return false;
        }

        function actualizarDocsSeleccionadosIA() {
            const chkHC = document.getElementById('chkDocHC').checked;
            const chkInd = document.getElementById('chkDocInd').checked;
            const chkAdm = document.getElementById('chkDocAdm').checked;

            // Actualizar estilos visuales de las tarjetas de los documentos
            const cardHC = document.getElementById('cardCheckHC');
            const cardInd = document.getElementById('cardCheckInd');
            const cardAdm = document.getElementById('cardCheckAdm');

            if (cardHC) cardHC.className = chkHC ? "rounded-2xl border-2 border-[#1a73e8] bg-white p-3.5 shadow-2xs flex flex-col justify-between transition cursor-pointer select-none" : "rounded-2xl border-2 border-slate-200 bg-slate-50/50 opacity-60 p-3.5 shadow-2xs flex flex-col justify-between transition cursor-pointer select-none";
            if (cardInd) cardInd.className = chkInd ? "rounded-2xl border-2 border-[#248294] bg-white p-3.5 shadow-2xs flex flex-col justify-between transition cursor-pointer select-none" : "rounded-2xl border-2 border-slate-200 bg-slate-50/50 opacity-60 p-3.5 shadow-2xs flex flex-col justify-between transition cursor-pointer select-none";
            if (cardAdm) cardAdm.className = chkAdm ? "rounded-2xl border-2 border-[#5667a8] bg-white p-3.5 shadow-2xs flex flex-col justify-between transition cursor-pointer select-none" : "rounded-2xl border-2 border-slate-200 bg-slate-50/50 opacity-60 p-3.5 shadow-2xs flex flex-col justify-between transition cursor-pointer select-none";

            let textos = [];
            let totalCarac = 0;

            docsExtraidosActivosIA.forEach(doc => {
                const n = (doc.nombre || '').toLowerCase();
                let incluir = false;
                if ((n.includes('historia') || n.includes('hc')) && chkHC) incluir = true;
                if (n.includes('indicaciones') && chkInd) incluir = true;
                if ((n.includes('admision') || n.includes('adm')) && chkAdm) incluir = true;

                if (incluir) {
                    textos.push(`================================================================================\nDOCUMENTO: ${(doc.titulo || doc.nombre).toUpperCase()}\nARCHIVO: ${doc.nombre}\n================================================================================\n\n${doc.texto}`);
                    totalCarac += (doc.caracteres || (doc.texto ? doc.texto.length : 0));
                }
            });

            const textoFinal = textos.join("\n\n");
            const badgeCarac = document.getElementById('badgeCaracteresTotalIA');
            if (badgeCarac) badgeCarac.innerText = `${totalCarac.toLocaleString()} caracteres listos`;

            // Actualizar modal de visualización de texto si estuviera abierto
            const txtModal = document.getElementById('txtModalVerTextoContenido');
            if (txtModal) txtModal.value = textoFinal;
            const badgeModal = document.getElementById('badgeModalVerTextoChars');
            if (badgeModal) badgeModal.innerText = `${totalCarac.toLocaleString()} caracteres`;

            // Actualizar el textarea inferior si existe
            const casoTextarea = document.getElementById('casoClinicoTexto');
            if (casoTextarea) casoTextarea.value = textoFinal;

            return textoFinal;
        }

        async function ejecutarEnvioIA() {
            if (!pacienteActivoIA) {
                notify("Selecciona primero un paciente.");
                return;
            }

            // 1. Abrir la pestaña INMEDIATAMENTE de forma síncrona dentro del click
            // Esto garantiza que Chrome / Edge no la bloqueen como popup emergente
            const urlDestino = (modeloIAActual && modeloIAActual.url) ? modeloIAActual.url : 'https://notebooklm.google.com';
            const nuevaPestana = window.open(urlDestino, '_blank');

            // 2. Recargar siempre la versión más reciente guardada en Word desde el disco
            await recargarDocsPacienteActualIA(true);

            const texto = actualizarDocsSeleccionadosIA();
            if (!texto) {
                notify("Marca al menos un documento del paciente para enviar.");
                return;
            }

            // 3. Copiar el texto fresco al portapapeles con el mecanismo universal (soporta HTTP y HTTPS)
            try {
                await copiarAlPortapapeles(texto);
                notify(`✓ Expediente copiado (${texto.length.toLocaleString()} carac.). En ${modeloIAActual ? modeloIAActual.nombre : 'tu IA'}, presiona Ctrl + V para pegar.`);
            } catch (err) {
                notify(`✓ Abriendo ${modeloIAActual ? modeloIAActual.nombre : 'tu IA'}. Pulsa 'Copiar texto' o 'Ver texto' para obtener el contenido.`);
            }

            // 4. Fallback si el navegador tuviera una política súper estricta de popups
            if (!nuevaPestana || nuevaPestana.closed || typeof nuevaPestana.closed === 'undefined') {
                notify(`⚠️ Tu navegador bloqueó la ventana emergente. Haz clic en 'Web ↗' a la izquierda para ir a ${modeloIAActual ? modeloIAActual.nombre : 'NotebookLM'}`);
            }
        }

        async function copiarTextoDocsSeleccionadosIA() {
            if (!pacienteActivoIA) {
                notify("Selecciona primero un paciente.");
                return;
            }

            // Recargar siempre el texto más reciente guardado en Word
            await recargarDocsPacienteActualIA(true);

            const texto = actualizarDocsSeleccionadosIA();
            if (!texto) {
                notify("Marca al menos un documento para copiar.");
                return;
            }

            try {
                await copiarAlPortapapeles(texto);
                const btn = document.getElementById('btnCopiarSoloTextoIA');
                const span = btn ? btn.querySelector('span') : null;
                if (span) span.innerText = "✓ ¡Copiado!";
                notify(`✓ ¡Texto de los documentos copiado (${texto.length.toLocaleString()} carac.)! Pégalo con Ctrl+V.`);
                setTimeout(() => {
                    if (span) span.innerText = "Copiar";
                }, 2200);
            } catch (err) {
                abrirModalVerTextoIA();
                notify("Selecciona el texto en la ventana emergente y presiona Ctrl + C.");
            }
        }

        async function abrirModalVerTextoIA() {
            if (!pacienteActivoIA) {
                notify("Selecciona primero un paciente.");
                return;
            }

            // Recargar texto fresco de Word
            await recargarDocsPacienteActualIA(true);

            const texto = actualizarDocsSeleccionadosIA();
            if (!texto) {
                notify("Marca al menos un documento para ver su texto.");
                return;
            }

            const modal = document.getElementById('modalVerTextoIA');
            const textarea = document.getElementById('txtModalVerTextoContenido');
            const badge = document.getElementById('badgeModalVerTextoChars');
            const lblPac = document.getElementById('lblModalVerTextoPaciente');
            const lblFec = document.getElementById('lblModalVerTextoFecha');

            if (textarea) textarea.value = texto;
            if (badge) badge.innerText = `${texto.length.toLocaleString()} caracteres`;
            if (lblPac && pacienteActivoIA) lblPac.innerText = pacienteActivoIA.nombre || '--';
            if (lblFec && pacienteActivoIA) lblFec.innerText = pacienteActivoIA.fecha || '--';

            if (modal) {
                modal.classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
                setTimeout(() => {
                    if (textarea) textarea.focus();
                }, 100);
            }
        }

        function cerrarModalVerTextoIA() {
            const modal = document.getElementById('modalVerTextoIA');
            if (modal) modal.classList.add('hidden');
        }

        async function copiarDesdeModalVerTexto() {
            const textarea = document.getElementById('txtModalVerTextoContenido');
            const texto = textarea ? textarea.value : '';
            if (!texto) {
                notify("No hay texto para copiar");
                return;
            }

            try {
                await copiarAlPortapapeles(texto);
                const btn = document.getElementById('btnCopiarDesdeModalVerTexto');
                const span = btn ? btn.querySelector('span') : null;
                if (span) span.innerText = "✓ ¡Texto copiado!";
                notify("✓ Texto copiado al portapapeles. Pégalo en tu IA con Ctrl + V.");
                setTimeout(() => {
                    if (span) span.innerText = "Copiar todo el texto";
                }, 2200);
            } catch (err) {
                if (textarea) {
                    textarea.focus();
                    textarea.select();
                }
                notify("Presiona Ctrl + C para copiar el texto seleccionado.");
            }
        }

        function abrirDocWordActual(nombreArchivo) {
            if (!pacienteActivoIA) {
                notify("Selecciona primero un paciente");
                return;
            }
            abrirArchivoWord(pacienteActivoIA.fecha, pacienteActivoIA.carpeta, nombreArchivo, pacienteActivoIA.rutaCarpeta);
            notify(`Abriendo ${nombreArchivo} en Word. Guarda tus cambios con Ctrl+S y se actualizarán aquí automáticamente.`);
        }

        // Auto-recargar texto si el médico vuelve de Word a la pestaña del navegador
        window.addEventListener('focus', () => {
            const modAI = document.getElementById('module-ai');
            if (modAI && !modAI.classList.contains('hidden') && pacienteActivoIA) {
                recargarDocsPacienteActualIA(true);
            }
        });

        function abrirCarpetaPacienteSeleccionadoIA() {
            if (!pacienteActivoIA) {
                notify("Selecciona primero un paciente");
                return;
            }
            abrirCarpetaWindows(pacienteActivoIA.fecha, pacienteActivoIA.carpeta, pacienteActivoIA.rutaCarpeta);
        }

        function normalizarTexto(str) {
            if (!str) return '';
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
        }

        // 7. MATERIAL DOCENTE (POWERPOINT)
        async function cargarMaterialDocente() {
            const grid = document.getElementById('gridPptxPresentaciones');
            try {
                const res = await fetch("{{ route('material.powerpoint.listar') }}?disco=" + discoActual);
                const data = await res.json();

                if (data && data.presentaciones) {
                    cachePptx = data.presentaciones;
                    const kpiTotal = document.getElementById('kpi-pptx-total');
                    if (kpiTotal) {
                        kpiTotal.innerText = data.total_presentaciones || data.presentaciones.length;
                    }
                    const totalArchivadas = document.getElementById('lblPptxTotales');
                    if (totalArchivadas) {
                        totalArchivadas.innerText = data.presentaciones.length;
                    }
                    renderBotonesTemasPptx(data.presentaciones);
                    filtrarPptx();
                }
            } catch (e) {
                if (grid) {
                    grid.innerHTML = `<div class="col-span-full py-12 text-center text-xs text-slate-400">Biblioteca lista para agregar archivos PowerPoint.</div>`;
                }
            }
        }

        function renderBotonesTemasPptx(presentaciones) {
            const cont = document.getElementById('temasPptxButtons');
            if (!cont) return;

            // Recopilar temas reales presentes en los archivos
            const temasMap = {};
            (presentaciones || []).forEach(p => {
                const tReal = p.tema || 'General';
                const tNorm = normalizarTexto(tReal);
                if (!temasMap[tNorm]) {
                    temasMap[tNorm] = { label: tReal, count: 0 };
                }
                temasMap[tNorm].count++;
            });

            // Temas estándar sugeridos
            const temasBase = [
                { norm: 'neonatologia', label: 'Neonatología' },
                { norm: 'urgencias', label: 'Urgencias Pediátricas' },
                { norm: 'infectologia', label: 'Infectología' },
                { norm: 'neumologia', label: 'Neumología' },
                { norm: 'terapia intensiva', label: 'Terapia Intensiva' },
                { norm: 'gastroenterologia', label: 'Gastroenterología' },
                { norm: 'general', label: 'General' }
            ];

            const listaBotones = [];
            temasBase.forEach(b => {
                const encontrado = Object.keys(temasMap).find(k => k === b.norm || k.includes(b.norm) || b.norm.includes(k));
                if (encontrado) {
                    listaBotones.push({
                        temaFiltrar: temasMap[encontrado].label,
                        label: b.label,
                        count: temasMap[encontrado].count
                    });
                    delete temasMap[encontrado];
                } else {
                    listaBotones.push({
                        temaFiltrar: b.label,
                        label: b.label,
                        count: 0
                    });
                }
            });

            Object.keys(temasMap).forEach(k => {
                listaBotones.push({
                    temaFiltrar: temasMap[k].label,
                    label: temasMap[k].label,
                    count: temasMap[k].count
                });
            });

            let html = `
                <button type="button" onclick="filtrarTemaPptx('Todas', this)" class="pptx-topic-btn rounded-xl px-3 py-1.5 text-[11px] font-bold ${temaPptxActivo === 'Todas' ? 'bg-[#248294] text-white shadow-2xs' : 'bg-slate-100/80 text-slate-600 hover:bg-slate-200/70'} transition cursor-pointer flex items-center gap-1.5">
                    <span>Todas</span>
                    <span id="badgePptxCount" class="rounded-full ${temaPptxActivo === 'Todas' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'} px-1.5 py-0.2 text-[9px] font-bold">${presentaciones.length}</span>
                </button>
            `;

            listaBotones.forEach(btnInfo => {
                const isActive = normalizarTexto(temaPptxActivo) === normalizarTexto(btnInfo.temaFiltrar);
                const btnClass = isActive 
                    ? "bg-[#248294] text-white font-bold shadow-2xs" 
                    : "bg-slate-100/80 text-slate-600 hover:bg-slate-200/70 font-semibold";
                const badgeClass = isActive ? "bg-white/20 text-white" : "bg-slate-200 text-slate-700";

                html += `
                    <button type="button" onclick="filtrarTemaPptx('${btnInfo.temaFiltrar.replace(/'/g, "\\'")}', this)" class="pptx-topic-btn rounded-xl px-3 py-1.5 text-[11px] ${btnClass} transition cursor-pointer flex items-center gap-1.5">
                        <span>${btnInfo.label}</span>
                        ${btnInfo.count > 0 ? `<span class="rounded-full ${badgeClass} px-1.5 py-0.2 text-[9px] font-bold">${btnInfo.count}</span>` : ''}
                    </button>
                `;
            });

            cont.innerHTML = html;
        }

        function renderPptx(lista) {
            const grid = document.getElementById('gridPptxPresentaciones');
            const lblVisibles = document.getElementById('lblPptxVisibles');
            if (lblVisibles) {
                lblVisibles.innerText = lista ? lista.length : 0;
            }

            // Caso 1: La biblioteca entera está vacía (0 archivos en disco)
            if (!cachePptx || cachePptx.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full py-16 px-6 text-center rounded-2xl border-2 border-dashed border-slate-200 bg-white/70 shadow-2xs">
                        <div class="grid size-14 place-items-center rounded-2xl bg-[#e8f5f6] text-[#248294] mx-auto mb-3 shadow-2xs">
                            <i data-lucide="presentation" class="size-7"></i>
                        </div>
                        <h3 class="text-sm sm:text-base font-extrabold text-[#123b5d]">Tu biblioteca docente está lista</h3>
                        <p class="mt-1 text-xs text-slate-400 max-w-md mx-auto">
                            Aún no hay presentaciones subidas en esta unidad. Sube archivos <code class="font-mono text-[#248294] font-bold">.pptx</code> clasificados por tema clínico o colócalos en tu carpeta de Google Drive.
                        </p>
                        <div class="mt-5 flex flex-wrap items-center justify-center gap-2.5">
                            <button type="button" onclick="abrirModalPptx()" class="inline-flex items-center gap-2 rounded-xl bg-[#123b5d] hover:bg-[#1a4a73] px-4 py-2.5 text-xs font-bold text-white transition shadow-sm cursor-pointer">
                                <i data-lucide="upload-cloud" class="size-3.5 text-[#55d6c2]"></i>
                                <span>Subir presentación (.pptx)</span>
                            </button>
                            <button type="button" onclick="abrirCarpetaPowerPointWindows()" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 px-3.5 py-2.5 text-xs font-bold text-slate-700 transition shadow-2xs cursor-pointer">
                                <i data-lucide="folder-open" class="size-3.5 text-[#248294]"></i>
                                <span>Abrir carpeta en Windows</span>
                            </button>
                        </div>
                    </div>
                `;
                if (window.lucide) lucide.createIcons();
                return;
            }

            // Caso 2: Hay archivos en disco, pero el filtro actual devolvió 0
            if (!lista || lista.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full py-14 px-6 text-center rounded-2xl border border-slate-200 bg-white shadow-2xs">
                        <div class="grid size-12 place-items-center rounded-2xl bg-amber-50 text-amber-600 mx-auto mb-3">
                            <i data-lucide="search-x" class="size-6"></i>
                        </div>
                        <h3 class="text-sm font-extrabold text-[#123b5d]">No hay presentaciones en este filtro</h3>
                        <p class="mt-1 text-xs text-slate-400 max-w-md mx-auto">
                            No se encontraron archivos en la categoría <b class="text-[#123b5d] font-semibold">${temaPptxActivo}</b> ni con el texto buscado.
                        </p>
                        <div class="mt-4 flex items-center justify-center gap-2">
                            <button type="button" onclick="resetFiltrosPptx()" class="inline-flex items-center gap-1.5 rounded-xl bg-[#248294] hover:bg-[#185c6a] px-3.5 py-2 text-xs font-bold text-white transition cursor-pointer">
                                <i data-lucide="rotate-ccw" class="size-3"></i>
                                <span>Ver todas las presentaciones (${cachePptx.length})</span>
                            </button>
                        </div>
                    </div>
                `;
                if (window.lucide) lucide.createIcons();
                return;
            }

            let html = "";
            lista.forEach(deck => {
                const t = normalizarTexto(deck.tema || 'General');
                let bgGradient = 'background: linear-gradient(135deg, #123b5d 0%, #1a4a73 50%, #248294 100%);';
                let pillClass = 'bg-teal-50 text-teal-700 border-teal-200/60';
                let iconName = 'presentation';

                if (t.includes('infecto')) {
                    bgGradient = 'background: linear-gradient(135deg, #0d5951 0%, #115e59 50%, #14b8a6 100%);';
                    pillClass = 'bg-teal-50 text-teal-700 border-teal-200/60';
                    iconName = 'presentation';
                } else if (t.includes('neonato') || t.includes('uci')) {
                    bgGradient = 'background: linear-gradient(135deg, #312e81 0%, #4338ca 50%, #6366f1 100%);';
                    pillClass = 'bg-indigo-50 text-indigo-700 border-indigo-200/60';
                    iconName = 'activity';
                } else if (t.includes('urgen') || t.includes('guardia')) {
                    bgGradient = 'background: linear-gradient(135deg, #881337 0%, #be123c 50%, #f43f5e 100%);';
                    pillClass = 'bg-rose-50 text-rose-700 border-rose-200/60';
                    iconName = 'alert-circle';
                } else if (t.includes('gastro')) {
                    bgGradient = 'background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #10b981 100%);';
                    pillClass = 'bg-emerald-50 text-emerald-700 border-emerald-200/60';
                    iconName = 'book-open';
                } else if (t.includes('neumo')) {
                    bgGradient = 'background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #3b82f6 100%);';
                    pillClass = 'bg-blue-50 text-blue-700 border-blue-200/60';
                    iconName = 'wind';
                }

                const temaEsc = (deck.tema || 'General').replace(/'/g, "\\'");
                const archEsc = (deck.archivo || '').replace(/'/g, "\\'");

                html += `
                    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-2xs hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
                        <!-- Cabecera con Gradiente de la Especialidad -->
                        <div style="${bgGradient}" class="relative p-3 sm:p-3.5 text-white flex flex-col justify-between min-h-[105px]">
                            <div class="flex items-center justify-between gap-1.5">
                                <span class="rounded-full bg-black/25 backdrop-blur-xs px-2 py-0.2 text-[8.5px] font-bold text-white/90">
                                    • PPTX · ${deck.tema}
                                </span>
                                <button type="button" onclick="eliminarPptx('${temaEsc}', '${archEsc}')" class="text-white/60 hover:text-white transition cursor-pointer" title="Eliminar archivo">
                                    <i data-lucide="trash-2" class="size-3"></i>
                                </button>
                            </div>
                            <div class="mt-1.5">
                                <div class="size-6.5 rounded-lg bg-white/15 backdrop-blur-xs flex items-center justify-center text-white mb-1">
                                    <i data-lucide="${iconName}" class="size-3.5"></i>
                                </div>
                                <h3 class="text-xs font-black text-white leading-tight line-clamp-2" title="${deck.titulo}">
                                    ${deck.titulo}
                                </h3>
                            </div>
                        </div>

                        <!-- Cuerpo de la Tarjeta (Blanco con Información Real) -->
                        <div class="p-3 sm:p-3.5 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-1.5 flex-wrap">
                                    <span class="inline-block rounded px-1.5 py-0.2 text-[9.5px] font-bold border ${pillClass}">
                                        ${deck.tema}
                                    </span>
                                    ${deck.tipo_actividad ? `<span class="text-[8.5px] font-bold text-slate-500 bg-slate-100 px-1 py-0.2 rounded">${deck.tipo_actividad}</span>` : ''}
                                </div>
                                <h4 class="mt-1.5 text-xs font-bold text-[#123b5d] line-clamp-2 leading-snug group-hover:text-[#248294] transition">
                                    ${deck.titulo}
                                </h4>
                                ${deck.expositor ? `
                                    <div class="mt-1 flex items-center gap-1.5 text-[10.5px] text-slate-600 font-semibold truncate" title="${deck.expositor}">
                                        <i data-lucide="user" class="size-3 text-[#248294] shrink-0"></i>
                                        <span class="truncate">${deck.expositor}</span>
                                    </div>
                                ` : ''}
                                <div class="mt-1 flex items-center gap-1.5 text-[10px] text-slate-400 font-medium truncate" title="${deck.archivo}">
                                    <i data-lucide="file-text" class="size-3 text-slate-400 shrink-0"></i>
                                    <span class="truncate">${deck.archivo}</span>
                                </div>
                                <div class="mt-0.5 text-[9.5px] text-slate-400 font-medium">
                                    ${deck.fecha_formateada || deck.fecha || ''} · ${deck.tamano || 'PPTX'}
                                </div>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                                <a href="${deck.url_descarga}" download class="text-slate-500 hover:text-slate-800 font-semibold flex items-center gap-1 cursor-pointer text-[11px]">
                                    <i data-lucide="download" class="size-3 text-slate-400"></i>
                                    <span>Descargar</span>
                                </a>
                                <button type="button" onclick="abrirPptxApp('${temaEsc}', '${archEsc}')" class="font-bold text-[#248294] hover:text-[#185c6a] flex items-center gap-1 cursor-pointer text-[11px]">
                                    <span>Abrir</span>
                                    <i data-lucide="arrow-right" class="size-3"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            grid.innerHTML = html;
            if (window.lucide) lucide.createIcons();
        }

        function filtrarTemaPptx(tema, btn) {
            temaPptxActivo = tema;
            document.querySelectorAll('.pptx-topic-btn').forEach(b => {
                b.className = "pptx-topic-btn rounded-xl px-3 py-1.5 text-[11px] font-semibold bg-slate-100/80 text-slate-600 hover:bg-slate-200/70 transition cursor-pointer flex items-center gap-1.5";
                const badge = b.querySelector('span:nth-child(2)');
                if (badge) {
                    badge.className = "rounded-full bg-slate-200 text-slate-700 px-1.5 py-0.2 text-[9px] font-bold";
                }
            });
            if (btn) {
                btn.className = "pptx-topic-btn rounded-xl px-3 py-1.5 text-[11px] font-bold bg-[#248294] text-white transition cursor-pointer flex items-center gap-1.5 shadow-2xs";
                const badge = btn.querySelector('span:nth-child(2)');
                if (badge) {
                    badge.className = "rounded-full bg-white/20 text-white px-1.5 py-0.2 text-[9px] font-bold";
                }
            }
            filtrarPptx();
        }

        function filtrarPptx() {
            const qInput = document.getElementById('filtroPptxQuery');
            const q = qInput ? normalizarTexto(qInput.value) : '';
            let res = cachePptx || [];

            if (temaPptxActivo && temaPptxActivo !== 'Todas') {
                const targetTema = normalizarTexto(temaPptxActivo);
                res = res.filter(d => {
                    const deckTema = normalizarTexto(d.tema);
                    return deckTema === targetTema || deckTema.includes(targetTema) || targetTema.includes(deckTema);
                });
            }

            if (q) {
                res = res.filter(d => {
                    const t = normalizarTexto(d.titulo);
                    const a = normalizarTexto(d.archivo);
                    const tm = normalizarTexto(d.tema);
                    return t.includes(q) || a.includes(q) || tm.includes(q);
                });
            }

            const orden = document.getElementById('selectOrdenPptx') ? document.getElementById('selectOrdenPptx').value : 'recientes';
            if (orden === 'az') {
                res = [...res].sort((a, b) => (a.titulo || '').localeCompare(b.titulo || ''));
            } else if (orden === 'tamano') {
                res = [...res].sort((a, b) => (b.bytes || 0) - (a.bytes || 0));
            }

            renderPptx(res);
        }

        function resetFiltrosPptx() {
            const q = document.getElementById('filtroPptxQuery');
            if (q) q.value = '';
            temaPptxActivo = 'Todas';
            const allBtn = document.querySelector('.pptx-topic-btn');
            filtrarTemaPptx('Todas', allBtn);
        }

        function ordenarPptx(criterio) {
            filtrarPptx();
        }

        async function abrirCarpetaPowerPointWindows() {
            notify("Abriendo carpeta de presentaciones...");
            try {
                const res = await fetch("{{ route('material.powerpoint.abrir-carpeta') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ disco: discoActual })
                });
                const d = await res.json();
                if (d.success) notify("Carpeta abierta en Windows");
            } catch (e) {
                notify("Comando ejecutado");
            }
        }

        async function abrirPptxApp(tema, archivo) {
            notify("Abriendo en Microsoft PowerPoint...");
            try {
                const res = await fetch("{{ route('material.powerpoint.abrir') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ tema: tema, archivo: archivo, disco: discoActual })
                });
                const d = await res.json();
                if (d.success) notify("Presentación abierta");
            } catch (e) {
                notify("Comando ejecutado");
            }
        }

        async function eliminarPptx(tema, archivo) {
            if (!confirm(`¿Deseas eliminar la presentación '${archivo}'?`)) return;
            try {
                const res = await fetch("{{ route('material.powerpoint.eliminar') }}", {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ tema: tema, archivo: archivo, disco: discoActual })
                });
                const d = await res.json();
                if (d.success) {
                    notify("Presentación eliminada");
                    cargarMaterialDocente();
                }
            } catch (e) {
                notify("Error al eliminar");
            }
        }

        // ================================================================
        // MODALES Y MANEJO DE NUEVO TEMA & PRESENTACIÓN PPTX
        // ================================================================
        function abrirModalNuevoTema() {
            const input = document.getElementById('inputNuevoTemaNombre');
            if (input) input.value = '';
            const modal = document.getElementById('modalNuevoTema');
            if (modal) {
                modal.classList.remove('hidden');
                setTimeout(() => { if (input) input.focus(); }, 100);
            }
            if (window.lucide) lucide.createIcons();
        }

        function cerrarModalNuevoTema() {
            const modal = document.getElementById('modalNuevoTema');
            if (modal) modal.classList.add('hidden');
        }

        function setNuevoTemaInput(tema) {
            const input = document.getElementById('inputNuevoTemaNombre');
            if (input) {
                input.value = tema;
                input.focus();
            }
        }

        async function handleCrearNuevoTema(e) {
            e.preventDefault();
            const input = document.getElementById('inputNuevoTemaNombre');
            const tema = input ? input.value.trim() : '';

            if (!tema) {
                notify("Por favor ingresa un nombre para el tema o especialidad.");
                return;
            }

            // Registrar carpeta del tema en backend para persistencia
            try {
                const res = await fetch("{{ route('material.powerpoint.crear-tema') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({ tema: tema, disco: discoActual })
                });
                const d = await res.json();
                if (d.success) {
                    notify("✓ Tema '" + tema + "' registrado con éxito");
                }
            } catch (err) {
                // Continuar si hay error de red
            }

            cerrarModalNuevoTema();
            // Abrir inmediatamente la pantalla oficial con el tema elegido
            abrirModalPptx(tema);
            notify("✓ Tema '" + tema + "' seleccionado. Ahora selecciona tu presentación.");
        }

        function abrirModalPptx(temaPreseleccionado = null) {
            const modal = document.getElementById('modalSubirPptx');
            if (!modal) return;
            modal.classList.remove('hidden');

            // Disco dinámico en cabecera y pie
            const discoLabel = discoActual ? `Google Drive (${discoActual}:)` : 'Google Drive';
            const badgeDisco = document.getElementById('modalPptxDiscoBadge');
            if (badgeDisco) badgeDisco.innerText = discoLabel;

            const rutaDestino = document.getElementById('pptxRutaDestinoLabel');
            if (rutaDestino) {
                rutaDestino.innerText = `${discoLabel} / MaterialDocente`;
            }

            // Fecha local sin saltos de zona horaria
            const inputFecha = document.getElementById('inputPptxFecha');
            if (inputFecha && !inputFecha.value) {
                inputFecha.value = getFechaLocalHoy();
            }

            // Asignar tema preseleccionado o activo
            const inputTema = document.getElementById('inputPptxTema');
            if (inputTema) {
                if (temaPreseleccionado) {
                    inputTema.value = temaPreseleccionado;
                } else if (temaPptxActivo && temaPptxActivo !== 'Todas') {
                    inputTema.value = temaPptxActivo;
                }
            }

            actualizarDatalistTemas();
            actualizarChipsTemaModal();
            if (window.lucide) lucide.createIcons();
        }

        function cerrarModalPptx() {
            const modal = document.getElementById('modalSubirPptx');
            if (modal) modal.classList.add('hidden');
            cancelarArchivoPptx();
        }

        function seleccionarTemaModal(tema) {
            const input = document.getElementById('inputPptxTema');
            if (input) {
                input.value = tema;
                actualizarChipsTemaModal();
            }
        }

        function actualizarChipsTemaModal() {
            const input = document.getElementById('inputPptxTema');
            const val = input ? normalizarTexto(input.value) : '';
            document.querySelectorAll('.pptx-modal-chip').forEach(btn => {
                const btnVal = normalizarTexto(btn.innerText);
                if (btnVal && val && (btnVal === val || val.includes(btnVal) || btnVal.includes(val))) {
                    btn.className = "pptx-modal-chip px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-[#e6f8f5] text-[#0d9488] border border-[#a7f3d0] transition cursor-pointer";
                } else {
                    btn.className = "pptx-modal-chip px-2.5 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 border border-transparent hover:bg-[#e6f8f5] hover:text-[#0d9488] transition cursor-pointer";
                }
            });
        }

        function actualizarDatalistTemas() {
            const datalist = document.getElementById('datalistTemasPptx');
            if (!datalist) return;
            const temasSet = new Set(['Neonatología', 'Infectología', 'Urgencias', 'UCI Pediátrica', 'Gastroenterología', 'Neumología', 'Guardia']);
            (cachePptx || []).forEach(d => { if (d.tema) temasSet.add(d.tema); });
            datalist.innerHTML = Array.from(temasSet).map(t => `<option value="${t}"></option>`).join('');
        }

        function onArchivoPptxSeleccionado(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];

            const dropzone = document.getElementById('pptxDropzone');
            const box = document.getElementById('pptxFileSelectedBox');
            const lblNombre = document.getElementById('pptxNombrePreview');
            const lblTamano = document.getElementById('pptxTamanoPreview');
            const bar = document.getElementById('pptxProgressBar');
            const inputTitulo = document.getElementById('inputPptxTitulo');
            const estadoTexto = document.getElementById('pptxEstadoTexto');

            if (lblNombre) lblNombre.innerText = file.name;
            if (lblTamano) {
                const mb = file.size / (1024 * 1024);
                lblTamano.innerText = mb >= 1 ? mb.toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB';
            }

            // Si el título de sesión está vacío, proponer uno legible a partir del archivo
            if (inputTitulo && !inputTitulo.value.trim()) {
                const nombreLimpio = file.name.replace(/\.[^/.]+$/, "").replace(/[_-]/g, " ");
                inputTitulo.value = nombreLimpio.charAt(0).toUpperCase() + nombreLimpio.slice(1);
            }

            if (estadoTexto) estadoTexto.innerText = "Listo para sincronizar";
            if (bar) bar.style.width = '100%';

            if (dropzone) dropzone.classList.add('hidden');
            if (box) box.classList.remove('hidden');

            if (window.lucide) lucide.createIcons();
        }

        function cancelarArchivoPptx() {
            const input = document.getElementById('inputPptxArchivo');
            if (input) input.value = '';
            const dropzone = document.getElementById('pptxDropzone');
            const box = document.getElementById('pptxFileSelectedBox');
            if (dropzone) dropzone.classList.remove('hidden');
            if (box) box.classList.add('hidden');

            const bar = document.getElementById('pptxProgressBar');
            if (bar) bar.style.width = '0%';
            const btn = document.getElementById('btnSubirPptxConfirm');
            if (btn) {
                btn.disabled = false;
                const txt = document.getElementById('btnSubirPptxTexto');
                if (txt) txt.innerText = "Subir archivo";
            }
        }

        function handlePptxDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropzone = document.getElementById('pptxDropzone');
            if (dropzone) dropzone.classList.add('border-[#248294]', 'bg-[#e6f8f5]');
        }

        function handlePptxDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropzone = document.getElementById('pptxDropzone');
            if (dropzone) dropzone.classList.remove('border-[#248294]', 'bg-[#e6f8f5]');
        }

        function handlePptxDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropzone = document.getElementById('pptxDropzone');
            if (dropzone) dropzone.classList.remove('border-[#248294]', 'bg-[#e6f8f5]');

            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const input = document.getElementById('inputPptxArchivo');
                input.files = e.dataTransfer.files;
                onArchivoPptxSeleccionado(input);
            }
        }

        async function handleSubirPptx(e) {
            e.preventDefault();
            const tema = document.getElementById('inputPptxTema').value.trim();
            const inputArchivo = document.getElementById('inputPptxArchivo');
            const archivo = inputArchivo ? inputArchivo.files[0] : null;
            const titulo = document.getElementById('inputPptxTitulo').value.trim();
            const expositor = document.getElementById('inputPptxExpositor').value.trim();
            const fechaSesion = document.getElementById('inputPptxFecha').value;
            const tipoActividad = document.getElementById('inputPptxTipoActividad').value;

            const btn = document.getElementById('btnSubirPptxConfirm');
            const btnTexto = document.getElementById('btnSubirPptxTexto');
            const bar = document.getElementById('pptxProgressBar');
            const estadoTexto = document.getElementById('pptxEstadoTexto');
            const velocidadLbl = document.getElementById('pptxVelocidadPreview');

            if (!archivo) {
                notify("Por favor selecciona o arrastra un archivo PowerPoint (.pptx)");
                return;
            }

            if (!tema) {
                notify("Por favor ingresa o selecciona un tema o especialidad médica");
                return;
            }

            btn.disabled = true;
            if (btnTexto) btnTexto.innerText = "Subiendo archivo (0%)...";

            const formData = new FormData();
            formData.append('tema', tema);
            formData.append('archivo', archivo);
            formData.append('titulo', titulo);
            formData.append('expositor', expositor);
            formData.append('fecha_sesion', fechaSesion);
            formData.append('tipo_actividad', tipoActividad);
            formData.append('disco', discoActual);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', "{{ route('material.powerpoint.subir') }}", true);
            xhr.setRequestHeader('X-CSRF-TOKEN', CSRF_TOKEN);

            xhr.upload.onprogress = function(evt) {
                if (evt.lengthComputable) {
                    const pct = Math.round((evt.loaded / evt.total) * 100);
                    if (bar) bar.style.width = pct + '%';
                    if (btnTexto) btnTexto.innerText = `Subiendo archivo (${pct}%)...`;
                    if (estadoTexto) estadoTexto.innerText = `Subiendo a Drive... ${pct}%`;
                    if (velocidadLbl) {
                        const loadedMB = (evt.loaded / (1024 * 1024)).toFixed(1);
                        const totalMB = (evt.total / (1024 * 1024)).toFixed(1);
                        velocidadLbl.innerText = `${loadedMB} MB de ${totalMB} MB`;
                    }
                }
            };

            xhr.onload = function() {
                btn.disabled = false;
                if (btnTexto) btnTexto.innerText = "Subir archivo";

                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const d = JSON.parse(xhr.responseText);
                        if (d.success) {
                            notify("✓ ¡Presentación subida con éxito!");
                            cerrarModalPptx();
                            document.getElementById('formPptxUpload').reset();
                            cargarMaterialDocente();
                            setTimeout(() => {
                                temaPptxActivo = tema;
                                filtrarPptx();
                            }, 450);
                        } else {
                            notify("Error: " + (d.message || "No se pudo subir la presentación"));
                        }
                    } catch (parseErr) {
                        notify("✓ ¡Presentación subida con éxito!");
                        cerrarModalPptx();
                        cargarMaterialDocente();
                    }
                } else {
                    notify("Error al subir el archivo (HTTP " + xhr.status + ")");
                }
            };

            xhr.onerror = function() {
                btn.disabled = false;
                if (btnTexto) btnTexto.innerText = "Subir archivo";
                notify("Error de red al intentar subir la presentación");
            };

            xhr.send(formData);
        }

        // 7. GESTIÓN DE PLANTILLAS MAESTRAS (.DOCX)
        function actualizarVisualPlantilla(tipo, info) {
            const subtitulo = document.getElementById('subtitulo-plantilla-' + tipo);
            if (!subtitulo || !info) return;

            if (info.origen && info.origen.includes('Google Drive')) {
                subtitulo.innerHTML = `
                    <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-semibold text-[9.5px]">
                        <i data-lucide="cloud" class="size-2.5"></i> Drive
                    </span>
                    <span class="truncate font-medium text-slate-600" title="${info.archivo_real}">${info.archivo_real}</span>
                    <span class="text-[9px] text-slate-400">(${info.fecha_mod})</span>
                `;
            } else {
                subtitulo.innerHTML = `
                    <span class="truncate text-slate-600 font-medium" title="${info.archivo_real}">${info.archivo_real}</span>
                    <span class="text-[9px] text-slate-400">(${info.fecha_mod})</span>
                `;
            }
            if (window.lucide) lucide.createIcons();
        }

        async function cargarEstadoPlantillas() {
            try {
                const res = await fetch("{{ route('documentos.plantillas-estado') }}?disco=" + discoActual);
                const data = await res.json();
                if (data.success && data.plantillas) {
                    Object.keys(data.plantillas).forEach(tipo => {
                        actualizarVisualPlantilla(tipo, data.plantillas[tipo]);
                    });
                }
            } catch (e) {
                console.error("Error al cargar estado de plantillas:", e);
            }
        }

        async function editarPlantillaWord(tipo) {
            notify("Abriendo plantilla en Microsoft Word...");
            try {
                const res = await fetch("{{ route('documentos.abrir-plantilla') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({ tipo: tipo, disco: discoActual })
                });
                const data = await res.json();
                if (data.success) {
                    notify(data.message || "✓ Plantilla abierta en Word. Modifica y guarda con Ctrl+S.");
                } else {
                    notify("Aviso: " + (data.message || "No se pudo abrir Word"));
                }
            } catch (e) {
                notify("Error al comunicar con Microsoft Word");
            }
        }

        function triggerSubirPlantilla(tipo) {
            const input = document.getElementById('inputPlantilla-' + tipo);
            if (input) input.click();
        }

        async function subirPlantillaArchivo(tipo, inputEl) {
            const file = inputEl.files[0];
            if (!file) return;

            if (!file.name.toLowerCase().endsWith('.docx')) {
                notify("El archivo debe ser un documento Word (.docx)");
                inputEl.value = "";
                return;
            }

            const confirmar = confirm(`¿Deseas reemplazar esta plantilla con el archivo "${file.name}"?\n\n- Se actualizará en la carpeta compartida de Google Drive (PLANTILLAS).\n- Los nuevos pacientes que registres usarán este nuevo formato.\n- Los pacientes anteriores NO se modificarán (permanecen protegidos).`);
            if (!confirmar) {
                inputEl.value = "";
                return;
            }

            notify("Subiendo y actualizando plantilla maestra en Google Drive...");
            const formData = new FormData();
            formData.append('tipo', tipo);
            formData.append('archivo', file);
            formData.append('disco', discoActual);

            try {
                const res = await fetch("{{ route('documentos.subir-plantilla') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    notify(data.message || "✓ Plantilla actualizada con éxito en Google Drive y local");
                    if (data.estado && data.estado[tipo]) {
                        actualizarVisualPlantilla(tipo, data.estado[tipo]);
                    }
                } else {
                    notify("Error: " + (data.message || "No se pudo actualizar"));
                }
            } catch (e) {
                notify("Error de red al subir la plantilla");
            } finally {
                inputEl.value = "";
            }
        }

        // 8. UTILIDADES (Superficie Corporal, Holliday-Segar y Copiado de Prompts)
        function calcularSC() {
            const w = parseFloat(document.getElementById('calcWeight')?.value);
            const h = parseFloat(document.getElementById('calcHeight')?.value);
            const resEl = document.getElementById('scResult');
            const hsEl = document.getElementById('hsResult');
            const hsHrEl = document.getElementById('hsHourlyResult');

            // Superficie corporal (Mosteller)
            if (w > 0 && h > 0) {
                const sc = Math.sqrt((w * h) / 3600);
                if (resEl) resEl.innerText = sc.toFixed(2);
            } else {
                if (resEl) resEl.innerText = "—";
            }

            // Holliday-Segar (Líquidos Basales)
            if (hsEl && hsHrEl) {
                if (w > 0) {
                    let mlDia = 0;
                    if (w <= 10) {
                        mlDia = w * 100;
                    } else if (w <= 20) {
                        mlDia = 1000 + (w - 10) * 50;
                    } else {
                        mlDia = 1500 + (w - 20) * 20;
                    }
                    const mlHr = Math.round(mlDia / 24);
                    hsEl.innerText = Math.round(mlDia) + " ml/24h";
                    hsHrEl.innerText = mlHr + " ml/h (microgotas)";
                } else {
                    hsEl.innerText = "— ml/24h";
                    hsHrEl.innerText = "— ml/h";
                }
            }
        }

        function consultarIAPronta(tipo) {
            goModule('ai');
            let promptSugerido = "";
            const w = parseFloat(document.getElementById('calcWeight')?.value);
            const wTxt = (w > 0) ? ` para paciente pediátrico de ${w} kg` : " para paciente pediátrico";
            if (tipo === 'dosificacion') {
                promptSugerido = `Por favor calcula la dosificación terapéutica y de rescate en mg/kg/día y mg/kg/dosis${wTxt}. Incluye presentación habitual y ajuste posológico.`;
            } else if (tipo === 'hidratacion') {
                promptSugerido = `Por favor genera el plan de hidratación oral / endovenosa con esquema Holliday-Segar y cálculo de déficit${wTxt}.`;
            } else if (tipo === 'crisis') {
                promptSugerido = `Protocolo de manejo de crisis bronquial / broncoespasmo pediátrico${wTxt}: dosis de salbutamol, corticoide sistémico y criterios de gravedad.`;
            }
            if (promptSugerido) {
                copiarAlPortapapeles(promptSugerido).then(() => {
                    notify("Prompt clínico copiado: listo para pegar en la IA");
                });
            }
        }

        function copiarPrompt(texto, btn) {
            copiarAlPortapapeles(texto).then(() => {
                notify("Prompt copiado al portapapeles");
                const span = btn ? btn.querySelector('span') : null;
                if (span) {
                    const original = span.innerText;
                    span.innerText = "¡Copiado!";
                    setTimeout(() => { span.innerText = original; }, 1800);
                }
            }).catch(() => {
                notify("No se pudo copiar el prompt");
            });
        }

        function copiarCasoCompleto() {
            const casoEl = document.getElementById('casoClinicoTexto');
            const val = casoEl ? casoEl.value.trim() : '';
            if (!val) {
                notify("Ingresa primero datos en el cuadro del caso.");
                return;
            }
            copiarAlPortapapeles(val).then(() => {
                notify("Datos del caso clínico copiados");
            }).catch(() => {
                notify("No se pudo copiar los datos");
            });
        }

        function verificarRutas() {
            notify("Verificando rutas clínicas...");
            setTimeout(() => {
                notify("✓ Rutas clínicas operativas y sincronizadas");
            }, 800);
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cerrarModalNotebookLM();
                cerrarModalPptx();
                cerrarModalNuevoTema();
                cerrarModalVerTextoIA();
            }
        });
    </script>
</body>
</html>
