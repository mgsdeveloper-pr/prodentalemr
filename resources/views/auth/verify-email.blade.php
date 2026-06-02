<x-guest-layout>
    <div class="mb-7 space-y-3">
        <div class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-700">
            Email Verification
        </div>
        <h2 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Verify your email address</h2>
        <p class="max-w-lg text-sm leading-6 text-slate-600 sm:leading-7">
            Before getting started, confirm your email using the link we sent you. This helps keep access secure and ensures we can route you correctly afterward.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="inline-flex h-14 w-full items-center justify-center rounded-2xl bg-slate-950 px-7 text-sm font-semibold uppercase tracking-[0.16em] text-white transition hover:bg-amber-600 focus:bg-amber-600 active:bg-amber-700 focus:ring-amber-300 sm:w-auto">
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm font-medium text-slate-500 underline decoration-slate-300 underline-offset-4 transition hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2">
                {{ __('Sign out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
