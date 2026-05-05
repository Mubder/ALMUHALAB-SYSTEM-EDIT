<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    protected $fillable = [
        'service_request_id', 'title', 'description', 'status_type',
        'scheduled_at', 'is_completed', 'completed_at',
        'created_by', 'is_visible_to_client', 'extra_data',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'        => 'datetime',
            'completed_at'        => 'datetime',
            'is_completed'        => 'boolean',
            'is_visible_to_client'=> 'boolean',
            'extra_data'          => 'array',
        ];
    }

    public static function statusTypes(): array
    {
        return MilestoneType::asOptions();
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(['name' => 'System']);
    }

    public function statusConfig(): array
    {
        $types = static::statusTypes();
        return $types[$this->status_type]
            ?? ['label' => ucfirst($this->status_type ?? ''), 'icon' => 'bi-circle', 'color' => 'secondary'];
    }

    public function isPast(): bool
    {
        return $this->is_completed;
    }

    public function isFuture(): bool
    {
        return ! $this->is_completed;
    }
}
