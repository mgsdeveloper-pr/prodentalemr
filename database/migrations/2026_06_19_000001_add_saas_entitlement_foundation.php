<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->string('plan_code')->nullable()->unique()->after('name');
            $table->string('plan_type')->default('pms_verification')->after('price');
            $table->string('workspace_mode')->default('choose')->after('plan_type');
            $table->json('included_features')->nullable()->after('included_modules');
            $table->json('plan_limits')->nullable()->after('included_features');
            $table->boolean('managed_services_allowed')->default(false)->after('plan_limits');
            $table->unsignedInteger('trial_days')->nullable()->after('managed_services_allowed');
            $table->boolean('demo_mode_available')->default(false)->after('trial_days');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('previous_subscription_plan_id')->nullable()->after('subscription_plan_id')->constrained('subscription_plans')->nullOnDelete();
            $table->string('change_type')->nullable()->after('previous_subscription_plan_id');
            $table->date('effective_date')->nullable()->after('change_type');
            $table->date('renewal_date')->nullable()->after('effective_date');
            $table->boolean('cancel_at_period_end')->default(false)->after('renewal_date');
            $table->timestamp('cancelled_at')->nullable()->after('cancel_at_period_end');
            $table->date('trial_starts_at')->nullable()->after('cancelled_at');
            $table->date('trial_ends_at')->nullable()->after('trial_starts_at');
            $table->boolean('is_demo')->default(false)->after('trial_ends_at');
            $table->string('service_status')->default('active')->after('is_demo');
            $table->string('service_status_reason')->nullable()->after('service_status');
            $table->string('proration_mode')->default('none')->after('service_status_reason');
            $table->decimal('proration_amount', 10, 2)->nullable()->after('proration_mode');
            $table->json('entitlement_overrides')->nullable()->after('proration_amount');
            $table->json('usage_snapshot')->nullable()->after('entitlement_overrides');
            $table->foreignId('account_manager_user_id')->nullable()->after('usage_snapshot')->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable()->after('account_manager_user_id');
            $table->text('billing_notes')->nullable()->after('internal_notes');
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('lifecycle_status')->default('active')->after('status');
            $table->string('onboarding_status')->default('pending')->after('lifecycle_status');
            $table->foreignId('account_manager_user_id')->nullable()->after('onboarding_status')->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable()->after('account_manager_user_id');
        });

        Schema::table('clinics', function (Blueprint $table): void {
            $table->string('service_status')->default('active')->after('clinic_operations_enabled');
            $table->string('pms_service_status')->default('active')->after('service_status');
            $table->string('verification_service_status')->default('active')->after('pms_service_status');
            $table->string('managed_services_status')->default('not_enabled')->after('verification_service_status');
            $table->date('trial_ends_at')->nullable()->after('managed_services_status');
            $table->boolean('demo_mode')->default(false)->after('trial_ends_at');
            $table->json('feature_overrides')->nullable()->after('demo_mode');
            $table->json('usage_snapshot')->nullable()->after('feature_overrides');
            $table->foreignId('account_manager_user_id')->nullable()->after('usage_snapshot')->constrained('users')->nullOnDelete();
            $table->text('service_notes')->nullable()->after('account_manager_user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('default_workspace')->nullable()->after('last_login_at');
            $table->json('allowed_workspaces')->nullable()->after('default_workspace');
            $table->json('feature_overrides')->nullable()->after('allowed_workspaces');
        });

        Schema::create('saas_entitlement_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_entitlement_audit_logs');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['default_workspace', 'allowed_workspaces', 'feature_overrides']);
        });

        Schema::table('clinics', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('account_manager_user_id');
            $table->dropColumn([
                'service_status',
                'pms_service_status',
                'verification_service_status',
                'managed_services_status',
                'trial_ends_at',
                'demo_mode',
                'feature_overrides',
                'usage_snapshot',
                'service_notes',
            ]);
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('account_manager_user_id');
            $table->dropColumn(['lifecycle_status', 'onboarding_status', 'internal_notes']);
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('previous_subscription_plan_id');
            $table->dropConstrainedForeignId('account_manager_user_id');
            $table->dropColumn([
                'change_type',
                'effective_date',
                'renewal_date',
                'cancel_at_period_end',
                'cancelled_at',
                'trial_starts_at',
                'trial_ends_at',
                'is_demo',
                'service_status',
                'service_status_reason',
                'proration_mode',
                'proration_amount',
                'entitlement_overrides',
                'usage_snapshot',
                'internal_notes',
                'billing_notes',
            ]);
        });

        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->dropUnique(['plan_code']);
            $table->dropColumn([
                'plan_code',
                'plan_type',
                'workspace_mode',
                'included_features',
                'plan_limits',
                'managed_services_allowed',
                'trial_days',
                'demo_mode_available',
            ]);
        });
    }
};
