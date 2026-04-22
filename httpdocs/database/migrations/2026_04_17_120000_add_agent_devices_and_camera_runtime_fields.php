<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('device_uuid')->unique();
            $table->string('pairing_token')->unique();
            $table->string('api_token_hash')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->string('version')->nullable();
            $table->string('os')->nullable();
            $table->string('last_ip')->nullable();
            $table->json('capabilities')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('cameras', function (Blueprint $table) {
            $table->foreignId('agent_device_id')->nullable()->after('organization_id')->constrained('agent_devices')->nullOnDelete();
            $table->string('purpose')->default('general')->after('name');
            $table->string('analysis_mode')->default('desk_monitoring')->after('purpose');
            $table->string('stream_status')->nullable()->after('status');
            $table->text('health_message')->nullable()->after('stream_status');
            $table->boolean('is_enabled')->default(true)->after('health_message');
            $table->timestamp('last_seen_at')->nullable()->after('is_enabled');
            $table->timestamp('last_frame_at')->nullable()->after('last_seen_at');
            $table->json('analysis_config')->nullable()->after('roi');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('camera_id')->nullable()->after('organization_id')->constrained('cameras')->nullOnDelete();
            $table->foreignId('agent_device_id')->nullable()->after('camera_id')->constrained('agent_devices')->nullOnDelete();
            $table->string('track_id')->nullable()->after('type');
            $table->decimal('confidence', 5, 2)->nullable()->after('track_id');
            $table->json('meta')->nullable()->after('duration_seconds');
            $table->index(['organization_id', 'camera_id', 'started_at'], 'events_org_camera_started_idx');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_org_camera_started_idx');
            $table->dropConstrainedForeignId('camera_id');
            $table->dropConstrainedForeignId('agent_device_id');
            $table->dropColumn(['track_id', 'confidence', 'meta']);
        });

        Schema::table('cameras', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_device_id');
            $table->dropColumn([
                'purpose',
                'analysis_mode',
                'stream_status',
                'health_message',
                'is_enabled',
                'last_seen_at',
                'last_frame_at',
                'analysis_config',
            ]);
        });

        Schema::dropIfExists('agent_devices');
    }
};
