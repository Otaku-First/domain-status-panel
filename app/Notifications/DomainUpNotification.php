<?php

namespace App\Notifications;

use App\Models\Domain;
use App\Models\DomainCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DomainUpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Domain $domain,
        public DomainCheck $check,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->success()
            ->subject("Domain Recovered: {$this->domain->hostname}")
            ->greeting('Good News!')
            ->line("Your domain **{$this->domain->hostname}** is back online.")
            ->line("**Response Code:** {$this->check->response_code}")
            ->line("**Response Time:** {$this->check->response_time_ms}ms")
            ->line("**Recovered at:** {$this->check->checked_at->format('Y-m-d H:i:s')} UTC")
            ->action('View Dashboard', url('/dashboard'))
            ->line('Monitoring will continue as scheduled.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'domain_id' => $this->domain->id,
            'hostname' => $this->domain->hostname,
            'check_id' => $this->check->id,
            'result' => $this->check->result->value,
        ];
    }
}