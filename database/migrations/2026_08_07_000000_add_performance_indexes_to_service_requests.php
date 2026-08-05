<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add composite indexes to service_requests table for fast query performance.
     */
    public function up(): void
    {
        if (Schema::hasTable('service_requests')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->index(['current_stage', 'stage_status'], 'sr_stage_status_idx');
                $table->index(['assigned_to', 'current_stage'], 'sr_assigned_stage_idx');
                $table->index(['user_id', 'created_at'], 'sr_user_created_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('service_requests')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->dropIndex('sr_stage_status_idx');
                $table->dropIndex('sr_assigned_stage_idx');
                $table->dropIndex('sr_user_created_idx');
            });
        }
    }
};
