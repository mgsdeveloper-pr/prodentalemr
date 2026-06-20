<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->json('row_results')->nullable();
            $table->json('failed_row_results')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'created_at'], 'appointment_import_batches_clinic_created_idx');
            $table->index(['user_id', 'created_at'], 'appointment_import_batches_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_import_batches');
    }
};
