<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dsos', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('account_code')->nullable()->unique();
            $table->string('primary_contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->default('USA');
            $table->string('lifecycle_status')->default('active');
            $table->string('billing_mode')->default('centralized');
            $table->string('service_status')->default('active');
            $table->boolean('status')->default(true);
            $table->foreignId('account_manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->foreignId('dso_id')->nullable()->after('id')->constrained('dsos')->nullOnDelete();
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('dso_id')->nullable()->after('id')->constrained('dsos')->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->after('organization_id')->constrained('clinics')->nullOnDelete();
            $table->string('subscription_scope')->default('organization')->after('clinic_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('dso_id')->nullable()->after('id')->constrained('dsos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dso_id');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dso_id');
            $table->dropConstrainedForeignId('clinic_id');
            $table->dropColumn('subscription_scope');
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dso_id');
        });

        Schema::dropIfExists('dsos');
    }
};
