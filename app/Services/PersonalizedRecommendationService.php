<?php

namespace App\Services;

use App\Models\DiscountCampaign;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Collection;

class PersonalizedRecommendationService
{
    public function getRecommendationsForUser(User $user, int $limit = 5): Collection
    {
        $favoriteIds = $user->getFavoriteItemIds();
        $purchasedIds = $user->getPurchasedItemIds();
        $purchasedCategoryIds = $user->getPurchasedCategoryIds();

        $discountedItems = $this->getAllActiveDiscountedItems();

        if ($discountedItems->isEmpty()) {
            return collect();
        }

        $favoriteDiscounted = $discountedItems->whereIn('id', $favoriteIds);
        $purchasedDiscounted = $discountedItems->whereIn('id', $purchasedIds);
        $categoryDiscounted = $discountedItems->whereIn('category_id', $purchasedCategoryIds)
            ->whereNotIn('id', $favoriteIds)
            ->whereNotIn('id', $purchasedIds);
        $otherDiscounted = $discountedItems
            ->whereNotIn('id', $favoriteIds)
            ->whereNotIn('id', $purchasedIds)
            ->whereNotIn('id', $categoryDiscounted->pluck('id')->toArray());

        $recommendations = collect();

        $recommendations = $recommendations->merge($favoriteDiscounted);
        $recommendations = $recommendations->merge($purchasedDiscounted);

        if ($recommendations->count() < $limit) {
            $remaining = $limit - $recommendations->count();
            $recommendations = $recommendations->merge($categoryDiscounted->take($remaining));
        }

        if ($recommendations->count() < $limit) {
            $remaining = $limit - $recommendations->count();
            $recommendations = $recommendations->merge($otherDiscounted->take($remaining));
        }

        return $recommendations->take($limit)->values();
    }

    public function getRecommendationsForCampaign(DiscountCampaign $campaign, User $user, int $limit = 5): Collection
    {
        $favoriteIds = $user->getFavoriteItemIds();
        $purchasedIds = $user->getPurchasedItemIds();
        $purchasedCategoryIds = $user->getPurchasedCategoryIds();

        $campaignItems = $campaign->items()->where('is_visible_online', true)->get();

        if ($campaignItems->isEmpty()) {
            return collect();
        }

        $favoriteItems = $campaignItems->whereIn('id', $favoriteIds);
        $purchasedItems = $campaignItems->whereIn('id', $purchasedIds);
        $categoryItems = $campaignItems->whereIn('category_id', $purchasedCategoryIds)
            ->whereNotIn('id', $favoriteIds)
            ->whereNotIn('id', $purchasedIds);
        $otherItems = $campaignItems
            ->whereNotIn('id', $favoriteIds)
            ->whereNotIn('id', $purchasedIds)
            ->whereNotIn('id', $categoryItems->pluck('id')->toArray());

        $recommendations = collect();
        $recommendations = $recommendations->merge($favoriteItems);
        $recommendations = $recommendations->merge($purchasedItems);

        if ($recommendations->count() < $limit) {
            $remaining = $limit - $recommendations->count();
            $recommendations = $recommendations->merge($categoryItems->take($remaining));
        }

        if ($recommendations->count() < $limit) {
            $remaining = $limit - $recommendations->count();
            $recommendations = $recommendations->merge($otherItems->take($remaining));
        }

        return $recommendations->take($limit)->values();
    }

    public function buildPersonalizedMessage(User $user, Collection $recommendations, string $campaignName = null): array
    {
        if ($recommendations->isEmpty()) {
            return [
                'title' => '🔥 New Discounts Available!',
                'body' => 'Check out our latest deals in the shop.',
            ];
        }

        $topDiscount = $recommendations->first();
        $maxDiscount = $recommendations->max('discount_percentage');

        $itemNames = $recommendations->take(3)->pluck('display_name')->toArray();
        $itemList = implode(', ', array_slice($itemNames, 0, 2));
        if (count($itemNames) > 2) {
            $itemList .= ' and more';
        }

        if ($campaignName) {
            $title = "🔥 {$campaignName} — Up to {$maxDiscount}% off!";
        } else {
            $title = "🔥 Up to {$maxDiscount}% off items you love!";
        }

        $body = "{$itemList} — just for you! Shop now before they're gone.";

        return compact('title', 'body');
    }

    public function getAllActiveDiscountedItems(): Collection
    {
        $perItemDiscounts = Item::visible()
            ->where('discount_is_active', true)
            ->whereNotNull('discount_percentage')
            ->where(function ($q) {
                $q->whereNull('discount_starts_at')->orWhere('discount_starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('discount_ends_at')->orWhere('discount_ends_at', '>=', now());
            })
            ->get();

        $campaignItemIds = DiscountCampaign::active()
            ->with('items')
            ->get()
            ->flatMap(function ($campaign) {
                return $campaign->items->pluck('id');
            })
            ->unique();

        $campaignItems = collect();
        if ($campaignItemIds->isNotEmpty()) {
            $campaignItems = Item::visible()->whereIn('id', $campaignItemIds)->get();
        }

        return $perItemDiscounts->merge($campaignItems)->unique('id')->values();
    }

    public function getSubscribedUsersWithRecommendations(int $limit = 5): Collection
    {
        $users = User::whereHas('pushSubscriptions')->get();
        $results = collect();

        foreach ($users as $user) {
            $recommendations = $this->getRecommendationsForUser($user, $limit);
            $message = $this->buildPersonalizedMessage($user, $recommendations);

            $results->push([
                'user' => $user,
                'recommendations' => $recommendations,
                'title' => $message['title'],
                'body' => $message['body'],
            ]);
        }

        return $results;
    }
}
