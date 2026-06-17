<?php

namespace App\Notifications;

use App\Models\DomainOrder;
use App\Services\Currency\MoneyFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email sent to the client when their domain order payment is confirmed.
 * Use via anonymous routing: Notification::route('mail', $email)->notify(new self($order))
 */
class DomainOrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DomainOrder $order) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $domain = $this->order->full_domain;
        $action = ucfirst($this->order->action);
        $years = $this->order->years;
        $price = app(MoneyFormatter::class)->format($this->order->retail_price, $this->order->currency);

        return (new MailMessage)
            ->subject("Your domain order for {$domain} has been received")
            ->greeting('Thank you for your order!')
            ->line("We've received your payment for the following domain order:")
            ->line("**Domain:** {$domain}")
            ->line("**Action:** {$action}")
            ->line("**Period:** {$years} year".($years > 1 ? 's' : ''))
            ->line("**Total paid:** {$price}")
            ->line("We're now processing your order. You'll receive a confirmation email once your domain is registered.")
            ->line("If you have any questions, please don't hesitate to contact us.")
            ->salutation('The WebsiteExpert Team');
    }
}
