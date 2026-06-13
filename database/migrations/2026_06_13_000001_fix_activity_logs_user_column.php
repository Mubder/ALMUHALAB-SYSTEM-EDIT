<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add temporary integer column
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id_temp')->nullable()->after('id');
        });

        // Copy data (cast string IDs to integers)
        DB::table('activity_logs')
            ->whereNotNull('user')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('activity_logs')
                        ->where('id', $row->id)
                        ->update(['user_id_temp' => (int) $row->user]);
                }
            });

        // Drop old string column, rename temp, add FK
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('user');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->renameColumn('user_id_temp', 'user');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreign('user')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user']);
            $table->dropColumn('user');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('user')->nullable();
        });
    }
};
