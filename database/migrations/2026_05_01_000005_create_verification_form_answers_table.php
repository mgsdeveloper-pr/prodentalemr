<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_form_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_work_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verification_form_question_id')->constrained()->cascadeOnDelete();
            $table->text('answer_value')->nullable();
            $table->timestamps();

            $table->unique(['billing_work_item_id', 'verification_form_question_id'], 'verification_form_answers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_form_answers');
    }
};
