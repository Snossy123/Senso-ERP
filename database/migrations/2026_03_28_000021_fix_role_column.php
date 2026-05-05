<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $adminRole = \DB::table('roles')->where('slug', 'admin')->first();
        $managerRole = \DB::table('roles')->where('slug', 'manager')->first();
        $cashierRole = \DB::table('roles')->where('slug', 'cashier')->first();

        $users = \DB::table('users')->whereNotNull('role')->get();
        foreach ($users as $user) {
            $roleId = match ($user->role) {
                'admin' => $adminRole?->id,
                'manager' => $managerRole?->id,
                default => $cashierRole?->id,
            };
            \DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 50)->default('staff')->after('email');
            }
        });
    }
};
