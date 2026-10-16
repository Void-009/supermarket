<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\PosItemAlias;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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

    /**
     * Fraction of the till's known inventory an export must cover before
     * reconcileCatalog is allowed to hide anything. Hiding is irreversible
     * from the customer's point of view for as long as nobody notices, so a
     * listing that looks truncated is refused outright rather than trusted.
     */
    public const COVERAGE_MIN = 0.9;

    /**
     * How many consecutive exports of the same, smaller size must arrive
     * before a shrinking catalog is believed and acted on. Two keeps a
     * one-off truncated file harmless while still letting a real bulk
     * cleanup through within a couple of minutes.
     */
    public const SHRINK_CONFIRMATIONS = 2;

    /**
     * Hard ceiling on how much of the catalog a single reconcile may hide.
     *
     * This is the rail that makes the whole feature safe: a truncated export
     * always looks like "hide nearly everything", so no run is ever allowed
     * to remove more than this share of the items the till has confirmed. A
     * genuine bulk cleanup is a small share of a large catalog and sails
     * through; a broken export is refused every single time, forever,
     * regardless of how many times it repeats.
     */
    public const MAX_HIDE_FRACTION = 0.2;

    public function process(array $payload): array
    {
        $action = strtolower((string) ($payload['action'] ?? 'update'));
        $posItemId = trim((string) ($payload['pos_item_id'] ?? ''));

        if ($posItemId === '') {
            return $this->fail('pos_item_id is required', $posItemId);
        }

        $isVariation = str_contains($posItemId, '@');
        $parentItemNumber = $isVariation ? explode('@', $posItemId, 2)[0] : null;

        // A POS Item Id that was previously matched to an existing row
        // always lands on that row, whatever action the POS sends for it.
        // This is what stops a re-created variation from duplicating: the
        // fresh id is only ever an alias of the original row.
        $alias = PosItemAlias::where('pos_item_id', $posItemId)->first();
        if ($alias) {
            if (! $alias->item) {
                // Canonical row was purged by an admin; the alias is dead.
                $alias->delete();
            } else {
                return $this->processAliased($alias->item, $payload, $action);
            }
        }

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

    /**
     * Apply a payload whose pos_item_id is aliased to an existing row.
     * Freshness is not checked: the canonical row's last_modified belongs
     * to an older POS id and must not reject fields from its replacement.
     */
    private function processAliased(Item $item, array $payload, string $action): array
    {
        if ($action === 'deactivate') {
            return $this->deactivate($item);
        }

        return $this->update($item, $payload, $item->isVariation());
    }

    private function create(array $payload, bool $isVariation, ?string $parentItemNumber): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->fail('name is required for create', $payload['pos_item_id'] ?? '');
        }

        if ($isVariation && $parentItemNumber) {
            $restored = $this->tryRestoreTombstone($payload, $parentItemNumber, $name);
            if ($restored) {
                return $restored;
            }
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
            'owned_by_pos' => true,
        ]);

        $this->syncBarcodes($item->id, $payload['barcodes'] ?? []);

        $this->retireSameNameSiblings($item);

        return $this->ok('created', $item);
    }

    /**
     * When the POS deletes a variation on runout it mints a brand new
     * Item Id on restock. If exactly one tombstoned sibling under the same
     * parent carries the incoming variation's name, revive that row instead
     * of creating a duplicate: the row keeps its images, description,
     * favourites and order history, and the new id is permanently aliased
     * to it.
     *
     * An identical cost price is treated as stronger proof, but a restock is
     * often re-priced, so cost only decides between candidates that already
     * share parent + name - it never has to match for the row to be revived.
     *
     * Zero or more than one candidate means "cannot be sure", so nothing is
     * aliased and a normal create proceeds - the duplicates review screen
     * surfaces the pair for a human to merge.
     */
    private function tryRestoreTombstone(array $payload, string $parentItemNumber, string $name): ?array
    {
        $candidates = Item::where('parent_item_number', $parentItemNumber)
            ->whereNotNull('pos_missing_since')
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $cost = number_format($this->money($payload['cost_price'] ?? 0), 2, '.', '');
        $incomingNameKey = Item::normalizedNameKey($name);

        $matches = $candidates->filter(
            fn (Item $candidate) => Item::variationGroupKey($candidate) === $incomingNameKey.'|'.$cost
        );

        // Same parent + same normalized name is already proof it is the same
        // product, so a re-listed variation at a new cost still revives the
        // original row. Cost only narrows the choice between name matches.
        if ($matches->isEmpty()) {
            $matches = $candidates->filter(
                fn (Item $candidate) => Item::normalizedNameKey((string) $candidate->name) === $incomingNameKey
            );
        }

        // Rows hidden by an admin (e.g. merged away on the review screen)
        // must never steal a restore from the live sibling they were
        // merged into. Prefer non-hidden candidates when there is a choice.
        $preferred = $matches->reject(fn (Item $candidate) => $candidate->hidden_by_admin);
        if ($preferred->isNotEmpty()) {
            $matches = $preferred;
        }

        if ($matches->count() !== 1) {
            return null;
        }

        $item = $matches->first();

        PosItemAlias::updateOrCreate(
            ['pos_item_id' => (string) $payload['pos_item_id']],
            ['item_id' => $item->id, 'reason' => 'restored']
        );

        $item->update([
            'pos_inactive' => $this->toBool($payload['inactive'] ?? false),
            'pos_missing_since' => null,
            'is_visible_online' => ! $item->hidden_by_admin,
            'last_synced_at' => now(),
        ]);

        // Apply the fresh POS fields (a restock often re-prices) so the
        // revived row never sells at a stale price.
        $this->update($item->fresh(), $payload, true);

        return $this->ok('restored', $item->fresh());
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

        // A manual admin hide is durable: the sync must never un-hide it.
        $hidden = (bool) ($raw['hidden_by_admin'] ?? false);

        if (! $isVariation && array_key_exists('quantity', $payload)) {
            $quantity = max(0, (int) $payload['quantity']);
            if ((int) ($raw['quantity'] ?? 0) !== $quantity) {
                $data['quantity'] = $quantity;
            }

            $visible = ! $hidden && ! $inactive && $quantity > 0;
            if ((bool) ($raw['is_visible_online'] ?? true) !== $visible) {
                $data['is_visible_online'] = $visible;
            }

            $status = $this->stockStatus($quantity);
            if (($raw['stock_status'] ?? null) !== $status) {
                $data['stock_status'] = $status;
            }
        } elseif (array_key_exists('inactive', $payload)) {
            $quantity = (int) ($raw['quantity'] ?? 0);
            $visible = ! $hidden && ! $inactive && ($isVariation || $quantity > 0);
            if ((bool) ($raw['is_visible_online'] ?? true) !== $visible) {
                $data['is_visible_online'] = $visible;
            }
        }

        // An update for a tombstoned row means the POS lists it again:
        // bring it back under the same id.
        if (($raw['pos_missing_since'] ?? null) !== null) {
            $data['pos_missing_since'] = null;
            $data['is_visible_online'] = ! $hidden;
        }

        if (array_key_exists('last_modified', $payload)) {
            $lastModified = $this->parseDate($payload['last_modified']);
            if ($lastModified) {
                $data['last_modified'] = $lastModified;
            }
        }

        // The first write from the till makes the row till-owned, so the
        // ownership marker follows the source of the data instead of
        // depending on a timestamp being set somewhere else. It costs one
        // extra write per row ever; no_changes skips keep working after it.
        if (! $item->owned_by_pos) {
            $data['owned_by_pos'] = true;
        }

        if (! empty($data)) {
            $data['last_synced_at'] = now();
            $item->update($data);
        }

        $barcodesChanged = $this->syncBarcodes($item->id, $payload['barcodes'] ?? []);

        // Runs even when the payload changed nothing: a pair of live
        // siblings that predates this code would otherwise survive on
        // no-op updates forever.
        $this->retireSameNameSiblings($item);

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
            'pos_missing_since' => now(),
            'last_synced_at' => now(),
        ]);

        // A deleted parent takes its variations with it; each revives when
        // its own id (or a fingerprint match for a fresh id) comes back.
        if (! $item->isVariation()) {
            Item::where('parent_item_number', $item->pos_item_id)
                ->whereNull('pos_missing_since')
                ->update([
                    'is_visible_online' => false,
                    'pos_inactive' => true,
                    'pos_missing_since' => now(),
                    'last_synced_at' => now(),
                ]);
        }

        return $this->ok('deactivated', $item);
    }

    /**
     * Keep exactly one live row per parent + name.
     *
     * The till routinely keeps listing a variation after it minted a
     * replacement id for the same pack size, or sells the same pack under
     * two ids at once. Neither is visible from the export: both ids are
     * present, so reconcile hides nothing, and the customer is shown two
     * identical cartons and has no way to know which one is real.
     *
     * So whenever the till touches a variation, any other live sibling that
     * normalizes to the same name is tombstoned. This is the same write
     * deactivate() and reconcileCatalog() use, which means it is fully
     * reversible: the till listing that id again revives it, and the row the
     * till just touched is never a candidate.
     *
     * owned_by_pos is deliberately not consulted here, for the same reason
     * reconcile skips it on variations: a second live row with the same
     * parent and the same name is a duplicate no matter who typed it, and
     * the customer cannot tell the difference. Rows an admin already hid are
     * left exactly as they are.
     */
    private function retireSameNameSiblings(Item $item): void
    {
        if (! $item->isVariation() || ! $item->parent_item_number) {
            return;
        }

        $nameKey = Item::normalizedNameKey((string) $item->name);
        if ($nameKey === '') {
            return;
        }

        $siblings = Item::where('parent_item_number', $item->parent_item_number)
            ->where('id', '!=', $item->id)
            ->whereNull('pos_missing_since')
            ->where('is_visible_online', true)
            ->where('hidden_by_admin', false)
            ->get();

        $losers = $siblings->filter(
            fn (Item $sibling) => Item::normalizedNameKey((string) $sibling->name) === $nameKey
        );

        if ($losers->isEmpty()) {
            return;
        }

        Item::whereIn('id', $losers->pluck('id'))->update([
            'is_visible_online' => false,
            'pos_inactive' => true,
            'stock_status' => 'out_of_stock',
            'pos_missing_since' => now(),
            'last_synced_at' => now(),
        ]);

        Log::info('POS retire duplicate variation(s) under {parent}: {ids} (kept {kept})', [
            'parent' => $item->parent_item_number,
            'ids' => $losers->pluck('pos_item_id')->implode(', '),
            'kept' => $item->pos_item_id,
        ]);
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
     * Reconcile the whole online catalog against a full POS inventory
     * listing. Two rules, both driven purely by what the till says:
     *
     *  1. MISSING  - listed online, absent from this export. Hidden, because
     *     the till has either deleted it or stopped exporting it.
     *  2. INACTIVE - listed in this export but flagged inactive. Hidden,
     *     because the till still knows the item but will not sell it.
     *
     * Together they close the gap left by per-item deactivates: if the agent
     * never had an id in its snapshot (a rebuilt snapshot, or a row typed in
     * by hand on this website) no deactivate was ever sent, and the item
     * would have stayed online forever.
     *
     * Safety rails, so a partial or truncated export can never hide the
     * live catalog:
     *  - COVERAGE GATE: nothing is hidden unless this export covers at least
     *    COVERAGE_MIN of the catalog size the till last confirmed. A
     *    suddenly smaller export is only believed after the same smaller
     *    size has come back SHRINK_CONFIRMATIONS times in a row, which is
     *    what a genuine mass-deletion looks like;
     *  - CEILING: one reconcile may never hide more than MAX_HIDE_FRACTION
     *    of the items the till has confirmed. A truncated export always looks
     *    like "hide nearly everything", so it is refused every time no
     *    matter how often it repeats - this is the rail that makes the whole
     *    feature safe to run unattended;
     *  - a variation is only hidden as MISSING when its PARENT is present in
     *    the same export (if the parent is absent we simply have not seen
     *    that family yet);
     *  - a product (a row with no parent) is only hidden as MISSING when
     *    the till itself created it, i.e. it carries a sync timestamp.
     *    Products typed in by hand on this website belong to the website and
     *    are never hidden by the sync;
     *  - anything the export still lists is never treated as missing;
     *  - rows already hidden are left exactly as they are.
     *
     * Nothing is ever deleted: hidden rows keep their images, favourites and
     * order history, and a matching restock revives them.
     *
     * @return array{
     *     applied: bool, skipped: bool, reason: ?string,
     *     seen_count: int, reference_count: int,
     *     missing_products: int, missing_variations: int, inactive_hidden: int,
     *     tombstoned: int, ids: array<int, string>
     * }
     */
    public function reconcileCatalog(
        array $seenPosItemIds,
        array $inactivePosItemIds = [],
        ?Carbon $runStartedAt = null,
        bool $dryRun = false
    ): array {
        $runStartedAt = $runStartedAt ?? now();
        $seenLookup = array_fill_keys(array_map('strval', $seenPosItemIds), true);
        $inactiveLookup = array_fill_keys(array_map('strval', $inactivePosItemIds), true);

        // Coverage gate - see the docblock. Never hide anything on the
        // strength of an inventory listing that looks incomplete.
        //
        // The reference size is the catalog size the till last confirmed,
        // bootstrapped from the rows that carry a sync timestamp so the very
        // first reconcile is already protected. When an export suddenly looks
        // much smaller we do not trust it straight away: a truncated file is
        // only believed once the SAME smaller size has come back a few times
        // in a row, which is what a genuine mass-deletion looks like. That
        // keeps a real cleanup possible without ever letting one bad export
        // empty the shop.
        $referenceCount = (int) Setting::get('pos_reconcile_ref_size', 0);
        if ($referenceCount <= 0) {
            $referenceCount = (int) Item::where('owned_by_pos', true)->count();
        }

        $seenCount = count($seenLookup);
        $belowReference = $referenceCount > 0 && $seenCount < $referenceCount * self::COVERAGE_MIN;
        $smallStreak = (int) Setting::get('pos_reconcile_small_streak', 0);
        $previousSmallSize = (int) Setting::get('pos_reconcile_prev_size', 0);

        $shrinkIsReal = $belowReference
            && $smallStreak >= self::SHRINK_CONFIRMATIONS
            && $previousSmallSize > 0
            && $seenCount >= $previousSmallSize * self::COVERAGE_MIN;

        if ($belowReference && ! $shrinkIsReal) {
            if (! $dryRun) {
                Setting::set('pos_reconcile_small_streak', $smallStreak + 1, 'sync');
                Setting::set('pos_reconcile_prev_size', $seenCount, 'sync');
            }

            return $this->reconcileSkipped(sprintf(
                'export looks partial: %d id(s) vs %d the till last confirmed (waiting for %d more identical export(s) before trusting it)',
                $seenCount,
                $referenceCount,
                max(0, self::SHRINK_CONFIRMATIONS - $smallStreak)
            ), $seenCount, $referenceCount);
        }

        $candidates = Item::whereNull('pos_missing_since')
            ->where('is_visible_online', true)
            ->get(['id', 'pos_item_id', 'parent_item_number', 'owned_by_pos']);

        // The ownership marker gates products but deliberately NOT
        // variations, and the two are different questions:
        //
        //   product   - the export no longer lists this id, but nothing else
        //               proves the till ever had it. A row typed on the
        //               website that carries a till-looking id looks exactly
        //               like a deleted till item, so it needs the marker.
        //   variation - it only qualifies when its parent is sitting right
        //               there in the same export, and a variation under a
        //               live till product is till id space. Requiring the
        //               marker here would protect every hand-typed stale
        //               carton, which is the exact duplicate this exists to
        //               remove.
        $absentProducts = $candidates->filter(fn (Item $i) => $i->parent_item_number === null
            && $i->owned_by_pos
            && ! isset($seenLookup[$i->pos_item_id]));

        $absentVariations = $candidates->filter(fn (Item $i) => $i->parent_item_number !== null
            && ! isset($seenLookup[$i->pos_item_id])
            && isset($seenLookup[$i->parent_item_number]));

        $inactive = $candidates->filter(fn (Item $i) => isset($seenLookup[$i->pos_item_id])
            && isset($inactiveLookup[$i->pos_item_id]));

        // Children of a product the till dropped ride along with it, so a
        // whole family disappears together instead of leaving orphans.
        $orphanVariations = collect();
        if ($absentProducts->isNotEmpty()) {
            $goneParents = $absentProducts->pluck('pos_item_id')->all();
            $orphanVariations = Item::whereNull('pos_missing_since')
                ->whereIn('parent_item_number', $goneParents)
                ->where('is_visible_online', true)
                ->get(['id', 'pos_item_id', 'parent_item_number']);
        }

        $all = $absentProducts
            ->concat($absentVariations)
            ->concat($inactive)
            ->concat($orphanVariations)
            ->unique('id')
            ->values();

        // Ceiling - see MAX_HIDE_FRACTION. Whatever the export claims, one
        // reconcile may never take out more than a fifth of what the till has
        // confirmed. This is what makes a truncated export harmless even when
        // it keeps arriving at the same wrong size.
        $confirmedCount = max(1, (int) Item::whereNotNull('last_synced_at')->count());
        $maxHide = (int) floor($confirmedCount * self::MAX_HIDE_FRACTION);

        if ($all->count() > $maxHide) {
            return $this->reconcileSkipped(sprintf(
                'refused: this export would hide %d item(s), more than the %d allowed per run (%d confirmed by the till) - looks like a bad export, nothing changed',
                $all->count(),
                $maxHide,
                $confirmedCount
            ), $seenCount, $referenceCount);
        }

        if (! $dryRun) {
            if ($all->isNotEmpty()) {
                Item::whereIn('id', $all->pluck('id'))->update([
                    'is_visible_online' => false,
                    'pos_inactive' => true,
                    'stock_status' => 'out_of_stock',
                    'pos_missing_since' => $runStartedAt,
                ]);
            }

            Setting::set('pos_reconcile_ref_size', $seenCount, 'sync');
            Setting::set('pos_reconcile_small_streak', 0, 'sync');
            Setting::set('pos_reconcile_prev_size', 0, 'sync');
        }

        return [
            'applied' => true,
            'skipped' => false,
            'reason' => null,
            'seen_count' => $seenCount,
            'reference_count' => $referenceCount,
            'missing_products' => $absentProducts->count(),
            'missing_variations' => $absentVariations->count() + $orphanVariations->count(),
            'inactive_hidden' => $inactive->count(),
            'tombstoned' => $all->count(),
            'ids' => $all->pluck('pos_item_id')->all(),
        ];
    }

    /**
     * Uniform "nothing was changed" answer, so every refusal path reports the
     * same shape to the agent, the artisan command and the tests.
     *
     * @return array{
     *     applied: bool, skipped: bool, reason: ?string,
     *     seen_count: int, reference_count: int,
     *     missing_products: int, missing_variations: int, inactive_hidden: int,
     *     tombstoned: int, ids: array<int, string>
     * }
     */
    private function reconcileSkipped(string $reason, int $seenCount, int $referenceCount): array
    {
        return [
            'applied' => false,
            'skipped' => true,
            'reason' => $reason,
            'seen_count' => $seenCount,
            'reference_count' => $referenceCount,
            'missing_products' => 0,
            'missing_variations' => 0,
            'inactive_hidden' => 0,
            'tombstoned' => 0,
            'ids' => [],
        ];
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
