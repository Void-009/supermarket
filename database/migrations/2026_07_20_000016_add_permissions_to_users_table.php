<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Duplicate of 000015: guarded so it is a no-op when the column exists.
        if (! Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('permissions')->nullable()->after('role');
            });
        }

        // Update existing users with default permissions based on role
        DB::table('users')->where('role', 'superadmin')->update([
            'permissions' => json_encode([
                'process_orders' => true,
                'modify_items' => true,
                'manage_categories' => true,
                'view_sync' => true,
                'manage_users' => true,
            ])
        ]);

        DB::table('users')->where('role', 'admin')->update([
            'permissions' => json_encode([
                'process_orders' => true,
                'modify_items' => true,
                'manage_categories' => true,
                'view_sync' => true,
                'manage_users' => false,
            ])
        ]);

        DB::table('users')->where('role', 'agent')->update([
            'permissions' => json_encode([
                'process_orders' => true,
                'modify_items' => false,
                'manage_categories' => false,
                'view_sync' => false,
                'manage_users' => false,
            ])
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
