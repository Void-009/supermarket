<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatQuickResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    private function authorizeChat(): void
    {
        abort_unless(auth()->user()->hasPermission('manage_chat'), 403, 'You do not have permission to manage chat.');
    }

    public function index(): JsonResponse
    {
        $this->authorizeChat();

        $chats = Chat::with(['customer', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])->where('status', 'open')->orderByDesc(
            ChatMessage::select('created_at')->whereColumn('chat_id', 'chats.id')->latest()->limit(1)
        )->get();

        $result = $chats->map(function ($chat) {
            $unread = $chat->messages()->where('is_read', false)->where('user_id', $chat->customer_id)->count();
            return [
                'id' => $chat->id,
                'customer_name' => $chat->customer->name,
                'customer_id' => $chat->customer_id,
                'last_message' => $chat->messages->first()?->message ?? '',
                'unread' => $unread,
                'created_at' => $chat->created_at->toISOString(),
            ];
        });

        $quickResponses = ChatQuickResponse::orderBy('title')->get();

        return response()->json([
            'success' => true,
            'chats' => $result,
            'quick_responses' => $quickResponses,
        ]);
    }

    public function show(Chat $chat): JsonResponse
    {
        $this->authorizeChat();

        $chat->messages()->where('is_read', false)->where('user_id', $chat->customer_id)->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'chat' => [
                'id' => $chat->id,
                'customer_name' => $chat->customer->name,
                'customer_email' => $chat->customer->email,
                'customer_phone' => $chat->customer->phone,
                'customer_id' => $chat->customer_id,
                'admin_id' => $chat->admin_id,
                'status' => $chat->status,
                'created_at' => $chat->created_at->toISOString(),
            ],
            'messages' => $chat->messages()->with('user')->orderBy('created_at')->get()->map(fn ($m) => $this->presentMessage($m)),
        ]);
    }

    public function reply(Request $request, Chat $chat): JsonResponse
    {
        $this->authorizeChat();

        $request->validate(['message' => 'required|string|max:2000']);

        $user = $request->user();

        if (! $chat->admin_id && $user->id !== $chat->customer_id) {
            $chat->update(['admin_id' => $user->id]);
        }

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        $message->load('user');

        return response()->json([
            'success' => true,
            'message' => $this->presentMessage($message),
        ], 201);
    }

    public function storeQuickResponse(Request $request): JsonResponse
    {
        $this->authorizeChat();

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $response = ChatQuickResponse::create($request->only('title', 'message'));

        return response()->json(['success' => true, 'response' => $response], 201);
    }

    public function destroyQuickResponse(ChatQuickResponse $quickResponse): JsonResponse
    {
        $this->authorizeChat();

        $quickResponse->delete();

        return response()->json(['success' => true]);
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
