<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_credentials', function (Blueprint $table): void {
            $table->id();
            $table->string('portal_name');
            $table->string('portal_category')->default('insurance');
            $table->string('login_url')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->string('account_reference')->nullable();
            $table->string('support_contact')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('mfa_required')->default(false);
            $table->string('mfa_method')->default('none');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('clinic_portal_credential_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('portal_credential_id')->constrained()->cascadeOnDelete();
            $table->string('portal_name')->nullable();
            $table->string('portal_category')->nullable();
            $table->string('login_url')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->string('account_reference')->nullable();
            $table->string('support_contact')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('mfa_required')->nullable();
            $table->string('mfa_method')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();

            $table->unique(['clinic_id', 'portal_credential_id'], 'clinic_portal_credential_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_portal_credential_overrides');
        Schema::dropIfExists('portal_credentials');
    }
};
