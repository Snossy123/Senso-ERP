<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add warehouse_id to pos_shifts
        if (! Schema::hasColumn('pos_shifts', 'warehouse_id')) {
            Schema::table('pos_shifts', function (Blueprint $table) {
                $table->foreignId('warehouse_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            });
        }

        // 2. Add product_variant_id to sale_items
        if (! Schema::hasColumn('sale_items', 'product_variant_id')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });
        Schema::table('pos_shifts', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
