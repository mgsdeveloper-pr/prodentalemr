<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_mailboxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->boolean('enabled')->default(false);
            $table->string('provider_label', 100)->nullable();
            $table->string('imap_host')->default('mail.medityaglobalservices.com');
            $table->unsignedInteger('imap_port')->default(993);
            $table->string('imap_encryption', 20)->default('ssl');
            $table->boolean('imap_validate_certificate')->default(false);
            $table->string('imap_username')->nullable();
            $table->text('imap_password')->nullable();
            $table->string('inbox_folder', 100)->default('INBOX');
            $table->string('spam_folder', 100)->default('INBOX.Spam');
            $table->string('sent_folder', 100)->default('INBOX.Sent');
            $table->string('smtp_host')->default('mail.medityaglobalservices.com');
            $table->unsignedInteger('smtp_port')->default(465);
            $table->string('smtp_encryption', 20)->default('ssl');
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_mailboxes');
    }
};
