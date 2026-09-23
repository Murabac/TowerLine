<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintAwaitingClosure extends Notification
{
    use Queueable;

    public function __construct(public Complaint $complaint) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.complaints.notify.resolved_subject', ['ref' => $this->complaint->reference_number]))
            ->line(__('app.complaints.notify.resolved_line', ['ref' => $this->complaint->reference_number]))
            ->action(__('app.complaints.notify.open'), route('complaints.show', $this->complaint));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'reference_number' => $this->complaint->reference_number,
            'event' => 'resolved',
        ];
    }
}
