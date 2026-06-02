@php
    $user = filament()->auth()->user();
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <style>
        .saas-account {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(260px, 0.85fr);
            gap: 1rem;
            align-items: stretch;
            width: 100%;
        }

        .saas-account__hero,
        .saas-account__side {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1.35rem;
            padding: 1.2rem 1.25rem;
            background: linear-gradient(180deg, #fffdf8 0%, #ffffff 100%);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.04);
        }

        .saas-account__hero {
            position: relative;
            overflow: hidden;
        }

        .saas-account__hero::after {
            content: '';
            position: absolute;
            inset: auto -2.5rem -3rem auto;
            width: 9rem;
            height: 9rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.14) 0%, rgba(245, 158, 11, 0) 72%);
            pointer-events: none;
        }

        .saas-account__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #a16207;
        }

        .saas-account__eyebrow::before {
            content: '';
            width: 0.6rem;
            height: 0.6rem;
            border-radius: 999px;
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            box-shadow: 0 0 0 0.18rem rgba(245, 158, 11, 0.14);
        }

        .saas-account__hero-main {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 1rem;
            align-items: end;
            margin-top: 1.1rem;
        }

        .saas-account__heading {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #0f172a;
            line-height: 1.1;
        }

        .saas-account__subheading {
            margin: 0.45rem 0 0;
            font-size: 0.98rem;
            line-height: 1.7;
            color: #64748b;
            max-width: 32rem;
        }

        .saas-account__name {
            margin: 0.9rem 0 0;
            font-size: 0.92rem;
            font-weight: 700;
            color: #0f172a;
        }

        .saas-account__side {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1rem;
        }

        .saas-account__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .saas-account__chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.58rem 0.9rem;
            border-radius: 999px;
            border: 1px solid rgba(245, 158, 11, 0.28);
            background: rgba(255, 251, 235, 0.92);
            color: #a16207;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1;
        }

        .saas-account__chip::before {
            content: '';
            width: 0.34rem;
            height: 0.34rem;
            border-radius: 999px;
            background: currentColor;
            opacity: 0.75;
        }

        .saas-account__cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            min-height: 3rem;
            padding: 0.85rem 1.05rem;
            border-radius: 1rem;
            background: #111827;
            color: #ffffff;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 700;
            box-shadow: 0 16px 26px rgba(15, 23, 42, 0.14);
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .saas-account__cta:hover {
            background: #0f172a;
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.18);
            transform: translateY(-1px);
        }

        .dark .saas-account__hero,
        .dark .saas-account__side {
            border-color: rgba(148, 163, 184, 0.12);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.96) 0%, rgba(15, 23, 42, 0.9) 100%);
            box-shadow: none;
        }

        .dark .saas-account__heading,
        .dark .saas-account__name {
            color: #f8fafc;
        }

        .dark .saas-account__subheading {
            color: #cbd5e1;
        }

        .dark .saas-account__chip {
            border-color: rgba(245, 158, 11, 0.28);
            background: rgba(120, 53, 15, 0.18);
            color: #fcd34d;
        }

        .dark .saas-account__cta {
            background: #f8fafc;
            color: #0f172a;
            box-shadow: none;
        }

        @media (max-width: 900px) {
            .saas-account {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="saas-account">
        <div class="saas-account__hero">
            <div class="saas-account__eyebrow">Platform Management</div>

            <div class="saas-account__hero-main">
                <x-filament-panels::avatar.user
                    size="lg"
                    :user="$user"
                    loading="lazy"
                />

                <div>
                    <h2 class="saas-account__heading">SaaS Control Console</h2>
                    <p class="saas-account__subheading">
                        Manage organizations, subscriptions, service enablement, and platform-wide settings from one clean administrative surface.
                    </p>
                    <p class="saas-account__name">{{ filament()->getUserName($user) }}</p>
                </div>
            </div>
        </div>

        <div class="saas-account__side">
            <div class="saas-account__chips">
                <span class="saas-account__chip">Organizations</span>
                <span class="saas-account__chip">Billing</span>
                <span class="saas-account__chip">Managed Services</span>
            </div>

            <a href="{{ route('saas.signout') }}" class="saas-account__cta">
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
