<x-filament-panels::page>
    <style>
        .payment-provider-shell { display: grid; gap: 18px; }
        .payment-provider-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .payment-provider-tab { border: 1px solid #dbe4ee; border-radius: 22px; background: #fff; padding: 18px 20px; text-align: left; cursor: pointer; box-shadow: 0 14px 30px rgba(15, 23, 42, .05); transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease; }
        .payment-provider-tab:hover { transform: translateY(-1px); border-color: #93c5fd; box-shadow: 0 18px 36px rgba(15, 23, 42, .08); }
        .payment-provider-tab.is-active { border-color: #f59e0b; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%); box-shadow: 0 18px 38px rgba(245, 158, 11, .16); }
        .payment-provider-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .payment-provider-title { margin: 0; color: #0f172a; font-size: 17px; font-weight: 900; }
        .payment-provider-copy { margin: 8px 0 0; color: #64748b; font-size: 13px; line-height: 1.55; }
        .payment-provider-status { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--status-border, #fed7aa); border-radius: 999px; background: var(--status-bg, #fff7ed); color: var(--status-text, #c2410c); padding: 6px 10px; font-size: 12px; font-weight: 850; }
        .payment-provider-dot { width: 8px; height: 8px; border-radius: 999px; background: currentColor; }
        .payment-provider-note { border: 1px solid #dbe4ee; border-radius: 20px; background: #f8fbff; color: #52637a; padding: 14px 16px; font-size: 13px; line-height: 1.6; }
        @media (max-width: 800px) { .payment-provider-tabs { grid-template-columns: 1fr; } }
    </style>

    <form wire:submit="save">
        <div class="payment-provider-shell">
            <div class="payment-provider-tabs">
                <button
                    type="button"
                    wire:click="showProvider('stripe')"
                    class="payment-provider-tab {{ $activeProvider === 'stripe' ? 'is-active' : '' }}"
                >
                    <div class="payment-provider-top">
                        <h2 class="payment-provider-title">Stripe</h2>
                        <span
                            class="payment-provider-status"
                            style="{{ (bool) data_get($data, 'stripe_enabled') ? '--status-bg:#f0fdf4;--status-border:#bbf7d0;--status-text:#047857;' : '' }}"
                        >
                            <span class="payment-provider-dot"></span>
                            {{ (bool) data_get($data, 'stripe_enabled') ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <p class="payment-provider-copy">Hosted checkout, invoice payment links, and Stripe webhooks.</p>
                </button>

                <button
                    type="button"
                    wire:click="showProvider('paypal')"
                    class="payment-provider-tab {{ $activeProvider === 'paypal' ? 'is-active' : '' }}"
                >
                    <div class="payment-provider-top">
                        <h2 class="payment-provider-title">PayPal</h2>
                        <span
                            class="payment-provider-status"
                            style="{{ (bool) data_get($data, 'paypal_enabled') ? '--status-bg:#f0fdf4;--status-border:#bbf7d0;--status-text:#047857;' : '' }}"
                        >
                            <span class="payment-provider-dot"></span>
                            {{ (bool) data_get($data, 'paypal_enabled') ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <p class="payment-provider-copy">PayPal Orders v2, capture-on-return, and verified webhooks.</p>
                </button>
            </div>

            <div class="payment-provider-note">
                Showing {{ $activeProvider === 'stripe' ? 'Stripe' : 'PayPal' }} settings only. Switch provider above when you need to manage another payment gateway.
            </div>

            {{ $this->form }}
        </div>
    </form>
</x-filament-panels::page>
