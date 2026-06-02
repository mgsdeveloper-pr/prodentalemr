<?php

use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->json('included_modules')->nullable()->after('max_users');
        });

        $defaultModules = array_keys(PanelPermissionMatrix::modules('clinic'));

        DB::table('subscription_plans')->update([
            'included_modules' => json_encode($defaultModules),
        ]);
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->dropColumn('included_modules');
        });
    }
};
