<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\WorkflowService;

class ServiceRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'assigned_to', 'service_type_id',
        'title', 'description', 'status',
        'current_stage', 'stage_status', 'is_rejected', 'stage_entered_at',
        'attachment_path',
        'client_country', 'destination_country', 'destination_city',
        'travel_date_start', 'travel_date_end',
        'companions_count', 'additional_notes',
    ];

    protected function casts(): array
    {
        return [
            'travel_date_start' => 'date',
            'travel_date_end'   => 'date',
            'stage_entered_at'  => 'datetime',
            'is_rejected'       => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault(['name' => 'Unknown']);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to')->withDefault();
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class)->withDefault(['name' => '—']);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class)
                    ->orderBy('scheduled_at')
                    ->orderBy('created_at');
    }

    public function stageAttachments()
    {
        return $this->hasMany(StageAttachment::class);
    }

    public function requestServices()
    {
        return $this->hasMany(RequestService::class)->with('service')
                    ->orderByRaw('scheduled_at IS NULL, scheduled_at ASC');
    }

    public function stageHistory()
    {
        return $this->hasMany(ServiceRequestStageHistory::class)
                    ->with('performer')
                    ->orderBy('created_at', 'desc');
    }

    public function comments()
    {
        return $this->hasMany(StageComment::class)
                    ->whereNull('parent_id')
                    ->with(['creator', 'replies.creator'])
                    ->orderBy('created_at', 'desc');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'subject_id')
                    ->where('subject_type', self::class);
    }

    // ── Workflow Helpers ──────────────────────────────────────────────

    public function currentStageConfig(): array
    {
        return WorkflowService::stage($this->current_stage ?? 1);
    }

    public function stageDaysElapsed(): int
    {
        return $this->stage_entered_at
            ? (int) $this->stage_entered_at->diffInDays(now())
            : 0;
    }

    public function isAtFinalStage(): bool
    {
        return $this->current_stage >= WorkflowService::stageCount();
    }

    public function isClosed(): bool
    {
        return $this->stage_status === 'Closed' || $this->is_rejected;
    }

    // ── Other Helpers ─────────────────────────────────────────────────

    public function durationDays(): ?int
    {
        if ($this->travel_date_start && $this->travel_date_end) {
            return $this->travel_date_start->diffInDays($this->travel_date_end);
        }
        return null;
    }
}
