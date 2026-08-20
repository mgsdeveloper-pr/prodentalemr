@props([
    'items' => [],
    'menuTitle' => 'Verification',
    'menuEyebrow' => 'Settings',
    'menuDescription' => 'Move between verification configuration pages from one focused workspace.',
    'active' => null,
])

<style>
    .verification-shell {
        display: grid;
        grid-template-columns: minmax(220px, 260px) minmax(0, 1fr);
        gap: 20px;
        align-items: start;
    }

    .verification-shell__aside {
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: none;
        overflow: hidden;
        position: sticky;
        top: 24px;
    }

    .verification-shell__menu-link {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 44px;
        padding: 10px 12px;
        border-radius: 0;
        border: 0;
        border-left: 3px solid transparent;
        background: #ffffff;
        color: #344054;
        text-decoration: none;
        transition: all 140ms ease;
    }

    .verification-shell__menu-link:hover {
        background: #f8fafc;
    }

    .verification-shell__menu-link.is-active {
        border-left-color: #0f8a83;
        background: #eaf7f5;
        color: #08756f;
    }

    .verification-shell__menu-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        color: #475569;
        flex-shrink: 0;
    }

    .verification-shell__menu-link.is-active .verification-shell__menu-icon {
        color: #0f8a83;
    }

    .verification-shell__menu-icon svg {
        width: 20px;
        height: 20px;
    }

    .verification-shell__submenu {
        display: flex;
        flex-direction: column;
        margin: 2px 0 4px 31px;
        border-left: 1px solid #dbe4ee;
    }

    .verification-shell__submenu-link {
        padding: 8px 12px 8px 18px;
        color: #667085;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
    }

    .verification-shell__submenu-link:hover,
    .verification-shell__submenu-link.is-active {
        color: #08756f;
        background: #f4fbfa;
    }

    .verification-shell__content {
        display: flex;
        flex-direction: column;
        gap: 16px;
        min-width: 0;
    }

    .verification-shell__content > div > section,
    .verification-shell__content > section {
        border-radius: 8px !important;
        box-shadow: none !important;
    }

    @media (max-width: 1100px) {
        .verification-shell {
            grid-template-columns: minmax(0, 1fr);
        }

        .verification-shell__aside {
            position: static;
        }
    }
</style>

<div class="verification-shell">
    <aside class="verification-shell__aside">
        <div style="padding: 16px 18px; border-bottom: 1px solid #edf2f7; background: #ffffff;">
            <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #0f766e;">
                {{ $menuEyebrow }}
            </div>
            <div style="font-size: 18px; line-height: 1.2; font-weight: 800; color: #101828;">
                {{ $menuTitle }}
            </div>
            <div style="margin-top: 7px; font-size: 12px; line-height: 1.55; color: #667085;">
                {{ $menuDescription }}
            </div>
        </div>

        <nav style="padding: 10px 8px 12px; display: flex; flex-direction: column; gap: 2px;">
            @foreach ($items as $item)
                @php
                    $isActive = ($active !== null && ($item['key'] ?? null) === $active)
                        || (($item['active'] ?? false) === true);
                    $children = $item['children'] ?? [];
                    $isParentActive = collect($children)->contains(fn (array $child): bool => ($child['key'] ?? null) === $active);
                @endphp
                <a href="{{ $item['url'] ?? '#' }}" class="verification-shell__menu-link{{ ($isActive || $isParentActive) ? ' is-active' : '' }}">
                    <span style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                        <span class="verification-shell__menu-icon">
                            <x-dynamic-component :component="$item['icon'] ?? 'heroicon-o-chevron-right'" />
                        </span>
                        <span style="font-size: 14px; font-weight: 700;">{{ $item['label'] }}</span>
                    </span>
                </a>
                @if ($children !== [])
                    <div class="verification-shell__submenu">
                        @foreach ($children as $child)
                            <a href="{{ $child['url'] }}" class="verification-shell__submenu-link{{ ($child['key'] ?? null) === $active ? ' is-active' : '' }}">
                                {{ $child['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </nav>
    </aside>

    <div class="verification-shell__content">
        {{ $slot }}
    </div>
</div>
