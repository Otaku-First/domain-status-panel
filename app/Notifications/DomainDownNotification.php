<?php

namespace App\Notifications;

use App\Models\Domain;
use App\Models\DomainCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DomainDownNotification extends Notification implements ShouldQueue
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
        $message = (new MailMessage)
            ->error()
            ->subject("Domain Down: {$this->domain->hostname}")
            ->greeting('Domain Alert!')
            ->line("Your domain **{$this->domain->hostname}** is currently unreachable.")
            ->line("**Status:** {$this->check->result->label()}")
            ->line("**Checked at:** {$this->check->checked_at->format('Y-m-d H:i:s')} UTC");

        if ($this->check->error_message) {
            $message->line("**Error:** {$this->check->error_message}");
        }

        if ($this->check->response_code) {
            $message->line("**Response Code:** {$this->check->response_code}");
        }

        return $message
            ->action('View Dashboard', url('/dashboard'))
            ->line('We will notify you when the domain is back online.');
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