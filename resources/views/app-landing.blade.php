<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ALMuhalab System - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: url('/images/Background.png') no-repeat center center;
            background-size: cover;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 0;
        }
        .wrapper {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .card {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.15);
            padding: 48px 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .75rem;
            margin-bottom: 8px;
        }
        .logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .logo-text {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
        }
        .subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 32px;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 12px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            color: #fff;
            border: none;
            box-shadow: 0 3px 12px rgba(245,158,11,.35);
        }
        .btn-primary:hover {
            opacity: .92;
            transform: translateY(-1px);
            box-shadow: 0 5px 16px rgba(245,158,11,.40);
        }
        .btn-outline {
            background: transparent;
            color: #1a1a2e;
            border: 2px solid #e5e7eb;
        }
        .btn-outline:hover {
            border-color: #f59e0b;
            color: #d97706;
        }
        .footer {
            position: relative;
            z-index: 1;
            text-align: center;
            margin-top: 1.5rem;
            font-size: .72rem;
            color: rgba(255, 255, 255, 0.7);
        }
        .btn-back {
            background: transparent;
            color: #9ca3af;
            font-weight: 500;
            font-size: 13px;
        }
        .btn-back:hover {
            color: #6b7280;
        }
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
            color: #d1d5db;
            font-size: 12px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="logo">
                <img src="{{ asset('images/Logo.png') }}" alt="ALMuhalab Logo">
                <span class="logo-text">ALMuhalab</span>
            </div>
            <div class="subtitle">Admin Control Panel</div>

            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-outline">Register</a>
                    @endif
                @endauth
            @endif

            <div class="divider">OR</div>

            <a href="https://almuhalab.net" class="btn btn-back">&larr; Back to Main Site</a>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} ALMuhalab International Co. — Kuwait City
        </div>
    </div>
</body>
</html>
