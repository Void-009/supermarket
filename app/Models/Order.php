<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "user_id", "customer_name", "customer_phone", "customer_email",
        "delivery_method", "delivery_address", "delivery_location_id", "payment_method", "delivery_fee",
        "is_bulk_order", "delivery_fee_status",
        "status", "total_amount", "payment_status", "payment_reference", "bank_receipt_path",
        "notes", "processed_by",
    ];

    protected function casts(): array
    {
        return [
            "total_amount" => "decimal:2",
            "delivery_fee" => "decimal:2",
            "is_bulk_order" => "boolean",
        ];
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where("status", "new");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(OrderComplaint::class);
    }

    /**
     * Statuses in which the customer may still amend their own order.
     */
    public const CUSTOMER_EDITABLE_STATUSES = ['new', 'processing'];

    /**
     * Whether the customer is allowed to edit this order.
     *
     * The order locks as soon as it leaves the new/processing stage. It also
     * locks once an admin verifies a bank transfer, because at that point the
     * amount has been reconciled against money that actually arrived.
     *
     * Cash on delivery is deliberately exempt from the payment check: checkout
     * marks those orders verified up front (CheckoutController), so without the
     * exemption a COD order would be locked before an admin ever saw it. For
     * COD the real "admin has acted" signal is the move to `paid`, which is
     * already covered by the status check above.
     */
    public function isEditableByCustomer(): bool
    {
        if (! in_array($this->status, self::CUSTOMER_EDITABLE_STATUSES, true)) {
            return false;
        }

        if ($this->payment_status === 'verified' && $this->payment_method !== 'cash_on_delivery') {
            return false;
        }

        return true;
    }

    /**
     * Human-readable reason the order can no longer be edited, or null when it
     * is still editable. Shown to the customer so the lock is never a mystery.
     */
    public function customerEditLockReason(): ?string
    {
        if ($this->isEditableByCustomer()) {
            return null;
        }

        if (in_array($this->status, ['paid', 'shipped', 'delivered'], true)) {
            return 'This order has already been paid for and is being prepared, so it can no longer be changed.';
        }

        if ($this->status === 'cancelled') {
            return 'This order was cancelled, so it can no longer be changed.';
        }

        return 'This order\'s payment has been verified, so it can no longer be changed.';
    }
}