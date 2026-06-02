<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_work_items', function (Blueprint $table): void {
            $table->foreignId('returned_by_user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->string('returned_by_role')->nullable()->after('returned_by_user_id');
            $table->text('return_reason')->nullable()->after('returned_by_role');
            $table->foreignId('info_requested_by_user_id')->nullable()->after('return_reason')->constrained('users')->nullOnDelete();
            $table->string('info_requested_by_role')->nullable()->after('info_requested_by_user_id');
            $table->text('info_request_reason')->nullable()->after('info_requested_by_role');
            $table->foreignId('clinic_responded_by_user_id')->nullable()->after('info_request_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('clinic_responded_at')->nullable()->after('clinic_responded_by_user_id');
            $table->foreignId('reworked_by_user_id')->nullable()->after('clinic_responded_at')->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->after('reworked_by_user_id')->constrained('users')->nullOnDelete();
        });

        $statusMap = [
            ['from' => 'unassigned', 'to' => 'pending'],
            ['from' => 'assigned', 'to' => 'pending'],
            ['from' => 'pending', 'to' => 'pending'],
            ['from' => 'incomplete', 'to' => 'in_progress'],
            ['from' => 'in_progress', 'to' => 'in_progress'],
            ['from' => 'waiting_on_payer', 'to' => 'in_progress'],
            ['from' => 'waiting_on_client', 'to' => 'awaiting_clinic_response'],
            ['from' => 'review', 'to' => 'review'],
            ['from' => 'ready_for_review', 'to' => 'review'],
            ['from' => 'audit', 'to' => 'returned_for_rework'],
            ['from' => 'done', 'to' => 'done'],
            ['from' => 'completed', 'to' => 'done'],
            ['from' => 'cancelled', 'to' => 'done'],
        ];

        foreach ($statusMap as $map) {
            DB::table('billing_work_items')
                ->where('status', $map['from'])
                ->update(['status' => $map['to']]);
        }
    }

    public function down(): void
    {
        Schema::table('billing_work_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('closed_by_user_id');
            $table->dropConstrainedForeignId('reworked_by_user_id');
            $table->dropColumn('clinic_responded_at');
            $table->dropConstrainedForeignId('clinic_responded_by_user_id');
            $table->dropColumn('info_request_reason');
            $table->dropColumn('info_requested_by_role');
            $table->dropConstrainedForeignId('info_requested_by_user_id');
            $table->dropColumn('return_reason');
            $table->dropColumn('returned_by_role');
            $table->dropConstrainedForeignId('returned_by_user_id');
        });
    }
};
