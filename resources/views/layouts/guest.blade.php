<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $settings = \App\Models\SaasSetting::current();
            $brandName = $settings->brandName();
        @endphp

        <title>{{ $brandName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <main class="min-h-screen bg-white lg:grid lg:grid-cols-[minmax(0,42%)_minmax(0,58%)]">
            <section class="relative hidden min-h-screen overflow-hidden border-r border-slate-200 lg:block" aria-label="ProDental dental workspace">
                <img
                    src="{{ $settings->loginImageUrl() }}"
                    alt="Modern dental clinic workspace"
                    class="absolute inset-0 h-full w-full object-cover"
                    fetchpriority="high"
                >

                <div class="absolute inset-x-0 bottom-0 border-t border-white/70 bg-white/90 px-10 py-8 backdrop-blur-sm">
                    <div class="flex items-center gap-4">
                        <x-application-logo class="h-12 w-12 flex-none text-sm" />
                        <div>
                            <p class="text-xl font-bold text-slate-950">{{ $brandName }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-600">Dental operations, connected.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="flex min-h-screen flex-col bg-white">
                <header class="flex h-20 items-center border-b border-slate-200 px-6 sm:px-10 lg:border-b-0 lg:px-14">
                    <a href="/" class="inline-flex items-center gap-3" aria-label="{{ $brandName }} home">
                        <x-application-logo class="h-10 w-10 flex-none text-xs" />
                        <span class="text-xl font-bold text-slate-950">{{ $brandName }}</span>
                    </a>
                </header>

                <div class="flex flex-1 items-center justify-center px-6 py-10 sm:px-10 lg:px-14">
                    <div class="w-full max-w-md">
                        {{ $slot }}
                    </div>
                </div>

                <footer class="px-6 pb-6 text-center text-xs text-slate-500 sm:px-10 lg:px-14">
                    Secure role-based access
                </footer>
            </section>
        </main>
    </body>
</html>
