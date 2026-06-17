<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Workspace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="margin: 0; min-height: 100vh; background: #f8fafc; color: #0f172a; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    <main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 32px;">
        <section style="width: min(940px, 100%);">
            <div style="margin-bottom: 22px;">
                <div style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase;">
                    {{ $clinic?->clinic_name ?: 'Clinic Workspace' }}
                </div>
                <h1 style="margin: 14px 0 0; font-size: clamp(32px, 5vw, 46px); line-height: 1.05; font-weight: 850; letter-spacing: 0; color: #0f172a;">
                    Choose Workspace
                </h1>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px;">
                @if ($canUseVerification)
                    <a
                        href="{{ route('clinic.switch-workspace', ['workspace' => \App\Support\ClinicWorkspace::VERIFICATION]) }}"
                        style="min-height: 210px; display: flex; flex-direction: column; justify-content: space-between; padding: 24px; border-radius: 22px; border: 1px solid #bfdbfe; background: #ffffff; color: #0f172a; text-decoration: none; box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);"
                    >
                        <div>
                            <div style="width: 46px; height: 46px; display: inline-flex; align-items: center; justify-content: center; border-radius: 16px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M9 3.75h6M7.5 6h9A1.5 1.5 0 0 1 18 7.5v12A1.5 1.5 0 0 1 16.5 21h-9A1.5 1.5 0 0 1 6 19.5v-12A1.5 1.5 0 0 1 7.5 6Z" />
                                </svg>
                            </div>
                            <h2 style="margin: 18px 0 8px; font-size: 24px; line-height: 1.2; font-weight: 850;">Verification Zone</h2>
                            <p style="margin: 0; color: #64748b; font-size: 15px; line-height: 1.6;">Requests, insurance verification, portal credentials, and clinic follow-up.</p>
                        </div>
                        <div style="margin-top: 24px; display: inline-flex; align-items: center; gap: 8px; color: #1d4ed8; font-size: 14px; font-weight: 800;">
                            Open Verification Zone
                            <span aria-hidden="true">-></span>
                        </div>
                    </a>
                @endif

                @if ($canUseClinicPms)
                    <a
                        href="{{ route('clinic.switch-workspace', ['workspace' => \App\Support\ClinicWorkspace::CLINIC_PMS]) }}"
                        style="min-height: 210px; display: flex; flex-direction: column; justify-content: space-between; padding: 24px; border-radius: 22px; border: 1px solid #fed7aa; background: #ffffff; color: #0f172a; text-decoration: none; box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);"
                    >
                        <div>
                            <div style="width: 46px; height: 46px; display: inline-flex; align-items: center; justify-content: center; border-radius: 16px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h12a2.25 2.25 0 0 1 2.25 2.25v9A2.25 2.25 0 0 1 18 18.75H6a2.25 2.25 0 0 1-2.25-2.25v-9ZM7.5 9.75h3M7.5 13.5h6M16.5 9.75h.008M16.5 13.5h.008" />
                                </svg>
                            </div>
                            <h2 style="margin: 18px 0 8px; font-size: 24px; line-height: 1.2; font-weight: 850;">Clinic PMS</h2>
                            <p style="margin: 0; color: #64748b; font-size: 15px; line-height: 1.6;">Patients, appointments, treatment plans, documents, claims, and billing work.</p>
                        </div>
                        <div style="margin-top: 24px; display: inline-flex; align-items: center; gap: 8px; color: #c2410c; font-size: 14px; font-weight: 800;">
                            Open Clinic PMS
                            <span aria-hidden="true">-></span>
                        </div>
                    </a>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
