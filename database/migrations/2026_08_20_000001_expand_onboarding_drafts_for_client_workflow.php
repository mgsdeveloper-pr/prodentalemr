<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_drafts', function (Blueprint $table): void {
            $table->index('user_id', 'onboarding_drafts_user_id_index');
        });

        Schema::table('onboarding_drafts', function (Blueprint $table): void {
            $table->dropUnique('onboarding_drafts_user_id_type_unique');
            $table->char('public_id', 26)->nullable()->after('id');
            $table->string('entry_point', 30)->default('internal')->after('type');
            $table->string('account_structure', 30)->nullable()->after('entry_point');
            $table->string('verification_model', 30)->nullable()->after('account_structure');
            $table->string('status', 30)->default('draft')->after('verification_model');
            $table->foreignId('organization_id')->nullable()->after('notification_sent_at')->constrained()->nullOnDelete();
            $table->foreignId('dso_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('dso_id');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->timestamp('activated_at')->nullable()->after('reviewed_at');
            $table->timestamp('expires_at')->nullable()->after('activated_at');
            $table->index(['user_id', 'status'], 'onboarding_drafts_user_status_index');
        });

        DB::table('onboarding_drafts')->orderBy('id')->each(function (object $draft): void {
            DB::table('onboarding_drafts')->where('id', $draft->id)->update([
                'public_id' => (string) Str::ulid(),
                'account_structure' => $draft->type === 'dso_onboarding' ? 'dso' : 'organization',
                'verification_model' => data_get(json_decode($draft->data ?? '{}', true), 'verification_model', 'managed_service'),
            ]);
        });

        Schema::table('onboarding_drafts', function (Blueprint $table): void {
            $table->unique('public_id', 'onboarding_drafts_public_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_drafts', function (Blueprint $table): void {
            $table->dropUnique('onboarding_drafts_public_id_unique');
            $table->dropIndex('onboarding_drafts_user_status_index');
            $table->dropConstrainedForeignId('dso_id');
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn([
                'public_id',
                'entry_point',
                'account_structure',
                'verification_model',
                'status',
                'submitted_at',
                'reviewed_at',
                'activated_at',
                'expires_at',
            ]);
        });

        Schema::table('onboarding_drafts', function (Blueprint $table): void {
            $table->unique(['user_id', 'type']);
            $table->dropIndex('onboarding_drafts_user_id_index');
        });
    }
};
