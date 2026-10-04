<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')->whereIn('role', ['superadmin', 'admin', 'agent'])->get();

        foreach ($users as $user) {
            $defaults = (new User(['role' => $user->role]))->getDefaultPermissions();
            $current = json_decode($user->permissions ?? '[]', true) ?? [];
            $merged = array_merge($defaults, $current);

            DB::table('users')->where('id', $user->id)->update([
                'permissions' => json_encode($merged),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
