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

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="relative min-h-screen overflow-hidden bg-[linear-gradient(145deg,_#f8fafc_0%,_#eef4ff_32%,_#fff8ef_100%)]">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -left-24 top-0 h-80 w-80 rounded-full bg-amber-300/25 blur-3xl"></div>
                <div class="absolute right-[-4rem] top-24 h-96 w-96 rounded-full bg-sky-300/20 blur-3xl"></div>
                <div class="absolute bottom-[-8rem] left-1/3 h-80 w-80 rounded-full bg-orange-200/35 blur-3xl"></div>
                <div class="absolute inset-x-0 top-0 h-40 bg-[linear-gradient(180deg,_rgba(15,23,42,0.05),_transparent)]"></div>
            </div>

            <div class="relative mx-auto flex min-h-screen max-w-3xl items-center justify-center px-4 py-6 sm:px-6 lg:px-8">
                <div class="w-full max-w-xl overflow-hidden rounded-[2rem] border border-white/80 bg-white/92 shadow-[0_35px_100px_rgba(15,23,42,0.16)] backdrop-blur">
                    <div class="border-b border-slate-200/70 bg-[linear-gradient(135deg,_rgba(255,255,255,0.92),_rgba(255,247,237,0.9))] px-6 py-5 sm:px-8 sm:py-6">
                        <div class="flex flex-col gap-4 text-center">
                            <a href="/" class="inline-flex flex-col items-center gap-3">
                                <x-application-logo class="h-16 w-16 text-xl shadow-[0_18px_40px_rgba(180,83,9,0.22)]" />
                                <div>
                                    <div class="text-2xl font-semibold tracking-tight text-slate-950">{{ $brandName }}</div>
                                    <div class="text-xs uppercase tracking-[0.3em] text-amber-700">Dental Operations Platform</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
