@php
    $user = filament()->auth()->user();
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <style>
        .clinic-account {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(250px, 0.85fr);
            gap: 1rem;
            align-items: stretch;
        }

        .clinic-account__hero,
        .clinic-account__side {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1.35rem;
            padding: 1.2rem 1.25rem;
            background: linear-gradient(180deg, #f8fcff 0%, #ffffff 100%);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.04);
        }

        .clinic-account__hero {
            position: relative;
            overflow: hidden;
        }

        .clinic-account__hero::after {
            content: '';
            position: absolute;
            inset: auto -2rem -3rem auto;
            width: 9rem;
            height: 9rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.12) 0%, rgba(14, 165, 233, 0) 72%);
            pointer-events: none;
        }

        .clinic-account__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.85rem;
            font-size: 0.73rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #0369a1;
        }

        .clinic-account__eyebrow::before {
            content: '';
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 999px;
            background: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.14);
        }

        .clinic-account__hero-main {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .clinic-account__heading {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .clinic-account__subheading {
            margin: 0.22rem 0 0;
            font-size: 0.94rem;
            color: #64748b;
            line-height: 1.5;
            max-width: 38rem;
        }

        .clinic-account__name {
            margin: 0.5rem 0 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .clinic-account__side {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.9rem;
        }

        .clinic-account__pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            width: fit-content;
            padding: 0.52rem 0.78rem;
            border-radius: 999px;
            border: 1px solid rgba(14, 165, 233, 0.18);
            background: rgba(240, 249, 255, 0.88);
            color: #0369a1;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .clinic-account__pill::before {
            content: '';
            width: 0.38rem;
            height: 0.38rem;
            border-radius: 999px;
            background: currentColor;
            opacity: 0.7;
        }

        .clinic-account__cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            border-radius: 1rem;
            background: #111827;
            color: #f8fafc;
            padding: 0.82rem 1rem;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.16);
        }

        .clinic-account__cta:hover {
            transform: translateY(-1px);
            background: #0f172a;
        }

        html.dark .clinic-account__hero,
        html.dark .clinic-account__side {
            border-color: rgba(255, 255, 255, 0.08);
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.94) 0%, rgba(15, 23, 42, 0.98) 100%);
            box-shadow: none;
        }

        html.dark .clinic-account__heading,
        html.dark .clinic-account__name {
            color: #f8fafc;
        }

        html.dark .clinic-account__subheading {
            color: #94a3b8;
        }

        html.dark .clinic-account__pill {
            border-color: rgba(14, 165, 233, 0.18);
            background: rgba(14, 165, 233, 0.08);
            color: #7dd3fc;
        }

        html.dark .clinic-account__cta {
            background: #0ea5e9;
            color: #082f49;
            box-shadow: none;
        }

        @media (max-width: 900px) {
            .clinic-account {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="clinic-account">
        <div class="clinic-account__hero">
            <div class="clinic-account__eyebrow">Clinical Workspace</div>

            <div class="clinic-account__hero-main">
                <x-filament-panels::avatar.user
                    size="lg"
                    :user="$user"
                    loading="lazy"
                />

                <div>
                    <h2 class="clinic-account__heading">Clinic Operations Hub</h2>
                    <p class="clinic-account__subheading">
                        Run patient workflows, appointments, treatment planning, and insurance intake from a calmer day-to-day workspace.
                    </p>
                    <p class="clinic-account__name">{{ filament()->getUserName($user) }}</p>
                </div>
            </div>
        </div>

        <div class="clinic-account__side">
            <span class="clinic-account__pill">Patients · Appointments · Encounters</span>

            <a href="{{ route('clinic.signout') }}" class="clinic-account__cta">
                {{
                    \Filament\Support\generate_icon_html(
                        \Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle,
                        alias: \Filament\View\PanelsIconAlias::WIDGETS_ACCOUNT_LOGOUT_BUTTON,
                        attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'h-5 w-5'])
                    )
                }}
                <span>{{ __('filament-panels::widgets/account-widget.actions.logout.label') }}</span>
            </a>
        </div>
    </div>
</x-filament-widgets::widget>
