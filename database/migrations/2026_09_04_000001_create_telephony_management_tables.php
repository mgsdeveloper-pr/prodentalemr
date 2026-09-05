<?php

use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telephony_accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('provider')->default('mightycall');
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('business_number')->nullable();
            $table->string('webphone_sdk_url')->nullable();
            $table->boolean('recording_enabled')->default(true);
            $table->boolean('transcription_enabled')->default(false);
            $table->boolean('ai_summary_enabled')->default(false);
            $table->unsignedSmallInteger('recording_retention_days')->default(365);
            $table->unsignedInteger('monthly_minute_limit')->nullable();
            $table->string('recording_announcement')->nullable();
            $table->string('webhook_token', 64)->unique();
            $table->boolean('is_platform_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_connected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'is_active']);
            $table->index(['provider', 'is_active']);
        });

        Schema::create('telephony_user_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telephony_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_user_id')->nullable();
            $table->string('extension')->nullable();
            $table->text('user_key')->nullable();
            $table->boolean('can_call')->default(true);
            $table->boolean('can_access_recordings')->default(false);
            $table->boolean('can_use_ai_summary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['telephony_account_id', 'user_id'], 'telephony_account_user_unique');
        });

        Schema::create('telephony_calls', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('telephony_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('billing_work_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('mightycall');
            $table->string('provider_call_id')->nullable()->index();
            $table->string('direction')->default('outbound');
            $table->string('from_number')->nullable();
            $table->string('to_number');
            $table->string('status')->default('initiated');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->text('recording_url')->nullable();
            $table->unsignedInteger('recording_duration_seconds')->nullable();
            $table->longText('transcript')->nullable();
            $table->longText('ai_summary')->nullable();
            $table->string('ai_review_status')->default('not_requested');
            $table->decimal('estimated_cost', 10, 4)->nullable();
            $table->longText('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['billing_work_item_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        foreach (['saas', 'verification', 'clinic'] as $panel) {
            foreach (PanelPermissionMatrix::ACTIONS as $action => $label) {
                Permission::findOrCreate(PanelPermissionMatrix::permissionName($panel, 'calling', $action), 'web');
            }
        }

        $this->grant('saas_admin', PanelPermissionMatrix::permissionNamesForModule('saas', 'calling'));
        $this->grant('verification_admin', PanelPermissionMatrix::permissionNamesForModule('verification', 'calling'));
        $this->grant('verification_manager', [
            PanelPermissionMatrix::permissionName('verification', 'calling', 'view'),
            PanelPermissionMatrix::permissionName('verification', 'calling', 'add'),
            PanelPermissionMatrix::permissionName('verification', 'calling', 'update'),
        ]);
        $this->grant('verification_user', [
            PanelPermissionMatrix::permissionName('verification', 'calling', 'view'),
            PanelPermissionMatrix::permissionName('verification', 'calling', 'add'),
        ]);
        $this->grant('clinic_admin', PanelPermissionMatrix::permissionNamesForModule('clinic', 'calling'));
        $this->grant('clinic_manager', [
            PanelPermissionMatrix::permissionName('clinic', 'calling', 'view'),
            PanelPermissionMatrix::permissionName('clinic', 'calling', 'add'),
            PanelPermissionMatrix::permissionName('clinic', 'calling', 'update'),
        ]);

    }

    public function down(): void
    {
        foreach (['saas', 'verification', 'clinic'] as $panel) {
            Permission::query()
                ->whereIn('name', PanelPermissionMatrix::permissionNamesForModule($panel, 'calling'))
                ->delete();
        }

        Schema::dropIfExists('telephony_calls');
        Schema::dropIfExists('telephony_user_assignments');
        Schema::dropIfExists('telephony_accounts');
    }

    private function grant(string $roleName, array $permissionNames): void
    {
        $role = Role::findOrCreate($roleName, 'web');
        $permissions = Permission::query()->whereIn('name', $permissionNames)->get();

        if ($permissions->isNotEmpty()) {
            $role->givePermissionTo($permissions);
        }
    }
};
