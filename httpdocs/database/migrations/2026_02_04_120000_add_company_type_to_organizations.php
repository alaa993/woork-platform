<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'company_type')) {
                $table->string('company_type')->default('company')->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'company_type')) {
                $table->dropColumn('company_type');
            }
        });
    }
};
