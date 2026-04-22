<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_device_id')->constrained('agent_devices')->cascadeOnDelete();
            $table->string('status')->default('online');
            $table->json('capabilities')->nullable();
            $table->dateTime('checked_at');
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        Schema::create('camera_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_device_id')->constrained('agent_devices')->cascadeOnDelete();
            $table->foreignId('camera_id')->constrained('cameras')->cascadeOnDelete();
            $table->string('stream_status')->default('pending');
            $table->text('health_message')->nullable();
            $table->timestamp('last_frame_at')->nullable();
            $table->dateTime('checked_at');
            $table->timestamps();

            $table->index(['camera_id', 'checked_at'], 'camera_heartbeats_camera_checked_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_heartbeats');
        Schema::dropIfExists('agent_heartbeats');
    }
};
