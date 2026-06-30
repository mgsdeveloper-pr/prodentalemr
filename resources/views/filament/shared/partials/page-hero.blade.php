@php
    $eyebrow = $eyebrow ?? null;
    $title = $title ?? '';
    $description = $description ?? null;
    $scopeLabel = $scopeLabel ?? null;
    $scopeValue = $scopeValue ?? null;
    $rightContent = $rightContent ?? null;
    $extraContent = $extraContent ?? null;
@endphp

<style>
    .pd-page-hero {
        position: relative;
    }

    .pd-page-hero__content {
        display: block;
        min-width: 0;
        padding-right: 360px;
    }

    .pd-page-hero__right {
        position: absolute;
        right: 24px;
        bottom: 24px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: flex-end;
        max-width: 420px;
    }

    @media (max-width: 960px) {
        .pd-page-hero__content {
            padding-right: 0;
        }

        .pd-page-hero__right {
            position: static;
            margin-top: 18px;
            align-items: flex-start;
            max-width: none;
        }
    }
</style>

<section class="pd-page-hero" style="border: 1px solid #dbe4ee; border-radius: 26px; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08); overflow: hidden;">
    <div style="padding: 24px;">
        <div>
            <div class="pd-page-hero__content" style="display: flex; flex-direction: column; gap: 12px;">
                @if (filled($eyebrow))
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; width: fit-content;">
                        {{ $eyebrow }}
                    </div>
                @endif

                <div>
                    <h2 style="margin: 0; font-size: 32px; font-weight: 800; color: #0f172a;">{{ $title }}</h2>

                    @if (filled($description))
                        <p style="margin: 10px 0 0; max-width: 980px; font-size: 15px; line-height: 1.75; color: #64748b;">
                            {{ $description }}
                        </p>
                    @endif

                    @if (filled($scopeLabel) || filled($scopeValue))
                        <p style="margin: 8px 0 0; font-size: 13px; font-weight: 700; color: #0f172a;">
                            @if (filled($scopeLabel))
                                {{ $scopeLabel }}:
                            @endif
                            {{ $scopeValue }}
                        </p>
                    @endif
                </div>
            </div>

            @if (filled($rightContent))
                <div class="pd-page-hero__right">
                    {!! $rightContent !!}
                </div>
            @endif
        </div>

        @if (filled($extraContent))
            <div style="margin-top: 18px;">
                {!! $extraContent !!}
            </div>
        @endif
    </div>
</section>
