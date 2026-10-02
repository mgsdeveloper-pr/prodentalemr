<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table) {
            $table->string('answer_layout', 40)->nullable();
            $table->string('response_category', 80)->nullable();
        });
        Schema::table('verification_template_versions', function (Blueprint $table) {
            $table->boolean('uses_section_layout')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('verification_form_questions', fn (Blueprint $table) => $table->dropColumn(['answer_layout', 'response_category']));
        Schema::table('verification_template_versions', fn (Blueprint $table) => $table->dropColumn('uses_section_layout'));
    }
};
