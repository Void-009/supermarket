<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasPermission('manage_bank_accounts')) {
            abort(403);
        }

        $bankAccounts = BankAccount::when($request->filled('search'), function ($q) use ($request) {
            $search = trim($request->input('search'));
            $q->where(function ($b) use ($search) {
                $b->where('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%");
            });
        })->orderBy('sort_order')->orderBy('bank_name')->get();
        return view('admin.bank-accounts.index', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasPermission('manage_bank_accounts')) {
            abort(403);
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:30',
            'bank_code' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $bankAccount = BankAccount::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bank account created successfully!',
                'bankAccount' => $bankAccount,
            ]);
        }

        return redirect()->route('admin.bank-accounts.index')
            ->with('success', 'Bank account created successfully!');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        if (!$request->user()->hasPermission('manage_bank_accounts')) {
            abort(403);
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:30',
            'bank_code' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $bankAccount->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bank account updated successfully!',
                'bankAccount' => $bankAccount,
            ]);
        }

        return redirect()->route('admin.bank-accounts.index')
            ->with('success', 'Bank account updated successfully!');
    }

    public function destroy(Request $request, BankAccount $bankAccount)
    {
        if (!$request->user()->hasPermission('manage_bank_accounts')) {
            abort(403);
        }

        $bankAccount->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bank account deleted successfully!',
            ]);
        }

        return redirect()->route('admin.bank-accounts.index')
            ->with('success', 'Bank account deleted successfully!');
    }

    public function toggleActive(Request $request, BankAccount $bankAccount)
    {
        if (!$request->user()->hasPermission('manage_bank_accounts')) {
            abort(403);
        }

        $bankAccount->update(['is_active' => !$bankAccount->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Bank account status updated!',
            'is_active' => $bankAccount->is_active,
        ]);
    }

    public function bulk(Request $request)
    {
        if (!$request->user()->hasPermission('manage_bank_accounts')) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:bank_accounts,id',
        ]);

        $accounts = BankAccount::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($accounts as $account) {
            if ($validated['action'] === 'delete') {
                $account->delete();
                $count++;
            } elseif ($validated['action'] === 'activate') {
                $account->update(['is_active' => true]);
                $count++;
            } elseif ($validated['action'] === 'deactivate') {
                $account->update(['is_active' => false]);
                $count++;
            }
        }

        $labels = ['delete' => 'deleted', 'activate' => 'activated', 'deactivate' => 'deactivated'];
        return response()->json([
            'success' => true,
            'message' => "{$count} account(s) {$labels[$validated['action']]} successfully.",
        ]);
    }
}
