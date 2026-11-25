<div class="space-y-4">
    <p>{{ $checkout->registration->email }} · {{ $checkout->public_id }}</p>
    <p>{{ $checkout->plan_snapshot['name'] ?? '' }} · {{ $checkout->currency }} {{ \App\Services\WebsiteCheckout\CheckoutMoney::decimal($checkout->amount_minor) }} · Initial payment only</p>
    <p>Status: {{ str($checkout->status)->replace('_', ' ')->headline() }}</p>
    @foreach ($checkout->attempts->sortByDesc('id') as $attempt)
        <div class="space-y-1 rounded-lg border p-4">
            <p class="font-semibold">{{ ucfirst($attempt->gateway) }} · {{ strtoupper($attempt->environment) }} · {{ $attempt->status }}</p>
            <p>Attempt: {{ $attempt->public_id }}</p>
            <p>Gateway checkout: {{ $attempt->remote_id ?? 'Creation awaiting confirmation' }}</p>
            <p>Payment reference: {{ $attempt->payment_reference ?? 'Not confirmed' }}</p>
            <p>Last checked: {{ $attempt->last_checked_at ?? 'Not checked' }}</p>
            @if ($attempt->last_error)<p>Gateway verification needs attention. Use Check gateway status, then reconcile with the gateway dashboard if it remains unresolved. Do not collect a second payment for an uncertain attempt.</p>@endif
        </div>
    @endforeach
</div>
