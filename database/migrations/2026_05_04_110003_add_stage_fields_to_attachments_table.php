<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->unsignedTinyInteger('stage_number')->nullable()->after('file_size');
            $table->enum('visibility', ['all', 'employee', 'admin'])->default('all')->after('stage_number');
            $table->foreignId('uploaded_by')->nullable()->after('visibility')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn(['stage_number', 'visibility', 'uploaded_by']);
        });
    }
};
