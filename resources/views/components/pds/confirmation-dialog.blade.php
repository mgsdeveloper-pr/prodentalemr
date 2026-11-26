@props(['title' => 'Confirm action', 'open' => false, 'dismiss' => null, 'returnFocus' => null])

<div
    {{ $attributes->class(['pds-confirmation-dialog']) }}
    role="dialog"
    aria-modal="true"
    aria-label="{{ $title }}"
    @if ($returnFocus)
        data-return-focus="{{ $returnFocus }}"
        x-data="{
            destroy() {
                const opener = this.$el.dataset.returnFocus;
                requestAnimationFrame(() => document.getElementById(opener)?.focus({ preventScroll: true }));
            }
        }"
        x-trap.inert.noscroll.noreturn="{{ $open ? 'true' : 'false' }}"
    @else
        x-data
        x-trap.inert.noscroll="{{ $open ? 'true' : 'false' }}"
    @endif
    @if ($dismiss) x-on:keydown.escape.stop.prevent="{{ $dismiss }}" @endif
    @unless ($open) hidden @endunless
>
    <div class="pds-confirmation-dialog__panel">
        <header class="pds-confirmation-dialog__header">
            <h2>{{ $title }}</h2>
            @isset($headerAction)
                {{ $headerAction }}
            @endisset
        </header>
        <div class="pds-confirmation-dialog__body">{{ $slot }}</div>
        @isset($actions)
            <footer class="pds-confirmation-dialog__actions">{{ $actions }}</footer>
        @endisset
    </div>
</div>

@once
    <style>
        .pds-confirmation-dialog {
            position: fixed;
            inset: 0;
            z-index: 90;
            display: grid;
            place-items: center;
            padding: 1rem;
            background: rgb(15 23 42 / 48%);
        }

        .pds-confirmation-dialog[hidden] { display: none; }

        .pds-confirmation-dialog__panel {
            display: flex;
            flex-direction: column;
            width: min(36rem, 100%);
            max-height: calc(100vh - 2rem);
            max-height: calc(100dvh - 2rem);
            overflow: hidden;
            border: 1px solid var(--pd-border, #dbe4ee);
            border-radius: .75rem;
            background: var(--pd-card, #fff);
            color: var(--pd-text, #0f172a);
            box-shadow: 0 24px 64px rgb(15 23 42 / 25%);
        }

        .pds-confirmation-dialog__header,
        .pds-confirmation-dialog__actions {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: .625rem;
            padding: 1rem;
        }

        .pds-confirmation-dialog__header {
            justify-content: space-between;
            border-bottom: 1px solid var(--pd-border, #dbe4ee);
        }

        .pds-confirmation-dialog__header h2 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .pds-confirmation-dialog__body {
            display: grid;
            gap: .625rem;
            min-height: 0;
            padding: 1rem;
            overflow-y: auto;
            overflow-wrap: anywhere;
            font-size: .875rem;
        }

        .pds-confirmation-dialog__actions {
            flex-wrap: wrap;
            justify-content: flex-end;
            border-top: 1px solid var(--pd-border, #dbe4ee);
        }
    </style>
@endonce
