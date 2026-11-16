<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        $actions = [];

        if ($record->stripe_subscription_id) {
            $actions[] = Action::make('stripe_dashboard')
                ->label('View in Stripe')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(
                    'https://dashboard.stripe.com/subscriptions/' . $record->stripe_subscription_id,
                    shouldOpenInNewTab: true
                );
        }

        return $actions;
    }
}
