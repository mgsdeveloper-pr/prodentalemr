<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('verification_inbox_enabled')->default(false)->after('verification_round_robin_last_user_id');
            $table->string('verification_inbox_provider')->nullable()->after('verification_inbox_enabled');
            $table->string('verification_inbox_host')->nullable()->after('verification_inbox_provider');
            $table->unsignedInteger('verification_inbox_port')->nullable()->after('verification_inbox_host');
            $table->string('verification_inbox_protocol')->default('imap')->after('verification_inbox_port');
            $table->string('verification_inbox_encryption')->default('ssl')->after('verification_inbox_protocol');
            $table->boolean('verification_inbox_validate_certificate')->default(false)->after('verification_inbox_encryption');
            $table->string('verification_inbox_username')->nullable()->after('verification_inbox_validate_certificate');
            $table->text('verification_inbox_password')->nullable()->after('verification_inbox_username');
            $table->string('verification_inbox_folder_inbox')->default('INBOX')->after('verification_inbox_password');
            $table->string('verification_inbox_folder_spam')->default('INBOX.Spam')->after('verification_inbox_folder_inbox');
            $table->unsignedInteger('verification_inbox_sync_frequency_minutes')->default(15)->after('verification_inbox_folder_spam');
            $table->unsignedInteger('verification_inbox_sync_window_days')->default(90)->after('verification_inbox_sync_frequency_minutes');
            $table->enum('verification_inbox_retention_mode', ['none', 'days', 'count'])->default('days')->after('verification_inbox_sync_window_days');
            $table->unsignedInteger('verification_inbox_retention_days')->default(90)->after('verification_inbox_retention_mode');
            $table->unsignedInteger('verification_inbox_keep_latest_count')->default(5000)->after('verification_inbox_retention_days');
            $table->unsignedInteger('verification_inbox_spam_retention_days')->default(30)->after('verification_inbox_keep_latest_count');
            $table->boolean('verification_inbox_preserve_flagged')->default(true)->after('verification_inbox_spam_retention_days');
            $table->boolean('verification_inbox_auto_cleanup_enabled')->default(true)->after('verification_inbox_preserve_flagged');
            $table->timestamp('verification_inbox_last_synced_at')->nullable()->after('verification_inbox_auto_cleanup_enabled');
            $table->timestamp('verification_inbox_last_cleanup_at')->nullable()->after('verification_inbox_last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'verification_inbox_enabled',
                'verification_inbox_provider',
                'verification_inbox_host',
                'verification_inbox_port',
                'verification_inbox_protocol',
                'verification_inbox_encryption',
                'verification_inbox_validate_certificate',
                'verification_inbox_username',
                'verification_inbox_password',
                'verification_inbox_folder_inbox',
                'verification_inbox_folder_spam',
                'verification_inbox_sync_frequency_minutes',
                'verification_inbox_sync_window_days',
                'verification_inbox_retention_mode',
                'verification_inbox_retention_days',
                'verification_inbox_keep_latest_count',
                'verification_inbox_spam_retention_days',
                'verification_inbox_preserve_flagged',
                'verification_inbox_auto_cleanup_enabled',
                'verification_inbox_last_synced_at',
                'verification_inbox_last_cleanup_at',
            ]);
        });
    }
};
