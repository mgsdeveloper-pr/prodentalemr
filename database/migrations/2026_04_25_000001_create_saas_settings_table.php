<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('platform_name')->default('ProDental EMR');
            $table->string('company_name')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('default_country')->default('USA');
            $table->string('default_timezone')->default('America/New_York');
            $table->string('default_currency')->default('USD');
            $table->boolean('maintenance_mode')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_settings');
    }
};
