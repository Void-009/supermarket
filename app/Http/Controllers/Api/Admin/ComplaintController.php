<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderComplaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    private function authorizeComplaints(): void
    {
        abort_unless(auth()->user()->hasPermission('manage_complaints'), 403, 'You do not have permission to manage complaints.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeComplaints();

        $query = OrderComplaint::with(['order', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('order', function ($o) use ($search) {
                        $o->where('customer_name', 'like', "%{$search}%")
                            ->orWhere('customer_phone', 'like', "%{$search}%");
                        $id = preg_replace('/[^0-9]/', '', $search);
                        if ($id !== '') {
                            $o->orWhere('id', (int) $id);
                        }
                    });
            });
        }

        $complaints = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'complaints' => collect($complaints->items())->map(fn ($c) => $this->presentComplaint($c)),
            'pagination' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'total' => $complaints->total(),
            ],
        ]);
    }

    public function show(OrderComplaint $complaint): JsonResponse
    {
        $this->authorizeComplaints();

        $complaint->load(['order', 'user', 'replies.user']);

        return response()->json([
            'success' => true,
            'complaint' => $this->presentComplaint($complaint),
        ]);
    }

    public function updateStatus(Request $request, OrderComplaint $complaint): JsonResponse
    {
        $this->authorizeComplaints();

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved',
        ]);

        $complaint->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Complaint status updated.',
            'complaint' => $this->presentComplaint($complaint->fresh(['order', 'user', 'replies.user'])),
        ]);
    }

    public function reply(Request $request, OrderComplaint $complaint): JsonResponse
    {
        $this->authorizeComplaints();

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $complaint->replies()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
        ]);

        if ($complaint->status === 'open') {
            $complaint->update(['status' => 'in_progress']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply sent.',
            'complaint' => $this->presentComplaint($complaint->fresh(['order', 'user', 'replies.user'])),
        ]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $this->authorizeComplaints();

        $validated = $request->validate([
            'action' => 'required|in:open,in_progress,resolved,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:order_complaints,id',
        ]);

        $complaints = OrderComplaint::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($complaints as $complaint) {
            if ($validated['action'] === 'delete') {
                $complaint->replies()->delete();
                $complaint->delete();
                $count++;
            } else {
                $complaint->update(['status' => $validated['action']]);
                $count++;
            }
        }

        $labels = ['open' => 'marked as open', 'in_progress' => 'marked as in progress', 'resolved' => 'marked as resolved', 'delete' => 'deleted'];

        return response()->json([
            'success' => true,
            'message' => "{$count} complaint(s) {$labels[$validated['action']]} successfully.",
        ]);
    }

    private function presentComplaint(OrderComplaint $complaint): array
    {
        return [
            'id' => $complaint->id,
            'order_id' => $complaint->order_id,
            'user_id' => $complaint->user_id,
            'subject' => $complaint->subject,
            'message' => $complaint->message,
            'status' => $complaint->status,
            'created_at' => $complaint->created_at?->toISOString(),
            'customer_name' => $complaint->user?->name,
            'customer_email' => $complaint->user?->email,
            'customer_phone' => $complaint->user?->phone,
            'order' => $complaint->order ? [
                'id' => $complaint->order->id,
                'status' => $complaint->order->status,
                'customer_name' => $complaint->order->customer_name,
                'total_amount' => (float) $complaint->order->total_amount,
            ] : null,
            'replies' => $complaint->replies->map(fn ($reply) => [
                'id' => $reply->id,
                'message' => $reply->message,
                'sender_name' => $reply->user?->name,
                'is_admin' => $reply->user && $reply->user->isAdminOrAgent(),
                'created_at' => $reply->created_at?->toISOString(),
            ])->values(),
        ];
    }
}
