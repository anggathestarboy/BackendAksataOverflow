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
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('comments')->cascadeOnDelete()->comment('null = top-level comment, filled = reply');
            $table->text('body');
            $table->integer('vote_score')->default(0);
            $table->boolean('is_accepted')->default(false);
            $table->timestamps();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('accepted_answer_id')->references('id')->on('comments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['accepted_answer_id']);
        });
        
        Schema::dropIfExists('comments');
    }
};
