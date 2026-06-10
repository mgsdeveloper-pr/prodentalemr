<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_inbox_mailboxes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('clinic_id')->unique();
            $table->boolean('verification_inbox_enabled')->default(false);
            $table->string('verification_inbox_provider', 50)->nullable();
            $table->string('verification_inbox_host')->nullable();
            $table->unsignedInteger('verification_inbox_port')->nullable();
            $table->string('verification_inbox_protocol', 20)->default('imap');
            $table->string('verification_inbox_encryption', 20)->nullable();
            $table->boolean('verification_inbox_validate_certificate')->default(false);
            $table->string('verification_inbox_username')->nullable();
            $table->text('verification_inbox_password')->nullable();
            $table->string('verification_inbox_folder_inbox', 100)->default('INBOX');
            $table->string('verification_inbox_folder_spam', 100)->default('INBOX.Spam');
            $table->unsignedInteger('verification_inbox_sync_frequency_minutes')->default(15);
            $table->unsignedInteger('verification_inbox_sync_window_days')->default(90);
            $table->string('verification_inbox_retention_mode', 30)->default('days');
            $table->unsignedInteger('verification_inbox_retention_days')->nullable();
            $table->unsignedInteger('verification_inbox_keep_latest_count')->nullable();
            $table->unsignedInteger('verification_inbox_spam_retention_days')->nullable();
            $table->boolean('verification_inbox_preserve_flagged')->default(true);
            $table->boolean('verification_inbox_auto_cleanup_enabled')->default(true);
            $table->timestamp('verification_inbox_last_synced_at')->nullable();
            $table->timestamp('verification_inbox_last_cleanup_at')->nullable();
            $table->timestamps();

            $table->foreign('clinic_id')
                ->references('id')
                ->on('clinics')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_inbox_mailboxes');
    }
};
