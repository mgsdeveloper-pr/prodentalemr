<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telephony_calls', function (Blueprint $table): void {
            $table->longText('ai_summary')->nullable()->change();
            $table->longText('provider_payload')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('telephony_calls', function (Blueprint $table): void {
            $table->json('ai_summary')->nullable()->change();
            $table->json('provider_payload')->nullable()->change();
        });
    }
};
