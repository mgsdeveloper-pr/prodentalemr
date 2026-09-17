<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40)->default('zuub');
            $table->string('environment', 20);
            $table->text('api_key')->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_check_status', 40)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['provider', 'environment']);
        });

        Schema::create('eligibility_connection_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('eligibility_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 50);
            $table->string('status', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_connection_events');
        Schema::dropIfExists('eligibility_connections');
    }
};
