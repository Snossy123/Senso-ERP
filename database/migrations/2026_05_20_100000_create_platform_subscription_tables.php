<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('limit_schema')->nullable();
            $table->string('feature_key')->nullable();
            $table->timestamps();
        });

        Schema::create('plan_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('module_key');
            $table->boolean('enabled')->default(true);
            $table->json('limits')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'module_key']);
        });

        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('billing_cycle', 20)->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_addon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_addon_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['plan_id', 'platform_addon_id']);
        });

        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver', 30);
            $table->text('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('price');
            $table->timestamp('trial_ends_at')->nullable()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['currency', 'trial_ends_at']);
        });

        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('plan_addon');
        Schema::dropIfExists('platform_addons');
        Schema::dropIfExists('platform_invoices');
        Schema::dropIfExists('plan_modules');
        Schema::dropIfExists('platform_modules');
    }
};
