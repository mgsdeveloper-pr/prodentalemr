<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_work_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('panel', 32)->nullable();
            $table->string('status', 64)->nullable();
            $table->string('outcome_status', 64)->nullable();
            $table->string('priority', 32)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['billing_work_item_id', 'created_at'], 'verification_form_submissions_item_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_form_submissions');
    }
};
