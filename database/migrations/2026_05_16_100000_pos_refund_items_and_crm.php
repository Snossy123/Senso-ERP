<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'qty_refunded')) {
                $table->unsignedInteger('qty_refunded')->default(0)->after('quantity');
            }
        });

        Schema::create('sale_refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_refund_id')->constrained('sale_refunds')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('line_amount', 12, 2);
            $table->unsignedInteger('restocked_qty')->default(0);
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'company')) {
                $table->string('company')->nullable()->after('name');
            }
            if (! Schema::hasColumn('customers', 'source')) {
                $table->string('source')->nullable()->after('company');
            }
            if (! Schema::hasColumn('customers', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')->nullable()->after('source')->constrained('users')->nullOnDelete();
            }
        });

        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->default('#6366f1');
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('customer_tag', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_tag_id')->constrained('customer_tags')->cascadeOnDelete();
            $table->primary(['customer_id', 'customer_tag_id']);
        });

        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
        Schema::dropIfExists('customer_tag');
        Schema::dropIfExists('customer_tags');
        Schema::dropIfExists('sale_refund_items');

        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'qty_refunded')) {
                $table->dropColumn('qty_refunded');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $cols = ['company', 'source', 'assigned_user_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    if ($col === 'assigned_user_id') {
                        $table->dropForeign(['assigned_user_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
