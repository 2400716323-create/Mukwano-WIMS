<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InventoryAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public Alert $alert)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! in_array(config('mail.default'), ['log', 'array'], true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Mukwano WIMS Alert: '.$this->alert->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->alert->title)
            ->line($this->alert->description)
            ->line('Severity: '.strtoupper($this->alert->severity));

        if ($this->alert->action_url) {
            $mail->action('View Alert', $this->alert->action_url);
        }

        return $mail->line('Mukwano Warehouse Inventory Management System');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'type' => $this->alert->type,
            'severity' => $this->alert->severity,
            'title' => $this->alert->title,
            'description' => $this->alert->description,
            'action_url' => $this->alert->action_url,
        ];
    }
}
