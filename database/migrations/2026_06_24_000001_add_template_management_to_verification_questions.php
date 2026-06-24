<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->string('verification_default_form_template')
                ->default('template_2')
                ->after('verification_pdf_output_mode');
        });

        Schema::table('verification_form_questions', function (Blueprint $table): void {
            $table->string('template_key')->default('template_1')->after('clinic_id');
            $table->text('select_options')->nullable()->after('placeholder');
            $table->boolean('has_note')->default(false)->after('select_options');
            $table->string('note_label')->nullable()->after('has_note');
            $table->string('note_placeholder')->nullable()->after('note_label');

            $table->index(
                ['clinic_id', 'template_key', 'section_key', 'is_active'],
                'verification_questions_template_section_index'
            );
        });

        Schema::table('verification_form_answers', function (Blueprint $table): void {
            $table->text('note_value')->nullable()->after('answer_value');
        });
    }

    public function down(): void
    {
        Schema::table('verification_form_answers', function (Blueprint $table): void {
            $table->dropColumn('note_value');
        });

        Schema::table('verification_form_questions', function (Blueprint $table): void {
            $table->dropIndex('verification_questions_template_section_index');
            $table->dropColumn([
                'template_key',
                'select_options',
                'has_note',
                'note_label',
                'note_placeholder',
            ]);
        });

        Schema::table('clinics', function (Blueprint $table): void {
            $table->dropColumn('verification_default_form_template');
        });
    }
};
