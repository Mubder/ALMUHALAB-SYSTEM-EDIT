<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ALMuhalab International Co.') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">

    {{-- Bootstrap CSS + Icons: always from CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Vite: Tailwind CSS + Alpine.js when available --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            margin: 0;
        }
        .login-bg {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: url('/images/Background.png') no-repeat center center;
            background-size: cover;
            position: relative;
        }
        .login-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
        }
        .login-bg .login-card {
            position: relative;
            z-index: 1;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
        }
        .login-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .75rem;
            margin-bottom: 2rem;
        }
        .login-brand-mark {
            width: 80px; height: 80px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            background: transparent;
        }
        .login-brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .login-brand-text {
            text-align: center;
        }
        .login-brand-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -.3px;
            display: block;
        }
        .login-brand-sub {
            font-size: .72rem;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: .1em;
            text-transform: uppercase;
            display: block;
            margin-top: .1rem;
        }
        .login-form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 2.25rem 2rem;
            box-shadow: 0 4px 24px rgba(0,0,0,.15), 0 1px 4px rgba(0,0,0,.08);
        }
        .login-form-card h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: .25rem;
        }
        .login-form-card .login-subtitle {
            font-size: .875rem;
            color: #64748b;
            margin-bottom: 1.75rem;
        }
        .login-form-card .form-label {
            color: #374151;
            font-size: .84rem;
            font-weight: 600;
            margin-bottom: .35rem;
        }
        .login-form-card .form-control {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            color: #1e293b;
            border-radius: 8px;
            padding: .6rem .9rem;
            font-size: .9rem;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .login-form-card .form-control:focus {
            background: #fff;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245,158,11,.12);
            color: #1e293b;
            outline: none;
        }
        .login-form-card .form-control::placeholder { color: #94a3b8; }
        .login-form-card .form-check-input {
            background-color: #fff;
            border-color: #cbd5e1;
        }
        .login-form-card .form-check-input:checked {
            background-color: #f59e0b;
            border-color: #f59e0b;
        }
        .login-form-card .form-check-label { color: #475569; font-size: .84rem; }
        .btn-login {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            border: none;
            color: #fff;
            font-weight: 600;
            font-size: .92rem;
            padding: .65rem 1.5rem;
            border-radius: 8px;
            width: 100%;
            transition: opacity .15s, transform .1s, box-shadow .15s;
            box-shadow: 0 3px 12px rgba(245,158,11,.35);
        }
        .btn-login:hover { opacity: .92; transform: translateY(-1px); color: #fff; box-shadow: 0 5px 16px rgba(245,158,11,.40); }
        .btn-login:active { transform: translateY(0); }
        .login-form-card a { color: #d97706; text-decoration: none; font-weight: 500; }
        .login-form-card a:hover { color: #b45309; }
        .forgot-link { color: #94a3b8 !important; font-size: .8rem; text-decoration: none; transition: color .15s; font-weight: 400; }
        .forgot-link:hover { color: #d97706 !important; }
        .login-divider {
            border-color: #e2e8f0;
            margin: 1.25rem 0;
        }
        .invalid-feedback { font-size: .78rem; }
        .login-form-card .is-invalid {
            border-color: #f87171 !important;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: .72rem;
            color: rgba(255, 255, 255, 0.7);
        }
    </style>
</head>
<body>
    <div class="login-bg">
        <div class="login-card">

            {{-- Brand --}}
            <div class="login-brand">
                <div class="login-brand-mark">
                    <img src="{{ asset('images/Logo.png') }}" alt="ALMuhalab Logo">
                </div>
                <div class="login-brand-text">
                    <span class="login-brand-name" style="color:#fff">ALMuhalab</span>
                    <span class="login-brand-sub" style="color:rgba(255,255,255,.7)">International Co.</span>
                </div>
            </div>

            {{-- Form Card --}}
            <div class="login-form-card">
                {{ $slot }}
            </div>

            <div class="login-footer">
                &copy; {{ date('Y') }} ALMuhalab International Co. — Kuwait City
            </div>

        </div>
    </div>

{{-- Bootstrap JS: always from CDN --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
