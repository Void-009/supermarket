<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function active(Request $request): JsonResponse
    {
        $chat = Chat::where('customer_id', $request->user()->id)->where('status', 'open')->first();

        return response()->json([
            'success' => true,
            'chat' => $chat ? ['id' => $chat->id] : null,
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $chat = Chat::where('customer_id', $request->user()->id)->where('status', 'open')->first();
        if (! $chat) {
            return response()->json(['success' => true, 'chat_id' => null, 'messages' => []]);
        }

        $chat->messages()
            ->where('is_read', false)
            ->where('user_id', '!=', $request->user()->id)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'chat_id' => $chat->id,
            'messages' => $chat->messages()
                ->with('user')
                ->orderBy('created_at')
                ->get()
                ->map(fn ($m) => $this->presentMessage($m))
                ->values(),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate(['message' => 'required|string|max:2000']);

        $user = $request->user();
        $chat = Chat::where('customer_id', $user->id)->where('status', 'open')->first();

        if (! $chat) {
            $chat = Chat::create([
                'customer_id' => $user->id,
                'status' => 'open',
            ]);
        }

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        $message->load('user');

        return response()->json([
            'success' => true,
            'chat_id' => $chat->id,
            'message' => $this->presentMessage($message),
        ], 201);
    }

    private function presentMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'user_id' => $message->user_id,
            'sender_name' => $message->user?->name,
            'is_admin' => $message->user && $message->user->isAdminOrAgent(),
            'created_at' => $message->created_at->toISOString(),
        ];
    }
}
