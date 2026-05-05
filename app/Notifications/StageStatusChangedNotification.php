<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Notifications\Notification;

class StageStatusChangedNotification extends Notification
{
    public function __construct(
        public ServiceRequest $serviceRequest,
        public string $fromStatus,
        public string $toStatus,
        public User $actor,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $isRejected = $this->toStatus === 'Rejected';

        return [
            'title'              => $isRejected
                ? "Request rejected: {$this->serviceRequest->title}"
                : "Status updated to: {$this->toStatus}",
            'message'            => "#{$this->serviceRequest->id} — {$this->serviceRequest->title}",
            'from_status'        => $this->fromStatus,
            'to_status'          => $this->toStatus,
            'actor_name'         => $this->actor->name,
            'service_request_id' => $this->serviceRequest->id,
            'url'                => route('service-requests.show', $this->serviceRequest),
            'icon'               => $isRejected ? 'bi-x-circle' : 'bi-arrow-repeat',
            'color'              => $isRejected ? 'danger' : 'primary',
        ];
    }
}
