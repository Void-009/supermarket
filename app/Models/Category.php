<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory, SoftDeletes;

protected $fillable = [
        "name", "slug", "description", "image",
        "is_active", "show_on_home", "sort_order", "parent_id",
    ];

    protected function casts(): array
    {
        return [
            "is_active" => "boolean",
            "show_on_home" => "boolean",
            "sort_order" => "integer",
        ];
    }

protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
        static::updating(function (Category $category) {
            if ($category->isDirty("name") && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        // Any write to a category can change which categories are part of the
        // active tree, so drop the cached id set. Without this the cached list
        // would go stale whenever categories are created, deactivated or
        // (soft-)deleted — including across requests in long-running processes.
        static::saved(function (Category $category) {
            static::$activeCategoryTreeCache = null;
        });
        static::deleted(function (Category $category) {
            static::$activeCategoryTreeCache = null;
        });
        static::restored(function (Category $category) {
            static::$activeCategoryTreeCache = null;
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, "parent_id");
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, "parent_id");
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CategoryImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Cached set of active category tree ids, invalidated on any category
     * save/delete/restore (see boot()).
     *
     * @var array<int>|null
     */
    protected static ?array $activeCategoryTreeCache = null;

    /**
     * IDs of every category that should be visible on the storefront.
     *
     * A category is excluded when any of these are true:
     *   - it is inactive (is_active = false),
     *   - it is soft-deleted (trashed),
     *   - it has no assigned category (null),
     *   - any of its ancestors is inactive or soft-deleted (cascade).
     *
     * Items filter on this set so "hide the whole category" also hides the
     * items inside it, even when those items are individually active.
     */
    public static function activeCategoryTreeIds(): array
    {
        if (static::$activeCategoryTreeCache !== null) {
            return static::$activeCategoryTreeCache;
        }

        $categories = static::withTrashed()->select(['id', 'parent_id', 'is_active', 'deleted_at'])->get();
        $byParent = $categories->groupBy('parent_id');

        $blocked = [];
        $inactive = $categories->where('is_active', false)
            ->merge($categories->whereNotNull('deleted_at'))
            ->pluck('id')
            ->values()
            ->all();

        $queue = $inactive;
        while (! empty($queue)) {
            $id = array_shift($queue);
            if (isset($blocked[$id])) {
                continue;
            }
            $blocked[$id] = true;

            foreach ($byParent->get($id, collect()) as $child) {
                if (! isset($blocked[$child->id])) {
                    $queue[] = $child->id;
                }
            }
        }

        static::$activeCategoryTreeCache = $categories
            ->reject(fn (Category $c) => isset($blocked[$c->id]))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return static::$activeCategoryTreeCache;
    }

    /**
     * Title-cased, consistently-spaced display name.
     * Fixes inconsistent casing ("padlock") and ampersand spacing ("BAGS &SACKS").
     */
    public function getTitleNameAttribute(): string
    {
        $normalized = preg_replace('/\s*&\s*/', ' & ', (string) $this->name);

        return Str::title($normalized);
    }

    /**
     * The representative image for the category (primary uploaded image,
     * otherwise the first uploaded image, otherwise the legacy image field).
     */
    public function displayImage(): ?string
    {
        if ($this->relationLoaded('images') === false) {
            $this->load('images');
        }

        $primary = $this->images->firstWhere('is_primary', true);
        $first = $this->images->first();

        $path = $primary?->path ?? $first?->path ?? $this->image;

        return $path ? asset('storage/' . $path) : null;
    }
}
