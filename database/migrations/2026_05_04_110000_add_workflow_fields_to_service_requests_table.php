<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_stage')->default(1)->after('status');
            $table->string('stage_status', 60)->default('New')->after('current_stage');
            $table->foreignId('assigned_to')->nullable()->after('user_id')
                  ->constrained('users')->nullOnDelete();
            $table->boolean('is_rejected')->default(false)->after('stage_status');
            $table->timestamp('stage_entered_at')->nullable()->after('is_rejected');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['current_stage', 'stage_status', 'assigned_to', 'is_rejected', 'stage_entered_at']);
        });
    }
};
