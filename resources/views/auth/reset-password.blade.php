<x-guest-layout>
    <div class="mb-7 space-y-3">
        <div class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-700">
            Set New Password
        </div>
        <h2 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Create a new password</h2>
        <p class="max-w-lg text-sm leading-6 text-slate-600 sm:leading-7">
            Choose a strong new password for your account. Once saved, you’ll be able to sign in normally with your updated credentials.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Work Email')" class="text-sm font-medium text-slate-700" />
            <x-text-input id="email" class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <x-input-label for="password" :value="__('New Password')" class="text-sm font-medium text-slate-700" />
            <x-text-input id="password" class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300" type="password" name="password" required autocomplete="new-password" placeholder="Enter a strong password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-5">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-sm font-medium text-slate-700" />

            <x-text-input id="password_confirmation" class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" placeholder="Re-enter your password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm" />
        </div>

        <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a class="text-sm font-medium text-slate-500 underline decoration-slate-300 underline-offset-4 transition hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2" href="{{ route('login') }}">
                Back to sign in
            </a>

            <x-primary-button class="inline-flex h-14 w-full items-center justify-center rounded-2xl bg-slate-950 px-7 text-sm font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-amber-600 focus:bg-amber-600 active:bg-amber-700 focus:ring-amber-300 sm:w-auto">
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
