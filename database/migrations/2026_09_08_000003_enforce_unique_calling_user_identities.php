<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateUsers = DB::table('telephony_user_assignments')->select('user_id')
            ->groupBy('user_id')->havingRaw('COUNT(*) > 1')->exists();
        $duplicateAgents = DB::table('telephony_user_assignments')
            ->whereNotNull('provider_user_id')->whereRaw("TRIM(provider_user_id) <> ''")
            ->selectRaw('LOWER(TRIM(provider_user_id)) AS agent_identity')
            ->groupByRaw('LOWER(TRIM(provider_user_id))')->havingRaw('COUNT(*) > 1')->exists();
        if ($duplicateUsers || $duplicateAgents) {
            throw new RuntimeException('Calling identity migration stopped: duplicate portal users or MightyCall agent IDs exist. Review User Calling Access and retain one identity per user before retrying. No mappings were removed.');
        }

        DB::table('telephony_user_assignments')
            ->update(['provider_user_id' => DB::raw("NULLIF(TRIM(provider_user_id), '')")]);
        Schema::table('telephony_user_assignments', function (Blueprint $table): void {
            $table->unique('user_id', 'telephony_assignment_user_unique');
            $table->unique('provider_user_id', 'telephony_assignment_agent_unique');
        });
    }

    public function down(): void
    {
        Schema::table('telephony_user_assignments', function (Blueprint $table): void {
            $table->dropUnique('telephony_assignment_user_unique');
            $table->dropUnique('telephony_assignment_agent_unique');
        });
    }
};
