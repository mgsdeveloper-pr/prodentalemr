<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_credential_security_questions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('portal_credential_id');
            $table->text('question');
            $table->text('answer');
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('portal_credential_id', 'pc_security_questions_credential_fk')
                ->references('id')
                ->on('portal_credentials')
                ->cascadeOnDelete();
            $table->index(['portal_credential_id', 'sort_order'], 'pc_security_questions_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_credential_security_questions');
    }
};
