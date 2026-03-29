<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('subscription_ends_at')->constrained('plans')->nullOnDelete();
            $table->enum('status', ['trial', 'active', 'expired', 'suspended'])->default('trial')->after('plan_id');
            $table->timestamp('subscription_start_at')->nullable()->after('status');
            $table->decimal('price', 10, 2)->default(0)->after('subscription_start_at');
            $table->string('billing_cycle', 20)->default('monthly')->after('price');
            $table->timestamp('next_billing_at')->nullable()->after('billing_cycle');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending')->after('next_billing_at');
            $table->string('currency', 3)->default('USD')->after('payment_status');
            $table->string('language', 10)->default('en')->after('currency');
            $table->string('timezone', 50)->default('UTC')->after('language');
            $table->json('tax_settings')->nullable()->after('timezone'); // tax_number, tax_rate, tax_included
            $table->text('notes')->nullable()->after('tax_settings');
            $table->timestamp('suspended_at')->nullable()->after('notes');
            $table->string('suspension_reason')->nullable()->after('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $columns = [
                'plan_id', 'status', 'subscription_start_at', 'price', 'billing_cycle',
                'next_billing_at', 'payment_status', 'currency', 'language', 'timezone',
                'tax_settings', 'notes', 'suspended_at', 'suspension_reason'
            ];
            $table->dropColumn($columns);
        });
    }
};
