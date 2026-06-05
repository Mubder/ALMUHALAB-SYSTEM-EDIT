<x-guest-layout>
    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-3 p-2 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-size:.84rem">
            {{ session('status') }}
        </div>
    @endif

    <h2 class="fw-bold mb-1" style="letter-spacing:-.3px">{{ __('Welcome back') }}</h2>
    <p class="login-subtitle">{{ __('Sign in to your account') }}</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" type="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="username"
                   placeholder="you@example.com">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password"
                   placeholder="••••••••">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="mb-4 d-flex align-items-center justify-content-between">
            <div class="form-check">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                <label for="remember_me" class="form-check-label">{{ __('Remember me') }}</label>
            </div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-link">{{ __('Forgot password?') }}</a>
            @endif
        </div>

        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-1"></i>{{ __('Sign In') }}
        </button>

        @if (Route::has('register'))
            <p class="text-center mt-4 mb-0" style="font-size:.875rem;color:#64748b">
                {{ __("Don't have an account?") }}
                <a href="{{ route('register') }}">{{ __('Create one') }}</a>
            </p>
        @endif
    </form>
</x-guest-layout>
