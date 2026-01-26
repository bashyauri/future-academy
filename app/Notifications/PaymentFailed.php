<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public $reference, public $amount)
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Payment Failed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your payment with reference ' . $this->reference . ' for ₦' . number_format($this->amount, 2) . ' was not successful.')
            ->line('Please try again or contact support if you need help.')
            ->action('Retry Payment', url('/dashboard'))
            ->line('Thank you for using our platform!');
    }
}
