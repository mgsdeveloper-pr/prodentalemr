<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('clinic_operatory_id')->nullable()->after('location_id')->constrained('clinic_operatories')->nullOnDelete();
            $table->unsignedInteger('duration_minutes')->nullable()->after('end_time');
            $table->timestamp('confirmed_at')->nullable()->after('duration_minutes');
            $table->timestamp('checked_in_at')->nullable()->after('confirmed_at');
            $table->timestamp('seated_at')->nullable()->after('checked_in_at');
            $table->timestamp('completed_at')->nullable()->after('seated_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->text('arrival_notes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clinic_operatory_id');
            $table->dropColumn([
                'duration_minutes',
                'confirmed_at',
                'checked_in_at',
                'seated_at',
                'completed_at',
                'cancelled_at',
                'arrival_notes',
            ]);
        });
    }
};
