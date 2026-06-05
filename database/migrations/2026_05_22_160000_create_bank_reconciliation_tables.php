<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('account_id');
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 4);
            $table->enum('type', ['debit', 'credit']);
            $table->boolean('is_reconciled')->default(false);
            $table->unsignedBigInteger('journal_entry_line_id')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->unsignedBigInteger('reconciled_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('journal_entry_line_id')->references('id')->on('journal_entry_lines')->onDelete('set null');
            $table->foreign('reconciled_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_payments', 'sale_id')) {
                $table->unsignedBigInteger('sale_id')->nullable()->after('order_id');
                $table->foreign('sale_id')->references('id')->on('sales')->onDelete('restrict');
            }
        });

        if (Schema::hasColumn('customer_payments', 'order_id')) {
            Schema::table('customer_payments', function (Blueprint $table) {
                $table->dropForeign(['order_id']);
            });

            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                \Illuminate\Support\Facades\DB::statement(
                    'ALTER TABLE customer_payments MODIFY order_id BIGINT UNSIGNED NULL'
                );
            }

            Schema::table('customer_payments', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
            });
        }

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'payment_fee_amount')) {
                $table->decimal('payment_fee_amount', 12, 4)->default(0)->after('total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'payment_fee_amount')) {
                $table->dropColumn('payment_fee_amount');
            }
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            if (Schema::hasColumn('customer_payments', 'sale_id')) {
                $table->dropForeign(['sale_id']);
                $table->dropColumn('sale_id');
            }
        });

        Schema::dropIfExists('bank_statement_lines');
    }
};
