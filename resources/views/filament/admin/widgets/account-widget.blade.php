@php
    $user = filament()->auth()->user();
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <style>
        .ops-account {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(260px, 0.8fr);
            gap: 1rem;
            align-items: stretch;
        }

        .ops-account__hero,
        .ops-account__actions {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1.35rem;
            background: linear-gradient(180deg, #fffdf8 0%, #ffffff 100%);
            padding: 1.2rem 1.25rem;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.045);
        }

        .ops-account__hero {
            position: relative;
            overflow: hidden;
        }

        .ops-account__hero::after {
            content: '';
            position: absolute;
            inset: auto -3rem -3rem auto;
            width: 8rem;
            height: 8rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.18) 0%, rgba(245, 158, 11, 0) 72%);
            pointer-events: none;
        }

        .ops-account__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.85rem;
            font-size: 0.73rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9a6700;
        }

        .ops-account__eyebrow::before {
            content: '';
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 999px;
            background: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.16);
        }

        .ops-account__hero-main {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .ops-account__heading {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .ops-account__subheading {
            margin: 0.2rem 0 0;
            font-size: 0.94rem;
            color: #64748b;
            line-height: 1.5;
            max-width: 38rem;
        }

        .ops-account__name {
            margin: 0.5rem 0 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .ops-account__actions {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.9rem;
            background: linear-gradient(180deg, #ffffff 0%, #fffbef 100%);
        }

        .ops-account__statline {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .ops-account__stat {
            padding: 0.8rem 0.9rem;
            border: 1px solid rgba(245, 158, 11, 0.12);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.88);
        }

        .ops-account__stat-label {
            margin: 0 0 0.22rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .ops-account__stat-value {
            margin: 0;
            font-size: 0.96rem;
            font-weight: 700;
            color: #0f172a;
        }

        .ops-account__cta {
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

        .ops-account__cta:hover {
            transform: translateY(-1px);
            background: #0f172a;
        }

        html.dark .ops-account__hero,
        html.dark .ops-account__actions,
        html.dark .ops-account__stat {
            border-color: rgba(255, 255, 255, 0.08);
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.94) 0%, rgba(15, 23, 42, 0.98) 100%);
            box-shadow: none;
        }

        html.dark .ops-account__heading,
        html.dark .ops-account__name,
        html.dark .ops-account__stat-value {
            color: #f8fafc;
        }

        html.dark .ops-account__subheading,
        html.dark .ops-account__stat-label {
            color: #94a3b8;
        }

        html.dark .ops-account__cta {
            background: #f59e0b;
            color: #111827;
            box-shadow: none;
        }

        @media (max-width: 900px) {
            .ops-account {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="ops-account">
        <div class="ops-account__hero">
            <div class="ops-account__eyebrow">Managed Services Operations</div>

            <div class="ops-account__hero-main">
                <x-filament-panels::avatar.user
                    size="lg"
                    :user="$user"
                    loading="lazy"
                />

                <div>
                    <h2 class="ops-account__heading">Insurance Verification Console</h2>
                    <p class="ops-account__subheading">
                        Work assigned queues, urgent follow-ups, and clinic-specific verification workflows from one operational workspace.
                    </p>
                    <p class="ops-account__name">{{ filament()->getUserName($user) }}</p>
                </div>
            </div>
        </div>

        <div class="ops-account__actions">
            <div class="ops-account__statline">
                <div class="ops-account__stat">
                    <p class="ops-account__stat-label">Role</p>
                    <p class="ops-account__stat-value">{{ method_exists($user, 'getPrimaryRoleLabel') ? ($user->getPrimaryRoleLabel() ?? 'Operations User') : 'Operations User' }}</p>
                </div>
                <div class="ops-account__stat">
                    <p class="ops-account__stat-label">Workspace</p>
                    <p class="ops-account__stat-value">Admin Panel</p>
                </div>
            </div>

            <a href="{{ route('admin.signout') }}" class="ops-account__cta">
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
