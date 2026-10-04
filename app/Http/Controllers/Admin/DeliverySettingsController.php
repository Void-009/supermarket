<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliverySetting;
use Illuminate\Http\Request;

class DeliverySettingsController extends Controller
{
    public function edit()
    {
        if (!auth()->user()->hasPermission('manage_delivery_locations')) {
            abort(403, 'You do not have permission to manage delivery settings.');
        }

        $settings = [
            'show_delivery_fee_selector' => DeliverySetting::showDeliveryFeeSelector(),
            'contact_agent_message' => DeliverySetting::getContactAgentMessage(),
            'contact_agent_phones' => DeliverySetting::getContactAgentPhones(),
            'contact_agent_whatsapps' => DeliverySetting::getContactAgentWhatsapps(),
            'bulk_quantity_threshold' => DeliverySetting::getBulkQuantityThreshold(),
        ];

        return view('admin.delivery-settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_delivery_locations')) {
            abort(403, 'You do not have permission to manage delivery settings.');
        }

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

        return redirect()->route('admin.delivery-settings.edit')->with('success', 'Delivery settings updated successfully.');
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
