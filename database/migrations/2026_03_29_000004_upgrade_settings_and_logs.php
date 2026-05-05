<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade Activity Logs
        Schema::table('activities', function (Blueprint $table) {
            if (! Schema::hasColumn('activities', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('activities', 'severity')) {
                $table->enum('severity', ['info', 'warning', 'critical', 'danger'])->default('info')->after('description');
            }
            if (! Schema::hasColumn('activities', 'before_values')) {
                $table->json('before_values')->nullable()->after('properties');
            }
            if (! Schema::hasColumn('activities', 'after_values')) {
                $table->json('after_values')->nullable()->after('before_values');
            }
        });

        // 2. Multi-tenant Settings System
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('group', 50)->index(); // business, localization, sales, inventory, security, notifications, integrations
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, boolean, integer, json
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // visible to frontend?
            $table->timestamps();

            $table->unique(['tenant_id', 'group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['tenant_id', 'severity', 'before_values', 'after_values']);
        });
    }
};
