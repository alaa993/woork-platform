<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_releases', function (Blueprint $table) {
            $table->dropUnique('agent_releases_version_unique');
            $table->unique(['version', 'platform'], 'agent_releases_version_platform_unique');
        });
    }

    public function down(): void
    {
        Schema::table('agent_releases', function (Blueprint $table) {
            $table->dropUnique('agent_releases_version_platform_unique');
            $table->unique('version');
        });
    }
};
