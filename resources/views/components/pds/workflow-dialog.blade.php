@props([
    'labelledBy',
    'dismiss' => null,
    'autoOpen' => true,
    'width' => '45rem',
    'placement' => 'center',
    'returnFocus' => null,
])

<dialog
    {{ $attributes->class(['pds-workflow-dialog', 'pds-workflow-dialog--corner' => $placement === 'corner'])->merge(['style' => '--pds-dialog-width: '.$width]) }}
    aria-labelledby="{{ $labelledBy }}"
    aria-modal="true"
    wire:ignore.self
    data-return-focus="{{ $returnFocus }}"
    x-data="{
        opener: null,
        init() {
            if ({{ $autoOpen ? 'true' : 'false' }}) this.$nextTick(() => this.open());
        },
        open() {
            if (this.$el.open || !this.$el.isConnected) return;
            this.opener = document.activeElement;
            this.$el.showModal();
        },
        wrapTab(event) {
            const controls = [...this.$el.querySelectorAll('a[href], button, input, select, textarea, summary, [tabindex]')]
                .filter(control => control.tabIndex >= 0
                    && !control.matches(':disabled')
                    && !control.closest('[inert]')
                    && control.getClientRects().length
                    && getComputedStyle(control).visibility === 'visible');
            const from = event.shiftKey ? controls[0] : controls.at(-1);
            const to = event.shiftKey ? controls.at(-1) : controls[0];

            if (to && (document.activeElement === from || document.activeElement === this.$el)) {
                event.preventDefault();
                to.focus();
            }
        },
        restore() {
            const target = this.$el.dataset.returnFocus;
            const opener = this.opener;
            this.opener = null;

            requestAnimationFrame(() => {
                const lastDialog = document.querySelector('dialog:modal:focus-within');
                const preferred = target ? document.getElementById(target) : null;
                const destination = preferred?.getClientRects().length ? preferred : opener;
                if (destination?.isConnected && (!lastDialog || lastDialog.contains(destination))) {
                    destination.focus({ preventScroll: true });
                }
            });
        },
        destroy() {
            if (this.$el.open) this.$el.close();
            if (this.opener) this.restore();
        },
    }"
    x-on:open-workflow-dialog="open()"
    x-on:close="if (opener) restore(); $dispatch('workflow-dialog-closed')"
    x-on:keydown.tab="wrapTab($event)"
    @if ($dismiss) x-on:cancel.prevent="{{ $dismiss }}" @endif
>
    {{ $slot }}
</dialog>

@once
    <style>
        .pds-workflow-dialog {
            position: fixed;
            inset: 0;
            box-sizing: border-box;
            width: min(var(--pds-dialog-width), calc(100% - 2rem));
            max-width: none;
            max-height: calc(100vh - 2rem);
            max-height: calc(100dvh - 2rem);
            margin: auto;
            padding: 0;
            overflow: auto;
            overscroll-behavior: contain;
            color: var(--pd-text, #0f172a);
            background: var(--pd-card, #fff);
            border: 1px solid var(--pd-border, #dbe4ee);
            border-radius: .75rem;
            box-shadow: 0 24px 64px rgb(15 23 42 / 25%);
        }

        html:has(.pds-workflow-dialog:modal) { overflow: hidden; }
        .pds-workflow-dialog::backdrop { background: rgb(15 23 42 / 52%); }
        .pds-workflow-dialog--corner { margin: auto 1rem 1rem auto; }
        .pds-workflow-dialog :focus-visible { outline: 2px solid var(--pwdl-primary, #0f766e); outline-offset: 2px; }
        .pds-workflow-dialog [role="alert"] { color: var(--pwdl-status-danger-text, #b42318); }
        .pds-workflow-dialog iframe { display: block; }

        @media (max-width: 40rem) {
            .pds-workflow-dialog [data-dialog-actions] { flex-wrap: wrap; }
            .pds-workflow-dialog [data-dialog-columns] { grid-template-columns: minmax(0, 1fr) !important; }
        }
    </style>
@endonce
