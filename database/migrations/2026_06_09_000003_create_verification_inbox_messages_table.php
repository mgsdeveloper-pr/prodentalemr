<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_inbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('mailbox_uid', 120);
            $table->string('folder_name', 255);
            $table->string('folder_type', 40)->index();
            $table->string('external_message_id', 255)->nullable()->index();
            $table->string('message_hash', 64)->nullable()->index();
            $table->string('subject')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable()->index();
            $table->string('reply_to_email')->nullable();
            $table->json('to_emails')->nullable();
            $table->json('cc_emails')->nullable();
            $table->json('bcc_emails')->nullable();
            $table->longText('snippet')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->json('headers')->nullable();
            $table->timestamp('received_at')->nullable()->index();
            $table->timestamp('synced_at')->nullable()->index();
            $table->boolean('is_read')->default(false)->index();
            $table->boolean('is_flagged')->default(false)->index();
            $table->boolean('is_spam')->default(false)->index();
            $table->boolean('has_attachments')->default(false);
            $table->unsignedInteger('attachment_count')->default(0);
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->boolean('is_protected')->default(false)->index();
            $table->timestamps();

            $table->unique(['folder_name', 'mailbox_uid'], 'verification_inbox_messages_folder_uid_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_inbox_messages');
    }
};
