<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_form_submissions', function (Blueprint $table): void {
            $table->unsignedInteger('version')->nullable()->after('priority');
        });

        $grouped = DB::table('verification_form_submissions')
            ->select('billing_work_item_id')
            ->groupBy('billing_work_item_id')
            ->pluck('billing_work_item_id');

        foreach ($grouped as $billingWorkItemId) {
            $rows = DB::table('verification_form_submissions')
                ->where('billing_work_item_id', $billingWorkItemId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id']);

            $version = 1;

            foreach ($rows as $row) {
                DB::table('verification_form_submissions')
                    ->where('id', $row->id)
                    ->update(['version' => $version]);

                $version++;
            }
        }

        Schema::table('verification_form_submissions', function (Blueprint $table): void {
            $table->unsignedInteger('version')->nullable(false)->change();
            $table->unique(['billing_work_item_id', 'version'], 'vfs_item_version_unique');
        });
    }

    public function down(): void
    {
        Schema::table('verification_form_submissions', function (Blueprint $table): void {
            $table->dropUnique('vfs_item_version_unique');
            $table->dropColumn('version');
        });
    }
};
