<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('verification_template_versions')) {
            return;
        }

        Schema::create('verification_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('template_key')->default('template_3')->index();
            $table->string('scope', 20)->default('master')->index();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_version_id')->nullable()->constrained('verification_template_versions')->nullOnDelete();
            $table->foreignId('source_version_id')->nullable()->constrained('verification_template_versions')->nullOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->string('name')->default('Master Template');
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['scope', 'template_key', 'status', 'is_active'], 'vtv_scope_template_status_active_idx');
            $table->index(['clinic_id', 'template_key', 'status', 'is_active'], 'vtv_clinic_template_status_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_template_versions');
    }
};
