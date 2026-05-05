<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Models\StageComment;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Notifications\Notification;

class CommentAddedNotification extends Notification
{
    public function __construct(
        public ServiceRequest $serviceRequest,
        public StageComment $comment,
        public User $actor,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $stageName = $this->comment->stage_number
            ? (WorkflowService::STAGES[$this->comment->stage_number]['label'] ?? 'Unknown Stage')
            : 'General';

        return [
            'title'              => "New comment on your request",
            'message'            => "\"{$this->actor->name}\" commented at {$stageName} stage",
            'preview'            => \Str::limit($this->comment->content, 80),
            'actor_name'         => $this->actor->name,
            'service_request_id' => $this->serviceRequest->id,
            'url'                => route('service-requests.show', $this->serviceRequest) . '#comments',
            'icon'               => 'bi-chat-left-text',
            'color'              => 'info',
        ];
    }
}
