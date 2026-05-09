<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'sales_tenant_client_idem_unique';

    public function up(): void
    {
        if (! Schema::hasColumn('sales', 'client_idempotency_key')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('client_idempotency_key', 191)->nullable()->after('notes');
            });
        }

        if (
            Schema::hasColumn('sales', 'client_idempotency_key')
            && Schema::hasColumn('sales', 'tenant_id')
            && ! Schema::hasIndex('sales', self::INDEX_NAME)
        ) {
            Schema::table('sales', function (Blueprint $table) {
                $table->unique(['tenant_id', 'client_idempotency_key'], self::INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }

        if (Schema::hasIndex('sales', self::INDEX_NAME)) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_NAME);
            });
        }

        if (Schema::hasColumn('sales', 'client_idempotency_key')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('client_idempotency_key');
            });
        }
    }
};
