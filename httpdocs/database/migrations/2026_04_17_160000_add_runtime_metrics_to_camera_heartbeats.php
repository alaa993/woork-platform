<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camera_heartbeats', function (Blueprint $table) {
            $table->string('analyzer')->nullable()->after('stream_status');
            $table->decimal('fps', 8, 2)->nullable()->after('last_frame_at');
            $table->json('observations')->nullable()->after('fps');
        });
    }

    public function down(): void
    {
        Schema::table('camera_heartbeats', function (Blueprint $table) {
            $table->dropColumn(['analyzer', 'fps', 'observations']);
        });
    }
};
