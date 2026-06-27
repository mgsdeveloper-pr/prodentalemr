<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table): void {
            $table->string('frequency_response_mode')->nullable()->after('select_options');
            $table->json('frequency_response_fields')->nullable()->after('frequency_response_mode');
        });
    }

    public function down(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table): void {
            $table->dropColumn([
                'frequency_response_mode',
                'frequency_response_fields',
            ]);
        });
    }
};
