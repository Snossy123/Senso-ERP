<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users', 'products', 'categories', 'suppliers', 'warehouses',
            'customers', 'sales', 'sale_items', 'orders', 'order_items',
            'activities', 'stock_movements'
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                    $tableBlueprint->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
                    $tableBlueprint->index('tenant_id');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'users', 'products', 'categories', 'suppliers', 'warehouses',
            'customers', 'sales', 'sale_items', 'orders', 'order_items',
            'activities', 'stock_movements'
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropForeign(['tenant_id']);
                    $tableBlueprint->dropColumn('tenant_id');
                });
            }
        }
    }
};
