<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_inbox_messages', function (Blueprint $table): void {
            $table->unsignedBigInteger('clinic_id')->nullable()->after('id')->index();
        });

        Schema::table('verification_inbox_messages', function (Blueprint $table): void {
            $table->foreign('clinic_id')
                ->references('id')
                ->on('clinics')
                ->nullOnDelete();
        });

        try {
            DB::statement('ALTER TABLE verification_inbox_messages DROP INDEX verification_inbox_messages_folder_uid_unique');
        } catch (\Throwable) {
            // Index may already be missing on some environments.
        }

        Schema::table('verification_inbox_messages', function (Blueprint $table): void {
            $table->unique(['clinic_id', 'folder_name', 'mailbox_uid'], 'v_inbox_messages_clinic_folder_uid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('verification_inbox_messages', function (Blueprint $table): void {
            $table->dropUnique('v_inbox_messages_clinic_folder_uid_unique');
            $table->dropForeign(['clinic_id']);
            $table->dropColumn('clinic_id');
        });

        Schema::table('verification_inbox_messages', function (Blueprint $table): void {
            $table->unique(['folder_name', 'mailbox_uid'], 'verification_inbox_messages_folder_uid_unique');
        });
    }
};
