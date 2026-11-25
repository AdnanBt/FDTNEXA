
<x-guest-layout>
    <div class="mb-8 text-center">
        <!-- FDTNexa Brand -->
        <a href="{{ url('/') }}" class="inline-flex justify-center">
            <img
                src="{{ asset('images/fdtnexa-web-logo.png') }}"
                alt="FDTNexa Engineering Software"
                class="w-64 max-w-full h-auto object-contain"
            >
        </a>

        <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-800">
            Welcome Back
        </h1>

        <p class="mt-2 text-sm text-slate-500">
            Sign in to your FDTNexa account
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 text-cyan-600 shadow-sm focus:ring-cyan-500"
                    name="remember"
                >
                <span class="ms-2 text-sm text-gray-600">
                    {{ __('Remember me') }}
                </span>
            </label>
        </div>

        <!-- Login Actions -->
        <div class="flex items-center justify-end mt-5">
            @if (Route::has('password.request'))
                <a
                    class="underline text-sm text-slate-500 hover:text-cyan-700 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500"
                    href="{{ route('password.request') }}"
                >
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-8 pt-5 border-t border-gray-100 text-center">
        <p class="text-xs text-slate-400">
            © {{ date('Y') }} FDTNexa. Engineering Software.
        </p>
    </div>
</x-guest-layout>
