@php
    $plantillaHC = file_exists(base_path('Plantillas/Historia_Clinica.docx'));
    $plantillaIndicaciones = file_exists(base_path('Plantillas/Indicaciones.docx'));
    $plantillaAdmision = file_exists(base_path('Plantillas/Admision.docx'));
@endphp
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HospitalPediatria | Panel clínico</title>
    <meta name="description" content="Panel de trabajo para la gestión asistencial pediátrica.">

    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('icon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icon-light-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-icon.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @endif

    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Instrument Sans', system-ui, -apple-system, sans-serif;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in-down {
            animation: fadeInDown 0.25s ease-out forwards;
        }
    </style>
</head>
<body class="min-h-screen bg-[#f4f8fc] text-slate-900 antialiased selection:bg-[#248294] selection:text-white">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-30 flex w-[252px] shrink-0 flex-col bg-[#123b5d] px-5 py-6 text-white transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:self-start lg:translate-x-0 -translate-x-full overflow-y-auto">
            <div class="flex items-center justify-between gap-3 px-2">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-white/12 text-[#8bd5df] shadow-inner">
                        <!-- HeartPulse Icon -->
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                            <path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold tracking-wide text-white">HospitalPediatria</p>
                        <p class="text-[11px] text-blue-100/65 font-medium">Sistema clínico</p>
                    </div>
                </div>
                <button type="button" onclick="toggleSidebar(false)" class="flex size-8 items-center justify-center rounded-lg text-white hover:bg-white/10 transition lg:hidden" aria-label="Cerrar menú">
                    <!-- X Icon -->
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="mt-12 flex flex-col gap-2" aria-label="Navegación principal">
                <a href="#inicio" class="nav-link flex items-center gap-3 rounded-xl bg-white/12 px-3 py-3 text-sm font-medium text-white transition hover:bg-white/15">
                    <!-- Hospital Icon -->
                    <svg class="size-4 shrink-0 text-[#8bd5df]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 6v4"/><path d="M14 14h-4"/><path d="M14 18h-4"/><path d="M14 8h-4"/><path d="M18 12h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2"/><path d="M18 22V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v18"/>
                    </svg>
                    <span>Pantalla inicial</span>
                </a>
                <a href="#documentacion" class="nav-link flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-blue-100/70 transition hover:bg-white/8 hover:text-white">
                    <!-- FileText Icon -->
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                    </svg>
                    <span>Documentación</span>
                </a>
                <a href="#asistencia" class="nav-link flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-blue-100/70 transition hover:bg-white/8 hover:text-white">
                    <!-- Bot Icon -->
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/>
                    </svg>
                    <span>Asistencia IA</span>
                </a>
                <a href="#material" class="nav-link flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-blue-100/70 transition hover:bg-white/8 hover:text-white">
                    <!-- BookOpen Icon -->
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                    <span>Material docente</span>
                </a>
            </nav>

            <!-- Sidebar Status Card -->
            <div class="mt-auto rounded-2xl border border-white/10 bg-white/7 p-4 shadow-sm backdrop-blur-xs">
                <div class="flex items-center gap-2 text-xs font-medium text-[#a6e3e4]">
                    <span class="relative flex size-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#55d6c2] opacity-75"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-[#55d6c2]"></span>
                    </span>
                    <span>Sistema operativo</span>
                </div>
                <p class="mt-2 text-xs leading-5 text-blue-100/60">Todos los accesos rápidos están disponibles.</p>
            </div>
        </aside>

        <!-- Mobile Sidebar Backdrop Overlay -->
        <div id="sidebarBackdrop" onclick="toggleSidebar(false)" class="fixed inset-0 z-20 bg-slate-950/40 backdrop-blur-xs transition-opacity duration-200 hidden lg:hidden" aria-hidden="true"></div>

        <!-- Main Workspace -->
        <section class="min-w-0 flex-1 flex flex-col">
            <!-- Topbar Header -->
            <header class="flex h-[76px] items-center justify-between border-b border-slate-200/80 bg-white/80 backdrop-blur px-5 sm:px-8 sticky top-0 z-10">
                <button type="button" onclick="toggleSidebar(true)" class="flex size-10 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 transition lg:hidden" aria-label="Abrir menú">
                    <!-- Menu Icon -->
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>
                    </svg>
                </button>

                <div class="hidden lg:block">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Panel de trabajo</p>
                    <p class="mt-0.5 text-sm text-slate-600 font-medium">Gestión asistencial pediátrica</p>
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-slate-800">Equipo de Pediatría</p>
                        <p class="text-xs text-slate-400">Acceso profesional</p>
                    </div>
                    <div class="flex size-10 items-center justify-center rounded-full bg-[#d7eef1] text-sm font-bold text-[#176072] ring-2 ring-white shadow-xs">
                        EP
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-8 lg:py-11" id="inicio">

                <!-- Hero Section Banner -->
                <div class="relative overflow-hidden rounded-[26px] bg-[#dff2f3] px-6 py-8 sm:px-9 sm:py-10 border border-[#cde8ea] shadow-xs">
                    <div class="relative z-[1] max-w-2xl">
                        <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/75 backdrop-blur-xs px-3 py-1.5 text-xs font-semibold text-[#1e7180] shadow-xs">
                            <span class="size-1.5 rounded-full bg-[#36bcae]"></span>
                            <span>Sesión activa</span>
                        </div>
                        <h1 class="text-3xl font-semibold tracking-[-0.04em] text-[#123b5d] sm:text-4xl">
                            Simplicidad para cuidar mejor.
                        </h1>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-[#386979] sm:text-base">
                            Accedé a la documentación, asistencia inteligente y recursos del HospitalPediatria desde un solo lugar.
                        </p>
                    </div>

                    <!-- Decorative Background Circles -->
                    <div class="pointer-events-none absolute -right-8 -top-16 size-56 rounded-full border-[28px] border-white/35 sm:size-72"></div>
                    <div class="pointer-events-none absolute -bottom-32 right-24 size-72 rounded-full border-[36px] border-[#bde6e8]/70"></div>
                    
                    <!-- Decorative Stethoscope Floating Badge -->
                    <div class="absolute bottom-7 right-8 hidden size-16 items-center justify-center rounded-2xl bg-white/70 backdrop-blur-xs text-[#248294] shadow-sm sm:flex border border-white/60">
                        <svg class="size-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/>
                        </svg>
                    </div>
                </div>

                <!-- Quick Access Header -->
                <div class="mt-9 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#248294]">Accesos rápidos</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-[#123b5d]">¿Qué necesitás hacer?</h2>
                    </div>
                    <p class="hidden text-right text-xs text-slate-400 sm:block">Seleccioná una acción para comenzar</p>
                </div>

                <!-- Workflow Cards List -->
                <div class="mt-6 flex flex-col gap-6">

                    <!-- Section 1: Documentación clínica -->
                    <section id="documentacion" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_8px_30px_rgba(18,59,93,0.045)] sm:p-6 transition">
                        <div class="mb-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex size-11 items-center justify-center rounded-xl bg-[#e8f5f6] text-[#248294]">
                                    <!-- FileText Icon -->
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-[#123b5d] text-base sm:text-lg">Documentación clínica</h3>
                                    <p class="mt-0.5 text-xs text-slate-400">3 documentos oficiales protegidos · Almacenamiento directo en carpetas</p>
                                </div>
                            </div>

                            <!-- 2 Botones Solicitados: Ingresar paciente y Ver historial -->
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" onclick="abrirModalIngresarPaciente()" class="inline-flex items-center gap-2 rounded-xl bg-[#248294] px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-[#1a6877] transition active:scale-[0.98] cursor-pointer">
                                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>
                                    </svg>
                                    <span>Ingresar paciente</span>
                                </button>

                                <button type="button" onclick="abrirModalHistorial()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 shadow-xs hover:border-[#248294] hover:bg-[#f1fbfb] hover:text-[#248294] transition cursor-pointer">
                                    <svg class="size-4 shrink-0 text-[#248294]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                    </svg>
                                    <span>Ver historial</span>
                                </button>
                            </div>
                        </div>

                        <!-- Barra de Estado e Información de Estructura de Carpetas -->
                        <div class="mb-5 rounded-xl border border-teal-100 bg-teal-50/70 p-3.5 text-xs text-slate-700 flex flex-col md:flex-row md:items-center md:justify-between gap-2.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-teal-100 text-[#248294] font-bold text-xs">📁</span>
                                <span class="truncate">Estructura automática: <code class="font-mono text-[#123b5d] font-bold bg-white px-1.5 py-0.5 rounded border border-teal-200/70">Disco {{ $discoPredeterminado ?? 'D' }}:\Pediatria / {{ date('Y-m-d') }} / [Nombre_Paciente] / (3 archivos)</code></span>
                            </div>
                            <span class="text-[11px] text-teal-800 font-medium shrink-0">Plantillas base en <code class="font-mono font-semibold">Plantillas/</code> nunca se modifican</span>
                        </div>

                        <!-- Las 3 Plantillas Oficiales (Historia Clínica, Indicaciones, Admisión) -->
                        <div class="grid gap-3 sm:grid-cols-3">
                            <!-- Card 1: Historia Clínica -->
                            <div class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-4 transition hover:-translate-y-0.5 hover:border-[#91d5da] hover:bg-[#f1fbfb] hover:shadow-[0_8px_20px_rgba(38,130,148,0.1)]">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="flex size-10 items-center justify-center rounded-xl bg-[#e8f5f6] text-[#248294] transition group-hover:scale-105">
                                            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                                            </svg>
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Doc 1</span>
                                    </div>
                                    <h4 class="text-sm font-semibold text-slate-800">Historia Clínica Pediátrica</h4>
                                    <p class="mt-1 truncate text-xs text-slate-400 font-mono">Historia_Clinica.docx</p>
                                </div>
                                <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between">
                                    <a href="{{ route('documentos.descargar-plantilla', ['tipo' => 'historia-clinica']) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-[#248294] hover:text-[#1c6978] transition" title="Descargar plantilla maestra">
                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>
                                        </svg>
                                        <span>Descargar en blanco</span>
                                    </a>
                                    <button type="button" onclick="abrirModalIngresarPaciente()" class="text-xs font-semibold text-slate-600 hover:text-[#248294] transition cursor-pointer">
                                        Asignar →
                                    </button>
                                </div>
                            </div>

                            <!-- Card 2: Indicaciones Médicas -->
                            <div class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-4 transition hover:-translate-y-0.5 hover:border-[#91d5da] hover:bg-[#f1fbfb] hover:shadow-[0_8px_20px_rgba(38,130,148,0.1)]">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="flex size-10 items-center justify-center rounded-xl bg-[#e8f5f6] text-[#248294] transition group-hover:scale-105">
                                            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>
                                            </svg>
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Doc 2</span>
                                    </div>
                                    <h4 class="text-sm font-semibold text-slate-800">Hoja de Indicaciones</h4>
                                    <p class="mt-1 truncate text-xs text-slate-400 font-mono">Indicaciones.docx</p>
                                </div>
                                <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between">
                                    <a href="{{ route('documentos.descargar-plantilla', ['tipo' => 'indicaciones']) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-[#248294] hover:text-[#1c6978] transition" title="Descargar plantilla maestra">
                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>
                                        </svg>
                                        <span>Descargar en blanco</span>
                                    </a>
                                    <button type="button" onclick="abrirModalIngresarPaciente()" class="text-xs font-semibold text-slate-600 hover:text-[#248294] transition cursor-pointer">
                                        Asignar →
                                    </button>
                                </div>
                            </div>

                            <!-- Card 3: Admisión -->
                            <div class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-4 transition hover:-translate-y-0.5 hover:border-[#91d5da] hover:bg-[#f1fbfb] hover:shadow-[0_8px_20px_rgba(38,130,148,0.1)]">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="flex size-10 items-center justify-center rounded-xl bg-[#e8f5f6] text-[#248294] transition group-hover:scale-105">
                                            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 6v4"/><path d="M14 14h-4"/><path d="M14 18h-4"/><path d="M14 8h-4"/><path d="M18 12h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2"/><path d="M18 22V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v18"/>
                                            </svg>
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Doc 3</span>
                                    </div>
                                    <h4 class="text-sm font-semibold text-slate-800">Admisión Pediátrica</h4>
                                    <p class="mt-1 truncate text-xs text-slate-400 font-mono">Admision.docx</p>
                                </div>
                                <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between">
                                    <a href="{{ route('documentos.descargar-plantilla', ['tipo' => 'admision']) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-[#248294] hover:text-[#1c6978] transition" title="Descargar plantilla maestra">
                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>
                                        </svg>
                                        <span>Descargar en blanco</span>
                                    </a>
                                    <button type="button" onclick="abrirModalIngresarPaciente()" class="text-xs font-semibold text-slate-600 hover:text-[#248294] transition cursor-pointer">
                                        Asignar →
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Section 2: Asistencia inteligente -->
                    <section id="asistencia" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_8px_30px_rgba(18,59,93,0.045)] sm:p-6 transition">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-[#ede9fe] text-[#7c3aed]">
                                <!-- Bot Icon -->
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-[#123b5d] text-base">Asistencia inteligente</h3>
                                <p class="mt-0.5 text-xs text-slate-400">Generación con IA</p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <!-- Card 1 -->
                            <button type="button" onclick="handleAction('Generar evolución')" class="group flex min-h-[92px] items-center gap-4 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-left transition hover:-translate-y-0.5 hover:border-[#c4b5fd] hover:bg-[#fbf9ff] hover:shadow-[0_8px_20px_rgba(124,58,237,0.1)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7c3aed]">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#e8e4fb] text-[#6e5db4] transition group-hover:scale-105">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M13 8H7"/><path d="M17 12H7"/>
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold leading-5 text-slate-800">Generar evolución</span>
                                    <span class="mt-1 block truncate text-xs text-slate-400">Redactar con ChatGPT</span>
                                </span>
                                <svg class="size-4 shrink-0 text-slate-300 transition group-hover:text-[#6e5db4] group-hover:translate-x-0.5 group-hover:-translate-y-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 7h10v10"/><path d="M7 17 17 7"/>
                                </svg>
                            </button>

                            <!-- Card 2 -->
                            <button type="button" onclick="handleAction('Procesar informe')" class="group flex min-h-[92px] items-center gap-4 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-left transition hover:-translate-y-0.5 hover:border-[#c4b5fd] hover:bg-[#fbf9ff] hover:shadow-[0_8px_20px_rgba(124,58,237,0.1)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7c3aed]">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#e8e4fb] text-[#6e5db4] transition group-hover:scale-105">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold leading-5 text-slate-800">Procesar informe</span>
                                    <span class="mt-1 block truncate text-xs text-slate-400">Analizar y redactar</span>
                                </span>
                                <svg class="size-4 shrink-0 text-slate-300 transition group-hover:text-[#6e5db4] group-hover:translate-x-0.5 group-hover:-translate-y-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 7h10v10"/><path d="M7 17 17 7"/>
                                </svg>
                            </button>

                            <!-- Card 3 -->
                            <button type="button" onclick="handleAction('Realizar transferencia')" class="group flex min-h-[92px] items-center gap-4 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-left transition hover:-translate-y-0.5 hover:border-[#c4b5fd] hover:bg-[#fbf9ff] hover:shadow-[0_8px_20px_rgba(124,58,237,0.1)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7c3aed]">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#e8e4fb] text-[#6e5db4] transition group-hover:scale-105">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M7 7h10v10"/><path d="M7 17 17 7"/>
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold leading-5 text-slate-800">Realizar transferencia</span>
                                    <span class="mt-1 block truncate text-xs text-slate-400">Preparar pase clínico</span>
                                </span>
                                <svg class="size-4 shrink-0 text-slate-300 transition group-hover:text-[#6e5db4] group-hover:translate-x-0.5 group-hover:-translate-y-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 7h10v10"/><path d="M7 17 17 7"/>
                                </svg>
                            </button>
                        </div>
                    </section>

                    <!-- Section 3: Material docente -->
                    <section id="material" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_8px_30px_rgba(18,59,93,0.045)] sm:p-6 transition">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-[#fef3c7] text-[#d97706]">
                                <!-- BookOpen Icon -->
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-[#123b5d] text-base">Material docente</h3>
                                <p class="mt-0.5 text-xs text-slate-400">Clases y recursos</p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <!-- Card 1: Temas en PowerPoint (Biblioteca y Subida) -->
                            <button type="button" onclick="abrirModalPowerPoint()" class="group flex min-h-[92px] items-center gap-4 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-left transition hover:-translate-y-0.5 hover:border-[#fed7aa] hover:bg-[#fffbf5] hover:shadow-[0_8px_20px_rgba(217,119,6,0.12)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d97706] cursor-pointer">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#fff2d9] text-[#bf7c22] transition group-hover:scale-105">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 3h20v14H2z"/><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 8h4a2 2 0 0 1 0 4H7z"/>
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold leading-5 text-slate-800">Temas en PowerPoint</span>
                                    <span class="mt-0.5 block truncate text-xs text-slate-400">Subir y explorar por temas</span>
                                    <span class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-[#bf7c22]">
                                        <span>2 opciones: Subir / Biblioteca</span>
                                        <span>→</span>
                                    </span>
                                </span>
                                <svg class="size-4 shrink-0 text-slate-300 transition group-hover:text-[#bf7c22] group-hover:translate-x-0.5 group-hover:-translate-y-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 7h10v10"/><path d="M7 17 17 7"/>
                                </svg>
                            </button>
                        </div>
                    </section>

                </div>

                <!-- Footer Section -->
                <footer class="mt-10 flex flex-col gap-3 border-t border-slate-200/80 pt-6 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <span class="font-medium text-slate-500">HospitalPediatria · Herramientas para el cuidado pediátrico</span>
                    <span class="flex items-center gap-2 text-slate-500">
                        <svg class="size-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/>
                        </svg>
                        <span>Documentos listos para guardar e imprimir</span>
                    </span>
                </footer>

            </div>
        </section>
    </div>

    <!-- Modal 1: Ingresar Nuevo Paciente -->
    <div id="modalIngresarPaciente" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs hidden transition-opacity" role="dialog" aria-modal="true">
        <div class="fixed inset-0" onclick="cerrarModalIngresarPaciente()"></div>

        <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 animate-fade-in-down">
            <!-- Header -->
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-[#e8f5f6] text-[#248294]">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-[#123b5d]">Ingresar Nuevo Paciente</h3>
                        <p class="text-xs text-slate-400">Genera la carpeta con los 3 documentos clínicos</p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalIngresarPaciente()" class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer" aria-label="Cerrar modal">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Form Stage -->
            <div id="formIngresarPacienteContent" class="mt-5 space-y-4">
                <div>
                    <label for="inputNuevoPacienteNombre" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Nombre completo del paciente:
                    </label>
                    <input type="text" id="inputNuevoPacienteNombre" oninput="actualizarPreviewCarpetaPaciente()" placeholder="Ej: Mateo Fernandez, Sofia Quispe, etc." class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-800 placeholder-slate-400 focus:border-[#248294] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#248294]/20 transition" onkeydown="if(event.key === 'Enter') submitNuevoPaciente()">
                </div>

                <!-- Live Path Preview Box -->
                <div class="rounded-xl border border-teal-200/90 bg-gradient-to-br from-teal-50/60 via-white to-slate-50 p-4 text-xs text-slate-700 shadow-xs">
                    <!-- Selector de disco -->
                    <div class="flex items-center justify-between gap-2 mb-3 pb-2.5 border-b border-teal-200/60">
                        <p class="font-bold text-slate-800 flex items-center gap-1.5 text-xs">
                            <span class="flex size-5 items-center justify-center rounded-md bg-[#248294] text-white text-[10px]">💾</span>
                            <span>Disco de almacenamiento:</span>
                        </p>
                        <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg border border-slate-200 text-xs font-semibold">
                            @foreach(($discos ?? [['disco' => 'D', 'es_predeterminado' => true], ['disco' => 'C', 'es_predeterminado' => false]]) as $d)
                                <button type="button" 
                                    onclick="seleccionarDisco('{{ $d['disco'] }}')" 
                                    id="btnDisco_{{ $d['disco'] }}" 
                                    class="btn-disco-opcion px-2.5 py-1 rounded-md transition cursor-pointer text-xs {{ ($d['disco'] === ($discoPredeterminado ?? 'D')) ? 'bg-[#248294] text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-200/70 font-medium' }}">
                                    Disco {{ $d['disco'] }}:\
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Estructura dividida: Pediatria -> Fecha -> Paciente -->
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5 flex items-center justify-between">
                        <span>Estructura por niveles de carpetas:</span>
                        <span class="text-[10px] text-teal-800 font-mono">100% Automático</span>
                    </p>
                    <div class="flex items-center gap-1 flex-wrap text-xs font-mono bg-white p-2.5 rounded-lg border border-slate-200 shadow-2xs">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-sky-50 text-sky-800 font-bold border border-sky-200">
                            💾 <span id="previewDiscoLetra">{{ $discoPredeterminado ?? 'D' }}:</span>\
                        </span>
                        <span class="text-slate-400 font-sans">➜</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-50 text-amber-900 font-bold border border-amber-200">
                            📁 Pediatria
                        </span>
                        <span class="text-slate-400 font-sans">➜</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                            📅 {{ date('Y-m-d') }}
                        </span>
                        <span class="text-slate-400 font-sans">➜</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-teal-50 text-[#123b5d] font-bold border border-teal-300">
                            👤 <span id="previewCarpetaNombre">nombre_paciente</span>
                        </span>
                    </div>

                    <!-- Ruta completa exacta para el usuario -->
                    <div class="mt-2 text-[11px] font-mono bg-slate-900 text-teal-300 p-2 rounded-lg truncate flex items-center gap-2">
                        <span class="text-slate-400 shrink-0">Ruta final:</span>
                        <span id="previewRutaCompleta" class="text-emerald-300 font-semibold truncate">{{ $discoPredeterminado ?? 'D' }}:\Pediatria\{{ date('Y-m-d') }}\nombre_paciente\</span>
                    </div>

                    <!-- Archivos oficiales que se incluirán dentro del paciente -->
                    <div class="mt-2.5 pt-2 border-t border-teal-200/50">
                        <p class="text-[11px] text-slate-500 mb-1">Se copiarán los 3 documentos oficiales adentro de la carpeta del paciente:</p>
                        <ul class="space-y-0.5 text-[11px] text-slate-600 font-mono bg-white/70 p-2 rounded-lg border border-slate-200/60">
                            <li class="flex items-center gap-1.5"><span class="text-slate-400">├──</span> 📄 Historia_Clinica.docx</li>
                            <li class="flex items-center gap-1.5"><span class="text-slate-400">├──</span> 📄 Indicaciones.docx</li>
                            <li class="flex items-center gap-1.5"><span class="text-slate-400">└──</span> 📄 Admision.docx</li>
                        </ul>
                    </div>
                </div>

                <div id="errorIngresarPaciente" class="hidden rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700"></div>

                <div class="pt-2 flex items-center gap-3">
                    <button type="button" onclick="cerrarModalIngresarPaciente()" class="w-1/3 rounded-xl border border-slate-200 bg-white py-3 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" id="btnSubmitPaciente" onclick="submitNuevoPaciente()" class="w-2/3 flex items-center justify-center gap-2 rounded-xl bg-[#248294] py-3 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-[#1a6877] transition active:scale-[0.99] cursor-pointer">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span id="textSubmitPaciente">Crear carpeta y 3 archivos</span>
                    </button>
                </div>
            </div>

            <!-- Success Stage (shown after creation) -->
            <div id="successIngresarPacienteContent" class="mt-5 space-y-4 hidden">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-emerald-900">
                    <div class="flex items-center gap-2 font-bold text-sm">
                        <svg class="size-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>
                        </svg>
                        <span>¡Paciente creado correctamente!</span>
                    </div>
                    <p class="mt-1 text-xs text-emerald-800">Carpeta guardada en el disco:</p>
                    <div class="mt-1.5 flex items-center justify-between gap-2 bg-white/90 p-2 rounded-lg border border-emerald-300">
                        <code id="successRutaRelativa" class="font-mono text-xs font-semibold text-emerald-950 truncate"></code>
                        <button type="button" onclick="copiarRutaExito()" class="shrink-0 inline-flex items-center gap-1 rounded bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-[11px] font-semibold px-2 py-1 transition cursor-pointer" title="Copiar ruta">
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                            </svg>
                            <span id="textBtnCopiarRuta">Copiar</span>
                        </button>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-600 mb-2">3 Documentos creados en la carpeta:</p>
                    <div id="successArchivosLista" class="space-y-2">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <div class="pt-2 flex flex-col gap-2.5">
                    <button type="button" id="btnAbrirCarpetaWindows" onclick="abrirCarpetaDirecta()" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#248294] py-3.5 text-xs sm:text-sm font-bold text-white shadow-md hover:bg-[#1a6877] transition cursor-pointer active:scale-[0.99]">
                        <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span>Abrir carpeta del paciente en Windows</span>
                    </button>
                    <div class="flex items-center justify-center">
                        <a id="successZipBtn" href="#" class="text-[11px] text-slate-400 hover:text-slate-600 underline font-medium transition">
                            (Opcional) Descargar copia de seguridad en .ZIP
                        </a>
                    </div>
                    <div class="flex gap-2 pt-1 border-t border-slate-100">
                        <button type="button" onclick="resetFormIngresarPaciente()" class="w-1/2 rounded-xl border border-slate-200 bg-white py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                            + Ingresar otro
                        </button>
                        <button type="button" onclick="irAHistorialDesdeNuevo()" class="w-1/2 rounded-xl bg-slate-800 py-2.5 text-xs font-semibold text-white hover:bg-slate-900 transition cursor-pointer">
                            Ver en historial →
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 2: Historial de Pacientes y Buscador con Filtro de Fechas -->
    <div id="modalHistorial" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs hidden transition-opacity" role="dialog" aria-modal="true">
        <div class="fixed inset-0" onclick="cerrarModalHistorial()"></div>

        <div class="relative z-10 w-full max-w-3xl rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 animate-fade-in-down flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-[#e8f5f6] text-[#248294]">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-[#123b5d]">Historial de Pacientes y Documentos</h3>
                        <p class="text-xs text-slate-400">Explorador de carpetas generadas en <code class="font-mono text-slate-600 font-semibold">📁 Pediatria/</code></p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalHistorial()" class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer" aria-label="Cerrar modal">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Filtros: Buscador por nombre + Selector por fecha -->
            <div class="mt-4 grid gap-3 sm:grid-cols-12 shrink-0">
                <!-- Buscador de pacientes -->
                <div class="sm:col-span-7 relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                    </div>
                    <input type="text" id="filtroHistorialBuscar" oninput="debounceCargarHistorial()" placeholder="Buscar por nombre de paciente..." class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3.5 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:border-[#248294] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#248294]/20 transition">
                </div>

                <!-- Filtro por fecha -->
                <div class="sm:col-span-5 relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
                        </svg>
                    </div>
                    <select id="filtroHistorialFecha" onchange="cargarHistorial()" class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-8 py-2.5 text-xs sm:text-sm text-slate-800 focus:border-[#248294] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#248294]/20 transition cursor-pointer appearance-none">
                        <option value="">Todas las fechas</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Contador y botón de refrescar -->
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500 shrink-0 px-1">
                <span id="historialContadorTexto">Cargando pacientes...</span>
                <button type="button" onclick="cargarHistorial()" class="inline-flex items-center gap-1 text-[#248294] hover:underline cursor-pointer font-medium">
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/>
                    </svg>
                    <span>Actualizar</span>
                </button>
            </div>

            <!-- Contenedor con la lista de pacientes -->
            <div id="historialListaContainer" class="mt-3 overflow-y-auto space-y-3 pr-1 grow min-h-[220px]">
                <!-- Cargado dinámicamente con AJAX -->
            </div>

            <!-- Footer -->
            <div class="mt-4 border-t border-slate-100 pt-3 flex items-center justify-between text-xs text-slate-400 shrink-0">
                <span class="truncate">Carpetas en disco: <code class="font-mono text-slate-600">Pediatria/{fecha}/{paciente}/</code></span>
                <button type="button" onclick="cerrarModalHistorial()" class="rounded-lg px-4 py-1.5 font-medium text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 3: Material Docente - Temas en PowerPoint (Biblioteca y Subida) -->
    <div id="modalPowerPoint" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/60 backdrop-blur-xs hidden transition-opacity" role="dialog" aria-modal="true">
        <div class="fixed inset-0" onclick="cerrarModalPowerPoint()"></div>

        <div class="relative z-10 w-full max-w-3xl rounded-2xl bg-white p-5 sm:p-6 shadow-2xl border border-slate-100 animate-fade-in-down flex flex-col max-h-[92vh]">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#fff2d9] text-[#d97706] shadow-xs">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h20v14H2z"/><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 8h4a2 2 0 0 1 0 4H7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-[#123b5d]">Temas en PowerPoint</h3>
                        <p class="text-xs text-slate-400">Material docente: explorar temas, buscar y subir clases</p>
                    </div>
                </div>

                <!-- Selector de Pestañas (Biblioteca / Subir) + Botón Cerrar -->
                <div class="flex items-center justify-between sm:justify-end gap-2 w-full sm:w-auto">
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-semibold">
                        <button type="button" id="tabBtnPptxBiblioteca" onclick="cambiarPestanaPowerPoint('biblioteca')" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition text-[#123b5d] bg-white shadow-xs cursor-pointer">
                            <svg class="size-3.5 text-[#d97706]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h10"/><path d="M7 12h10"/><path d="M7 17h6"/>
                            </svg>
                            <span>Biblioteca</span>
                            <span id="pptxBadgeCount" class="rounded-full bg-amber-100 text-amber-800 text-[10px] px-1.5 py-0.2 font-bold">0</span>
                        </button>
                        <button type="button" id="tabBtnPptxSubir" onclick="cambiarPestanaPowerPoint('subir')" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition text-slate-500 hover:text-slate-800 cursor-pointer">
                            <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>
                            </svg>
                            <span>+ Subir</span>
                        </button>
                    </div>

                    <button type="button" onclick="cerrarModalPowerPoint()" class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer" aria-label="Cerrar modal">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- CONTENIDO PESTAÑA 1: BIBLIOTECA CON BUSCADOR Y FILTRADO POR TEMA -->
            <div id="viewPptxBiblioteca" class="flex flex-col grow overflow-hidden">
                <!-- Barra de Filtros: Buscador por palabra clave + Selector de Tema -->
                <div class="mt-4 grid gap-3 sm:grid-cols-12 shrink-0">
                    <!-- Buscador de clases/presentaciones -->
                    <div class="sm:col-span-7 relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                            </svg>
                        </div>
                        <input type="text" id="filtroPowerPointBuscar" oninput="debounceCargarPowerPoint()" placeholder="Buscar por tema, clase o nombre de archivo..." class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-8 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:border-[#d97706] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 transition">
                        <button type="button" id="btnLimpiarBuscarPptx" onclick="limpiarBuscarPptx()" class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 cursor-pointer">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Filtro por Tema -->
                    <div class="sm:col-span-5 relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="size-4 text-[#d97706]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>
                            </svg>
                        </div>
                        <select id="filtroPowerPointTema" onchange="cargarPresentacionesPowerPoint()" class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-8 py-2.5 text-xs sm:text-sm text-slate-800 focus:border-[#d97706] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 transition cursor-pointer appearance-none">
                            <option value="">Todos los temas</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Contador y acciones rápidas -->
                <div class="mt-3 flex items-center justify-between text-xs text-slate-500 shrink-0 px-1">
                    <span id="powerPointContadorTexto">Cargando presentaciones...</span>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="cambiarPestanaPowerPoint('subir')" class="inline-flex items-center gap-1 text-[#d97706] hover:text-[#b45309] font-semibold cursor-pointer">
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            <span>+ Subir clase</span>
                        </button>
                        <button type="button" onclick="cargarPresentacionesPowerPoint()" class="inline-flex items-center gap-1 text-slate-500 hover:text-slate-800 cursor-pointer font-medium">
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/>
                            </svg>
                            <span>Actualizar</span>
                        </button>
                    </div>
                </div>

                <!-- Lista de presentaciones -->
                <div id="powerPointListaContainer" class="mt-3 overflow-y-auto space-y-2.5 pr-1 grow min-h-[220px]">
                    <!-- Cargado dinámicamente con AJAX -->
                </div>
            </div>

            <!-- CONTENIDO PESTAÑA 2: SUBIR PRESENTACIÓN -->
            <div id="viewPptxSubir" class="hidden flex-col grow overflow-y-auto mt-4 space-y-4 pr-1">
                <div class="rounded-xl border border-amber-200/70 bg-amber-50/60 p-3.5 text-xs text-amber-900">
                    <div class="flex items-center gap-2 font-bold text-amber-900">
                        <span class="flex size-5 items-center justify-center rounded-full bg-amber-200 text-amber-800 text-[11px]">💡</span>
                        <span>Subir material docente para el equipo</span>
                    </div>
                    <p class="mt-1 text-amber-800/90 leading-relaxed">
                        Sube diapositivas asignándoles un tema médico. Se guardarán en la carpeta de material docente y podrás abrirlas directamente en PowerPoint con un solo clic.
                    </p>
                </div>

                <!-- Formulario de subida -->
                <form id="formSubirPowerPoint" onsubmit="event.preventDefault(); submitSubirPowerPoint();" class="space-y-4">
                    <!-- Zona Drag & Drop / Selección de Archivo -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                            Archivo de PowerPoint (.pptx, .ppt):
                        </label>
                        <div id="pptxDropzone" onclick="document.getElementById('pptxFileInput').click()" class="border-2 border-dashed border-slate-200 hover:border-[#d97706] hover:bg-[#fffdfa] rounded-xl p-5 text-center transition cursor-pointer bg-slate-50/50 group">
                            <input type="file" id="pptxFileInput" accept=".pptx,.ppt,.ppsx,.pps,.potx,.odp,.pdf" class="hidden" onchange="actualizarArchivoSeleccionado(this)">
                            
                            <div id="pptxDropzoneEmpty" class="flex flex-col items-center">
                                <div class="flex size-12 items-center justify-center rounded-2xl bg-[#fff2d9] text-[#d97706] group-hover:scale-105 transition shadow-xs">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>
                                    </svg>
                                </div>
                                <p class="mt-2 text-sm font-semibold text-slate-700">Hacé clic para elegir o arrastrá tu presentación aquí</p>
                                <p class="mt-0.5 text-xs text-slate-400">Formatos soportados: PPTX, PPT, PPSX (hasta 150 MB)</p>
                            </div>

                            <div id="pptxDropzoneSelected" class="hidden items-center justify-between bg-white rounded-lg p-3 border border-amber-200 text-left">
                                <div class="flex items-center gap-3 min-w-0 mr-2">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-[#d97706] font-bold text-xs">PPT</span>
                                    <div class="min-w-0">
                                        <p id="pptxSelectedNombre" class="text-xs font-bold text-slate-800 truncate"></p>
                                        <p id="pptxSelectedTamano" class="text-[11px] text-slate-400 font-mono"></p>
                                    </div>
                                </div>
                                <button type="button" onclick="event.stopPropagation(); deseleccionarArchivoPptx()" class="text-xs text-rose-500 hover:text-rose-700 font-semibold px-2 py-1 rounded hover:bg-rose-50 transition cursor-pointer">
                                    Cambiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Campo Tema o Especialidad -->
                    <div>
                        <label for="pptxTemaInput" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Tema o Especialidad Médica:
                        </label>
                        <input type="text" id="pptxTemaInput" list="sugerenciasTemas" placeholder="Ej: Neonatología, Urgencias, Infectología..." oninput="actualizarPreviewRutaPptx()" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:border-[#d97706] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 transition">
                        <datalist id="sugerenciasTemas">
                            <option value="Neonatología">
                            <option value="Urgencias Pediátricas">
                            <option value="Infectología">
                            <option value="Neumología y Respiratorio">
                            <option value="Nutrición y Desarrollo">
                            <option value="Cuidados Intensivos (UTIP)">
                            <option value="Guardia y Casos Clínicos">
                            <option value="Farmacología Pediátrica">
                            <option value="General">
                        </datalist>

                        <!-- Botones rápidos de temas sugeridos -->
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] text-slate-400 mr-1">Frecuentes:</span>
                            <button type="button" onclick="seleccionarTemaRapido('Neonatología')" class="rounded-full bg-slate-100 hover:bg-amber-100 hover:text-amber-800 text-[11px] px-2.5 py-0.5 text-slate-600 transition cursor-pointer">Neonatología</button>
                            <button type="button" onclick="seleccionarTemaRapido('Urgencias Pediátricas')" class="rounded-full bg-slate-100 hover:bg-amber-100 hover:text-amber-800 text-[11px] px-2.5 py-0.5 text-slate-600 transition cursor-pointer">Urgencias</button>
                            <button type="button" onclick="seleccionarTemaRapido('Infectología')" class="rounded-full bg-slate-100 hover:bg-amber-100 hover:text-amber-800 text-[11px] px-2.5 py-0.5 text-slate-600 transition cursor-pointer">Infectología</button>
                            <button type="button" onclick="seleccionarTemaRapido('Neumología')" class="rounded-full bg-slate-100 hover:bg-amber-100 hover:text-amber-800 text-[11px] px-2.5 py-0.5 text-slate-600 transition cursor-pointer">Neumología</button>
                            <button type="button" onclick="seleccionarTemaRapido('Guardia')" class="rounded-full bg-slate-100 hover:bg-amber-100 hover:text-amber-800 text-[11px] px-2.5 py-0.5 text-slate-600 transition cursor-pointer">Guardia</button>
                        </div>
                    </div>

                    <!-- Campo Título descriptivo (opcional) -->
                    <div>
                        <label for="pptxTituloInput" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Título o Descripción de la Clase (Opcional):
                        </label>
                        <input type="text" id="pptxTituloInput" placeholder="Ej: Reanimación Cardiopulmonar Avanzada Pediátrica" oninput="actualizarPreviewRutaPptx()" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:border-[#d97706] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 transition">
                        <p class="mt-1 text-[11px] text-slate-400">Si se deja vacío, se usará el nombre del archivo subido.</p>
                    </div>

                    <!-- Live Path Preview Box -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 text-xs text-slate-600">
                        <p class="font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                            <span class="text-amber-600 font-bold">📁</span>
                            <span>Carpeta de destino en el disco:</span>
                        </p>
                        <code id="pptxPreviewRuta" class="font-mono text-xs font-bold text-amber-900 block truncate bg-white p-2 rounded border border-slate-200">
                            Disco D:\Pediatria\MaterialDocente\PowerPoint\General\[archivo.pptx]
                        </code>
                    </div>

                    <!-- Alertas de Error y Éxito -->
                    <div id="errorSubirPptx" class="hidden rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700"></div>

                    <div id="exitoSubirPptx" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs text-emerald-800">
                        <div class="flex items-center gap-2 font-bold text-sm text-emerald-900">
                            <svg class="size-4 text-emerald-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>¡Presentación guardada con éxito!</span>
                        </div>
                        <p id="exitoSubirPptxTexto" class="mt-1 text-emerald-800 truncate"></p>
                        <div class="mt-3 flex items-center gap-2">
                            <button type="button" id="btnAbrirRecienSubido" onclick="abrirRecienSubido()" class="inline-flex items-center gap-1.5 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white px-3 py-1.5 font-semibold text-xs transition cursor-pointer shadow-xs">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                                <span>Abrir en PowerPoint</span>
                            </button>
                            <button type="button" onclick="cambiarPestanaPowerPoint('biblioteca')" class="rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-900 px-3 py-1.5 font-semibold text-xs transition cursor-pointer">
                                Ver en biblioteca
                            </button>
                        </div>
                    </div>

                    <!-- Botón Submit -->
                    <div class="pt-2">
                        <button type="submit" id="btnSubmitSubirPptx" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#d97706] py-3 text-xs sm:text-sm font-bold text-white shadow-md hover:bg-[#b45309] transition cursor-pointer active:scale-[0.99] disabled:opacity-50">
                            <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>
                            </svg>
                            <span id="textBtnSubmitSubirPptx">Guardar presentación en la biblioteca</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer del Modal -->
            <div class="mt-4 border-t border-slate-100 pt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs text-slate-400 shrink-0">
                <span class="truncate flex items-center gap-1">
                    <span>📁 Carpeta en disco:</span>
                    <code id="pptxFooterRuta" class="font-mono text-slate-600 truncate">Pediatria/MaterialDocente/PowerPoint/</code>
                </span>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" onclick="abrirCarpetaPowerPointDirecta()" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-slate-700 hover:bg-slate-50 transition font-medium cursor-pointer" title="Abrir carpeta en el Explorador de Windows">
                        <svg class="size-3.5 text-amber-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span>Abrir carpeta</span>
                    </button>
                    <button type="button" onclick="cerrarModalPowerPoint()" class="rounded-lg px-4 py-1.5 font-medium text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="actionAlert" class="fixed bottom-6 right-6 z-50 hidden items-center gap-3 rounded-2xl bg-slate-900/95 backdrop-blur px-5 py-3.5 text-white shadow-2xl border border-white/10 animate-fade-in-down max-w-sm">
        <div class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <p id="actionAlertTitle" class="text-xs font-semibold text-white truncate"></p>
            <p id="actionAlertSub" class="text-[11px] text-slate-300 truncate">Guardado en Pediatria/</p>
        </div>
    </div>

    <!-- Client-side Interactions -->
    <script>
        const urlIngresarPaciente = "{{ route('documentos.ingresar-paciente') }}";
        const urlHistorial = "{{ route('documentos.historial') }}";
        const urlAbrirCarpeta = "{{ route('documentos.abrir-carpeta') }}";
        const urlAbrirArchivo = "{{ route('documentos.abrir-archivo') }}";
        const urlPowerPointListar = "{{ route('material.powerpoint.listar') }}";
        const urlPowerPointSubir = "{{ route('material.powerpoint.subir') }}";
        const urlPowerPointAbrir = "{{ route('material.powerpoint.abrir') }}";
        const urlPowerPointAbrirCarpeta = "{{ route('material.powerpoint.abrir-carpeta') }}";
        const urlPowerPointEliminar = "{{ route('material.powerpoint.eliminar') }}";
        let alertTimeout = null;
        let debounceTimer = null;
        let debouncePptxTimer = null;
        let discoSeleccionado = "{{ $discoPredeterminado ?? 'D' }}";
        const fechaHoyActual = "{{ date('Y-m-d') }}";
        let ultimoPacienteCreado = null;
        let ultimaPresentacionSubida = null;
        let archivoPptxSeleccionado = null;

        // --- APERTURA DIRECTA EN WINDOWS ---
        function abrirCarpetaDirecta(fecha = null, paciente = null, disco = null) {
            const f = fecha || ultimoPacienteCreado?.fecha;
            const p = paciente || ultimoPacienteCreado?.paciente_carpeta;
            const d = disco || ultimoPacienteCreado?.disco || discoSeleccionado;

            if (!f || !p) {
                handleAction('Aviso', 'No se ha especificado paciente para abrir carpeta.');
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            handleAction('Abriendo carpeta...', `${d}:\\Pediatria\\${f}\\${p}`);

            fetch(urlAbrirCarpeta, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ fecha: f, paciente: p, disco: d })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    handleAction('Carpeta abierta en Windows', data.ruta || `${d}:\\Pediatria\\${f}\\${p}`);
                } else {
                    handleAction('Aviso', data.message || 'No se pudo abrir la carpeta');
                }
            })
            .catch(() => {
                handleAction('Error', 'No se pudo abrir el Explorador de Windows');
            });
        }

        function abrirArchivoDirecto(fecha, paciente, archivo, disco) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            handleAction('Abriendo documento...', archivo);

            fetch(urlAbrirArchivo, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ fecha: fecha, paciente: paciente, archivo: archivo, disco: disco })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    handleAction('Documento abierto', `${archivo} en Microsoft Word`);
                } else {
                    handleAction('Aviso', data.message || 'No se pudo abrir el archivo');
                }
            })
            .catch(() => {
                handleAction('Error', 'No se pudo abrir el archivo');
            });
        }

        // --- SELECCIÓN DE DISCO Y PREVIEW ---
        function seleccionarDisco(letra) {
            discoSeleccionado = (letra || 'D').toUpperCase();

            document.querySelectorAll('.btn-disco-opcion').forEach(btn => {
                btn.className = 'btn-disco-opcion px-2.5 py-1 rounded-md transition cursor-pointer text-xs text-slate-600 hover:bg-slate-200/70 font-medium';
            });

            const activeBtn = document.getElementById('btnDisco_' + discoSeleccionado);
            if (activeBtn) {
                activeBtn.className = 'btn-disco-opcion px-2.5 py-1 rounded-md transition cursor-pointer text-xs bg-[#248294] text-white shadow-xs font-bold';
            }

            const previewDisco = document.getElementById('previewDiscoLetra');
            if (previewDisco) {
                previewDisco.textContent = discoSeleccionado + ':';
            }

            actualizarPreviewCarpetaPaciente();
        }

        function actualizarPreviewCarpetaPaciente() {
            const input = document.getElementById('inputNuevoPacienteNombre');
            const previewNombre = document.getElementById('previewCarpetaNombre');
            const previewRuta = document.getElementById('previewRutaCompleta');

            const raw = input ? input.value.trim() : '';
            let clean = 'nombre_paciente';
            if (raw) {
                clean = raw.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_\-]/g, '') || 'paciente';
            }

            if (previewNombre) {
                previewNombre.textContent = clean;
            }

            if (previewRuta) {
                previewRuta.textContent = `${discoSeleccionado}:\\Pediatria\\${fechaHoyActual}\\${clean}\\`;
            }
        }

        function copiarRutaExito() {
            const el = document.getElementById('successRutaRelativa');
            const texto = el ? el.textContent.trim().replace(/^📁\s*/, '') : '';
            if (texto) {
                navigator.clipboard.writeText(texto).then(() => {
                    const btnText = document.getElementById('textBtnCopiarRuta');
                    if (btnText) {
                        btnText.textContent = '¡Copiado!';
                        setTimeout(() => { btnText.textContent = 'Copiar'; }, 2000);
                    }
                });
            }
        }

        // --- MODAL: INGRESAR PACIENTE ---
        function abrirModalIngresarPaciente() {
            const modal = document.getElementById('modalIngresarPaciente');
            document.getElementById('formIngresarPacienteContent').classList.remove('hidden');
            document.getElementById('successIngresarPacienteContent').classList.add('hidden');
            document.getElementById('errorIngresarPaciente').classList.add('hidden');
            
            const input = document.getElementById('inputNuevoPacienteNombre');
            input.value = '';
            actualizarPreviewCarpetaPaciente();
            modal.classList.remove('hidden');
            setTimeout(() => input.focus(), 150);
        }

        function cerrarModalIngresarPaciente() {
            document.getElementById('modalIngresarPaciente').classList.add('hidden');
        }

        function resetFormIngresarPaciente() {
            abrirModalIngresarPaciente();
        }

        function irAHistorialDesdeNuevo() {
            cerrarModalIngresarPaciente();
            abrirModalHistorial();
        }

        function submitNuevoPaciente() {
            const input = document.getElementById('inputNuevoPacienteNombre');
            const nombre = input ? input.value.trim() : '';
            const errorEl = document.getElementById('errorIngresarPaciente');
            const btnSubmit = document.getElementById('btnSubmitPaciente');
            const textSubmit = document.getElementById('textSubmitPaciente');

            if (!nombre) {
                errorEl.textContent = 'Por favor, escribí el nombre del paciente.';
                errorEl.classList.remove('hidden');
                input.focus();
                return;
            }

            errorEl.classList.add('hidden');
            btnSubmit.disabled = true;
            textSubmit.textContent = 'Generando carpeta y 3 archivos...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            fetch(urlIngresarPaciente, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ 
                    nombre: nombre,
                    disco: discoSeleccionado 
                })
            })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                textSubmit.textContent = 'Crear carpeta y 3 archivos';

                if (!data.success) {
                    errorEl.textContent = data.message || 'Error al crear el paciente.';
                    errorEl.classList.remove('hidden');
                    return;
                }

                ultimoPacienteCreado = data;

                // Mostrar vista de éxito
                document.getElementById('formIngresarPacienteContent').classList.add('hidden');
                const successContent = document.getElementById('successIngresarPacienteContent');
                successContent.classList.remove('hidden');

                document.getElementById('successRutaRelativa').textContent = `📁 ${data.ruta_relativa}\\`;
                document.getElementById('successZipBtn').href = data.zip_url;

                const listaArchivos = document.getElementById('successArchivosLista');
                listaArchivos.innerHTML = (data.archivos || []).map(arch => `
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2.5 transition hover:bg-slate-50">
                        <div class="flex items-center gap-2.5 truncate mr-2">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-[#248294] font-bold text-xs border border-teal-100">
                                📄
                            </span>
                            <div class="truncate">
                                <span class="block text-xs font-bold text-slate-800">${arch.titulo}</span>
                                <span class="block font-mono text-[11px] text-slate-400 truncate">${arch.nombre} (${arch.tamano})</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-2 py-0.5 text-[11px] font-bold">
                                <svg class="size-3 text-emerald-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                <span>Creado</span>
                            </span>
                            <button type="button" onclick="abrirArchivoDirecto('${data.fecha}', '${data.paciente_carpeta}', '${arch.nombre}', '${data.disco}')" class="inline-flex items-center gap-1 rounded-lg bg-[#248294] hover:bg-[#1a6877] text-white px-2.5 py-1 text-xs font-semibold shadow-2xs transition cursor-pointer" title="Abrir directamente en Microsoft Word">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/>
                                </svg>
                                <span>Abrir</span>
                            </button>
                        </div>
                    </div>
                `).join('');

                handleAction(`Paciente ingresado: ${data.paciente}`, `📁 ${data.ruta_relativa}\\`);
            })
            .catch(err => {
                btnSubmit.disabled = false;
                textSubmit.textContent = 'Crear carpeta y 3 archivos';
                errorEl.textContent = 'Error de conexión al ingresar paciente. Intente nuevamente.';
                errorEl.classList.remove('hidden');
            });
        }

        // --- MODAL: HISTORIAL Y FILTROS ---
        function debounceCargarHistorial() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(cargarHistorial, 250);
        }

        function abrirModalHistorial(filtroFechaPrevia = '') {
            const modal = document.getElementById('modalHistorial');
            modal.classList.remove('hidden');
            if (filtroFechaPrevia) {
                document.getElementById('filtroHistorialFecha').value = filtroFechaPrevia;
            }
            cargarHistorial();
        }

        function cerrarModalHistorial() {
            document.getElementById('modalHistorial').classList.add('hidden');
        }

        function cargarHistorial() {
            const buscar = document.getElementById('filtroHistorialBuscar').value.trim();
            const fecha = document.getElementById('filtroHistorialFecha').value;
            const lista = document.getElementById('historialListaContainer');
            const contadorTexto = document.getElementById('historialContadorTexto');

            lista.innerHTML = `
                <div class="flex flex-col items-center justify-center py-10 text-slate-400">
                    <svg class="size-6 animate-spin text-[#248294]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span class="mt-2 text-xs">Cargando pacientes desde el disco...</span>
                </div>
            `;

            const url = new URL(urlHistorial, window.location.origin);
            if (buscar) url.searchParams.append('buscar', buscar);
            if (fecha) url.searchParams.append('fecha', fecha);

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    // Actualizar selector de fechas preservando selección
                    const selectFecha = document.getElementById('filtroHistorialFecha');
                    const valorActual = selectFecha.value;
                    const fechas = data.fechas_disponibles || [];
                    
                    let optionsHtml = '<option value="">Todas las fechas</option>';
                    fechas.forEach(f => {
                        const selected = f === valorActual ? 'selected' : '';
                        optionsHtml += `<option value="${f}" ${selected}>${f}</option>`;
                    });
                    selectFecha.innerHTML = optionsHtml;

                    contadorTexto.textContent = `Mostrando ${data.total} paciente(s)`;

                    if (!data.pacientes || data.pacientes.length === 0) {
                        lista.innerHTML = `
                            <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center">
                                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-slate-700">No se encontraron pacientes</p>
                                <p class="mt-1 text-xs text-slate-400">Probá con otro nombre o cambiando el filtro de fecha.</p>
                            </div>
                        `;
                        return;
                    }

                    lista.innerHTML = data.pacientes.map(p => {
                        const archivosHtml = p.archivos.map(arch => `
                            <div class="flex items-center justify-between rounded-lg bg-white px-3 py-2 border border-slate-200/70 text-xs">
                                <div class="flex items-center gap-2 truncate mr-2">
                                    <svg class="size-4 shrink-0 text-[#248294]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                    </svg>
                                    <span class="font-mono font-medium text-slate-700 truncate">${arch.nombre}</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-[11px] text-slate-400 font-mono">${arch.tamano}</span>
                                    <button type="button" onclick="abrirArchivoDirecto('${p.fecha}', '${p.carpeta}', '${arch.nombre}', '${p.disco}')" class="inline-flex items-center gap-1 rounded-md bg-[#248294] px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-[#1a6877] transition cursor-pointer" title="Abrir directamente en Microsoft Word">
                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/>
                                        </svg>
                                        <span>Abrir</span>
                                    </button>
                                    <a href="${arch.url}" download class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-500 hover:text-slate-800 transition cursor-pointer" title="Descargar copia">
                                        <span>Descargar</span>
                                    </a>
                                </div>
                            </div>
                        `).join('');

                        return `
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 transition hover:border-[#91d5da] hover:bg-[#f8fdfd]">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-slate-200/60">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 font-bold text-xs">👤</span>
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-bold text-slate-800 capitalize truncate">${p.nombre}</h4>
                                            <p class="font-mono text-[11px] text-slate-500 truncate">📁 ${p.ruta_relativa}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 flex-wrap">
                                        <span class="rounded-full bg-sky-50 text-sky-800 border border-sky-200 px-2.5 py-0.5 text-[11px] font-bold font-mono">💾 Disco ${p.disco || 'D'}:</span>
                                        <span class="rounded-full bg-slate-200/80 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 font-mono">📅 ${p.fecha_formateada}</span>
                                        <button type="button" onclick="abrirCarpetaDirecta('${p.fecha}', '${p.carpeta}', '${p.disco}')" class="inline-flex items-center gap-1 rounded-lg bg-[#248294] px-2.5 py-1 text-xs font-semibold text-white hover:bg-[#1a6877] transition cursor-pointer" title="Abrir carpeta en el Explorador de Windows">
                                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                            </svg>
                                            <span>Abrir carpeta</span>
                                        </button>
                                        <a href="${p.zip_url}" download class="inline-flex items-center gap-1 rounded-lg bg-slate-100 border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition cursor-pointer" title="Descargar copia ZIP">
                                            <span>ZIP</span>
                                        </a>
                                    </div>
                                </div>

                                <div class="mt-3 space-y-1.5">
                                    ${archivosHtml}
                                </div>
                            </div>
                        `;
                    }).join('');
                })
                .catch(err => {
                    contadorTexto.textContent = 'Error al cargar';
                    lista.innerHTML = `
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700 text-center">
                            Ocurrió un error al cargar el historial. Por favor, intentá nuevamente.
                        </div>
                    `;
                });
        }

        // --- MATERIAL DOCENTE: TEMAS EN POWERPOINT ---
        function abrirModalPowerPoint() {
            const modal = document.getElementById('modalPowerPoint');
            modal.classList.remove('hidden');
            cambiarPestanaPowerPoint('biblioteca');
            actualizarPreviewRutaPptx();
            initPptxDropzone();
        }

        function cerrarModalPowerPoint() {
            const modal = document.getElementById('modalPowerPoint');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function cambiarPestanaPowerPoint(tab) {
            const viewBiblio = document.getElementById('viewPptxBiblioteca');
            const viewSubir = document.getElementById('viewPptxSubir');
            const btnBiblio = document.getElementById('tabBtnPptxBiblioteca');
            const btnSubir = document.getElementById('tabBtnPptxSubir');

            if (tab === 'biblioteca') {
                viewBiblio.classList.remove('hidden');
                viewSubir.classList.add('hidden');

                btnBiblio.className = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition text-[#123b5d] bg-white shadow-xs cursor-pointer';
                btnSubir.className = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition text-slate-500 hover:text-slate-800 cursor-pointer';

                cargarPresentacionesPowerPoint();
            } else {
                viewBiblio.classList.add('hidden');
                viewSubir.classList.remove('hidden');

                btnSubir.className = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition text-[#123b5d] bg-white shadow-xs cursor-pointer';
                btnBiblio.className = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition text-slate-500 hover:text-slate-800 cursor-pointer';

                actualizarPreviewRutaPptx();
            }
        }

        function actualizarPreviewRutaPptx() {
            const previewEl = document.getElementById('pptxPreviewRuta');
            if (!previewEl) return;

            const temaVal = document.getElementById('pptxTemaInput')?.value.trim() || 'General';
            const tituloVal = document.getElementById('pptxTituloInput')?.value.trim();
            const fileInput = document.getElementById('pptxFileInput');
            
            let nombreArchivo = 'archivo.pptx';
            if (tituloVal) {
                const ext = fileInput?.files?.[0] ? fileInput.files[0].name.split('.').pop() : 'pptx';
                nombreArchivo = tituloVal.replace(/\s+/g, '_') + '.' + ext;
            } else if (fileInput?.files?.[0]) {
                nombreArchivo = fileInput.files[0].name;
            }

            const cleanTema = temaVal.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_\-\.]/g, '') || 'General';

            previewEl.textContent = `Disco ${discoSeleccionado}:\\Pediatria\\MaterialDocente\\PowerPoint\\${cleanTema}\\${nombreArchivo}`;
        }

        function seleccionarTemaRapido(tema) {
            const input = document.getElementById('pptxTemaInput');
            if (input) {
                input.value = tema;
                actualizarPreviewRutaPptx();
            }
        }

        function actualizarArchivoSeleccionado(input) {
            const emptyState = document.getElementById('pptxDropzoneEmpty');
            const selectedState = document.getElementById('pptxDropzoneSelected');
            const nombreEl = document.getElementById('pptxSelectedNombre');
            const tamanoEl = document.getElementById('pptxSelectedTamano');
            const tituloInput = document.getElementById('pptxTituloInput');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                archivoPptxSeleccionado = file;

                const bytes = file.size;
                const tamanoStr = bytes >= 1048576 
                    ? (bytes / 1048576).toFixed(2) + ' MB' 
                    : (bytes / 1024).toFixed(0) + ' KB';

                nombreEl.textContent = file.name;
                tamanoEl.textContent = tamanoStr;

                emptyState.classList.add('hidden');
                selectedState.classList.remove('hidden');
                selectedState.classList.add('flex');

                if (!tituloInput.value) {
                    const cleanName = file.name.substring(0, file.name.lastIndexOf('.')).replace(/[_-]+/g, ' ');
                    tituloInput.value = cleanName;
                }

                actualizarPreviewRutaPptx();
            }
        }

        function deseleccionarArchivoPptx() {
            const input = document.getElementById('pptxFileInput');
            if (input) input.value = '';
            archivoPptxSeleccionado = null;

            const emptyState = document.getElementById('pptxDropzoneEmpty');
            const selectedState = document.getElementById('pptxDropzoneSelected');

            if (selectedState) {
                selectedState.classList.add('hidden');
                selectedState.classList.remove('flex');
            }
            if (emptyState) {
                emptyState.classList.remove('hidden');
            }

            actualizarPreviewRutaPptx();
        }

        function debounceCargarPowerPoint() {
            const buscarInput = document.getElementById('filtroPowerPointBuscar');
            const btnLimpiar = document.getElementById('btnLimpiarBuscarPptx');

            if (btnLimpiar) {
                if (buscarInput.value.trim().length > 0) {
                    btnLimpiar.classList.remove('hidden');
                } else {
                    btnLimpiar.classList.add('hidden');
                }
            }

            clearTimeout(debouncePptxTimer);
            debouncePptxTimer = setTimeout(() => {
                cargarPresentacionesPowerPoint();
            }, 250);
        }

        function limpiarBuscarPptx() {
            const buscarInput = document.getElementById('filtroPowerPointBuscar');
            const btnLimpiar = document.getElementById('btnLimpiarBuscarPptx');
            if (buscarInput) buscarInput.value = '';
            if (btnLimpiar) btnLimpiar.classList.add('hidden');
            cargarPresentacionesPowerPoint();
        }

        function cargarPresentacionesPowerPoint() {
            const buscar = document.getElementById('filtroPowerPointBuscar')?.value.trim() || '';
            const tema = document.getElementById('filtroPowerPointTema')?.value || '';
            const lista = document.getElementById('powerPointListaContainer');
            const contadorTexto = document.getElementById('powerPointContadorTexto');
            const badgeCount = document.getElementById('pptxBadgeCount');
            const footerRuta = document.getElementById('pptxFooterRuta');

            if (!lista) return;

            lista.innerHTML = `
                <div class="flex flex-col items-center justify-center py-10 text-slate-400">
                    <svg class="size-6 animate-spin text-[#d97706]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span class="mt-2 text-xs">Cargando presentaciones de PowerPoint...</span>
                </div>
            `;

            const url = new URL(urlPowerPointListar, window.location.origin);
            if (buscar) url.searchParams.append('q', buscar);
            if (tema) url.searchParams.append('tema', tema);
            if (discoSeleccionado) url.searchParams.append('disco', discoSeleccionado);

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    // Actualizar selector de temas preservando selección
                    const selectTema = document.getElementById('filtroPowerPointTema');
                    if (selectTema) {
                        const valorActual = selectTema.value;
                        const temas = data.temas || [];
                        let optionsHtml = '<option value="">Todos los temas</option>';
                        temas.forEach(t => {
                            const selected = t === valorActual ? 'selected' : '';
                            optionsHtml += `<option value="${t}" ${selected}>${t}</option>`;
                        });
                        selectTema.innerHTML = optionsHtml;
                    }

                    if (badgeCount) {
                        badgeCount.textContent = data.total || 0;
                    }

                    if (footerRuta && data.ruta_base) {
                        footerRuta.textContent = data.ruta_base;
                    }

                    if (contadorTexto) {
                        contadorTexto.textContent = `Mostrando ${data.total} presentación(es) de PowerPoint`;
                    }

                    if (!data.presentaciones || data.presentaciones.length === 0) {
                        lista.innerHTML = `
                            <div class="rounded-xl border border-dashed border-amber-200/90 bg-amber-50/30 p-8 text-center">
                                <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-[#fff2d9] text-[#d97706]">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 3h20v14H2z"/><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 8h4a2 2 0 0 1 0 4H7z"/>
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-bold text-slate-800">No se encontraron presentaciones de PowerPoint</p>
                                <p class="mt-1 text-xs text-slate-400">Probá buscando con otro término o subí una nueva clase para este tema.</p>
                                <button type="button" onclick="cambiarPestanaPowerPoint('subir')" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-[#d97706] px-4 py-2 text-xs font-bold text-white hover:bg-[#b45309] transition cursor-pointer shadow-xs">
                                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    <span>Subir la primera presentación</span>
                                </button>
                            </div>
                        `;
                        return;
                    }

                    lista.innerHTML = data.presentaciones.map(item => `
                        <div class="rounded-xl border border-slate-200 bg-white p-3.5 transition hover:border-[#fed7aa] hover:bg-[#fffdfa] shadow-xs">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#fff2d9] text-[#d97706] font-bold text-xs shadow-xs">
                                        PPT
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-800 capitalize truncate" title="${item.titulo}">
                                            ${item.titulo}
                                        </h4>
                                        <div class="mt-1 flex items-center gap-2 flex-wrap text-[11px] text-slate-400">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-semibold">
                                                🏷️ ${item.tema}
                                            </span>
                                            <span class="font-mono text-slate-500">${item.archivo}</span>
                                            <span>·</span>
                                            <span class="font-mono">${item.tamano}</span>
                                            <span>·</span>
                                            <span>${item.fecha_formateada}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 flex-wrap">
                                    <button type="button" onclick="abrirArchivoPowerPointDirecto('${item.tema}', '${item.archivo}', '${item.disco}')" class="inline-flex items-center gap-1.5 rounded-lg bg-[#d97706] hover:bg-[#b45309] px-3 py-1.5 text-xs font-bold text-white transition cursor-pointer shadow-xs active:scale-[0.98]" title="Abrir directamente en Microsoft PowerPoint">
                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="5 3 19 12 5 21 5 3"/>
                                        </svg>
                                        <span>Abrir</span>
                                    </button>

                                    <button type="button" onclick="abrirCarpetaPowerPointDirecta('${item.tema}', '${item.disco}')" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition cursor-pointer" title="Abrir carpeta de este tema en Windows">
                                        <svg class="size-3.5 text-amber-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                        </svg>
                                        <span>Carpeta</span>
                                    </button>

                                    <a href="${item.url_descarga}" download class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1.5 text-xs font-medium text-slate-500 hover:text-slate-800 transition cursor-pointer" title="Descargar copia">
                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15"/>
                                        </svg>
                                    </a>

                                    <button type="button" onclick="eliminarPresentacionPowerPoint('${item.tema}', '${item.archivo}', '${item.disco}')" class="inline-flex items-center justify-center size-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="Eliminar presentación">
                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('');
                })
                .catch(() => {
                    contadorTexto.textContent = 'Error al cargar';
                    lista.innerHTML = `
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700 text-center">
                            Ocurrió un error al cargar las presentaciones. Por favor, intentá nuevamente.
                        </div>
                    `;
                });
        }

        function abrirArchivoPowerPointDirecto(tema, archivo, disco = null) {
            const d = disco || discoSeleccionado;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            handleAction('Abriendo PowerPoint...', `${archivo}`);

            fetch(urlPowerPointAbrir, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ tema, archivo, disco: d })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    handleAction('PowerPoint abierto en Windows', archivo);
                } else {
                    handleAction('Aviso', data.message || 'No se pudo abrir la presentación');
                }
            })
            .catch(() => {
                handleAction('Error', 'No se pudo comunicar con el servidor para abrir PowerPoint');
            });
        }

        function abrirCarpetaPowerPointDirecta(tema = null, disco = null) {
            const d = disco || discoSeleccionado;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            handleAction('Abriendo carpeta de PowerPoint...', tema ? `Tema: ${tema}` : 'Material docente');

            fetch(urlPowerPointAbrirCarpeta, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ tema, disco: d })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    handleAction('Carpeta abierta en Windows', data.ruta || 'MaterialDocente/PowerPoint');
                } else {
                    handleAction('Aviso', data.message || 'No se pudo abrir la carpeta');
                }
            })
            .catch(() => {
                handleAction('Error', 'No se pudo abrir el Explorador de Windows');
            });
        }

        function submitSubirPowerPoint() {
            const fileInput = document.getElementById('pptxFileInput');
            const temaInput = document.getElementById('pptxTemaInput');
            const tituloInput = document.getElementById('pptxTituloInput');
            const errorBox = document.getElementById('errorSubirPptx');
            const exitoBox = document.getElementById('exitoSubirPptx');
            const btnSubmit = document.getElementById('btnSubmitSubirPptx');
            const textBtn = document.getElementById('textBtnSubmitSubirPptx');

            errorBox.classList.add('hidden');
            exitoBox.classList.add('hidden');

            if (!fileInput.files || !fileInput.files[0]) {
                errorBox.textContent = 'Por favor selecciona un archivo PowerPoint (.pptx o .ppt).';
                errorBox.classList.remove('hidden');
                return;
            }

            const formData = new FormData();
            formData.append('archivo', fileInput.files[0]);
            formData.append('tema', temaInput.value.trim() || 'General');
            formData.append('titulo', tituloInput.value.trim());
            formData.append('disco', discoSeleccionado);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            btnSubmit.disabled = true;
            textBtn.textContent = 'Subiendo presentación al disco...';

            fetch(urlPowerPointSubir, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                textBtn.textContent = 'Guardar presentación en la biblioteca';

                if (data.success) {
                    ultimaPresentacionSubida = {
                        archivo: data.archivo.nombre,
                        tema: data.archivo.tema,
                        disco: data.archivo.disco
                    };

                    exitoBox.classList.remove('hidden');
                    document.getElementById('exitoSubirPptxTexto').textContent = `${data.archivo.nombre} guardado en el tema "${data.archivo.tema}" (${data.archivo.disco}:\\...)`;

                    deseleccionarArchivoPptx();
                    tituloInput.value = '';

                    cargarPresentacionesPowerPoint();
                    handleAction('Presentación subida con éxito', data.archivo.nombre);
                } else {
                    errorBox.textContent = data.message || 'Error al subir la presentación.';
                    errorBox.classList.remove('hidden');
                }
            })
            .catch(() => {
                btnSubmit.disabled = false;
                textBtn.textContent = 'Guardar presentación en la biblioteca';
                errorBox.textContent = 'Ocurrió un error inesperado al subir el archivo.';
                errorBox.classList.remove('hidden');
            });
        }

        function abrirRecienSubido() {
            if (ultimaPresentacionSubida) {
                abrirArchivoPowerPointDirecto(
                    ultimaPresentacionSubida.tema,
                    ultimaPresentacionSubida.archivo,
                    ultimaPresentacionSubida.disco
                );
            }
        }

        function eliminarPresentacionPowerPoint(tema, archivo, disco) {
            if (!confirm(`¿Estás seguro de que deseas eliminar la presentación "${archivo}"? Esta acción borrará el archivo del disco.`)) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            fetch(urlPowerPointEliminar, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ tema, archivo, disco })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    handleAction('Archivo eliminado', archivo);
                    cargarPresentacionesPowerPoint();
                } else {
                    handleAction('Error', data.message || 'No se pudo eliminar el archivo');
                }
            })
            .catch(() => {
                handleAction('Error', 'No se pudo eliminar la presentación');
            });
        }

        function initPptxDropzone() {
            const dropzone = document.getElementById('pptxDropzone');
            if (!dropzone || dropzone.dataset.initialized) return;

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('border-[#d97706]', 'bg-[#fffbf5]');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('border-[#d97706]', 'bg-[#fffbf5]');
                }, false);
            });

            dropzone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files && files.length > 0) {
                    const input = document.getElementById('pptxFileInput');
                    input.files = files;
                    actualizarArchivoSeleccionado(input);
                }
            }, false);

            dropzone.dataset.initialized = 'true';
        }

        // --- FUNCIONES GENERALES ---
        function toggleSidebar(open) {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (open) {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        function handleAction(label, sub = '') {
            const alertBox = document.getElementById('actionAlert');
            const titleEl = document.getElementById('actionAlertTitle');
            const subEl = document.getElementById('actionAlertSub');
            
            if (!alertBox || !titleEl) return;

            titleEl.textContent = label;
            if (subEl) {
                subEl.textContent = sub || 'Operación completada';
            }

            alertBox.classList.remove('hidden');
            alertBox.classList.add('flex');

            if (alertTimeout) {
                clearTimeout(alertTimeout);
            }

            alertTimeout = setTimeout(() => {
                alertBox.classList.add('hidden');
                alertBox.classList.remove('flex');
            }, 3600);
        }

        // Cerrar modales con tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cerrarModalIngresarPaciente();
                cerrarModalHistorial();
                cerrarModalPowerPoint();
                toggleSidebar(false);
            }
        });

        // Resaltar ítem de navegación activo al hacer scroll
        const sections = document.querySelectorAll('section[id], div[id="inicio"]');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            let current = 'inicio';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 120;
                if (pageYOffset >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                const href = link.getAttribute('href').replace('#', '');
                if (href === current) {
                    link.classList.add('bg-white/12', 'text-white');
                    link.classList.remove('text-blue-100/70');
                } else {
                    link.classList.remove('bg-white/12', 'text-white');
                    link.classList.add('text-blue-100/70');
                }
            });
        });
    </script>
</body>
</html>
