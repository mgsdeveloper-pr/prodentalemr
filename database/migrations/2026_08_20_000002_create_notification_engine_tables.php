<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_events', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('event_type', 120);
            $table->nullableMorphs('source');
            $table->nullableMorphs('subject');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level', 32)->default('info');
            $table->string('title');
            $table->text('message');
            $table->text('target_url')->nullable();
            $table->json('payload')->nullable();
            $table->string('idempotency_key', 191)->unique();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['organization_id', 'clinic_id', 'occurred_at'], 'notification_events_scope_date_idx');
            $table->index(['event_type', 'occurred_at'], 'notification_events_type_date_idx');
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('notification_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('panel', 32)->nullable();
            $table->string('channel', 32);
            $table->string('status', 32)->default('pending');
            $table->string('destination')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('provider_reference')->nullable();
            $table->json('meta')->nullable();
            $table->string('delivery_key', 64)->unique();
            $table->timestamps();

            $table->index(['recipient_user_id', 'panel', 'status'], 'notification_deliveries_recipient_panel_idx');
            $table->index(['channel', 'status', 'created_at'], 'notification_deliveries_channel_status_idx');
        });

        Schema::table('verification_notifications', function (Blueprint $table): void {
            $table->foreignId('notification_event_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('notification_delivery_id')->nullable()->after('notification_event_id')->constrained()->nullOnDelete();
        });

        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('verification_email_notifications_enabled')->default(false)->after('verification_notify_on_sla_alert');
            $table->boolean('verification_email_on_urgent')->default(true)->after('verification_email_notifications_enabled');
            $table->boolean('verification_email_on_clinic_action')->default(true)->after('verification_email_on_urgent');
            $table->boolean('verification_email_on_sla')->default(true)->after('verification_email_on_clinic_action');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'verification_email_notifications_enabled',
                'verification_email_on_urgent',
                'verification_email_on_clinic_action',
                'verification_email_on_sla',
            ]);
        });

        Schema::table('verification_notifications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('notification_delivery_id');
            $table->dropConstrainedForeignId('notification_event_id');
        });

        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_events');
    }
};
