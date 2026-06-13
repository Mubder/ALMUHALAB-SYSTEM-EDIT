<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make column nullable first
        $driver = config('database.default');
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE request_services MODIFY created_by BIGINT UNSIGNED NULL');
        } else {
            DB::statement('ALTER TABLE request_services ALTER COLUMN created_by DROP NOT NULL');
        }

        // Add foreign key with nullOnDelete
        Schema::table('request_services', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_services', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        $driver = config('database.default');
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE request_services MODIFY created_by BIGINT UNSIGNED NOT NULL');
        } else {
            DB::statement('ALTER TABLE request_services ALTER COLUMN created_by SET NOT NULL');
        }
    }
};
