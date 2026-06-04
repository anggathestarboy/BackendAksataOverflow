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
        Schema::create('votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('target_id');
            $table->string('target_type', 20)->comment('post, comment');
            $table->string('vote_type', 10)->comment('upvote, downvote');
            $table->timestamp('created_at')->useCurrent();
            
            $table->unique(['user_id', 'target_id', 'target_type'], 'votes_unique');
            $table->index(['target_id', 'target_type'], 'votes_target_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
