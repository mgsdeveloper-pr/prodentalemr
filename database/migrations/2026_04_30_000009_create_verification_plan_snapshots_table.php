<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_plan_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_work_item_id')->constrained()->cascadeOnDelete();
            $table->string('plan_priority')->default('primary');
            $table->string('payer_name')->nullable();
            $table->string('member_id')->nullable();
            $table->string('group_number')->nullable();
            $table->string('subscriber_name')->nullable();
            $table->date('subscriber_dob')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_plan_snapshots');
    }
};
