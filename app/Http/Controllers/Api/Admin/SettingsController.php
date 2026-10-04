<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\DeliverySetting;
use App\Models\PaymentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'payment' => PaymentSettings::getActive(),
            'bank_accounts' => BankAccount::orderBy('sort_order')->orderBy('bank_name')->get(),
            'delivery' => [
                'show_delivery_fee_selector' => DeliverySetting::showDeliveryFeeSelector(),
                'contact_agent_message' => DeliverySetting::getContactAgentMessage(),
                'contact_agent_phones' => DeliverySetting::getContactAgentPhones(),
                'contact_agent_whatsapps' => DeliverySetting::getContactAgentWhatsapps(),
                'bulk_quantity_threshold' => DeliverySetting::getBulkQuantityThreshold(),
            ],
        ]);
    }

    public function updatePayment(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_payment_settings'), 403, 'You do not have permission to manage payment settings.');

        $paymentSettings = PaymentSettings::getActive();

        if (! $paymentSettings) {
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

        return response()->json([
            'success' => true,
            'message' => 'Payment settings updated successfully!',
            'payment' => $paymentSettings->fresh(),
        ]);
    }

    public function updateDelivery(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasPermission('manage_delivery_locations'), 403, 'You do not have permission to manage delivery settings.');

        $validated = $request->validate([
            'show_delivery_fee_selector' => 'required|in:0,1',
            'contact_agent_message' => 'nullable|string|max:1000',
            'contact_agent_phones' => 'nullable|array',
            'contact_agent_phones.*' => 'nullable|string|max:50',
            'contact_agent_whatsapps' => 'nullable|array',
            'contact_agent_whatsapps.*' => 'nullable|string|max:50',
            'bulk_quantity_threshold' => 'required|integer|min:1',
        ]);

        $phones = $this->cleanNumbers($validated['contact_agent_phones'] ?? []);
        $whatsapps = $this->cleanNumbers($validated['contact_agent_whatsapps'] ?? []);

        DeliverySetting::set('show_delivery_fee_selector', $validated['show_delivery_fee_selector']);
        DeliverySetting::set('contact_agent_message', $validated['contact_agent_message'] ?? '');
        DeliverySetting::set('contact_agent_phones', json_encode($phones));
        DeliverySetting::set('contact_agent_whatsapps', json_encode($whatsapps));
        DeliverySetting::set('contact_agent_phone', $phones[0] ?? '');
        DeliverySetting::set('contact_agent_whatsapp', $whatsapps[0] ?? '');
        DeliverySetting::set('bulk_quantity_threshold', (string) $validated['bulk_quantity_threshold']);

        return response()->json([
            'success' => true,
            'message' => 'Delivery settings updated successfully.',
            'delivery' => [
                'show_delivery_fee_selector' => DeliverySetting::showDeliveryFeeSelector(),
                'contact_agent_message' => DeliverySetting::getContactAgentMessage(),
                'contact_agent_phones' => DeliverySetting::getContactAgentPhones(),
                'contact_agent_whatsapps' => DeliverySetting::getContactAgentWhatsapps(),
                'bulk_quantity_threshold' => DeliverySetting::getBulkQuantityThreshold(),
            ],
        ]);
    }

    private function cleanNumbers(array $list): array
    {
        $out = [];
        foreach ($list as $number) {
            $number = trim((string) $number);
            if ($number === '' || in_array($number, $out, true)) {
                continue;
            }
            $out[] = $number;
        }

        return $out;
    }
}
