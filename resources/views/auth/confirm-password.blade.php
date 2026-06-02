<x-guest-layout>
    <div class="mb-7 space-y-3">
        <div class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-700">
            Secure Confirmation
        </div>
        <h2 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Confirm your password</h2>
        <p class="max-w-lg text-sm leading-6 text-slate-600 sm:leading-7">
            This is a protected area. Please confirm your password before continuing with the requested action.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-slate-700" />

            <x-text-input id="password" class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                            type="password"
                            name="password" placeholder="Re-enter your password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
        </div>

        <div class="mt-8 flex justify-end">
            <x-primary-button class="inline-flex h-14 w-full items-center justify-center rounded-2xl bg-slate-950 px-7 text-sm font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-amber-600 focus:bg-amber-600 active:bg-amber-700 focus:ring-amber-300 sm:w-auto">
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
