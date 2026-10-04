<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PosSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sync_id' => ['required', 'uuid'],
            'source' => ['sometimes', 'string', 'max:100'],
            'items' => ['required', 'array', 'max:500'],
            'items.*.pos_item_id' => ['required', 'string', 'max:190'],
            'items.*.action' => ['required', 'in:create,update,deactivate'],
            'items.*.name' => ['sometimes', 'string', 'max:255'],
            'items.*.display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.category' => ['sometimes', 'nullable', 'string', 'max:190'],
            'items.*.cost_price' => ['sometimes', 'numeric', 'min:0'],
            'items.*.selling_price' => ['sometimes', 'numeric', 'min:0'],
            'items.*.promo_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'items.*.promo_start_at' => ['sometimes', 'nullable', 'date'],
            'items.*.promo_end_at' => ['sometimes', 'nullable', 'date'],
            'items.*.quantity' => ['sometimes', 'numeric', 'min:0'],
            'items.*.inactive' => ['sometimes', 'boolean'],
            'items.*.last_modified' => ['sometimes', 'nullable', 'date'],
            'items.*.barcodes' => ['sometimes', 'array', 'max:50'],
            'items.*.barcodes.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'sync_id.required' => 'A sync_id (UUID) is required.',
            'sync_id.uuid' => 'sync_id must be a valid UUID.',
            'items.required' => 'The items array is required.',
            'items.max' => 'A sync batch cannot contain more than :max items.',
            'items.*.pos_item_id.required' => 'Every item requires a pos_item_id.',
            'items.*.action.in' => 'action must be one of create, update or deactivate.',
        ];
    }
}
