<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_form_questions', function (Blueprint $table): void {
            $table->id();
            $table->string('prompt');
            $table->string('section_key');
            $table->string('form_type')->default('both');
            $table->string('input_type')->default('text');
            $table->string('help_text')->nullable();
            $table->string('placeholder')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_form_questions');
    }
};
