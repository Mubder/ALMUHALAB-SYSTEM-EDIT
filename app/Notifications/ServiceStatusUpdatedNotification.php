<?php

namespace App\Notifications;

use App\Models\RequestService;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Notifications\Notification;

class ServiceStatusUpdatedNotification extends Notification
{
    public function __construct(
        public ServiceRequest $serviceRequest,
        public RequestService $requestService,
        public string $oldStatus,
        public User $actor,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $cfg    = $this->requestService->statusConfig();
        $name   = $this->requestService->service->name ?? 'Service';
        $old    = RequestService::STATUSES[$this->oldStatus]['label'] ?? $this->oldStatus;
        $new    = $cfg['label'];

        return [
            'title'              => "Service status updated: {$name}",
            'message'            => "#{$this->serviceRequest->id} — {$old} → {$new}",
            'action'             => 'service_status_updated',
            'service_name'       => $name,
            'old_status'         => $this->oldStatus,
            'new_status'         => $this->requestService->status,
            'actor_name'         => $this->actor->name,
            'service_request_id' => $this->serviceRequest->id,
            'url'                => route('service-requests.show', $this->serviceRequest),
            'icon'               => $cfg['icon'],
            'color'              => $cfg['color'],
        ];
    }
}
