<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('portal_credential_password_histories');

        Schema::create('portal_credential_password_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portal_credential_id');
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('clinic_id')->nullable();
            $table->foreignId('changed_by_user_id')->nullable();
            $table->string('changed_by_name')->nullable();
            $table->text('password_snapshot');
            $table->timestamps();

            $table->foreign('portal_credential_id', 'pc_pwd_hist_credential_fk')
                ->references('id')
                ->on('portal_credentials')
                ->cascadeOnDelete();
            $table->foreign('organization_id', 'pc_pwd_hist_org_fk')
                ->references('id')
                ->on('organizations')
                ->nullOnDelete();
            $table->foreign('clinic_id', 'pc_pwd_hist_clinic_fk')
                ->references('id')
                ->on('clinics')
                ->nullOnDelete();
            $table->foreign('changed_by_user_id', 'pc_pwd_hist_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_credential_password_histories');
    }
};
