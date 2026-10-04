<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemBarcode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Single source of truth for mapping POS data onto the online catalog.
 *
 * Used by both the new API queue pipeline (ProcessPosSyncBatch) and the
 * legacy pos:sync artisan command so the field mapping never diverges.
 *
 * The payload is a normalized POS item:
 *
 *   action:       create|update|deactivate
 *   pos_item_id:  string            (the POS Item Id, e.g. "45021" or "1070@4907")
 *   name:         string            (required for create)
 *   display_name: string|null
 *   category:     string|null       (resolved/auto-created by name)
 *   cost_price:   float
 *   selling_price:float
 *   promo_price:  float|null
 *   promo_start_at:string|null
 *   promo_end_at: string|null
 *   quantity:     int
 *   inactive:     bool              (POS "Inactive" column)
 *   last_modified:string|null       (POS "Last Modified" column)
 *   barcodes:     array             (each entry "type|value", type in item_number|product_id|display_name)
 *
 * Online-only fields (description, image_url, is_admin_favorite, ...) are
 * NEVER overwritten by this service.
 */
class PosSyncService
{
    public const BARCODE_TYPES = ['item_number', 'product_id', 'display_name'];

    public function process(array $payload): array
    {
        $action = strtolower((string) ($payload['action'] ?? 'update'));
        $posItemId = trim((string) ($payload['pos_item_id'] ?? ''));

        if ($posItemId === '') {
            return $this->fail('pos_item_id is required', $posItemId);
        }

        $isVariation = str_contains($posItemId, '@');
        $parentItemNumber = $isVariation ? explode('@', $posItemId, 2)[0] : null;

        if ($action === 'deactivate') {
            $item = Item::where('pos_item_id', $posItemId)->first();
            if (! $item) {
                return $this->skip('missing', $posItemId);
            }

            return $this->deactivate($item);
        }

        $item = Item::where('pos_item_id', $posItemId)->first();

        if ($action === 'update') {
            if ($item && ! $this->isFresh($item, $payload)) {
                return $this->skip('stale', $posItemId);
            }

            // Upsert semantics: an update for an unknown item becomes a create.
            if (! $item) {
                return $this->create($payload, $isVariation, $parentItemNumber);
            }

            return $this->update($item, $payload, $isVariation);
        }

        if ($action === 'create') {
            if ($item) {
                return $this->update($item, $payload, $isVariation);
            }

            return $this->create($payload, $isVariation, $parentItemNumber);
        }

        return $this->fail("Unknown action: {$action}", $posItemId);
    }

