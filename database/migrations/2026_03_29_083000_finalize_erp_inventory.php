<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure Units table is correct
        if (!Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('short_name')->nullable();
                $table->foreignId('base_unit_id')->nullable()->constrained('units')->onDelete('set null');
                $table->string('operator')->default('*');
                $table->decimal('operator_value', 10, 4)->default(1);
                $table->boolean('is_active')->default(true);
                $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 2. Modify Products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'has_variants')) {
                $table->boolean('has_variants')->default(false)->after('is_ecommerce');
            }
            if (!Schema::hasColumn('products', 'valuation_method')) {
                $table->enum('valuation_method', ['fifo', 'average', 'standard'])->default('average')->after('has_variants');
            }
            if (!Schema::hasColumn('products', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('weight')->constrained('units')->onDelete('set null');
            }
        });

        // 3. Product Variants
        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('sku')->unique();
                $table->string('barcode')->nullable();
                $table->decimal('purchase_price', 12, 2)->nullable();
                $table->decimal('selling_price', 12, 2)->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Warehouse Stock
        if (!Schema::hasTable('product_warehouse_stocks')) {
            Schema::create('product_warehouse_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
                $table->integer('quantity')->default(0);
                $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['product_id', 'product_variant_id', 'warehouse_id'], 'stock_unique_idx');
            });
        }

        // 5. Purchase Orders
        if (!Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
                $table->foreignId('warehouse_id')->constrained()->onDelete('restrict');
                $table->string('reference_no')->unique();
                $table->date('order_date');
                $table->date('expected_date')->nullable();
                $table->enum('status', ['draft', 'ordered', 'partial', 'received', 'cancelled'])->default('draft');
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });

            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('restrict');
                $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('restrict');
                $table->integer('quantity');
                $table->integer('received_quantity')->default(0);
                $table->decimal('unit_cost', 12, 2);
                $table->decimal('total', 15, 2);
                $table->timestamps();
            });
        }

        // 6. Stock Transfers
        if (!Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('reference_no')->unique();
                $table->foreignId('from_warehouse_id')->constrained('warehouses')->onDelete('restrict');
                $table->foreignId('to_warehouse_id')->constrained('warehouses')->onDelete('restrict');
                $table->enum('status', ['draft', 'in_transit', 'completed', 'cancelled'])->default('draft');
                $table->date('transfer_date');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
            });

            Schema::create('stock_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_transfer_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('restrict');
                $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('restrict');
                $table->integer('quantity');
                $table->timestamps();
            });
        }

        // 7. Stock Movements
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'product_variant_id')) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('stock_movements', 'purchase_order_id')) {
                $table->foreignId('purchase_order_id')->nullable()->after('reference')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('stock_movements', 'stock_transfer_id')) {
                $table->foreignId('stock_transfer_id')->nullable()->after('purchase_order_id')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('stock_movements', 'before_quantity')) {
                $table->integer('before_quantity')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('stock_movements', 'after_quantity')) {
                $table->integer('after_quantity')->default(0)->after('before_quantity');
            }
            if (!Schema::hasColumn('stock_movements', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->default(0)->after('after_quantity');
            }
            if (!Schema::hasColumn('stock_movements', 'total_value')) {
                $table->decimal('total_value', 15, 2)->default(0)->after('unit_cost');
            }
        });
    }

    public function down(): void
    {
        // Not implementing down for this consolidated fix as it's meant to correct state
    }
};
