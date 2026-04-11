<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('account_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('key')->index(); // e.g., 'sales_revenue'
            $table->unsignedBigInteger('account_id');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            
            // Each tenant has one account per key
            $table->unique(['tenant_id', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_settings');
    }
};
