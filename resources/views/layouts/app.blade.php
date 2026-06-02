<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('app.name') }} — Service Request Management">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Case System') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @if(app()->isLocale('ar'))
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @else
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @endif

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    {{-- Bootstrap CSS + Icons always loaded from CDN — required for navbar collapse to work --}}
    @if(app()->isLocale('ar'))
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        /* ─────────────────────────────────────────────────
           DESIGN TOKENS
        ───────────────────────────────────────────────── */
        :root {
            --font-base: 'Inter', system-ui, -apple-system, sans-serif;

            --color-bg:          #f0f2f5;
            --color-surface:     #ffffff;
            --color-border:      #e5e7eb;
            --color-border-light:#f3f4f6;

            --color-primary:     #2563eb;
            --color-primary-soft:#eff6ff;
            --color-success:     #16a34a;
            --color-success-soft:#f0fdf4;
            --color-danger:      #dc2626;
            --color-danger-soft: #fef2f2;
            --color-warning:     #d97706;
            --color-warning-soft:#fffbeb;
            --color-muted:       #6b7280;
            --color-subtle:      #9ca3af;

            --nav-bg:            #0f172a;
            --nav-border:        rgba(255,255,255,.06);
            --nav-hover:         rgba(255,255,255,.08);
            --nav-active:        rgba(255,255,255,.13);

            --radius-sm:  6px;
            --radius-md:  10px;
            --radius-lg:  14px;
            --radius-xl:  18px;
            --radius-pill:999px;

            --shadow-xs:  0 1px 2px rgba(0,0,0,.05);
            --shadow-sm:  0 1px 6px rgba(0,0,0,.07);
            --shadow-md:  0 4px 16px rgba(0,0,0,.08);
            --shadow-lg:  0 8px 32px rgba(0,0,0,.10);

            --transition: .18s ease;
        }

        /* ─────────────────────────────────────────────────
           ARABIC / RTL OVERRIDES
        ───────────────────────────────────────────────── */
        html[lang="ar"] {
            --font-base: 'Cairo', system-ui, sans-serif;
        }
        html[lang="ar"] body {
            letter-spacing: 0;
        }
        html[dir="rtl"] .site-nav .navbar-brand { letter-spacing: 0; }
        html[dir="rtl"] .bi { display: inline-block; }
        html[dir="rtl"] .me-1 { margin-right: 0 !important; margin-left: .25rem !important; }
        html[dir="rtl"] .me-2 { margin-right: 0 !important; margin-left: .5rem !important; }
        html[dir="rtl"] .ms-1 { margin-left: 0 !important; margin-right: .25rem !important; }
        html[dir="rtl"] .ms-2 { margin-left: 0 !important; margin-right: .5rem !important; }
        html[dir="rtl"] .ps-4 { padding-right: 1.5rem !important; padding-left: 0 !important; }
        html[dir="rtl"] .pe-4 { padding-left: 1.5rem !important; padding-right: 0 !important; }
        html[dir="rtl"] .notif-panel { left: 0; right: auto; }
        html[dir="rtl"] .tl-connector { }
        html[dir="rtl"] .toast-stack { left: 1.25rem; right: auto; }
        html[dir="rtl"] .dropdown-menu-end { --bs-position: start; }
        html[dir="rtl"] .notif-count { left: 3px; right: auto; }
        html[dir="rtl"] .lang-btn-active { font-weight: 700; }

        /* ─────────────────────────────────────────────────
           BASE
        ───────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-base);
            background: var(--color-bg);
            color: #1e293b;
            font-size: 14.5px;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Custom scrollbar (webkit) */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Skip link for accessibility */
        .skip-link {
            position: absolute; top: -100%; left: 1rem;
            background: var(--color-primary); color: #fff;
            padding: .5rem 1rem; border-radius: var(--radius-sm);
            font-weight: 600; z-index: 9999; transition: top .2s;
        }
        .skip-link:focus { top: 1rem; }

        /* ─────────────────────────────────────────────────
           TOP LOADING BAR
        ───────────────────────────────────────────────── */
        #page-loader {
            position: fixed; top: 0; left: 0; right: 0;
            height: 3px; z-index: 9998;
            background: linear-gradient(90deg, var(--color-primary), #60a5fa, var(--color-primary));
            background-size: 200% 100%;
            animation: loader-slide 1.2s ease infinite;
            display: none;
        }
        #page-loader.active { display: block; }
        @keyframes loader-slide {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ─────────────────────────────────────────────────
           NAVBAR
        ───────────────────────────────────────────────── */
        .site-nav {
            background: var(--nav-bg);
            border-bottom: 1px solid var(--nav-border);
            position: sticky; top: 0; z-index: 1030;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .site-nav .navbar-brand {
            color: #fff !important;
            gap: .75rem;
            padding-top: .55rem;
            padding-bottom: .55rem;
        }
        .brand-mark {
            width: 40px; height: 40px;
            background: linear-gradient(145deg, #b45309, #f59e0b 60%, #fbbf24);
            border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(245,158,11,.30), inset 0 1px 0 rgba(255,255,255,.18);
            position: relative;
            overflow: hidden;
        }
        .brand-mark::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,.12) 0%, transparent 55%);
            border-radius: inherit;
        }
        .brand-mark-letter {
            font-size: 1.35rem;
            font-weight: 900;
            color: #fff;
            font-family: 'Cairo', 'Inter', system-ui, sans-serif;
            line-height: 1;
            position: relative;
            text-shadow: 0 1px 3px rgba(0,0,0,.25);
            letter-spacing: 0;
        }
        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        .brand-name {
            font-size: .975rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.2px;
            white-space: nowrap;
        }
        .brand-sub {
            font-size: .62rem;
            font-weight: 500;
            color: rgba(255,255,255,.45);
            letter-spacing: .09em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        html[dir="rtl"] .brand-name { letter-spacing: 0; }
        .site-nav .nav-link {
            color: rgba(255,255,255,.7) !important;
            font-weight: 500;
            font-size: .875rem;
            padding: .45rem .85rem !important;
            border-radius: var(--radius-sm);
            transition: color var(--transition), background var(--transition);
        }
        .site-nav .nav-link:hover,
        .site-nav .nav-link.show  { color: #fff !important; background: var(--nav-hover); }
        .site-nav .nav-link.active { color: #fff !important; background: var(--nav-active); }

        .site-nav .dropdown-menu {
            border: 1px solid rgba(255,255,255,.08);
            background: #1e293b;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            padding: .35rem;
            min-width: 200px;
        }
        .site-nav .dropdown-menu .dropdown-item {
            color: rgba(255,255,255,.78);
            font-size: .855rem;
            font-weight: 500;
            padding: .5rem .85rem;
            border-radius: var(--radius-sm);
            transition: background var(--transition), color var(--transition);
            display: flex; align-items: center; gap: .55rem;
        }
        .site-nav .dropdown-menu .dropdown-item:hover { background: rgba(255,255,255,.08); color: #fff; }
        .site-nav .dropdown-menu .dropdown-item.text-danger { color: #f87171 !important; }
        .site-nav .dropdown-menu .dropdown-item.text-danger:hover { background: rgba(239,68,68,.12); }
        .site-nav .dropdown-menu hr { border-color: rgba(255,255,255,.08); margin: .35rem 0; }

        /* User avatar chip */
        .nav-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: var(--color-primary);
            color: #fff; font-weight: 700; font-size: .78rem;
            display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        /* ─────────────────────────────────────────────────
           NOTIFICATION BELL
        ───────────────────────────────────────────────── */
        .notif-trigger {
            position: relative;
            color: rgba(255,255,255,.7) !important;
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            border-radius: var(--radius-sm);
            transition: color var(--transition), background var(--transition);
            padding: 0 !important;
        }
        .notif-trigger:hover { color: #fff !important; background: var(--nav-hover); }

        .notif-count {
            position: absolute;
            top: 3px; right: 3px;
            min-width: 16px; height: 16px;
            background: var(--color-danger);
            color: #fff;
            font-size: .62rem; font-weight: 700;
            border-radius: var(--radius-pill);
            display: flex; align-items: center; justify-content: center;
            padding: 0 .3em;
            border: 2px solid var(--nav-bg);
            line-height: 1;
        }

        .notif-panel {
            width: 380px;
            /* ⚠ Do NOT set display here — Bootstrap toggles display:none ↔ block via .show.
               We override only when .show is present (below). */
            border: 1px solid rgba(255,255,255,.08) !important;
            background: #1e293b !important;
            border-radius: var(--radius-lg) !important;
            box-shadow: var(--shadow-lg) !important;
            padding: 0 !important;
        }
        /* Apply flex layout only when Bootstrap makes the panel visible */
        .notif-panel.show {
            display: flex !important;
            flex-direction: column;
            max-height: 480px;
            overflow: hidden;
        }
        .notif-panel-header {
            padding: .85rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0;
        }
        .notif-panel-header .title {
            font-size: .875rem; font-weight: 700; color: #fff; letter-spacing: -.2px;
        }
        .notif-panel-header .mark-all-btn {
            font-size: .75rem; color: rgba(255,255,255,.45);
            background: none; border: none; padding: 0; cursor: pointer;
            transition: color var(--transition);
        }
        .notif-panel-header .mark-all-btn:hover { color: #60a5fa; }

        .notif-list { overflow-y: auto; flex: 1; }

        .notif-row {
            padding: .75rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.05);
            display: flex; align-items: flex-start; gap: .65rem;
            transition: background var(--transition);
            cursor: default;
        }
        .notif-row:last-child { border-bottom: none; }
        .notif-row:hover { background: rgba(255,255,255,.04); }
        .notif-row.unread { background: rgba(37,99,235,.1); }
        .notif-row.unread:hover { background: rgba(37,99,235,.15); }

        .notif-icon {
            width: 34px; height: 34px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .9rem; flex-shrink: 0; margin-top: .1rem;
        }
        .notif-row .notif-title  { font-size: .82rem; font-weight: 600; color: #f1f5f9; line-height: 1.3; }
        .notif-row .notif-msg    { font-size: .775rem; color: rgba(255,255,255,.5); margin-top: .1rem; line-height: 1.35; }
        .notif-row .notif-time   { font-size: .7rem; color: rgba(255,255,255,.3); margin-top: .3rem; }
        .notif-row .notif-actions { display: flex; align-items: center; gap: .75rem; margin-top: .3rem; }
        .notif-row .notif-actions a,
        .notif-row .notif-actions button {
            font-size: .72rem; color: #60a5fa;
            background: none; border: none; padding: 0; cursor: pointer;
            transition: color var(--transition);
        }
        .notif-row .notif-actions a:hover,
        .notif-row .notif-actions button:hover { color: #93c5fd; }

        .notif-empty {
            padding: 2.5rem 1rem; text-align: center; color: rgba(255,255,255,.3);
        }
        .notif-empty i { font-size: 2rem; display: block; margin-bottom: .5rem; }
        .notif-empty span { font-size: .82rem; }

        /* ─────────────────────────────────────────────────
           FLASH TOASTS
        ───────────────────────────────────────────────── */
        .toast-stack {
            position: fixed;
            top: 72px; right: 1.25rem;
            z-index: 1090;
            display: flex; flex-direction: column; gap: .5rem;
            width: 340px; max-width: calc(100vw - 2rem);
        }
        .flash-toast {
            background: var(--color-surface);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--color-border);
            border-left: 4px solid transparent;
            padding: .9rem 1rem;
            display: flex; align-items: flex-start; gap: .75rem;
            animation: toast-in .28s cubic-bezier(.34,1.56,.64,1) forwards;
        }
        .flash-toast.success { border-left-color: var(--color-success); }
        .flash-toast.danger  { border-left-color: var(--color-danger); }
        .flash-toast.info    { border-left-color: var(--color-primary); }
        .flash-toast.warning { border-left-color: var(--color-warning); }

        .flash-toast .toast-icon {
            font-size: 1.15rem; flex-shrink: 0; margin-top: .05rem;
        }
        .flash-toast.success .toast-icon { color: var(--color-success); }
        .flash-toast.danger  .toast-icon { color: var(--color-danger); }
        .flash-toast.info    .toast-icon { color: var(--color-primary); }
        .flash-toast.warning .toast-icon { color: var(--color-warning); }

        .flash-toast .toast-body { flex: 1; min-width: 0; }
        .flash-toast .toast-body p { margin: 0; font-size: .855rem; font-weight: 500; color: #1e293b; line-height: 1.45; }

        .flash-toast .toast-close {
            background: none; border: none; padding: 0; color: var(--color-subtle);
            cursor: pointer; font-size: 1.1rem; line-height: 1; flex-shrink: 0;
            transition: color var(--transition);
        }
        .flash-toast .toast-close:hover { color: #374151; }

        .flash-toast.hiding {
            animation: toast-out .22s ease forwards;
        }

        @keyframes toast-in {
            from { opacity: 0; transform: translateX(24px) scale(.97); }
            to   { opacity: 1; transform: translateX(0) scale(1); }
        }
        @keyframes toast-out {
            from { opacity: 1; transform: translateX(0) scale(1); }
            to   { opacity: 0; transform: translateX(24px) scale(.95); }
        }

        /* ─────────────────────────────────────────────────
           PAGE CHROME
        ───────────────────────────────────────────────── */
        .page-wrapper {
            max-width: 1320px;
            margin: 0 auto;
            padding: 1.5rem 1.25rem 3rem;
        }

        /* Breadcrumb (optional per-page) */
        .app-breadcrumb {
            display: flex; align-items: center; gap: .35rem;
            font-size: .8rem; color: var(--color-muted);
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }
        .app-breadcrumb a { color: var(--color-muted); text-decoration: none; transition: color var(--transition); }
        .app-breadcrumb a:hover { color: var(--color-primary); }
        .app-breadcrumb .sep { color: var(--color-border); }
        .app-breadcrumb .current { color: #1e293b; font-weight: 600; }

        /* ─────────────────────────────────────────────────
           SHARED COMPONENTS (used across pages)
        ───────────────────────────────────────────────── */
        .page-card {
            background: var(--color-surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--color-border);
            padding: 1.75rem;
        }
        .page-card-sm { padding: 1.25rem; }

        /* Section heading inside page cards */
        .section-title {
            font-size: .8rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .07em;
            color: var(--color-muted);
            margin-bottom: 1rem;
        }

        /* Tables */
        .table { --bs-table-hover-bg: #f8faff; }
        .table > :not(caption) > * > * { padding: .8rem 1rem; vertical-align: middle; }
        .table thead th {
            font-size: .78rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .06em;
            color: var(--color-muted);
            background: #f8fafc;
            border-bottom: 1px solid var(--color-border);
        }
        .table tbody td { color: #374151; font-size: .875rem; }
        .table tbody tr { transition: background var(--transition); }

        /* Badges */
        .badge-pill {
            font-size: .72rem; font-weight: 600;
            padding: .28em .75em;
            border-radius: var(--radius-pill);
        }
        .badge-status {
            font-size: .74rem; padding: .3em .75em;
            border-radius: var(--radius-pill); font-weight: 600;
        }

        /* Forms */
        .form-label { font-weight: 600; font-size: .855rem; color: #374151; margin-bottom: .4rem; }
        .form-control, .form-select {
            border-radius: var(--radius-sm);
            border-color: var(--color-border);
            font-size: .875rem;
            transition: border-color var(--transition), box-shadow var(--transition);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }
        .form-control::placeholder { color: #9ca3af; }

        /* Buttons */
        .btn { font-weight: 600; font-size: .855rem; border-radius: var(--radius-sm); }
        .btn-sm { font-size: .8rem; }
        .btn-xs { font-size: .75rem; padding: .2rem .6rem; border-radius: var(--radius-sm); }
        .btn-action { font-size: .8rem; padding: .3rem .7rem; }

        /* Stat card */
        .stat-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 1.25rem 1.5rem;
            transition: transform var(--transition), box-shadow var(--transition);
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .stat-card .stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1; }
        .stat-card .stat-label { font-size: .78rem; color: var(--color-muted); font-weight: 500; margin-top: .25rem; }

        /* ─────────────────────────────────────────────────
           TIMELINE (vertical, shared)
        ───────────────────────────────────────────────── */
        .tl-wrapper { position: relative; }
        .tl-item { display: flex; gap: .9rem; }
        .tl-left {
            display: flex; flex-direction: column;
            align-items: center; flex-shrink: 0; width: 2rem;
        }
        .tl-marker {
            width: 2rem; height: 2rem; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; flex-shrink: 0; z-index: 1;
        }
        .tl-marker-done    { background: var(--color-success); color: #fff; }
        .tl-marker-current { background: var(--color-primary); color: #fff; animation: tl-pulse 2s infinite; }
        .tl-marker-future  { background: #fff; border: 2px solid var(--color-border); color: var(--color-subtle); }
        .tl-marker-audit   { color: #fff; }
        .tl-connector { flex: 1; width: 2px; background: var(--color-border); min-height: 1.25rem; margin: .2rem 0; }
        .tl-connector-done { background: var(--color-success); }
        .tl-body { flex: 1; padding-bottom: 1.25rem; min-width: 0; }
        .tl-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: .9rem 1rem;
            transition: box-shadow var(--transition);
        }
        .tl-card:hover { box-shadow: var(--shadow-sm); }
        .tl-card-current { border-color: var(--color-primary); border-width: 2px; background: var(--color-primary-soft); }
        .tl-card-done { background: #fafafa; }
        .tl-item.tl-future .tl-card { opacity: .7; }
        .tl-item:last-child .tl-body { padding-bottom: 0; }
        @keyframes tl-pulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(37,99,235,.35); }
            60%      { box-shadow: 0 0 0 7px rgba(37,99,235,.0); }
        }

        /* ─────────────────────────────────────────────────
           STAGE PROGRESS BAR — card-style stepper
        ───────────────────────────────────────────────── */
        .stage-progress {
            display: flex;
            align-items: stretch;
            gap: 0;
            overflow-x: auto;
            scrollbar-width: none;
            padding: .25rem 0;
        }
        .stage-progress::-webkit-scrollbar { display: none; }

        .stage-step {
            display: flex;
            flex-direction: row;
            align-items: center;
            flex: 1;
            min-width: 90px;
            position: relative;
        }
        .stage-step:last-child { flex: 0 1 auto; }

        /* The card itself */
        .stage-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .7rem .5rem .65rem;
            border-radius: var(--radius-md);
            text-align: center;
            flex-shrink: 0;
            width: 82px;
            transition: transform .2s, box-shadow .2s;
        }

        .stage-circle {
            width: 38px; height: 38px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .95rem;
            flex-shrink: 0;
            border: 2px solid var(--color-border);
            background: var(--color-surface);
            color: var(--color-subtle);
            transition: all .25s;
        }
        .stage-num {
            font-size: .58rem; color: var(--color-subtle);
            font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
            line-height: 1;
        }
        .stage-name {
            font-size: .68rem; font-weight: 700;
            color: #374151; line-height: 1.3;
        }
        .stage-status-badge {
            display: inline-block;
            font-size: .58rem; font-weight: 700;
            padding: .12em .55em;
            border-radius: var(--radius-pill);
        }

        .stage-connector {
            flex: 1;
            height: 2px;
            background: var(--color-border);
            transition: background .25s;
            min-width: 6px;
            align-self: center;
            margin-top: -10px;
        }

        /* past */
        .stage-step.past .stage-card    { background: #f0fdf4; }
        .stage-step.past .stage-circle  { background: var(--color-success); border-color: var(--color-success); color: #fff; }
        .stage-step.past .stage-connector { background: var(--color-success); }
        .stage-step.past .stage-name    { color: var(--color-success); }
        .stage-step.past .stage-num     { color: var(--color-success); opacity:.7; }
        .stage-step.past .stage-status-badge { background: #dcfce7; color: #166534; }

        /* current */
        .stage-step.current .stage-card {
            background: #eff6ff;
            box-shadow: 0 0 0 2px var(--color-primary);
        }
        .stage-step.current .stage-circle {
            background: var(--color-primary); border-color: var(--color-primary); color: #fff;
            box-shadow: 0 0 0 5px rgba(37,99,235,.15);
        }
        .stage-step.current .stage-name { color: var(--color-primary); font-weight: 800; }
        .stage-step.current .stage-num  { color: var(--color-primary); }
        .stage-step.current .stage-status-badge { background: #dbeafe; color: #1e40af; }

        /* future */
        .stage-step.future .stage-card  { background: transparent; }
        .stage-step.future .stage-circle { opacity: .4; }
        .stage-step.future .stage-name  { color: var(--color-subtle); }
        .stage-step.future .stage-status-badge { display: none; }

        /* rejected */
        .stage-step.rejected .stage-card   { background: #fff1f2; box-shadow: 0 0 0 2px var(--color-danger); }
        .stage-step.rejected .stage-circle { background: var(--color-danger); border-color: var(--color-danger); color: #fff; }
        .stage-step.rejected .stage-name   { color: var(--color-danger); }
        .stage-step.rejected .stage-status-badge { background: #fee2e2; color: #991b1b; }

        /* ─────────────────────────────────────────────────
           PAGINATION (global compact override)
        ───────────────────────────────────────────────── */
        .pagination {
            --bs-pagination-font-size: .8rem;
            --bs-pagination-padding-x: .65rem;
            --bs-pagination-padding-y: .32rem;
            --bs-pagination-border-radius: var(--radius-sm);
            margin-bottom: 0;
            gap: .2rem;
            flex-wrap: wrap;
        }
        .pagination .page-link {
            border-radius: var(--radius-sm) !important;
            font-size: .8rem;
            line-height: 1.4;
            color: var(--color-primary);
            transition: background var(--transition), color var(--transition);
        }
        .pagination .page-item.active .page-link {
            background: var(--color-primary);
            border-color: var(--color-primary);
        }
        .pagination .page-item.disabled .page-link { color: var(--color-subtle); }
        .pagination .page-link i { vertical-align: middle; }

        /* ─────────────────────────────────────────────────
           UTILITIES
        ───────────────────────────────────────────────── */
        .fw-500 { font-weight: 500; }
        .fw-600 { font-weight: 600; }
        .fw-700 { font-weight: 700; }
        .text-xs { font-size: .78rem; }
        .text-2xs { font-size: .7rem; }
        .gap-xs { gap: .35rem; }
        .rounded-pill-sm { border-radius: var(--radius-pill); }

        /* Collapsible arrow */
        [data-bs-toggle="collapse"] .collapse-arrow {
            transition: transform .2s;
            font-size: .75rem; color: var(--color-subtle);
        }
        [data-bs-toggle="collapse"][aria-expanded="true"] .collapse-arrow {
            transform: rotate(180deg);
        }
        /* Mobile: compact navbar */
        @media (max-width: 575.98px) {
            .brand-mark { width: 32px !important; height: 32px !important; }
            .brand-mark-letter { font-size: .85rem !important; }
            .brand-name { font-size: .8rem !important; }
            .brand-sub { display: none; }
            .site-nav { padding-top: .4rem !important; padding-bottom: .4rem !important; }
            .site-nav .nav-link { font-size: .8rem !important; padding: .35rem .5rem !important; }
            .nav-avatar { width: 28px !important; height: 28px !important; font-size: .7rem !important; }
            .navbar-brand { gap: .4rem !important; }
        }
    </style>

    @stack('styles')
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to content</a>
    <div id="page-loader"></div>

    {{-- ═══════════════════════════════════════════════════════
         NAVBAR
    ════════════════════════════════════════════════════════ --}}
    <nav class="site-nav navbar navbar-expand-lg" aria-label="Main navigation">
      <div class="container-fluid px-4" style="max-width:1320px;margin:0 auto;">

        {{-- Brand --}}
        <a class="navbar-brand d-flex align-items-center text-decoration-none"
           href="{{ route('service-requests.index') }}">
            <span class="brand-mark">
                <span class="brand-mark-letter">م</span>
            </span>
            <span class="brand-text">
                <span class="brand-name">ALMuhalab</span>
                <span class="brand-sub">International Co.</span>
            </span>
        </a>

        <div class="collapse navbar-collapse order-lg-0" id="navMain">

          {{-- Left links --}}
          <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1 mt-2 mt-lg-0">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 me-1"></i>{{ __('Dashboard') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('service-requests.index') ? 'active' : '' }}"
                   href="{{ route('service-requests.index') }}">
                    <i class="bi bi-list-ul me-1"></i>{{ __('Requests') }}
                </a>
            </li>

            @auth
                @if(auth()->user()->hasPermission('create_request'))
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="{{ route('service-requests.create') }}">
                        <i class="bi bi-plus-circle me-1"></i>{{ __('New Request') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_trash'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('service-requests.trash') ? 'active' : '' }}"
                       href="{{ route('service-requests.trash') }}">
                        <i class="bi bi-trash me-1"></i>{{ __('Trash') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('manage_pages'))
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="{{ route('admin.pages.index') }}">
                        <i class="bi bi-file-earmark-text me-1"></i>{{ __('Page Builder') }}
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('manage_users'))
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people me-1"></i>{{ __('Users') }}
                    </a>
                </li>
                @endif
            @endauth
          </ul>
        </div>

        {{-- Right controls — always visible, outside collapse --}}
        <ul class="navbar-nav align-items-center flex-row gap-0 gap-lg-1 ms-auto">

          {{-- Admin Dropdown (always visible) --}}
          @auth
          @if(auth()->user()->hasPermission('manage_users') || auth()->user()->hasPermission('manage_pages'))
          <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-none d-lg-flex {{ request()->routeIs('admin.*') ? 'active' : '' }}"
                 href="#" data-bs-toggle="dropdown" data-bs-offset="0,6" role="button">
                  <i class="bi bi-shield-lock"></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                      <a class="dropdown-item {{ request()->routeIs('admin.users.*') ? 'text-white' : '' }}"
                         href="{{ route('admin.users.index') }}">
                          <i class="bi bi-people"></i>{{ __('Users') }}
                      </a>
                  </li>
                  <li>
                      <a class="dropdown-item" href="{{ route('admin.roles.index') }}">
                          <i class="bi bi-shield-check"></i>{{ __('Roles & Permissions') }}
                      </a>
                  </li>
                  <li>
                      <a class="dropdown-item" href="{{ route('admin.service-types.index') }}">
                          <i class="bi bi-tags"></i>{{ __('Service Types') }}
                      </a>
                  </li>
                  @if(auth()->user()->hasPermission('manage_service_catalog'))
                  <li>
                      <a class="dropdown-item" href="{{ route('admin.service-catalog.index') }}">
                          <i class="bi bi-grid-3x3-gap"></i>{{ __('Service Catalog') }}
                      </a>
                  </li>
                  @endif
                  <li>
                      <a class="dropdown-item {{ request()->routeIs('admin.milestone-types.*') ? 'text-white' : '' }}"
                         href="{{ route('admin.milestone-types.index') }}">
                          <i class="bi bi-diagram-3"></i>{{ __('Milestone Types') }}
                      </a>
                  </li>
                  @if(auth()->user()->hasPermission('view_audit_log'))
                  <li><hr class="dropdown-divider"></li>
                  <li>
                      <a class="dropdown-item" href="{{ route('admin.audit-log.index') }}">
                          <i class="bi bi-clock-history"></i>{{ __('Audit Log') }}
                      </a>
                  </li>
                  @endif
                  @if(auth()->user()->hasPermission('manage_pages'))
                  <li><hr class="dropdown-divider"></li>
                  <li>
                      <a class="dropdown-item" href="{{ route('admin.pages.index') }}">
                          <i class="bi bi-file-earmark-text"></i>{{ __('Page Builder') }}
                      </a>
                  </li>
                  @endif
              </ul>
          </li>
          @elseif(auth()->user()->hasPermission('manage_pages'))
          <li class="nav-item d-none d-lg-flex">
              <a class="nav-link" href="{{ route('admin.pages.index') }}" title="{{ __('Page Builder') }}">
                  <i class="bi bi-file-earmark-text"></i>
              </a>
          </li>
          @endif
          @endauth

          {{-- Language Switcher --}}
          <li class="nav-item">
              <div class="d-flex align-items-center gap-0"
                   style="background:rgba(255,255,255,.08);border-radius:6px;padding:2px">
                  <a href="{{ route('lang.switch', 'ar') }}"
                     class="nav-link px-2 py-1 {{ app()->isLocale('ar') ? 'text-white fw-bold' : '' }}"
                     style="font-size:.78rem;border-radius:4px;{{ app()->isLocale('ar') ? 'background:rgba(255,255,255,.18)' : '' }}">
                     AR
                  </a>
                  <a href="{{ route('lang.switch', 'en') }}"
                     class="nav-link px-2 py-1 {{ app()->isLocale('en') ? 'text-white fw-bold' : '' }}"
                     style="font-size:.78rem;border-radius:4px;{{ app()->isLocale('en') ? 'background:rgba(255,255,255,.18)' : '' }}">
                     EN
                  </a>
              </div>
          </li>

          @guest
              <li class="nav-item">
                  <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
              </li>
              <li class="nav-item">
                  <a class="btn btn-primary btn-sm px-3" href="{{ route('register') }}">{{ __('Register') }}</a>
              </li>
          @else

              {{-- Notification Bell --}}
              @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
              <li class="nav-item dropdown">
                  <a class="nav-link notif-trigger" href="#"
                     data-bs-toggle="dropdown" data-bs-offset="0,8"
                     role="button" aria-label="Notifications">
                      <i class="bi bi-bell fs-6"></i>
                      @if($unreadCount > 0)
                          <span class="notif-count">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                      @endif
                  </a>

                  <div class="dropdown-menu notif-panel">
                      <div class="notif-panel-header">
                          <span class="title">
                              <i class="bi bi-bell me-1 opacity-75"></i>{{ __('Notifications') }}
                              @if($unreadCount > 0)
                                  <span class="badge bg-primary rounded-pill ms-1" style="font-size:.62rem">{{ $unreadCount }}</span>
                              @endif
                          </span>
                          @if($unreadCount > 0)
                              <form action="{{ route('notifications.read-all') }}" method="POST" class="d-inline">
                                  @csrf @method('PATCH')
                                  <button type="submit" class="mark-all-btn">{{ __('Mark all read') }}</button>
                              </form>
                          @endif
                      </div>

                      <div class="notif-list">
                          @forelse(auth()->user()->notifications()->latest()->take(15)->get() as $notif)
                              @php $d = $notif->data; @endphp
                              <div class="notif-row {{ $notif->read_at ? '' : 'unread' }}">
                                  <div class="notif-icon bg-{{ $d['color'] ?? 'primary' }} bg-opacity-10 text-{{ $d['color'] ?? 'primary' }}">
                                      <i class="bi {{ $d['icon'] ?? 'bi-bell' }}"></i>
                                  </div>
                                  <div style="flex:1;min-width:0">
                                      <div class="notif-title">{{ $d['title'] ?? 'Notification' }}</div>
                                      <div class="notif-msg">{{ Str::limit($d['message'] ?? '', 80) }}</div>
                                      <div class="notif-actions">
                                          <span class="notif-time">{{ $notif->created_at->diffForHumans() }}</span>
                                          @if(!empty($d['url']))
                                              <a href="{{ $d['url'] }}">View →</a>
                                          @endif
                                          @if(!$notif->read_at)
                                              <form action="{{ route('notifications.read', $notif->id) }}" method="POST" class="d-inline">
                                                  @csrf @method('PATCH')
                                                  <button type="submit">{{ __('Mark as read') }}</button>
                                              </form>
                                          @endif
                                      </div>
                                  </div>
                              </div>
                          @empty
                              <div class="notif-empty">
                                  <i class="bi bi-bell-slash"></i>
                                  <span>{{ __('No notifications yet') }}</span>
                              </div>
                          @endforelse
                      </div>
                      <div style="padding:.6rem 1rem;border-top:1px solid rgba(255,255,255,.08);text-align:center">
                          <a href="{{ route('notifications.index') }}"
                             style="font-size:.78rem;color:rgba(255,255,255,.45);text-decoration:none">
                              {{ __('View all notifications') }} <i class="bi bi-arrow-right"></i>
                          </a>
                      </div>
                  </div>
              </li>

              {{-- New Request (desktop only) --}}
              @if(auth()->user()->hasPermission('create_request'))
              <li class="nav-item d-none d-lg-inline-block">
                  <a class="btn btn-primary btn-sm px-3" href="{{ route('service-requests.create') }}">
                      <i class="bi bi-plus-lg me-1"></i>{{ __('New Request') }}
                  </a>
              </li>
              @endif

              {{-- User menu --}}
              <li class="nav-item dropdown">
                  <a class="nav-link d-flex align-items-center gap-2 dropdown-toggle"
                     href="#" role="button" data-bs-toggle="dropdown" data-bs-offset="0,8">
                      <span class="nav-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                      <span class="d-none d-lg-inline text-white fw-500" style="font-size:.855rem">
                          {{ Auth::user()->name }}
                      </span>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                      <li class="px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,.08)">
                          <div class="text-white fw-600" style="font-size:.855rem">{{ Auth::user()->name }}</div>
                          <div style="font-size:.75rem;color:rgba(255,255,255,.4)">{{ Auth::user()->email }}</div>
                          @if(Auth::user()->role)
                              <span class="badge bg-primary bg-opacity-25 text-primary rounded-pill mt-1"
                                    style="font-size:.65rem">{{ Auth::user()->role->name }}</span>
                          @endif
                      </li>
                      <li>
                          <a class="dropdown-item" href="{{ route('profile.edit') }}">
                              <i class="bi bi-person"></i>{{ __('Profile Settings') }}
                          </a>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                          <form action="{{ route('logout') }}" method="POST">
                              @csrf
                              <button class="dropdown-item text-danger" type="submit">
                                  <i class="bi bi-box-arrow-right"></i>{{ __('Sign Out') }}
                              </button>
                          </form>
                      </li>
                  </ul>
              </li>

              {{-- Fallback logout/profile — always visible (for when dropdown/JS fails) --}}
              <li class="nav-item">
                  <a class="nav-link px-2" href="{{ route('profile.edit') }}" title="{{ __('Profile Settings') }}" style="color:rgba(255,255,255,.5);font-size:.78rem">
                      <i class="bi bi-person"></i>
                  </a>
              </li>
              <li class="nav-item">
                  <form action="{{ route('logout') }}" method="POST">
                      @csrf
                      <button type="submit" class="nav-link px-2" style="background:none;border:none;color:rgba(255,255,255,.5);font-size:.78rem" title="{{ __('Sign Out') }}">
                          <i class="bi bi-box-arrow-right"></i>
                      </button>
                  </form>
              </li>

          @endguest
        </ul>

        <button class="navbar-toggler border-0 text-white" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMain"
                aria-controls="navMain" aria-expanded="false">
            <i class="bi bi-list fs-4"></i>
        </button>
      </div>
    </nav>

    {{-- ═══════════════════════════════════════════════════════
         TOAST NOTIFICATIONS
    ════════════════════════════════════════════════════════ --}}
    <div class="toast-stack" role="status" aria-live="polite" aria-atomic="true">
        @if(session('success'))
            <div class="flash-toast success" data-autohide="5000">
                <i class="bi bi-check-circle-fill toast-icon"></i>
                <div class="toast-body"><p>{{ session('success') }}</p></div>
                <button class="toast-close" aria-label="Close"><i class="bi bi-x"></i></button>
            </div>
        @endif
        @if(session('error'))
            <div class="flash-toast danger" data-autohide="7000">
                <i class="bi bi-exclamation-circle-fill toast-icon"></i>
                <div class="toast-body"><p>{{ session('error') }}</p></div>
                <button class="toast-close" aria-label="Close"><i class="bi bi-x"></i></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="flash-toast warning" data-autohide="6000">
                <i class="bi bi-exclamation-triangle-fill toast-icon"></i>
                <div class="toast-body"><p>{{ session('warning') }}</p></div>
                <button class="toast-close" aria-label="Close"><i class="bi bi-x"></i></button>
            </div>
        @endif
        @if(session('info'))
            <div class="flash-toast info" data-autohide="5000">
                <i class="bi bi-info-circle-fill toast-icon"></i>
                <div class="toast-body"><p>{{ session('info') }}</p></div>
                <button class="toast-close" aria-label="Close"><i class="bi bi-x"></i></button>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════
         MAIN CONTENT
    ════════════════════════════════════════════════════════ --}}
    <main id="main-content">
        <div class="page-wrapper">

            @hasSection('breadcrumbs')
            <nav class="app-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('service-requests.index') }}"><i class="bi bi-house me-1"></i>{{ __('Home') }}</a>
                @yield('breadcrumbs')
            </nav>
            @endif

            @yield('content')
        </div>
    </main>

    {{-- ═══════════════════════════════════════════════════════
         SCRIPTS
    ════════════════════════════════════════════════════════ --}}
    {{-- Bootstrap JS always loaded from CDN — required for navbar collapse --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/app.js'])
    @endif

    <script>
    (() => {
        /* ── Toast close & auto-dismiss ── */
        function dismissToast(el) {
            el.classList.add('hiding');
            el.addEventListener('animationend', () => el.remove(), { once: true });
        }

        document.querySelectorAll('.flash-toast').forEach(toast => {
            const delay = parseInt(toast.dataset.autohide || 5000);
            const tid = setTimeout(() => dismissToast(toast), delay);
            toast.querySelector('.toast-close')?.addEventListener('click', () => {
                clearTimeout(tid);
                dismissToast(toast);
            });
        });

        /* ── Page loader on navigation ── */
        const loader = document.getElementById('page-loader');
        document.addEventListener('click', e => {
            const link = e.target.closest('a[href]');
            if (!link) return;
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript') ||
                link.target === '_blank' || e.ctrlKey || e.metaKey) return;
            loader.classList.add('active');
        });
        window.addEventListener('pageshow', () => loader.classList.remove('active'));

        /* ── Sticky nav shadow on scroll ── */
        const nav = document.querySelector('.site-nav');
        const onScroll = () => {
            nav.style.boxShadow = window.scrollY > 4
                ? '0 2px 16px rgba(0,0,0,.25)'
                : 'none';
        };
        window.addEventListener('scroll', onScroll, { passive: true });
    })();
    </script>

    @stack('scripts')
</body>
</html>
