<?php

namespace App\Filament\Resources\BusinessResource\Pages;

use App\Filament\Resources\BusinessResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBusiness extends ViewRecord
{
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        $record  = $this->getRecord();
        $actions = [EditAction::make()];

        if ($record->stripe_customer_id) {
            $actions[] = Action::make('stripe_customer')
                ->label('Stripe Customer')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(
                    'https://dashboard.stripe.com/customers/' . $record->stripe_customer_id,
                    shouldOpenInNewTab: true
                );
        }

        if ($record->stripe_subscription_id) {
            $actions[] = Action::make('stripe_subscription')
                ->label('Stripe Subscription')
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
