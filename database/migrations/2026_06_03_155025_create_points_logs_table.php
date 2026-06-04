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
        Schema::create('points_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('points')->comment('positive = earn, negative = deduct');
            $table->string('action_type', 50)->index('points_log_action_type_idx')->comment('post_upvoted, answer_accepted, comment_upvoted, post_created, etc');
            $table->uuid('reference_id')->nullable()->comment('related post_id or comment_id');
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_logs');
    }
};
