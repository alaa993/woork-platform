<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_releases', function (Blueprint $table) {
            $table->string('artifact_name')->nullable()->after('artifact_path');
            $table->string('checksum_sha256', 64)->nullable()->after('artifact_name');
            $table->unsignedBigInteger('artifact_size')->nullable()->after('checksum_sha256');
        });
    }

    public function down(): void
    {
        Schema::table('agent_releases', function (Blueprint $table) {
            $table->dropColumn(['artifact_name', 'checksum_sha256', 'artifact_size']);
        });
    }
};
