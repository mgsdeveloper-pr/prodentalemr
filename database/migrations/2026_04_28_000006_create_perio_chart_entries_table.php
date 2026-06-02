<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perio_chart_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perio_chart_id')->constrained()->cascadeOnDelete();
            $table->string('tooth_number', 10);

            foreach (['mb', 'b', 'db', 'ml', 'l', 'dl'] as $site) {
                $table->unsignedTinyInteger("probing_depth_{$site}")->nullable();
            }

            foreach (['mb', 'b', 'db', 'ml', 'l', 'dl'] as $site) {
                $table->tinyInteger("recession_{$site}")->nullable();
            }

            foreach (['mb', 'b', 'db', 'ml', 'l', 'dl'] as $site) {
                $table->boolean("bleeding_{$site}")->default(false);
            }

            $table->string('mobility')->nullable();
            $table->string('furcation')->nullable();
            $table->boolean('suppuration')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perio_chart_entries');
    }
};
