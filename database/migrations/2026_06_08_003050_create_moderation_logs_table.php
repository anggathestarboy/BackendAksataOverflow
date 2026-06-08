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
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('moderator_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('target_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('action_type', 50)
                ->comment('ban, unban, warning, delete_post, delete_comment, close_post');

            $table->string('reason', 255);

            $table->text('notes')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('moderator_id', 'moderation_logs_moderator_id_idx');
            $table->index('target_user_id', 'moderation_logs_target_user_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
    }
};
