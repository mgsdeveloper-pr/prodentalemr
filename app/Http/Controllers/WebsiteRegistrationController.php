<?php

namespace App\Http\Controllers;

use App\Models\OnboardingDraft;
use App\Models\SubscriptionPlan;
use App\Models\WebsiteRegistration;
use App\Services\ClientOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WebsiteRegistrationController extends Controller
{
    private const FIELDS = ['owner_name', 'owner_phone', 'organization_name', 'organization_owner_name', 'organization_email', 'organization_phone', 'clinic_name', 'location_name', 'location_address', 'location_city', 'location_state', 'location_zip_code', 'location_country', 'clinic_timezone', 'dso_name', 'dso_admin_name', 'dso_admin_email', 'subscription_plan_id'];

    public function plans()
    {
        $states = \App\Support\UsLocationOptions::stateOptions();
        return response()->json([
            'plans' => SubscriptionPlan::websiteAvailable()->orderBy('website_sort_order')->orderBy('id')->get()->map(fn ($plan) => $this->plan($plan)),
            'onboarding_options' => [
                'location_country' => ['USA' => 'United States'],
                'clinic_timezone' => \App\Support\UsTimezoneOptions::options(),
                'location_state' => $states,
                'cities' => collect(array_keys($states))->mapWithKeys(fn ($state) => [$state => \App\Support\UsLocationOptions::cityOptions($state)]),
            ],
        ]);
    }

    public function start(Request $request, ClientOnboardingService $onboarding)
    {
        if (is_string($request->input('email'))) $request->merge(['email' => strtolower(trim($request->input('email')))]);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:254'], 'owner_name' => ['required', 'string', 'max:150'],
            'clinic_name' => ['required', 'string', 'max:200'],
            'account_structure' => ['required', Rule::in(ClientOnboardingService::STRUCTURES)],
            'verification_model' => ['required', Rule::in(ClientOnboardingService::VERIFICATION_MODELS)],
        ]);
        $this->limitEmail($data['email']);
        try {
            $registration = DB::transaction(function () use ($data, $onboarding) {
                $existing = WebsiteRegistration::where('email', $data['email'])->lockForUpdate()->first();
                if ($existing) return $existing;
                $draft = $onboarding->start(null, $data['account_structure'], $data['verification_model'], 'website');
                $onboarding->save($draft, [...$draft->data, 'owner_name' => $data['owner_name'], 'owner_email' => $data['email'], 'organization_owner_name' => $data['owner_name'], 'organization_email' => $data['email'], 'clinic_name' => $data['clinic_name']], 1);
                return WebsiteRegistration::create(['onboarding_draft_id' => $draft->id, 'email' => $data['email']]);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $exception) {
            // A simultaneous start may have registered this email first. Its
            // existing data stays authoritative; the losing transaction rolled back.
            $registration = WebsiteRegistration::where('email', $data['email'])->first();
            if (! $registration) throw $exception;
        }
        return $this->sendResume($registration);
    }

    public function resume(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:254']]);
        $email = strtolower(trim($data['email']));
        $this->limitEmail($email);
        // Uniform availability response regardless of whether the email exists.
        $this->mailReady();
        $registration = WebsiteRegistration::where('email', $email)->first();
        if ($registration) return $this->sendResume($registration);
        return response()->json(['message' => 'If a registration exists, a secure resume link has been emailed to you.']);
    }

    public function redeem(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64']]);
        return DB::transaction(function () use ($data) {
            $registration = WebsiteRegistration::where('resume_hash', hash('sha256', $data['token']))->lockForUpdate()->first();
            abort_unless($registration && $registration->resume_expires_at?->isFuture(), 410, 'This link expired or was used. Request a new resume link.');
            $access = bin2hex(random_bytes(32));
            $registration->update(['resume_hash' => null, 'resume_expires_at' => null, 'access_hash' => hash('sha256', $access), 'access_expires_at' => now()->addHours(12), 'email_verified_at' => $registration->email_verified_at ?? now()]);
            $registration->draft->update(['expires_at' => now()->addDays(14)]);
            return response()->json(['access_token' => $access, 'registration' => $this->payload($registration)]);
        });
    }

    public function show(Request $request)
    {
        return response()->json(['registration' => $this->payload($this->authorized($request))]);
    }

    public function save(Request $request, ClientOnboardingService $onboarding)
    {
        $registration = $this->authorized($request);
        return DB::transaction(function () use ($request, $registration, $onboarding) {
            $draft = OnboardingDraft::whereKey($registration->onboarding_draft_id)->lockForUpdate()->firstOrFail();
            $this->editable($draft);
            $data = $request->validate($this->rules(false));
            $state = array_replace($draft->data ?? [], $data);
            if (array_key_exists('subscription_plan_id', $data)) {
                $state['plan_snapshot'] = $data['subscription_plan_id'] ? $this->plan($this->selectedPlan($data['subscription_plan_id'], $draft)) : null;
            }
            $onboarding->save($draft, $state, $this->step($state));
            return response()->json(['registration' => $this->payload($registration->fresh()), 'message' => 'Progress saved.']);
        });
    }

    public function submit(Request $request)
    {
        $registration = $this->authorized($request);
        return DB::transaction(function () use ($registration) {
            $draft = OnboardingDraft::whereKey($registration->onboarding_draft_id)->lockForUpdate()->firstOrFail();
            if ($draft->status === OnboardingDraft::STATUS_SUBMITTED) return response()->json(['registration' => $this->payload($registration)]);
            $this->editable($draft);
            $state = $draft->data ?? [];
            Validator::make($state, $this->rules(true))->validate();
            Validator::make($state, ['location_city' => [Rule::in(array_keys(\App\Support\UsLocationOptions::cityOptions($state['location_state']))) ]])->validate();
            if ($draft->account_structure === 'dso') Validator::make($state, ['dso_name' => 'required|string|max:200'])->validate();
            $plan = $this->selectedPlan($state['subscription_plan_id'], $draft);
            $snapshot = $this->plan($plan);
            abort_unless(($state['plan_snapshot'] ?? null) === $snapshot, 409, 'This plan changed. Select it again and review the updated details.');
            $draft->update(['status' => OnboardingDraft::STATUS_SUBMITTED, 'last_completed_step' => 4, 'submitted_at' => now()]);
            return response()->json(['registration' => $this->payload($registration->fresh()), 'message' => 'Registration submitted for review. No payment has been taken.']);
        });
    }

    public function authorized(Request $request): WebsiteRegistration
    {
        $token = (string) $request->header('X-Registration-Token');
        abort_unless(strlen($token) === 64, 401, 'Please request a resume link.');
        $registration = WebsiteRegistration::where('access_hash', hash('sha256', $token))->first();
        abort_unless($registration && $registration->email_verified_at && $registration->access_expires_at?->isFuture(), 401, 'Your session expired. Please request a resume link.');
        return $registration;
    }

    private function editable(OnboardingDraft $draft): void
    {
        abort_if(\App\Models\WebsiteCheckout::whereHas('registration', fn ($q) => $q->where('onboarding_draft_id', $draft->id))->exists(), 409, 'Checkout has started. Contact the team to change the agreed plan or billing details.');
        abort_unless(in_array($draft->status, [OnboardingDraft::STATUS_DRAFT, OnboardingDraft::STATUS_CHANGES_REQUESTED], true), 409, 'This registration is with the team for review and cannot be edited.');
    }

    private function rules(bool $complete): array
    {
        $rules = [];
        foreach (self::FIELDS as $field) $rules[$field] = ['sometimes', 'nullable', 'string', 'max:255'];
        $rules['owner_phone'] = ['sometimes', 'nullable', 'string', 'max:30'];
        $rules['organization_email'] = ['sometimes', 'nullable', 'email', 'max:254'];
        $rules['dso_admin_email'] = ['sometimes', 'nullable', 'email', 'max:254'];
        $rules['clinic_timezone'] = ['sometimes', 'nullable', 'timezone'];
        $rules['subscription_plan_id'] = ['sometimes', 'nullable', 'integer'];
        if ($complete) {
            foreach (['owner_name', 'owner_phone', 'organization_name', 'clinic_name', 'location_name', 'location_country', 'location_state', 'location_city', 'clinic_timezone'] as $field) $rules[$field][0] = 'required';
            $rules['location_country'][] = Rule::in(['USA']);
            $rules['location_state'][] = Rule::in(array_keys(\App\Support\UsLocationOptions::stateOptions()));
            $rules['clinic_timezone'][] = Rule::in(array_keys(\App\Support\UsTimezoneOptions::options()));
            $rules['subscription_plan_id'] = ['required', 'integer'];
        }
        return $rules;
    }

    private function selectedPlan(int $id, OnboardingDraft $draft): SubscriptionPlan
    {
        $plan = SubscriptionPlan::websiteAvailable()->where('verification_model', $draft->verification_model)->find($id);
        abort_unless($plan, 422, 'Choose an available plan for your selected service.');
        return $plan;
    }

    private function plan(SubscriptionPlan $plan): array
    {
        return ['id' => $plan->id, 'name' => $plan->name, 'description' => $plan->website_description, 'offering' => $plan->verification_model, 'price' => $plan->quote_required ? null : $plan->price, 'currency' => $plan->currency, 'billing_interval' => $plan->billing_interval, 'quote_required' => $plan->quote_required, 'max_clinics' => $plan->max_clinics, 'max_users' => $plan->max_users, 'monthly_verifications' => data_get($plan->plan_limits, 'monthly_verifications'), 'features' => $plan->included_features ?? []];
    }

    private function payload(WebsiteRegistration $registration): array
    {
        $draft = $registration->draft;
        // Never expose staff notes, provisioning settings, or unrelated draft fields.
        return ['reference' => $draft->public_id, 'email' => $registration->email, 'status' => $draft->status, 'step' => $draft->last_completed_step, 'structure' => $draft->account_structure, 'offering' => $draft->verification_model, 'saved_at' => $draft->updated_at->toIso8601String(), 'data' => collect($draft->data ?? [])->only([...self::FIELDS, 'plan_snapshot'])->all()];
    }

    private function step(array $state): int
    {
        if (!empty($state['subscription_plan_id'])) return 3;
        foreach (['organization_name', 'location_name', 'location_country', 'clinic_timezone', 'owner_phone'] as $field) if (empty($state[$field])) return 1;
        return 2;
    }

    private function limitEmail(string $email): void
    {
        $key = 'website-resume:'.hash('sha256', $email);
        abort_if(RateLimiter::tooManyAttempts($key, 3), 429, 'Please wait before requesting another link.');
        RateLimiter::hit($key, 900);
    }

    private function mailReady(): void
    {
        abort_if(in_array(config('mail.default'), ['log', 'array'], true), 503, 'Resume email delivery is not configured. Your saved registration is retained. Contact info@denentiq.com.');
    }

    private function sendResume(WebsiteRegistration $registration)
    {
        $this->mailReady();
        $url = (string) config('website_registration.resume_url');
        abort_unless(str_starts_with($url, 'https://') && ! str_contains($url, '#'), 503, 'Registration URL is not configured.');
        $token = bin2hex(random_bytes(32));
        $registration->update(['resume_hash' => hash('sha256', $token), 'resume_expires_at' => now()->addMinutes(30)]);
        try {
            Mail::raw("Continue your Denentiq registration:\n\n".$url.'#resume='.$token."\n\nThis link expires in 30 minutes and can be used once. If you did not request this, ignore this email.", fn ($message) => $message->to($registration->email)->subject('Continue your Denentiq registration'));
        } catch (\Throwable $e) {
            report($e);
            abort(503, 'We could not send your resume email. Your saved registration is retained. Please try again later.');
        }
        return response()->json(['message' => 'If a registration exists, a secure resume link has been emailed to you.']);
    }
}
