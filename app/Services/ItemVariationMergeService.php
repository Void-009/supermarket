<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemBarcode;
use Illuminate\Support\Facades\DB;

/**
 * Folds one variation row into another (the survivor) when they are the
 * same product listed twice. All child records move across, unique
 * constraints are respected, and online-only fields are copied only where
 * the survivor has none. The loser is never deleted — order history may
 * still reference it (order_items has a RESTRICT foreign key) — it is
 * marked hidden_by_admin so the POS sync can never un-hide it.
 */
class ItemVariationMergeService
{
    public function merge(Item $survivor, Item $loser): void
    {
        if ($survivor->id === $loser->id) {
            throw new \InvalidArgumentException('Cannot merge an item into itself.');
        }

        DB::transaction(function () use ($survivor, $loser) {
            $this->moveBarcodes($survivor, $loser);
            $this->moveImages($survivor, $loser);
            $this->moveTranslations($survivor, $loser);
            $this->moveFavorites($survivor, $loser);
            $this->moveDiscountCampaigns($survivor, $loser);
            $this->moveCartItems($survivor, $loser);

            // order_items has no unique constraint; a plain reassign keeps
            // every past order line attached to the surviving product.
            DB::table('order_items')->where('item_id', $loser->id)->update(['item_id' => $survivor->id]);

            $this->copyOnlineOnlyFields($survivor, $loser);

            $loser->update([
                'is_visible_online' => false,
                'hidden_by_admin' => true,
            ]);
        });
    }

    /**
     * item_barcodes is unique on (item_id, barcode_value): drop the loser's
     * duplicates of values the survivor already has, then move the rest.
     */
    private function moveBarcodes(Item $survivor, Item $loser): void
    {
        $survivorValues = ItemBarcode::where('item_id', $survivor->id)->pluck('barcode_value')->all();

        if ($survivorValues !== []) {
            ItemBarcode::where('item_id', $loser->id)
                ->whereIn('barcode_value', $survivorValues)
                ->delete();
        }

        ItemBarcode::where('item_id', $loser->id)->update(['item_id' => $survivor->id]);
    }

    private function moveImages(Item $survivor, Item $loser): void
    {
        DB::table('item_images')->where('item_id', $loser->id)->update(['item_id' => $survivor->id]);
    }

    /**
     * item_translations is unique on (item_id, locale): the survivor's own
     * wording wins, the loser's colliding locales are dropped.
     */
    private function moveTranslations(Item $survivor, Item $loser): void
    {
        $survivorLocales = DB::table('item_translations')->where('item_id', $survivor->id)->pluck('locale')->all();

        if ($survivorLocales !== []) {
            DB::table('item_translations')
                ->where('item_id', $loser->id)
                ->whereIn('locale', $survivorLocales)
                ->delete();
        }

        DB::table('item_translations')->where('item_id', $loser->id)->update(['item_id' => $survivor->id]);
    }

    /**
     * favorites is unique on (user_id, item_id): a customer who favourited
     * both keeps a single favourite on the survivor.
     */
    private function moveFavorites(Item $survivor, Item $loser): void
    {
        $survivorUserIds = DB::table('favorites')->where('item_id', $survivor->id)->pluck('user_id')->all();

        if ($survivorUserIds !== []) {
            DB::table('favorites')
                ->where('item_id', $loser->id)
                ->whereIn('user_id', $survivorUserIds)
                ->delete();
        }

        DB::table('favorites')->where('item_id', $loser->id)->update(['item_id' => $survivor->id]);
    }

    /**
     * discount_campaign_items is unique on (discount_campaign_id, item_id).
     */
    private function moveDiscountCampaigns(Item $survivor, Item $loser): void
    {
        $survivorCampaignIds = DB::table('discount_campaign_items')->where('item_id', $survivor->id)->pluck('discount_campaign_id')->all();

        if ($survivorCampaignIds !== []) {
            DB::table('discount_campaign_items')
                ->where('item_id', $loser->id)
                ->whereIn('discount_campaign_id', $survivorCampaignIds)
                ->delete();
        }

        DB::table('discount_campaign_items')->where('item_id', $loser->id)->update(['item_id' => $survivor->id]);
    }

    /**
     * cart_items has no unique constraint: reassign, then fold duplicate
     * lines for the same customer/session into one summed line.
     */
    private function moveCartItems(Item $survivor, Item $loser): void
    {
        $moved = DB::table('cart_items')->where('item_id', $loser->id)->get();

        foreach ($moved as $row) {
            $existing = DB::table('cart_items')
                ->where('item_id', $survivor->id)
                ->where('user_id', $row->user_id)
                ->where('session_id', $row->session_id)
                ->first();

            if ($existing) {
                DB::table('cart_items')->where('id', $existing->id)
                    ->update(['quantity' => $existing->quantity + $row->quantity]);
                DB::table('cart_items')->where('id', $row->id)->delete();
            } else {
                DB::table('cart_items')->where('id', $row->id)
                    ->update(['item_id' => $survivor->id]);
            }
        }
    }

    /**
     * The survivor keeps whatever it already has; only genuinely missing
     * online-only fields are filled from the loser.
     */
    private function copyOnlineOnlyFields(Item $survivor, Item $loser): void
    {
        $fill = [];

        if (empty($survivor->getRawOriginal('image_url')) && ! empty($loser->getRawOriginal('image_url'))) {
            $fill['image_url'] = $loser->getRawOriginal('image_url');
        }

        if (empty($survivor->description) && ! empty($loser->description)) {
            $fill['description'] = $loser->description;
        }

        foreach (['is_admin_favorite', 'is_bulky'] as $flag) {
            if (! $survivor->{$flag} && $loser->{$flag}) {
                $fill[$flag] = true;
            }
        }

        if ($fill !== []) {
            $survivor->update($fill);
        }
    }
}
