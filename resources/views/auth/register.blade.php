<x-guest-layout>
    <div class="mb-7 space-y-3">
        <div class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-700">
            New Organization
        </div>
        <h2 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Create your workspace</h2>
        <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:leading-7">
            Set up your owner account, organization, clinic, and first location in one guided step so your team can start with a clean operational foundation.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('Owner Name')" class="text-sm font-medium text-slate-700" />
                <x-text-input
                    id="name"
                    class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                    type="text"
                    name="name"
                    :value="old('name')"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Dr. or Admin name"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2 text-sm" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Owner Email')" class="text-sm font-medium text-slate-700" />
                <x-text-input
                    id="email"
                    class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autocomplete="username"
                    placeholder="owner@organization.com"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
            </div>

            <div>
                <x-input-label for="phone" :value="__('Phone')" class="text-sm font-medium text-slate-700" />
                <x-text-input
                    id="phone"
                    class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                    type="text"
                    name="phone"
                    :value="old('phone')"
                    required
                    autocomplete="phone"
                    placeholder="Contact number"
                />
                <x-input-error :messages="$errors->get('phone')" class="mt-2 text-sm" />
            </div>

            <div>
                <x-input-label for="organization_name" :value="__('Organization Name')" class="text-sm font-medium text-slate-700" />
                <x-text-input
                    id="organization_name"
                    class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                    type="text"
                    name="organization_name"
                    :value="old('organization_name')"
                    required
                    placeholder="Parent business or group name"
                />
                <x-input-error :messages="$errors->get('organization_name')" class="mt-2 text-sm" />
            </div>

            <div>
                <x-input-label for="clinic_name" :value="__('Clinic Name')" class="text-sm font-medium text-slate-700" />
                <x-text-input
                    id="clinic_name"
                    class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                    type="text"
                    name="clinic_name"
                    :value="old('clinic_name')"
                    required
                    placeholder="Primary clinic name"
                />
                <x-input-error :messages="$errors->get('clinic_name')" class="mt-2 text-sm" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="location_name" :value="__('Location Name')" class="text-sm font-medium text-slate-700" />
                <x-text-input
                    id="location_name"
                    class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                    type="text"
                    name="location_name"
                    :value="old('location_name')"
                    required
                    placeholder="First working location"
                />
                <x-input-error :messages="$errors->get('location_name')" class="mt-2 text-sm" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-slate-700" />
                <x-text-input
                    id="password"
                    class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Create a password"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-sm font-medium text-slate-700" />
                <x-text-input
                    id="password_confirmation"
                    class="mt-2 block h-14 w-full rounded-2xl border-slate-200 bg-white/90 px-4 text-base shadow-sm transition focus:border-amber-400 focus:ring-amber-300"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm your password"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm" />
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a 
                class="text-sm font-medium text-slate-500 underline decoration-slate-300 underline-offset-4 transition hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2" 
                href="{{ route('login') }}"
            >
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="inline-flex h-14 w-full items-center justify-center rounded-2xl bg-slate-950 px-7 text-sm font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-amber-600 focus:bg-amber-600 active:bg-amber-700 focus:ring-amber-300 sm:w-auto">
                {{ __('Register Clinic') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
