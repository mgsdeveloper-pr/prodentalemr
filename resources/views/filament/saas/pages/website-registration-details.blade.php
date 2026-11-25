@php
    $draft = $registration->draft;
    $data = $draft->data ?? [];
    $labels = [
        'owner_name' => 'Contact name', 'owner_phone' => 'Phone',
        'organization_name' => 'Organization', 'organization_owner_name' => 'Organization owner',
        'organization_email' => 'Organization email', 'organization_phone' => 'Organization phone',
        'clinic_name' => 'Clinic', 'location_name' => 'Location', 'location_address' => 'Address',
        'location_city' => 'City', 'location_state' => 'State', 'location_zip_code' => 'Postal code',
        'location_country' => 'Country', 'clinic_timezone' => 'Time zone',
        'dso_name' => 'DSO', 'dso_admin_name' => 'DSO administrator', 'dso_admin_email' => 'DSO administrator email',
    ];
    $plan = $data['plan_snapshot'] ?? null;
@endphp
<div class="space-y-4">
    <p>Payment: {{ str($registration->checkout?->status ?? 'not_started')->replace('_', ' ')->headline() }}. Payment attempts and references are available in Billing → Website Payments.</p>
    <p>{{ $registration->email }} · {{ $registration->email_verified_at ? 'Email verified' : 'Email not yet verified' }}</p>
    <p>Reference: {{ $draft->public_id }} · Status: {{ str($draft->status)->replace('_', ' ')->headline() }}</p>
    <p>Offering: {{ \App\Models\SubscriptionPlan::verificationModelOptions()[$draft->verification_model] ?? $draft->verification_model }}</p>
    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($labels as $key => $label)
            <div><dt class="font-semibold">{{ $label }}</dt><dd>{{ filled($data[$key] ?? null) ? $data[$key] : 'Not provided' }}</dd></div>
        @endforeach
    </dl>
    <div>
        <p class="font-semibold">Customer's selected offer</p>
        @if ($plan)
            <p>{{ $plan['name'] ?? '' }} · {{ ($plan['quote_required'] ?? false) ? 'Custom quote' : (($plan['currency'] ?? '').' '.($plan['price'] ?? '')) }} · {{ str($plan['billing_interval'] ?? '')->replace('_', ' ') }}</p>
            <p>{{ $plan['description'] ?? '' }}</p>
        @else
            <p>No plan selected yet.</p>
        @endif
    </div>
</div>
