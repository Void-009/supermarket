<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderComplaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        if ($order->user_id !== Auth::id()) {
            abort(403, 'This is not your order.');
        }

        $complaint = OrderComplaint::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Complaint submitted successfully.',
                'complaint' => $complaint,
            ], 201);
        }

        return redirect()->route('customer.orders.show', $order->id)
            ->with('success', 'Complaint submitted successfully.');
    }

    public function show(OrderComplaint $complaint)
    {
        if ($complaint->user_id !== Auth::id()) {
            abort(403, 'This is not your complaint.');
        }

        $complaint->load(['order', 'replies.user']);

        return view('customer.complaints.show', compact('complaint'));
    }

    public function reply(Request $request, OrderComplaint $complaint)
    {
        if ($complaint->user_id !== Auth::id()) {
            abort(403, 'This is not your complaint.');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $complaint->replies()->create([
            'user_id' => Auth::id(),
            'message' => $validated['message'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reply sent.',
            ]);
        }

        return redirect()->back()->with('success', 'Reply sent.');
    }
}
