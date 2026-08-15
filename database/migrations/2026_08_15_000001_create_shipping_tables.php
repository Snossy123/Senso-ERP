<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('driver', 32)->default('qp');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('base_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->decimal('default_weight', 8, 3)->default(1);
            $table->boolean('auto_create_on_checkout')->default(false);
            $table->timestamp('last_history_synced_at')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('city');
            $table->string('city_label')->nullable();
            $table->decimal('fee', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'city']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('shippable_type');
            $table->unsignedBigInteger('shippable_id');
            $table->string('carrier', 32)->default('qp');
            $table->string('carrier_serial')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('status')->nullable();
            $table->text('status_note')->nullable();
            $table->decimal('total_fees', 12, 2)->default(0);
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('full_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'shippable_type', 'shippable_id'], 'shipments_shippable_unique');
            $table->index(['tenant_id', 'carrier_serial']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_integrations');
    }
};
