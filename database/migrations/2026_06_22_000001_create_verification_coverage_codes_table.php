<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_coverage_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_work_item_id')->constrained()->cascadeOnDelete();
            $table->string('code_system')->default('ada');
            $table->string('category')->nullable()->index();
            $table->string('code', 30)->nullable()->index();
            $table->string('description')->nullable();
            $table->string('coverage_status')->nullable();
            $table->decimal('coverage_percent', 5, 2)->nullable();
            $table->string('frequency')->nullable();
            $table->string('age_limit')->nullable();
            $table->string('waiting_period')->nullable();
            $table->string('service_history')->nullable();
            $table->string('pre_auth_required')->nullable();
            $table->text('pre_auth_details')->nullable();
            $table->string('downgrade_applies')->nullable();
            $table->string('downgrade_to')->nullable();
            $table->text('payment_guideline')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['billing_work_item_id', 'category', 'sort_order'], 'verification_coverage_codes_work_item_category_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_coverage_codes');
    }
};
