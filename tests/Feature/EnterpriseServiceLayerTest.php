<?php

use App\Models\BillingWorkItemAttachment;
use App\Models\Organization;
use App\Models\Invoice;
use App\Models\NotificationDelivery;
use App\Models\NotificationEvent;
use App\Models\Payment;
use App\Models\User;
use App\Models\VerificationNotification;
use App\Jobs\DispatchNotificationDelivery;
use App\Services\Documents\BillingWorkItemAttachmentService;
use App\Services\Notifications\VerificationNotificationService;
use App\Services\Notifications\NotificationEngine;
use App\Services\Notifications\ProductNotificationService;
use App\Support\SaasMailSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->user = User::factory()->create(['status' => true]);
    $this->organization = Organization::create([
        'name' => 'Service Layer Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@service-layer.test',
        'phone' => '5551002000',
        'status' => true,
    ]);
});

it('normalizes smtp server names before configuring mail transport', function () {
    expect(SaasMailSettings::normalizeHost('https://smtp-relay.brevo.com:'))
        ->toBe('smtp-relay.brevo.com')
        ->and(SaasMailSettings::normalizeHost(' smtp-relay.brevo.com/ '))
        ->toBe('smtp-relay.brevo.com');
});

it('publishes a tracked payment notification for saas recipients', function () {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $invoice = Invoice::create([
        'organization_id' => $this->organization->id,
        'issue_date' => today(),
        'due_date' => today()->addDays(7),
        'status' => 'sent',
        'total_amount' => 125,
        'balance_due' => 125,
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'organization_id' => $this->organization->id,
        'payment_date' => today(),
        'amount' => 125,
        'payment_method' => 'card',
    ]);

    $event = NotificationEvent::query()->where('event_type', 'billing.payment_received')->first();

    expect($event)->not->toBeNull()
        ->and($event->deliveries()->where('recipient_user_id', $admin->id)->where('channel', 'in_app')->exists())->toBeTrue();
});

it('records account security alerts for the affected user', function () {
    app(ProductNotificationService::class)->accountSecurity(
        $this->user,
        'password_reset',
        'Password changed',
        'Your ProDental password was successfully changed.',
        'security.password-reset.test-user',
    );

    $event = NotificationEvent::query()->where('event_type', 'security.password_reset')->first();

    expect($event)->not->toBeNull()
        ->and($event->deliveries()->where('recipient_user_id', $this->user->id)->where('status', 'delivered')->exists())->toBeTrue();
});

it('marks one or all verification notifications as read through the notification service', function () {
    $service = app(VerificationNotificationService::class);

    $first = VerificationNotification::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'panel' => 'verification',
        'activity_type' => 'assignment_changed',
        'level' => 'info',
        'title' => 'Assigned',
        'message' => 'A verification was assigned.',
    ]);

    $second = VerificationNotification::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'panel' => 'verification',
        'activity_type' => 'status_changed',
        'level' => 'info',
        'title' => 'Updated',
        'message' => 'A verification was updated.',
    ]);

    $service->markRead($first);

    expect($first->fresh()->read_at)->not->toBeNull()
        ->and($second->fresh()->read_at)->toBeNull();

    $service->markAllReadForPanel($this->user, 'verification');

    expect($second->fresh()->read_at)->not->toBeNull();
});

it('publishes idempotent in-app notification events and delivery records', function () {
    $engine = app(NotificationEngine::class);
    $writes = 0;
    $eventData = [
        'event_type' => 'verification.assignment_changed',
        'organization_id' => $this->organization->id,
        'level' => 'info',
        'title' => 'Verification assigned',
        'message' => 'A verification request was assigned.',
        'idempotency_key' => 'test.assignment.1001',
    ];
    $recipients = [[
        'user' => $this->user,
        'panel' => 'verification',
        'channels' => ['in_app'],
        'target_url' => '/verification/verifications/1',
    ]];
    $writer = function () use (&$writes): void {
        $writes++;
    };

    $first = $engine->publish($eventData, $recipients, $writer);
    $second = $engine->publish($eventData, $recipients, $writer);

    expect($first->is($second))->toBeTrue()
        ->and(NotificationEvent::query()->count())->toBe(1)
        ->and(NotificationDelivery::query()->count())->toBe(1)
        ->and(NotificationDelivery::query()->first()->status)->toBe(NotificationDelivery::STATUS_DELIVERED)
        ->and($writes)->toBe(1);
});

it('queues external notification delivery without exposing it during the request', function () {
    Queue::fake();

    app(NotificationEngine::class)->publish([
        'event_type' => 'verification.sla_overdue',
        'organization_id' => $this->organization->id,
        'level' => 'danger',
        'title' => 'Internal patient-aware title',
        'message' => 'Internal patient-aware message',
        'payload' => [
            'external_title' => 'Verification update requires attention',
            'external_message' => 'Sign in to review the verification securely.',
        ],
        'idempotency_key' => 'test.sla.1001',
    ], [[
        'user' => $this->user,
        'panel' => 'verification',
        'channels' => ['email'],
    ]]);

    Queue::assertPushed(DispatchNotificationDelivery::class);

    expect(NotificationDelivery::query()->first())
        ->status->toBe(NotificationDelivery::STATUS_PENDING);
});

it('synchronizes verification read state with its engine delivery record', function () {
    $event = NotificationEvent::create([
        'event_type' => 'verification.status_changed',
        'organization_id' => $this->organization->id,
        'level' => 'info',
        'title' => 'Status changed',
        'message' => 'A status changed.',
        'idempotency_key' => 'test.status.1001',
        'occurred_at' => now(),
    ]);
    $delivery = NotificationDelivery::create([
        'notification_event_id' => $event->id,
        'recipient_user_id' => $this->user->id,
        'panel' => 'verification',
        'channel' => 'in_app',
        'status' => NotificationDelivery::STATUS_DELIVERED,
        'destination' => (string) $this->user->id,
        'delivery_key' => hash('sha256', 'test.status.1001'),
        'delivered_at' => now(),
    ]);
    $notification = VerificationNotification::create([
        'notification_event_id' => $event->id,
        'notification_delivery_id' => $delivery->id,
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'panel' => 'verification',
        'activity_type' => 'status_changed',
        'level' => 'info',
        'title' => 'Status changed',
        'message' => 'A status changed.',
    ]);

    app(VerificationNotificationService::class)->markRead($notification);

    expect($notification->fresh()->read_at)->not->toBeNull()
        ->and($delivery->fresh()->read_at)->not->toBeNull();
});

it('checks verification request attachment file availability through the document service', function () {
    Storage::fake('local');

    $service = app(BillingWorkItemAttachmentService::class);
    $attachment = new BillingWorkItemAttachment([
        'file_path' => 'verification/test-attachment.pdf',
        'original_file_name' => 'test-attachment.pdf',
    ]);

    expect($service->exists($attachment))->toBeFalse();

    Storage::disk('local')->put('verification/test-attachment.pdf', 'sample');

    expect($service->exists($attachment))->toBeTrue();
});
