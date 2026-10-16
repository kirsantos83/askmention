<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            $table->string('source', 16)->default('demo');
            $table->json('engine_errors')->nullable();
            $table->unsignedInteger('requests_made')->default(0);
        });

        Schema::table('run_answers', function (Blueprint $table) {
            $table->string('source', 16)->default('demo');
            $table->string('model_provider')->nullable();
            $table->string('model_id')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->decimal('cost', 14, 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('run_answers', function (Blueprint $table) {
            $table->dropColumn(['source', 'model_provider', 'model_id', 'prompt_tokens', 'completion_tokens', 'total_tokens', 'cost']);
        });

        Schema::table('runs', function (Blueprint $table) {
            $table->dropColumn(['source', 'engine_errors', 'requests_made']);
        });
    }
};
