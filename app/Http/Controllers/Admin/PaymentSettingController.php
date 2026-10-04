<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentSettingController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->hasPermission('manage_payment_settings'), 403);

        $paymentSettings = PaymentSettings::getActive();
        return view('admin.payment-settings.index', compact('paymentSettings'));
    }

    public function edit()
    {
        abort_unless(Auth::user()->hasPermission('manage_payment_settings'), 403);

        $paymentSettings = PaymentSettings::getActive();
        if (!$paymentSettings) {
            $paymentSettings = new PaymentSettings();
        }
        return view('admin.payment-settings.edit', compact('paymentSettings'));
    }

    public function update(Request $request)
    {
        abort_unless(Auth::user()->hasPermission('manage_payment_settings'), 403);

        $paymentSettings = PaymentSettings::getActive();

        if (!$paymentSettings) {
            $paymentSettings = new PaymentSettings();
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:30',
            'bank_code' => 'nullable|string|max:20',
            'delivery_fee' => 'required|numeric|min:0',
            'bank_transfer_instructions' => 'nullable|string',
            'whatsapp_number' => 'nullable|string|max:20',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'x_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ]);

        $paymentSettings->fill($validated);
        $paymentSettings->save();

        return redirect()->route('admin.payment-settings.index')
            ->with('success', 'Payment settings updated successfully!');
    }
}