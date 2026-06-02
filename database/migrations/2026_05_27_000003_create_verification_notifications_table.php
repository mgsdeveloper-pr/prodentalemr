<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('billing_work_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('panel', 32);
            $table->string('activity_type', 120)->nullable();
            $table->string('level', 32)->default('info');
            $table->string('title');
            $table->text('message');
            $table->text('target_url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'panel', 'read_at'], 'verif_notif_user_panel_read_idx');
            $table->index(['clinic_id', 'panel', 'created_at'], 'verif_notif_clinic_panel_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_notifications');
    }
};
