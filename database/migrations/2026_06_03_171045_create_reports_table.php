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
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('target_id');
            $table->string('target_type', 20)->comment('post, comment, user');
            $table->string('reason', 100)->comment('spam, harassment, misinformation, inappropriate, etc');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending')->index('reports_status_idx')->comment('pending, reviewed, resolved, dismissed');
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete()->comment('moderator user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            
            $table->index(['target_id', 'target_type'], 'reports_target_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
