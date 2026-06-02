<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->text('address')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('zip_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('zip_code');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'address',
                'city',
                'state',
                'zip_code',
                'country',
            ]);
        });
    }
};
