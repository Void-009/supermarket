<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreatePosSyncToken extends Command
{
    protected $signature = 'pos:token:create
        {--name=POS Sync Agent}
        {--email=pos-sync@mdelight.local}
        {--regenerate : Delete existing pos-sync tokens before issuing a new one}';

    protected $description = 'Create (or reuse) a dedicated POS sync agent and issue a Sanctum token scoped to pos:sync';

    public function handle(): int
    {
        $name = $this->option('name');
        $email = strtolower(trim($this->option('email')));

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Str::random(48),
                'role' => 'agent',
                'is_active' => true,
                'permissions' => [],
            ]);
            $this->info("Created POS sync agent user [{$email}].");
        }

        if ($this->option('regenerate')) {
            $user->tokens()->where('name', 'pos-sync')->delete();
        }

        if ($user->tokens()->where('name', 'pos-sync')->exists()) {
            $this->warn('A pos-sync token already exists. Use --regenerate to replace it.');

            return Command::SUCCESS;
        }

        $token = $user->createToken('pos-sync', ['pos:sync']);

        $this->info('POS sync token created. Copy it now — it will not be shown again:');
        $this->line('');
        $this->line($token->plainTextToken);
        $this->line('');
        $this->info('Send it as: Authorization: Bearer <token> to POST /api/pos/sync');

        return Command::SUCCESS;
    }
}
