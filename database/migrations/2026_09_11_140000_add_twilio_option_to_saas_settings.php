<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table) {
            $table->boolean('twilio_option_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', fn (Blueprint $table) => $table->dropColumn('twilio_option_enabled'));
    }
};
