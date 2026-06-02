<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mappings = [
            ['from' => 'unassigned', 'to' => 'pending'],
            ['from' => 'assigned', 'to' => 'pending'],
            ['from' => 'in_progress', 'to' => 'incomplete'],
            ['from' => 'waiting_on_client', 'to' => 'incomplete'],
            ['from' => 'waiting_on_payer', 'to' => 'incomplete'],
            ['from' => 'ready_for_review', 'to' => 'review'],
            ['from' => 'completed', 'to' => 'done'],
            ['from' => 'cancelled', 'to' => 'done'],
        ];

        foreach ($mappings as $mapping) {
            DB::table('billing_work_items')
                ->where('status', $mapping['from'])
                ->update(['status' => $mapping['to']]);
        }

        DB::table('billing_work_items')
            ->whereNull('status')
            ->update(['status' => 'pending']);
    }

    public function down(): void
    {
        //
    }
};
