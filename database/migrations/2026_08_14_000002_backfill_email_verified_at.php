<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Email verification was introduced after launch. Treat all existing
     * accounts as verified so nobody is locked out of their account.
     * Only newly registered accounts will be required to verify.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // No-op: we cannot know which accounts were manually verified.
    }
};
