<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Notifications\Notification;

class StageTransitionNotification extends Notification
{
    public function __construct(
        public ServiceRequest $serviceRequest,
        public int $fromStage,
        public int $toStage,
        public string $action,   // 'advanced' | 'returned'
        public User $actor,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $from = WorkflowService::STAGES[$this->fromStage]['label'] ?? "Stage {$this->fromStage}";
        $to   = WorkflowService::STAGES[$this->toStage]['label']   ?? "Stage {$this->toStage}";

        $title   = match ($this->action) {
            'advanced' => "Request moved to: {$to}",
            'returned' => "Request returned to: {$to}",
            default    => "Request stage changed",
        };

        return [
            'title'              => $title,
            'message'            => "#{$this->serviceRequest->id} — {$this->serviceRequest->title}",
            'action'             => $this->action,
            'from_stage'         => $this->fromStage,
            'to_stage'           => $this->toStage,
            'actor_name'         => $this->actor->name,
            'service_request_id' => $this->serviceRequest->id,
            'url'                => route('service-requests.show', $this->serviceRequest),
            'icon'               => $this->action === 'returned' ? 'bi-arrow-left-circle' : 'bi-arrow-right-circle',
            'color'              => $this->action === 'returned' ? 'warning' : 'success',
        ];
    }
}
