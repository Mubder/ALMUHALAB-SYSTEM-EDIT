<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Shift stage numbers 4, 5, 6, 7 up by +1 to insert the new Stage 4 (Translation & Overseas Review).
     */
    public function up(): void
    {
        // 1. Shift service_requests current_stage
        if (Schema::hasTable('service_requests')) {
            DB::statement('UPDATE service_requests SET current_stage = current_stage + 1 WHERE current_stage >= 4');
        }

        // 2. Shift service_request_stage_history
        if (Schema::hasTable('service_request_stage_history')) {
            DB::statement('UPDATE service_request_stage_history SET from_stage = from_stage + 1 WHERE from_stage >= 4');
            DB::statement('UPDATE service_request_stage_history SET to_stage = to_stage + 1 WHERE to_stage >= 4');
        }

        // 3. Shift stage_attachments
        if (Schema::hasTable('stage_attachments')) {
            DB::statement('UPDATE stage_attachments SET stage = stage + 1 WHERE stage >= 4');
        }

        // 4. Shift stage_comments
        if (Schema::hasTable('stage_comments') && Schema::hasColumn('stage_comments', 'stage_number')) {
            DB::statement('UPDATE stage_comments SET stage_number = stage_number + 1 WHERE stage_number >= 4');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('service_requests')) {
            DB::statement('UPDATE service_requests SET current_stage = current_stage - 1 WHERE current_stage > 4');
        }
        if (Schema::hasTable('service_request_stage_history')) {
            DB::statement('UPDATE service_request_stage_history SET from_stage = from_stage - 1 WHERE from_stage > 4');
            DB::statement('UPDATE service_request_stage_history SET to_stage = to_stage - 1 WHERE to_stage > 4');
        }
        if (Schema::hasTable('stage_attachments')) {
            DB::statement('UPDATE stage_attachments SET stage = stage - 1 WHERE stage > 4');
        }
        if (Schema::hasTable('stage_comments') && Schema::hasColumn('stage_comments', 'stage_number')) {
            DB::statement('UPDATE stage_comments SET stage_number = stage_number - 1 WHERE stage_number > 4');
        }
    }
};
