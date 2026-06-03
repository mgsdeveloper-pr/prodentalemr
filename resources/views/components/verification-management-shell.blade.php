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
        grid-template-columns: minmax(220px, 270px) minmax(0, 1fr);
        gap: 20px;
        align-items: start;
    }

    .verification-shell__aside {
        border: 1px solid #dbe4ee;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        overflow: hidden;
        position: sticky;
        top: 24px;
    }

    .verification-shell__menu-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 18px;
        text-decoration: none;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        transition: all 140ms ease;
    }

    .verification-shell__menu-link:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .verification-shell__menu-link.is-active {
        border-color: #c7d2fe;
        background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 100%);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.12);
    }

    .verification-shell__menu-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 12px;
        border: 1px solid #dbe4ee;
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        flex-shrink: 0;
    }

    .verification-shell__menu-link.is-active .verification-shell__menu-icon {
        border-color: #bfdbfe;
        background: #dbeafe;
        color: #1d4ed8;
    }

    .verification-shell__menu-arrow {
        display: inline-flex;
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #e2e8f0;
        flex-shrink: 0;
    }

    .verification-shell__menu-link.is-active .verification-shell__menu-arrow {
        background: #2563eb;
    }

    .verification-shell__content {
        display: flex;
        flex-direction: column;
        gap: 16px;
        min-width: 0;
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
        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);">
            <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #0f766e;">
                {{ $menuEyebrow }}
            </div>
            <div style="font-size: 24px; line-height: 1.1; font-weight: 800; color: #0f172a;">
                {{ $menuTitle }}
            </div>
            <div style="margin-top: 10px; font-size: 13px; line-height: 1.7; color: #64748b;">
                {{ $menuDescription }}
            </div>
        </div>

        <nav style="padding: 14px; display: flex; flex-direction: column; gap: 8px;">
            @foreach ($items as $item)
                @php
                    $isActive = ($active !== null && ($item['key'] ?? null) === $active)
                        || (($item['active'] ?? false) === true);
                    $iconLabel = $item['icon_label'] ?? collect(explode(' ', $item['label']))
                        ->filter()
                        ->take(2)
                        ->map(fn (string $word): string => strtoupper(substr($word, 0, 1)))
                        ->implode('');
                @endphp
                <a
                    href="{{ $item['url'] }}"
                    class="verification-shell__menu-link{{ $isActive ? ' is-active' : '' }}"
                >
                    <span style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                        <span class="verification-shell__menu-icon">{{ $iconLabel }}</span>
                        <span style="display: flex; flex-direction: column; gap: 3px; min-width: 0;">
                            <span style="font-size: 14px; font-weight: 800; color: {{ $isActive ? '#1d4ed8' : '#0f172a' }};">
                                {{ $item['label'] }}
                            </span>
                            @if (! empty($item['description']))
                                <span style="font-size: 12px; line-height: 1.5; color: #64748b;">
                                    {{ $item['description'] }}
                                </span>
                            @endif
                        </span>
                    </span>
                    <span class="verification-shell__menu-arrow"></span>
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="verification-shell__content">
        {{ $slot }}
    </div>
</div>
