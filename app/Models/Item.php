<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        "pos_item_id", "parent_item_number", "category_id", "name", "display_name",
        "slug", "description", "cost_price", "selling_price",
        "discount_percentage", "discount_is_active", "discount_starts_at", "discount_ends_at",
        "promo_price", "promo_start_at", "promo_end_at",
        "quantity", "image_url", "is_visible_online", "is_bulky", "is_admin_favorite", "stock_status",
        "last_modified", "last_synced_at", "pos_inactive",
    ];

    /**
     * own_image_url is the image actually stored on this row (before any
     * parent fallback). It is exposed so admin editors never persist an
     * inherited image onto a variation by accident.
     */
    protected $appends = ['own_image_url'];

    protected function casts(): array
    {
        return [
            "cost_price" => "decimal:2",
            "selling_price" => "decimal:2",
            "discount_percentage" => "decimal:2",
            "discount_is_active" => "boolean",
            "discount_starts_at" => "datetime",
            "discount_ends_at" => "datetime",
            "promo_price" => "decimal:2",
            "promo_start_at" => "datetime",
            "promo_end_at" => "datetime",
            "last_modified" => "datetime",
            "last_synced_at" => "datetime",
            "pos_inactive" => "boolean",
            "is_visible_online" => "boolean",
            "is_bulky" => "boolean",
            "is_admin_favorite" => "boolean",
            "quantity" => "integer",
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Item $item) {
            if (empty($item->slug)) {
                $item->slug = Str::slug($item->name);
            }
        });
        static::updating(function (Item $item) {
            if ($item->isDirty("name") && empty($item->slug)) {
                $item->slug = Str::slug($item->name);
            }
        });
    }

public function scopeVisible(Builder $query): Builder
    {
        return $query->where("is_visible_online", true)
            ->whereIn("category_id", Category::activeCategoryTreeIds());
    }

    /**
     * Whether this item may be shown on the storefront at all.
     *
     * Requires the item to be marked visible AND its category (including the
     * whole ancestor chain) to be active / not soft-deleted. This mirrors the
     * `visible()` scope for already-loaded models.
     */
    public function visibleInStore(): bool
    {
        if (! $this->is_visible_online) {
            return false;
        }

        return in_array((int) $this->category_id, Category::activeCategoryTreeIds(), true);
    }

    public function scopeSearchable(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where("name", "like", "%{$search}%")
              ->orWhereHas("barcodes", function (Builder $bq) use ($search) {
                  $bq->where("barcode_value", "like", "%{$search}%");
              });
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ItemBarcode::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItemImage::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ItemTranslation::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->attributes['display_name'] ?? $this->attributes['name'];
    }

    /**
     * Effective image used for display. Variations automatically share their
     * parent's image unless they have their own image uploaded on the item page.
     */
    public function getImageUrlAttribute(?string $value): ?string
    {
        if (! empty($value)) {
            return $value;
        }

        if ($this->isVariation()) {
            $parent = $this->parentItem;
            if ($parent && ! empty($parent->getRawOriginal('image_url'))) {
                return $parent->getRawOriginal('image_url');
            }
        }

        return $value;
    }

    /**
     * The image stored directly on this item, ignoring the parent fallback.
     */
    public function getOwnImageUrlAttribute(): ?string
    {
        return $this->getRawOriginal('image_url');
    }

    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, "parent_item_number", "pos_item_id");
    }

    public function isVariation(): bool
    {
        return ! empty($this->parent_item_number) || str_contains((string) $this->pos_item_id, '@');
    }

    /**
     * Effective sellable stock for the online shop.
     *
     * Variation rows come from the POS export with quantity 0 (per-variation
     * stock is not reported), so their availability follows the parent: if the
     * parent is in stock every child is treated as in stock, and when the
     * parent runs out every child is treated as out of stock. Non-variations
     * use their own quantity.
     */
    public function effectiveQuantity(): int
    {
        if ($this->isVariation()) {
            $parent = $this->parentItem;
            return $parent ? (int) $parent->quantity : (int) $this->quantity;
        }

        return (int) $this->quantity;
    }

    /**
     * Whether the item can be purchased right now in the online shop.
     */
    public function isPurchasableOnline(): bool
    {
        if ($this->pos_inactive || ! $this->visibleInStore()) {
            return false;
        }

        return $this->effectiveQuantity() > 0;
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_item_number');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(Item::class, "parent_item_number", "pos_item_id");
    }

    public function favoritedBy(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function discountCampaigns(): BelongsToMany
    {
        return $this->belongsToMany(DiscountCampaign::class, 'discount_campaign_items')
            ->withTimestamps();
    }

    public function scopeDiscounted($query)
    {
        return $query->where('discount_is_active', true)
            ->whereNotNull('discount_percentage')
            ->where(function ($q) {
                $q->whereNull('discount_starts_at')->orWhere('discount_starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('discount_ends_at')->orWhere('discount_ends_at', '>=', now());
            });
    }

    public function getDiscountPriceAttribute(): ?float
    {
        if (!$this->isDiscounted()) {
            return null;
        }
        return round((float) $this->selling_price * (1 - (float) $this->discount_percentage / 100), 2);
    }

    public function isDiscounted(): bool
    {
        if (!$this->discount_is_active || !$this->discount_percentage) {
            return false;
        }
        if ($this->discount_starts_at && $this->discount_starts_at->isFuture()) return false;
        if ($this->discount_ends_at && $this->discount_ends_at->isPast()) return false;
        return true;
    }

    public function effectivePrice(): float
    {
        return (float) ($this->discount_price ?? $this->selling_price);
    }

    public function effectivePriceLabel(): string
    {
        return $this->isDiscounted() ? (string) $this->discount_price : (string) $this->selling_price;
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (!$user) return false;
        return $this->favoritedBy()->where('user_id', $user->id)->exists();
    }

    /**
     * Pseudo star rating derived from order volume (no review system).
     * Requires the 'orderItems' relation count to be loaded (withCount('orderItems')).
     */
    public function pseudoRating(): ?float
    {
        $count = (int) ($this->order_items_count ?? 0);

        return match (true) {
            $count >= 50 => 5.0,
            $count >= 20 => 4.5,
            $count >= 10 => 4.0,
            $count >= 5  => 3.5,
            $count >= 1  => 3.0,
            default      => null,
        };
    }
}
