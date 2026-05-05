<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained()->onDelete('set null');
            }
            if (! Schema::hasColumn('users', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('branch_id')->constrained('users')->onDelete('set null');
            }
            if (! Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('created_by')->constrained()->onDelete('set null');
            }
            if (! Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->unsignedInteger('failed_login_attempts')->default(0)->after('role_id');
            }
            if (! Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            }
            if (! Schema::HasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('locked_until');
            }
            if (! Schema::HasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('password_changed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['last_login_at', 'last_login_ip', 'branch_id', 'created_by', 'role_id', 'failed_login_attempts', 'locked_until', 'password_changed_at', 'must_change_password'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
