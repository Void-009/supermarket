<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\PushSubscription;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, CanResetPassword, SoftDeletes, HasApiTokens;

    protected $fillable = [
        "name", "email", "password", "phone", "address", "role", "is_active",
        "profile_image", "permissions",
    ];

    protected $hidden = [
        "password", "remember_token",
    ];

    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "is_active" => "boolean",
            "permissions" => "array",
        ];
    }

    public function isSuperadmin(): bool
    {
        return $this->role === "superadmin";
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ["superadmin", "admin"]);
    }

    public function isAgent(): bool
    {
        return $this->role === "agent";
    }

    public function isCustomer(): bool
    {
        return $this->role === "customer";
    }

    public function isAdminOrAgent(): bool
    {
        return in_array($this->role, ["superadmin", "admin", "agent"]);
    }

    /**
     * Send the email verification notification using the branded template.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmail);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function processedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'processed_by');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(OrderComplaint::class);
    }

    public function complaintReplies(): HasMany
    {
        return $this->hasMany(ComplaintReply::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function getFavoriteItemIds(): array
    {
        return $this->favorites()->pluck('item_id')->toArray();
    }

    public function getPurchasedItemIds(): array
    {
        return OrderItem::whereHas('order', function ($q) {
            $q->where('user_id', $this->id)
              ->whereIn('status', ['delivered', 'completed']);
        })->pluck('item_id')->toArray();
    }

    public function getPurchasedCategoryIds(): array
    {
        return Item::whereIn('id', $this->getPurchasedItemIds())
            ->distinct()->pluck('category_id')->toArray();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        $permissions = $this->permissions ?? [];

        return $permissions[$permission] ?? false;
    }

    /**
     * Active staff who are allowed to action an order: any superadmin, plus
     * admins and agents that were granted the process_orders permission. This
     * mirrors the `can:process_orders` middleware guarding the order routes.
     */
    public function scopeCanProcessOrders(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereIn('role', ['superadmin', 'admin', 'agent'])
            ->where(function (Builder $q) {
                $q->where('role', 'superadmin')
                    ->orWhere('permissions->process_orders', true);
            });
    }

    /**
     * Recipients for order-arrival and order-changed browser notifications.
     */
    public static function orderAlertRecipients()
    {
        return static::query()->canProcessOrders()->get();
    }

    public function getDefaultPermissions(): array
    {
        return match ($this->role) {
            'superadmin' => [
                'process_orders' => true,
                'modify_items' => true,
                'manage_categories' => true,
                'manage_delivery_locations' => true,
                'manage_bank_accounts' => true,
                'manage_payment_settings' => true,
                'view_sync' => true,
                'manage_users' => true,
                'view_revenue' => true,
                'manage_complaints' => true,
                'manage_discounts' => true,
                'manage_chat' => true,
                'reverse_orders' => true,
            ],
            'admin' => [
                'process_orders' => true,
                'modify_items' => true,
                'manage_categories' => true,
                'manage_delivery_locations' => true,
                'manage_bank_accounts' => false,
                'manage_payment_settings' => true,
                'view_sync' => true,
                'manage_users' => false,
                'view_revenue' => true,
                'manage_complaints' => true,
                'manage_discounts' => true,
                'manage_chat' => true,
                'reverse_orders' => false,
            ],
            'agent' => [
                'process_orders' => true,
                'modify_items' => false,
                'manage_categories' => false,
                'manage_delivery_locations' => false,
                'manage_bank_accounts' => false,
                'manage_payment_settings' => false,
                'view_sync' => false,
                'manage_users' => false,
                'view_revenue' => false,
                'manage_complaints' => true,
                'manage_discounts' => false,
                'manage_chat' => true,
                'reverse_orders' => false,
            ],
            default => [],
        };
    }
}