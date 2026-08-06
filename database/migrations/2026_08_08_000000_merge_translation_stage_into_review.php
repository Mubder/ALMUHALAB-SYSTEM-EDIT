<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Move any service requests sitting at stage 4 back to stage 2
        DB::table('service_requests')->where('current_stage', 4)->update(['current_stage' => 2]);

        if (Schema::hasTable('service_request_stage_history')) {
            DB::table('service_request_stage_history')->where('from_stage', 4)->update(['from_stage' => 2]);
            DB::table('service_request_stage_history')->where('to_stage', 4)->update(['to_stage' => 2]);
        }

        // 2. Decrement stage numbers 5, 6, 7, 8 down to 4, 5, 6, 7
        DB::table('service_requests')->where('current_stage', 5)->update(['current_stage' => 4]);
        DB::table('service_requests')->where('current_stage', 6)->update(['current_stage' => 5]);
        DB::table('service_requests')->where('current_stage', 7)->update(['current_stage' => 6]);
        DB::table('service_requests')->where('current_stage', 8)->update(['current_stage' => 7]);

        if (Schema::hasTable('service_request_stage_history')) {
            DB::table('service_request_stage_history')->where('from_stage', 5)->update(['from_stage' => 4]);
            DB::table('service_request_stage_history')->where('from_stage', 6)->update(['from_stage' => 5]);
            DB::table('service_request_stage_history')->where('from_stage', 7)->update(['from_stage' => 6]);
            DB::table('service_request_stage_history')->where('from_stage', 8)->update(['from_stage' => 7]);

            DB::table('service_request_stage_history')->where('to_stage', 5)->update(['to_stage' => 4]);
            DB::table('service_request_stage_history')->where('to_stage', 6)->update(['to_stage' => 5]);
            DB::table('service_request_stage_history')->where('to_stage', 7)->update(['to_stage' => 6]);
            DB::table('service_request_stage_history')->where('to_stage', 8)->update(['to_stage' => 7]);
        }
    }

    public function down(): void
    {
        // No-op
    }
};
