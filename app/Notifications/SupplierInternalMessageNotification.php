<?php

namespace App\Notifications;

use App\Models\SupplierMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupplierInternalMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SupplierMessage $supplierMessage
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->supplierMessage->loadMissing('supplier');

        return [
            'type' => 'supplier_internal_message',
            'supplier_message_id' => $this->supplierMessage->id,
            'supplier_id' => $this->supplierMessage->supplier_id,
            'supplier_name' => $this->supplierMessage->supplier?->name,
            'subject' => $this->supplierMessage->subject,
            'message' => $this->supplierMessage->message,
            'sender_id' => $this->supplierMessage->sender_id,
            'title' => 'New internal supplier message',
            'description' => $this->supplierMessage->subject,
            'action_url' => route(
                'suppliers.messages.index',
                $this->supplierMessage->supplier_id
            ),
        ];
    }
}
