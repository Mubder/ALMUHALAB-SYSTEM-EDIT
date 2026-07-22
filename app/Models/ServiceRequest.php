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
        'request_number', 'display_number',
        'client_name', 'client_phone_code', 'client_phone', 'client_email',
        'title', 'description', 'status',
        'current_stage', 'stage_status', 'is_rejected', 'stage_entered_at',
        'attachment_path',
        'client_country', 'destination_country', 'destination_city',
        'travel_date_start', 'travel_date_end',
        'companions_count', 'companions_data', 'additional_notes',
    ];

    protected function casts(): array
    {
        return [
            'travel_date_start' => 'date',
            'travel_date_end'   => 'date',
            'stage_entered_at'  => 'datetime',
            'is_rejected'       => 'boolean',
            'companions_data'   => 'array',
        ];
    }

    protected static function booted()
    {
        static::created(function ($sr) {
            try {
                $webhookUrl = env('KCA_WEBHOOK_URL', 'https://app.kazma.ai/api/v1/integrations/almuhalab/webhook');
                $secret = env('KCA_BRIDGE_TOKEN');
                if ($webhookUrl && $secret) {
                    $client = new \GuzzleHttp\Client();
                    $client->postAsync($webhookUrl, [
                        'headers' => [
                            'Authorization' => "Bearer {$secret}",
                            'Accept'        => 'application/json',
                        ],
                        'json' => [
                            'event' => 'request.created',
                            'data'  => [
                                'id' => $sr->id,
                                'display_number' => $sr->display_number,
                                'title' => $sr->title,
                                'description' => $sr->description,
                                'client_name' => $sr->client_name,
                                'client_phone' => $sr->client_phone,
                                'client_email' => $sr->client_email,
                                'current_stage' => $sr->current_stage,
                                'status' => $sr->status,
                            ],
                            'timestamp' => now()->toIso8601String()
                        ]
                    ]);
                }
            } catch (\Throwable $e) {
                // Ignore fallback
            }
        });
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

    public function fieldVisibilities()
    {
        return $this->hasMany(RequestFieldVisibility::class);
    }

    /**
     * Returns a keyed map of field_name → visibility rule.
     * Fields with no rule default to 'all' (visible to everyone).
     */
    public function fieldVisibilityMap(): array
    {
        return $this->fieldVisibilities->keyBy('field_name')->toArray();
    }

    /**
     * Check whether a specific field is visible to the given user.
     * Admin with edit_request always sees everything.
     */
    public function isFieldVisibleTo(string $field, User $user, array $map = []): bool
    {
        if ($user->hasPermission('edit_request')) {
            return true;
        }

        $rule = $map[$field] ?? null;

        if (! $rule) {
            return true; // no rule = visible to all
        }

        return match ($rule['visibility']) {
            'all'      => true,
            'employee' => $user->hasPermission('view_request') || $user->hasPermission('transition_stage'),
            'admin'    => isset($rule['required_permission']) && $rule['required_permission']
                            ? ($user->role && $user->role->name === $rule['required_permission'])
                            : false,
            default    => true,
        };
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
