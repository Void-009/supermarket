<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use App\Models\User;
use App\Notifications\AbandonedCartReminder;
use Illuminate\Console\Command;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'notifications:abandoned-carts
                            {--hours=2 : Number of hours after which a cart is considered abandoned}';

    protected $description = 'Send push notifications to users with abandoned carts';

    public function handle(): int
    {
        $thresholdHours = (int) $this->option('hours');
        $cutoff = now()->subHours($thresholdHours);

        $this->info("Checking for carts older than {$thresholdHours} hour(s)...");

        $userIds = CartItem::where('created_at', '<', $cutoff)
            ->whereDoesntHave('user.orders', function ($q) use ($cutoff) {
                $q->where('created_at', '>=', $cutoff);
            })
            ->distinct()
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->info('No abandoned carts found.');
            return Command::SUCCESS;
        }

        $users = User::whereIn('id', $userIds)
            ->whereHas('pushSubscriptions')
            ->get();

        $sent = 0;

        foreach ($users as $user) {
            $cartItems = CartItem::where('user_id', $user->id)
                ->where('created_at', '<', $cutoff)
                ->get();

            if ($cartItems->isEmpty()) {
                continue;
            }

            $itemCount = $cartItems->sum('quantity');
            $totalAmount = $cartItems->sum(function ($item) {
                return ($item->item?->selling_price ?? 0) * $item->quantity;
            });

            if ($totalAmount <= 0) {
                continue;
            }

            $user->notify(new AbandonedCartReminder($itemCount, $totalAmount));
            $sent++;
        }

        $this->info("Sent {$sent} abandoned cart reminder(s).");

        return Command::SUCCESS;
    }
}
