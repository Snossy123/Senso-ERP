<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE journal_entries MODIFY COLUMN status ENUM('draft', 'approved', 'posted', 'cancelled') DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE journal_entries MODIFY COLUMN status ENUM('draft', 'posted', 'cancelled') DEFAULT 'draft'");
        }
    }
};
