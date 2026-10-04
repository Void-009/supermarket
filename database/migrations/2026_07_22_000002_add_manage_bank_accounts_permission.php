<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')->whereNotNull('permissions')->get();

        foreach ($users as $user) {
            $permissions = json_decode($user->permissions, true) ?? [];
            if (!isset($permissions['manage_bank_accounts'])) {
                $permissions['manage_bank_accounts'] = $user->role === 'superadmin';
                DB::table('users')->where('id', $user->id)->update([
                    'permissions' => json_encode($permissions),
                ]);
            }
        }
    }

    public function down(): void
    {
        $users = DB::table('users')->whereNotNull('permissions')->get();

        foreach ($users as $user) {
            $permissions = json_decode($user->permissions, true) ?? [];
            unset($permissions['manage_bank_accounts']);
            DB::table('users')->where('id', $user->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        }
    }
};
