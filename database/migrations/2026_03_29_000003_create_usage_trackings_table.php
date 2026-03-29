<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('resource', 50); // users, products, orders
            $table->integer('current_usage')->default(0);
            $table->integer('capacity_limit')->default(0); // Renamed from 'limit'
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'resource']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_trackings');
    }
};