    private function create(array $payload, bool $isVariation, ?string $parentItemNumber): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->fail('name is required for create', $payload['pos_item_id'] ?? '');
        }

        $quantity = max(0, (int) ($payload['quantity'] ?? 0));
        $inactive = $this->toBool($payload['inactive'] ?? false);

        $item = Item::create([
            'pos_item_id' => (string) $payload['pos_item_id'],
            'parent_item_number' => $parentItemNumber,
            'category_id' => $this->resolveCategory($payload['category'] ?? ''),
            'name' => $name,
            'display_name' => $this->nullableString($payload['display_name'] ?? null),
            'slug' => $this->makeUniqueSlug($name),
            'cost_price' => $this->money($payload['cost_price'] ?? 0),
            'selling_price' => $this->money($payload['selling_price'] ?? 0),
            'promo_price' => $this->nullableMoney($payload['promo_price'] ?? null),
            'promo_start_at' => $this->parseDate($payload['promo_start_at'] ?? null),
            'promo_end_at' => $this->parseDate($payload['promo_end_at'] ?? null),
            'quantity' => $quantity,
            'is_visible_online' => ! $inactive && ($isVariation || $quantity > 0),
            'pos_inactive' => $inactive,
            'stock_status' => $this->stockStatus($quantity),
            'last_modified' => $this->parseDate($payload['last_modified'] ?? null) ?? now(),
            'last_synced_at' => now(),
        ]);

        $this->syncBarcodes($item->id, $payload['barcodes'] ?? []);

        return $this->ok('created', $item);
    }

    private function update(Item $item, array $payload, bool $isVariation): array
    {
        $raw = $item->getRawOriginal();
        $data = [];

        if (array_key_exists('name', $payload)) {
            $name = trim((string) $payload['name']);
            if ($name !== '' && (string) ($raw['name'] ?? '') !== $name) {
                $data['name'] = $name;
            }
        }

        if (array_key_exists('display_name', $payload)) {
            $displayName = $this->nullableString($payload['display_name']);
            if (($raw['display_name'] ?? null) !== $displayName) {
                $data['display_name'] = $displayName;
            }
        }

        if (array_key_exists('category', $payload)) {
            $categoryId = $this->resolveCategory($payload['category']);
            if ((int) ($raw['category_id'] ?? 0) !== $categoryId) {
                $data['category_id'] = $categoryId;
            }
        }

        foreach (['cost_price', 'selling_price'] as $field) {
            if (array_key_exists($field, $payload)) {
                $value = $this->money($payload[$field]);
                if ((float) ($raw[$field] ?? 0) !== $value) {
                    $data[$field] = $value;
                }
            }
        }

        if (array_key_exists('promo_price', $payload)) {
            $value = $this->nullableMoney($payload['promo_price']);
            if (($raw['promo_price'] ?? null) != $value) {
                $data['promo_price'] = $value;
            }
        }

        foreach (['promo_start_at', 'promo_end_at'] as $field) {
            if (array_key_exists($field, $payload)) {
                $value = $this->parseDate($payload[$field]);
                $valueStr = $value?->format('Y-m-d H:i:s') ?? '';
                $rawStr = ! empty($raw[$field]) ? Carbon::parse($raw[$field])->format('Y-m-d H:i:s') : '';
                if ($rawStr !== $valueStr) {
                    $data[$field] = $value;
                }
            }
        }

        if (array_key_exists('inactive', $payload)) {
            $inactive = $this->toBool($payload['inactive']);
            if ((bool) ($raw['pos_inactive'] ?? false) !== $inactive) {
                $data['pos_inactive'] = $inactive;
            }
        }

        $inactive = $data['pos_inactive'] ?? (bool) ($raw['pos_inactive'] ?? false);

        if (! $isVariation && array_key_exists('quantity', $payload)) {
            $quantity = max(0, (int) $payload['quantity']);
            if ((int) ($raw['quantity'] ?? 0) !== $quantity) {
                $data['quantity'] = $quantity;
            }

            $visible = ! $inactive && $quantity > 0;
            if ((bool) ($raw['is_visible_online'] ?? true) !== $visible) {
                $data['is_visible_online'] = $visible;
            }

            $status = $this->stockStatus($quantity);
            if (($raw['stock_status'] ?? null) !== $status) {
                $data['stock_status'] = $status;
            }
        } elseif (array_key_exists('inactive', $payload)) {
            $quantity = (int) ($raw['quantity'] ?? 0);
            $visible = ! $inactive && ($isVariation || $quantity > 0);
            if ((bool) ($raw['is_visible_online'] ?? true) !== $visible) {
                $data['is_visible_online'] = $visible;
            }
        }

        if (array_key_exists('last_modified', $payload)) {
            $lastModified = $this->parseDate($payload['last_modified']);
            if ($lastModified) {
                $data['last_modified'] = $lastModified;
            }
        }

        if (! empty($data)) {
            $data['last_synced_at'] = now();
            $item->update($data);
        }

        $barcodesChanged = $this->syncBarcodes($item->id, $payload['barcodes'] ?? []);

        if (empty($data) && ! $barcodesChanged) {
            return $this->skip('no_changes', $item->pos_item_id);
        }

        return $this->ok('updated', $item);
    }

    private function deactivate(Item $item): array
    {
        $item->update([
            'is_visible_online' => false,
            'pos_inactive' => true,
            'last_synced_at' => now(),
        ]);

        return $this->ok('deactivated', $item);
    }

    /**
     * Skip when the incoming payload is not newer than what is already stored.
     */
    private function isFresh(Item $item, array $payload): bool
    {
        $incoming = $this->parseDate($payload['last_modified'] ?? null);
        if (! $incoming) {
            return true;
        }

        if (! $item->last_modified) {
            return true;
        }

        return $incoming->greaterThanOrEqualTo($item->last_modified);
    }

    private function resolveCategory(?string $name): int
    {
        $name = $this->normalizeCategoryName($name);

        $category = Category::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if (! $category) {
            $category = Category::create([
                'name' => $name,
                'slug' => $this->makeUniqueCategorySlug(Str::slug($name)),
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        return $category->id;
    }

    /**
     * Categories from the POS export may include a shelf hint separated
     * by a pipe, e.g. "SPICES|SPICES SHELF TWO". Only the name before the
     * pipe is the real category; the shelf is ignored so every shelf
     * variant resolves to the same base category.
     */
    private function normalizeCategoryName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'Uncategorized';
        }

        if (str_contains($name, '|')) {
            $name = trim(explode('|', $name, 2)[0]);
        }

        return $name === '' ? 'Uncategorized' : $name;
    }

    /**
     * Post-sync category maintenance, run after a batch is applied:
     *
     * Merge shelf-variant categories ("SPICES|SPICES SHELF TWO") into their
     * base category ("SPICES") and move all items over. A deleted base
     * category is never restored (it stays hidden) — it is only created
     * brand new when it does not exist at all.
     *
     * The online active/inactive status is fully manual: the sync never
     * activates, deactivates or restores categories. It only attaches items
     * to the name-resolved category (creating a truly-new category only when
     * that name does not exist anywhere, even in the trash).
     */
    public function syncCategories(): array
    {
        $merged = 0;

        $variants = Category::where('name', 'like', '%|%')->get();

        foreach ($variants as $variant) {
            $baseName = $this->normalizeCategoryName($variant->name);
            if ($baseName === $variant->name) {
                continue;
            }

            $base = Category::withTrashed()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($baseName)])
                ->first();

            if (! $base) {
                $base = Category::create([
                    'name' => $baseName,
                    'slug' => $this->makeUniqueCategorySlug(Str::slug($baseName)),
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
            }

            if ($base->id === $variant->id) {
                continue;
            }

            Item::where('category_id', $variant->id)->update(['category_id' => $base->id]);
            $variant->delete();
            $merged++;
        }

        return ['merged' => $merged, 'deactivated' => []];
    }

    private function makeUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $counter = 2;
        while (Item::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function makeUniqueCategorySlug(string $slug): string
    {
        if ($slug === '') {
            $slug = 'category';
        }

        $original = $slug;
        $counter = 2;
        while (Category::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter++;
        }

        return $slug;
    }

    /**
     * @return bool true when the barcode set changed
     */
    private function syncBarcodes(int $itemId, array $barcodes): bool
    {
        $candidate = $this->normalizeBarcodes($barcodes);

        $current = ItemBarcode::where('item_id', $itemId)
            ->pluck('barcode_value')
            ->map(fn (string $value) => $this->normalizeBarcode($value))
            ->filter()
            ->sort()
            ->values()
            ->all();

        if ($candidate === $current) {
            return false;
        }

        ItemBarcode::where('item_id', $itemId)->delete();

        if (! empty($candidate)) {
            ItemBarcode::insertOrIgnore(
                collect($candidate)
                    ->map(fn (string $entry) => explode('|', $entry, 2))
                    ->map(fn (array $parts) => [
                        'item_id' => $itemId,
                        'barcode_type' => $parts[0],
                        'barcode_value' => $parts[1],
                    ])
                    ->all()
            );
        }

        return true;
    }

    private function normalizeBarcodes(array $barcodes): array
    {
        $seen = [];
        $list = [];

        foreach ($barcodes as $barcode) {
            if (! is_string($barcode) && ! is_numeric($barcode)) {
                continue;
            }
            $normalized = $this->normalizeBarcode($barcode);
            if ($normalized === null || isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $list[] = $normalized;
        }

        sort($list);

        return $list;
    }

    private function normalizeBarcode(mixed $barcode): ?string
    {
        $barcode = trim((string) $barcode);
        if ($barcode === '') {
            return null;
        }

        if (str_contains($barcode, '|')) {
            [$type, $value] = explode('|', $barcode, 2);
            $type = strtolower(trim($type));
            if (! in_array($type, self::BARCODE_TYPES, true)) {
                return null;
            }
            $value = trim($value);
            if ($value === '') {
                return null;
            }

            return $type.'|'.$value;
        }

        return 'item_number|'.$barcode;
    }

    private function money(mixed $value): float
    {
        return round((float) $value, 2);
    }

    private function nullableMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function toBool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'TRUE', 'True', 'yes', 'Yes', 'YES'], true);
    }

    private function stockStatus(int $quantity): string
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        }

        if ($quantity < 5) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    private function ok(string $result, Item $item): array
    {
        return [
            'result' => $result,
            'item_id' => $item->id,
            'pos_item_id' => $item->pos_item_id,
        ];
    }

    private function skip(string $reason, string $posItemId): array
    {
        return [
            'result' => 'skipped',
            'reason' => $reason,
            'pos_item_id' => $posItemId,
        ];
    }

    private function fail(string $error, string $posItemId = ''): array
    {
        return [
            'result' => 'failed',
            'error' => $error,
            'pos_item_id' => $posItemId,
        ];
    }
}
