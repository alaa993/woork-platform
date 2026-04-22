<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->unsignedInteger('cameras_limit')->default(3);
            $t->unsignedInteger('employees_limit')->default(15);
            $t->decimal('price_monthly', 8, 2)->default(0);
            $t->decimal('price_yearly', 8, 2)->default(0);
            $t->unsignedInteger('trial_days')->default(14);
            $t->json('features')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('country')->nullable();
            $t->string('language')->default('en');
            $t->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $t->unsignedBigInteger('owner_user_id')->nullable();
            $t->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $t->string('status')->default('trial');
            $t->timestamp('trial_ends_at')->nullable();
            $t->timestamp('current_period_end')->nullable();
            $t->string('stripe_id')->nullable();
            $t->string('payment_method')->nullable();
            $t->timestamps();
        });

// عدّل جدول users الافتراضي ليوافق Woork
Schema::table('users', function (Blueprint $t) {
    if (!Schema::hasColumn('users', 'organization_id')) {
        $t->foreignId('organization_id')
          ->nullable()
          ->constrained('organizations')
          ->nullOnDelete()
          ->after('id');
    }
    if (!Schema::hasColumn('users', 'phone')) {
        $t->string('phone')->nullable()->unique()->after('email');
    }
    if (!Schema::hasColumn('users', 'role')) {
        $t->string('role')->default('company_admin')->after('phone');
    }
    // password/remember_token موجودان عادةً في المايغريشن الافتراضي
});

        Schema::create('rooms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $t->string('name');
            $t->string('location')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('cameras', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $t->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $t->string('name');
            $t->string('rtsp_url')->nullable();
            $t->string('status')->nullable();
            $t->json('roi')->nullable();
            $t->timestamps();
        });

        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $t->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $t->string('name');
            $t->string('title')->nullable();
            $t->json('photos')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $t->string('type');
           $t->timestamp('started_at')->nullable();
			$t->timestamp('ended_at')->nullable();
            $t->integer('duration_seconds')->default(0);
            $t->timestamps();
        });

        Schema::create('daily_summaries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $t->date('date');
            $t->integer('work_minutes')->default(0);
            $t->integer('idle_minutes')->default(0);
            $t->integer('phone_minutes')->default(0);
            $t->integer('away_minutes')->default(0);
            $t->integer('phone_count')->default(0);
            $t->integer('away_count')->default(0);
            $t->integer('score')->default(0);
            $t->timestamps();
            $t->unique(['organization_id', 'employee_id', 'date']);
        });

        Schema::create('alerts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $t->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $t->string('kind');
            $t->string('level')->nullable();
            $t->string('channel')->nullable();
            $t->boolean('is_active')->default(true);
            $t->json('rules')->nullable();
            $t->text('message');
            $t->timestamps();
        });

        Schema::create('policies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $t->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $t->boolean('save_video')->default(false);
            $t->json('work_hours')->nullable();
            $t->json('breaks')->nullable();
            $t->json('visibility')->nullable();
            $t->json('thresholds')->nullable();
            $t->timestamps();
        });

        Schema::create('otp_codes', function (Blueprint $t) {
            $t->id();
            $t->string('phone');
            $t->string('code');
            $t->timestamp('expires_at');
            $t->timestamp('consumed_at')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('policies');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('daily_summaries');
        Schema::dropIfExists('events');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('cameras');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('users');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('plans');
		Schema::table('users', function (Blueprint $t) {
    if (Schema::hasColumn('users', 'organization_id')) {
        $t->dropConstrainedForeignId('organization_id');
    }
    if (Schema::hasColumn('users', 'role')) {
        $t->dropColumn('role');
    }
    if (Schema::hasColumn('users', 'phone')) {
        $t->dropColumn('phone');
    }
});
    }
};