<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Item;

trait PresentsItems
{
    protected function presentItem(Item $item): array
    {
        $gallery = $item->images
            ->sortBy('sort_order')
            ->map(fn ($img) => $this->fullUrl($img->path))
            ->values()
            ->all();

        return [
            'id' => $item->id,
            'pos_item_id' => $item->pos_item_id,
            'parent_item_number' => $item->parent_item_number,
            'category_id' => $item->category_id,
            'category_name' => $item->category?->name,
            'name' => $item->name,
            'display_name' => $item->display_name,
            'slug' => $item->slug,
            'description' => $item->description,
            'selling_price' => (float) $item->selling_price,
            'cost_price' => (float) $item->cost_price,
            'discount_percentage' => (float) $item->discount_percentage,
            'discount_is_active' => (bool) $item->discount_is_active,
            'discount_price' => $item->discount_price,
            'effective_price' => $item->effectivePrice(),
            'is_discounted' => $item->isDiscounted(),
            'promo_price' => $item->promo_price !== null ? (float) $item->promo_price : null,
            'quantity' => (int) $item->quantity,
            'effective_quantity' => $item->effectiveQuantity(),
            'stock_status' => $item->stock_status,
            'is_visible_online' => (bool) $item->is_visible_online,
            'is_bulky' => (bool) $item->is_bulky,
            'is_admin_favorite' => (bool) $item->is_admin_favorite,
            'is_variation' => $item->isVariation(),
            'is_purchasable_online' => $item->isPurchasableOnline(),
            'image_url' => $this->fullUrl($item->image_url),
            'own_image_url' => $this->fullUrl($item->own_image_url),
            'gallery' => $gallery,
            'pseudo_rating' => $item->pseudoRating(),
            'order_count' => $item->order_items_count ?? null,
            'created_at' => $item->created_at?->toISOString(),
            'updated_at' => $item->updated_at?->toISOString(),
        ];
    }

    protected function presentItemCard(Item $item): array
    {
        return [
            'id' => $item->id,
            'pos_item_id' => $item->pos_item_id,
            'category_id' => $item->category_id,
            'category_name' => $item->category?->name,
            'name' => $item->name,
            'display_name' => $item->display_name,
            'slug' => $item->slug,
            'selling_price' => (float) $item->selling_price,
            'discount_percentage' => (float) $item->discount_percentage,
            'discount_price' => $item->discount_price,
            'effective_price' => $item->effectivePrice(),
            'is_discounted' => $item->isDiscounted(),
            'quantity' => (int) $item->quantity,
            'effective_quantity' => $item->effectiveQuantity(),
            'stock_status' => $item->stock_status,
            'is_bulky' => (bool) $item->is_bulky,
            'is_admin_favorite' => (bool) $item->is_admin_favorite,
            'is_variation' => $item->isVariation(),
            'is_purchasable_online' => $item->isPurchasableOnline(),
            'image_url' => $this->fullUrl($item->image_url),
            'pseudo_rating' => $item->pseudoRating(),
            'order_count' => $item->order_items_count ?? null,
        ];
    }

    protected function fullUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
