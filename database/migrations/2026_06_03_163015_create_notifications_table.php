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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete()->comment('recipient');
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete()->comment('who triggered the notification');
            $table->string('type', 50)->comment('reply, like, upvote, follow, answer_accepted, mention');
            $table->uuid('reference_id')->nullable()->comment('related post_id or comment_id');
            $table->string('reference_type', 20)->nullable()->comment('post, comment');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['user_id', 'is_read'], 'notifications_user_unread_idx');
            $table->index('created_at', 'notifications_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
