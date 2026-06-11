<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_mailboxes', function (Blueprint $table): void {
            $table->unsignedInteger('attachment_limit_mb')->default(25)->after('from_address');
        });
    }

    public function down(): void
    {
        Schema::table('user_mailboxes', function (Blueprint $table): void {
            $table->dropColumn('attachment_limit_mb');
        });
    }
};
