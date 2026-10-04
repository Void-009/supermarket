<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderComplaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->hasPermission('manage_complaints'), 403);

        $query = OrderComplaint::with(['order', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        $complaints = $query->latest()->paginate(15)->withQueryString();

        return view('admin.complaints.index', compact('complaints'));
    }

    public function show(OrderComplaint $complaint)
    {
        abort_unless(Auth::user()->hasPermission('manage_complaints'), 403);

        $complaint->load(['order', 'user', 'replies.user']);

        return view('admin.complaints.show', compact('complaint'));
    }

    public function updateStatus(Request $request, OrderComplaint $complaint)
    {
        abort_unless(Auth::user()->hasPermission('manage_complaints'), 403);

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved',
        ]);

        $complaint->update(['status' => $validated['status']]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Complaint status updated.',
                'complaint' => $complaint,
            ]);
        }

        return redirect()->back()->with('success', 'Complaint status updated.');
    }

    public function reply(Request $request, OrderComplaint $complaint)
    {
        abort_unless(Auth::user()->hasPermission('manage_complaints'), 403);

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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reply sent.',
            ]);
        }

        return redirect()->back()->with('success', 'Reply sent.');
    }

    public function bulk(Request $request)
    {
        abort_unless(Auth::user()->hasPermission('manage_complaints'), 403);

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
}
