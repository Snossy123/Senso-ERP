<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade Sales table
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'status')) {
                $table->enum('status', ['completed', 'held', 'voided', 'refunded'])->default('completed')->after('payment_status');
            }
            if (!Schema::hasColumn('sales', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('sales', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('sales', 'amount_tendered')) {
                $table->decimal('amount_tendered', 12, 2)->default(0)->after('payment_method');
            }
            if (!Schema::hasColumn('sales', 'change_due')) {
                $table->decimal('change_due', 12, 2)->default(0)->after('amount_tendered');
            }
            if (!Schema::hasColumn('sales', 'void_reason')) {
                $table->string('void_reason')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('sales', 'voided_by')) {
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales', 'voided_at')) {
                $table->timestamp('voided_at')->nullable();
            }
            if (!Schema::hasColumn('sales', 'shift_id')) {
                $table->unsignedBigInteger('shift_id')->nullable()->after('user_id');
            }
        });

        // 2. Upgrade sale_items table with item-level discounts
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'discount_pct')) {
                $table->decimal('discount_pct', 5, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('sale_items', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_pct');
            }
        });

        // 3. Shift Management
        Schema::create('pos_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('terminal_id')->nullable();
            $table->decimal('opening_float', 12, 2)->default(0);
            $table->decimal('closing_float', 12, 2)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // 4. Held Orders (Park & Resume)
        Schema::create('held_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->json('cart_data');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();

            $table->index('tenant_id');
        });

        // 5. Refunds Table
        Schema::create('sale_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('refund_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('reason')->nullable();
            $table->enum('method', ['original', 'cash', 'credit'])->default('original');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_refunds');
        Schema::dropIfExists('held_orders');
        Schema::dropIfExists('pos_shifts');

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['discount_pct', 'discount_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'customer_name', 'customer_email',
                'amount_tendered', 'change_due', 'void_reason',
                'voided_by', 'voided_at', 'shift_id',
            ]);
        });
    }
};
