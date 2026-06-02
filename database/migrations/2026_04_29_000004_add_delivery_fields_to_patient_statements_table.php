<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_statements', function (Blueprint $table) {
            $table->string('recipient_email')->nullable()->after('status');
            $table->timestamp('sent_at')->nullable()->after('recipient_email');
            $table->foreignId('last_sent_by')->nullable()->after('sent_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_statements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_sent_by');
            $table->dropColumn(['recipient_email', 'sent_at']);
        });
    }
};
