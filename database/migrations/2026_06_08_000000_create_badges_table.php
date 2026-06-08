<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon_url', 500)->nullable();

            $table->string('tier', 20)
                ->comment('bronze, silver, gold, platinum');

            $table->string('condition_type', 50)
                ->comment('reputation_points, posts_count, answers_accepted, comments_count');

            $table->integer('condition_value');

            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
