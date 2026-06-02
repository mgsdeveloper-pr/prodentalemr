<x-guest-layout>
    <div class="mb-7 space-y-3">
        <div class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-700">
            Welcome Back
        </div>
        <h2 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Sign in to continue</h2>
        <p class="max-w-lg text-sm leading-6 text-slate-600 sm:leading-7">
            Access your SaaS or clinic workspace with the email assigned to your account. We’ll route you to the right operational panel after login.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Work Email')" class="text-sm font-medium text-slate-700" />
            <x-text-input
                id="email"
                class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="name@company.com"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-slate-700" />

            <x-text-input
                id="password"
                class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
        </div>

        <!-- Remember Me -->
        <div class="mt-5 space-y-3 sm:flex sm:items-center sm:justify-between sm:gap-4 sm:space-y-0">
            <label for="remember_me" class="inline-flex items-center gap-3">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-400" name="remember">
                <span class="text-sm leading-6 text-slate-600">{{ __('Keep me signed in on this device') }}</span>
            </label>

            <div class="hidden text-xs uppercase tracking-[0.22em] text-slate-400 sm:block">
                Protected Session
            </div>
        </div>

        <div class="mt-6 flex flex-col items-center gap-4 sm:mt-8 sm:flex-row sm:items-center sm:justify-between">
            @if (Route::has('password.request'))
                <a class="text-center text-sm font-medium text-slate-500 underline decoration-slate-300 underline-offset-4 transition hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 sm:text-left" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="inline-flex h-14 w-full items-center justify-center rounded-2xl bg-slate-950 px-7 text-sm font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-amber-600 focus:bg-amber-600 active:bg-amber-700 focus:ring-amber-300 sm:ms-auto sm:w-auto">
                {{ __('Log in') }}
            </x-primary-button>
        </div>

        <div class="mt-7 rounded-[1.5rem] border border-slate-200/80 bg-slate-50/80 px-4 py-4 sm:mt-8 sm:px-5">
            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Need access help?</div>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                If your account was recently created or your role changed, use your assigned email and contact your platform administrator if you still cannot enter the correct workspace.
            </p>
        </div>
    </form>
</x-guest-layout>
