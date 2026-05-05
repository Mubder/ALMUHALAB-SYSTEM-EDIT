<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_stage_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('from_stage')->nullable();
            $table->unsignedTinyInteger('to_stage');
            $table->string('stage_key', 50);
            $table->string('status', 60);
            $table->string('action', 30); // entered, advanced, returned, status_changed, rejected, force_transitioned
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_stage_history');
    }
};
