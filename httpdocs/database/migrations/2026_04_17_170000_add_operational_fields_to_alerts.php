<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->foreignId('camera_id')->nullable()->after('organization_id')->constrained('cameras')->nullOnDelete();
            $table->foreignId('agent_device_id')->nullable()->after('camera_id')->constrained('agent_devices')->nullOnDelete();
            $table->string('source')->default('analytics')->after('channel');
            $table->timestamp('resolved_at')->nullable()->after('is_active');

            $table->index(['organization_id', 'source', 'kind'], 'alerts_org_source_kind_idx');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex('alerts_org_source_kind_idx');
            $table->dropConstrainedForeignId('camera_id');
            $table->dropConstrainedForeignId('agent_device_id');
            $table->dropColumn(['source', 'resolved_at']);
        });
    }
};
