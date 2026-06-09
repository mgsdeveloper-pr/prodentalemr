<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_inbox_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('verification_inbox_message_id');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('part_number', 50)->nullable();
            $table->string('content_id')->nullable();
            $table->boolean('is_inline')->default(false);
            $table->string('storage_disk', 50)->default('verification_inbox');
            $table->string('storage_path')->nullable();
            $table->timestamps();

            $table->foreign('verification_inbox_message_id', 'v_inbox_attach_message_fk')
                ->references('id')
                ->on('verification_inbox_messages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_inbox_attachments');
    }
};
