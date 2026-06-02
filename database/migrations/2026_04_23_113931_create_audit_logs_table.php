<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();

            $table->string('module');
            $table->string('action');
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('device_info')->nullable();

            $table->timestamps();
        });

        // Schema::create('audit_logs', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
