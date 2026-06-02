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
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 48px 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
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
            background: #1a1a2e;
            color: #fff;
        }
        .btn-primary:hover {
            background: #2d2d4a;
        }
        .btn-outline {
            background: transparent;
            color: #1a1a2e;
            border: 2px solid #e5e7eb;
        }
        .btn-outline:hover {
            border-color: #1a1a2e;
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
    <div class="card">
        <div class="logo">ALMuhalab</div>
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
</body>
</html>
