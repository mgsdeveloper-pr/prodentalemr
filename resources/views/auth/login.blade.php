<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-950 sm:text-4xl">Welcome back</h1>
        <p class="mt-2 text-base leading-6 text-slate-600">Sign in to access your workspace.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Work email')" class="text-sm font-semibold text-slate-700" />
            <x-text-input
                id="email"
                class="mt-2 block h-12 w-full rounded-lg border-slate-300 bg-white px-3.5 text-base shadow-sm transition focus:border-teal-600 focus:ring-teal-600"
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

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm font-semibold text-slate-700" />
            <div class="relative mt-2">
                <x-text-input
                    id="password"
                    class="block h-12 w-full rounded-lg border-slate-300 bg-white px-3.5 pe-12 text-base shadow-sm transition focus:border-teal-600 focus:ring-teal-600"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                />
                <button
                    type="button"
                    id="toggle-password"
                    class="absolute inset-y-0 right-0 inline-flex w-12 items-center justify-center text-slate-500 transition hover:text-teal-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-600"
                    aria-label="Show password"
                    title="Show password"
                >
                    @svg('heroicon-o-eye', 'h-5 w-5', ['id' => 'password-visible-icon', 'aria-hidden' => 'true'])
                    @svg('heroicon-o-eye-slash', 'hidden h-5 w-5', ['id' => 'password-hidden-icon', 'aria-hidden' => 'true'])
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center gap-2.5">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-teal-700 shadow-sm focus:ring-teal-600" name="remember">
                <span class="text-sm text-slate-600">{{ __('Keep me signed in') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-teal-700 transition hover:text-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="inline-flex h-12 w-full items-center justify-center rounded-lg border border-teal-800 bg-teal-700 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:bg-teal-800 active:bg-teal-900 focus:ring-teal-600">
            {{ __('Sign in') }}
        </x-primary-button>
    </form>

    <p class="mt-7 text-center text-sm text-slate-500">
        Need access help? Contact your administrator.
    </p>

    <script>
        document.getElementById('toggle-password')?.addEventListener('click', function () {
            const password = document.getElementById('password');
            const showing = password.type === 'text';

            password.type = showing ? 'password' : 'text';
            this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            this.setAttribute('title', showing ? 'Show password' : 'Hide password');
            document.getElementById('password-visible-icon')?.classList.toggle('hidden', ! showing);
            document.getElementById('password-hidden-icon')?.classList.toggle('hidden', showing);
        });
    </script>
</x-guest-layout>
