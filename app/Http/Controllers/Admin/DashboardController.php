<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PosSyncAgentStatus;
use App\Models\PosSyncBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isAgent()) {
            $totalItems = Item::count();
            $totalOrders = Order::where('processed_by', $user->id)->count();
            $totalRevenue = Payment::where('status', 'success')
                ->whereHas('order', fn($q) => $q->where('processed_by', $user->id))
                ->sum('amount');
            $pendingOrders = Order::where('status', 'new')->count();
            $recentOrders = Order::with('items', 'processor')->latest()->limit(10)->get();
        } else {
            $totalItems = Item::count();
            $totalOrders = Order::count();
            $totalRevenue = Payment::where('status', 'success')->sum('amount');
            $pendingOrders = Order::where('status', 'new')->count();
            $recentOrders = Order::with('items', 'processor')->latest()->limit(10)->get();
        }

        $agentStatus = PosSyncAgentStatus::where('source', 'pos-agent')->first();
        $lastBatch = PosSyncBatch::latest()->first();

        return view('admin.dashboard', compact(
            'totalItems',
            'totalOrders',
            'totalRevenue',
            'pendingOrders',
            'recentOrders',
            'agentStatus',
            'lastBatch'
        ));
    }

    public function profile()
    {
        return view('admin.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($request->filled('current_password')) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
            }

            $user->password = Hash::make($data['new_password']);
        }

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $data['profile_image'] = $request->file('profile_image')->store('profile', 'public');
        } else {
            unset($data['profile_image']);
        }

        unset($data['current_password'], $data['new_password'], $data['new_password_confirmation']);

        $user->update($data);

        return redirect()->route('admin.profile')->with('success', 'Profile updated successfully.');
    }
}