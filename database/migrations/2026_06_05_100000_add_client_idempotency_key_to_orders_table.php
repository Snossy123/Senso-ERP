<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'orders_tenant_client_idem_unique';

    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'client_idempotency_key')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('client_idempotency_key', 191)->nullable()->after('order_number');
            });
        }

        if (
            Schema::hasColumn('orders', 'client_idempotency_key')
            && Schema::hasColumn('orders', 'tenant_id')
            && ! Schema::hasIndex('orders', self::INDEX_NAME)
        ) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique(['tenant_id', 'client_idempotency_key'], self::INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (Schema::hasIndex('orders', self::INDEX_NAME)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_NAME);
            });
        }

        if (Schema::hasColumn('orders', 'client_idempotency_key')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('client_idempotency_key');
            });
        }
    }
};
