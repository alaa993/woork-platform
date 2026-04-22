<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_releases', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('channel')->default('stable');
            $table->string('platform')->default('windows-x64');
            $table->string('artifact_path');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'platform', 'is_active'], 'agent_releases_channel_platform_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_releases');
    }
};
