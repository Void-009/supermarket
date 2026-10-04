<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DiscountAlert;
use App\Services\PersonalizedRecommendationService;
use Illuminate\Console\Command;

class SendDiscountAlert extends Command
{
    protected $signature = 'notifications:discount-alert
                            {--campaign= : Optional campaign ID to notify about}';

    protected $description = 'Send personalized discount push notifications to subscribed users';

    public function handle(PersonalizedRecommendationService $recommendationService): int
    {
        $campaignId = $this->option('campaign');

        if ($campaignId) {
            return $this->handleCampaignNotification($recommendationService, $campaignId);
        }

        return $this->handleGeneralDiscountNotification($recommendationService);
    }

    protected function handleGeneralDiscountNotification(PersonalizedRecommendationService $recommendationService): int
    {
        $results = $recommendationService->getSubscribedUsersWithRecommendations();
        $sent = 0;

        foreach ($results as $result) {
            if ($result['recommendations']->isEmpty()) continue;

            $result['user']->notify(new DiscountAlert(
                $result['title'],
                $result['body'],
                url('/shop'),
            ));
            $sent++;
        }

        $this->info("Sent {$sent} personalized discount notification(s).");
        return Command::SUCCESS;
    }

    protected function handleCampaignNotification(
        PersonalizedRecommendationService $recommendationService,
        string $campaignId
    ): int {
        $campaign = \App\Models\DiscountCampaign::find($campaignId);
        if (!$campaign || !$campaign->isCurrentlyActive()) {
            $this->error('Campaign not found or not active.');
            return Command::FAILURE;
        }

        $users = User::whereHas('pushSubscriptions')->get();
        $sent = 0;

        foreach ($users as $user) {
            $recommendations = $recommendationService->getRecommendationsForCampaign($campaign, $user);
            if ($recommendations->isEmpty()) continue;

            $message = $recommendationService->buildPersonalizedMessage(
                $user, $recommendations, $campaign->name
            );

            $user->notify(new DiscountAlert(
                $message['title'],
                $message['body'],
                url('/shop'),
            ));
            $sent++;
        }

        $campaign->update(['notified_at' => now()]);
        $this->info("Sent {$sent} personalized notification(s) for campaign '{$campaign->name}'.");
        return Command::SUCCESS;
    }
}
