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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('title', 300);
            $table->text('body');
            $table->string('status', 20)->default('open')->comment('open, closed, deleted')->index('posts_status_idx');
            $table->integer('view_count')->default(0);
            $table->integer('vote_score')->default(0);
            $table->boolean('is_answered')->default(false);
            $table->uuid('accepted_answer_id')->nullable()->comment('FK to comments.id');
            $table->timestamps();
            
            $table->index('created_at', 'posts_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
