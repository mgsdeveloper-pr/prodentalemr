<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table): void {
            $table->string('semantic_key', 100)->nullable()->index();
            $table->string('information_scope', 30)->default('unclassified');
            $table->string('reuse_policy', 30)->default('fresh_verification');
        });

        // Copies retain their original lineage identity without rewriting answers or snapshots.
        $parents = DB::table('verification_form_questions')->pluck('source_question_id', 'id')->all();
        DB::table('verification_form_questions')->select('id')->orderBy('id')->chunkById(500, function ($rows) use ($parents): void {
            foreach ($rows as $row) {
                $root = $row->id;
                $visited = [];
                while (! empty($parents[$root]) && array_key_exists($parents[$root], $parents) && ! isset($visited[$root])) {
                    $visited[$root] = true;
                    $root = $parents[$root];
                }
                DB::table('verification_form_questions')->where('id', $row->id)
                    ->update(['semantic_key' => 'question:'.$root]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table): void {
            $table->dropIndex(['semantic_key']);
            $table->dropColumn(['semantic_key', 'information_scope', 'reuse_policy']);
        });
    }
};
