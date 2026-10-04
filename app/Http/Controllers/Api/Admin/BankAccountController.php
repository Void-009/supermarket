<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    private function authorizeBankAccounts(): void
    {
        abort_unless(auth()->user()->hasPermission('manage_bank_accounts'), 403, 'You do not have permission to manage bank accounts.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeBankAccounts();

        $bankAccounts = BankAccount::when($request->filled('search'), function ($q) use ($request) {
            $search = trim($request->input('search'));
            $q->where(fn ($b) => $b->where('bank_name', 'like', "%{$search}%")
                ->orWhere('account_name', 'like', "%{$search}%")
                ->orWhere('account_number', 'like', "%{$search}%"));
        })->orderBy('sort_order')->orderBy('bank_name')->get();

        return response()->json([
            'success' => true,
            'bank_accounts' => $bankAccounts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeBankAccounts();

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

        return response()->json([
            'success' => true,
            'message' => 'Bank account created successfully!',
            'bank_account' => $bankAccount,
        ], 201);
    }

    public function update(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorizeBankAccounts();

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

        return response()->json([
            'success' => true,
            'message' => 'Bank account updated successfully!',
            'bank_account' => $bankAccount->fresh(),
        ]);
    }

    public function destroy(BankAccount $bankAccount): JsonResponse
    {
        $this->authorizeBankAccounts();

        $bankAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bank account deleted successfully!',
        ]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $this->authorizeBankAccounts();

        $validated = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:bank_accounts,id',
        ]);

        $accounts = BankAccount::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($accounts as $account) {
            match ($validated['action']) {
                'delete' => $account->delete(),
                'activate' => $account->update(['is_active' => true]),
                'deactivate' => $account->update(['is_active' => false]),
            };
            $count++;
        }

        $labels = ['delete' => 'deleted', 'activate' => 'activated', 'deactivate' => 'deactivated'];

        return response()->json([
            'success' => true,
            'message' => "{$count} account(s) {$labels[$validated['action']]} successfully.",
        ]);
    }
}
