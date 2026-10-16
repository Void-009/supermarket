<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Contract for POST /api/pos/sync/reconcile: the agent posts the complete
 * list of POS Item Ids currently in its inventory, plus the subset the till
 * currently flags Inactive, so the server can hide anything that vanished
 * (runout deletes) or that the till has stopped selling. Naturally
 * idempotent, so no sync_id dedup is needed.
 */
class PosSyncReconcileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pos_item_ids' => 'required|array|min:1|max:60000',
            'pos_item_ids.*' => 'required|string|max:100',
            'inactive_pos_item_ids' => 'nullable|array|max:60000',
            'inactive_pos_item_ids.*' => 'required|string|max:100',
            'source' => 'nullable|string|max:100',
            'dry_run' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'pos_item_ids.required' => 'pos_item_ids is required (post the full inventory id list).',
            'pos_item_ids.min' => 'pos_item_ids looks truncated: at least one id is required.',
        ];
    }
}
