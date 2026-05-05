<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_pages', function (Blueprint $table) {
            $table->json('layout_schema')->nullable()->after('seo');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_pages', function (Blueprint $table) {
            $table->dropColumn('layout_schema');
        });
    }
};
