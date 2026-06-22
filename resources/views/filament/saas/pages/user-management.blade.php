@php
    $cards = collect($this->getCards())->where('visible', true);
@endphp

<x-filament-panels::page>
    <style>
        .saas-user-management { display: grid; gap: 24px; }
        .saas-user-hero { border: 1px solid #dbe4ee; border-radius: 26px; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); padding: 28px 32px; box-shadow: 0 16px 34px rgba(15, 23, 42, .06); }
        .saas-user-pill { display: inline-flex; align-items: center; border: 1px solid #bfdbfe; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 850; letter-spacing: .14em; padding: 8px 14px; text-transform: uppercase; }
        .saas-user-title { margin: 16px 0 8px; color: #020617; font-size: 36px; line-height: 1; font-weight: 900; letter-spacing: -.04em; }
        .saas-user-copy { margin: 0; max-width: 840px; color: #52637a; font-size: 15px; line-height: 1.7; }
        .saas-user-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .saas-user-card { display: flex; gap: 18px; align-items: flex-start; border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; padding: 22px; box-shadow: 0 16px 34px rgba(15, 23, 42, .05); text-decoration: none; transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
        .saas-user-card:hover { transform: translateY(-2px); border-color: #93c5fd; box-shadow: 0 22px 42px rgba(15, 23, 42, .09); }
        .saas-user-icon { display: inline-flex; width: 48px; height: 48px; align-items: center; justify-content: center; border-radius: 18px; background: #fff7ed; color: #c2410c; font-size: 22px; font-weight: 900; flex: 0 0 auto; }
        .saas-user-card-title { margin: 0; color: #0f172a; font-size: 19px; font-weight: 900; }
        .saas-user-card-copy { margin: 8px 0 0; color: #64748b; font-size: 14px; line-height: 1.6; }
        .saas-user-action { display: inline-flex; margin-top: 14px; color: #0f766e; font-size: 13px; font-weight: 850; }
        .saas-user-empty { border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; padding: 36px; color: #64748b; text-align: center; }
        @media (max-width: 900px) { .saas-user-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="saas-user-management">
        <section class="saas-user-hero">
            <div class="saas-user-pill">Settings</div>
            <h1 class="saas-user-title">User Management</h1>
            <p class="saas-user-copy">
                Manage SaaS users, roles, and permission rules from one place.
            </p>
        </section>

        @if ($cards->isEmpty())
            <section class="saas-user-empty">
                You do not have access to user management settings.
            </section>
        @else
            <section class="saas-user-grid">
                @foreach ($cards as $card)
                    <a class="saas-user-card" href="{{ $card['url'] }}">
                        <div class="saas-user-icon">
                            {{ $card['icon'] === 'shield' ? 'S' : 'U' }}
                        </div>
                        <div>
                            <h2 class="saas-user-card-title">{{ $card['title'] }}</h2>
                            <p class="saas-user-card-copy">{{ $card['description'] }}</p>
                            <span class="saas-user-action">Open {{ $card['title'] }}</span>
                        </div>
                    </a>
                @endforeach
            </section>
        @endif
    </div>
</x-filament-panels::page>
