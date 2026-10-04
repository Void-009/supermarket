<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $chat = Chat::find($chatId);
    return $chat && ($user->id === $chat->customer_id || $user->id === $chat->admin_id);
});

Broadcast::channel('admin.chats', function ($user) {
    return in_array($user->role, ['superadmin', 'admin', 'agent']);
});

Broadcast::channel('admin.orders', function ($user) {
    return in_array($user->role, ['superadmin', 'admin', 'agent']);
});
