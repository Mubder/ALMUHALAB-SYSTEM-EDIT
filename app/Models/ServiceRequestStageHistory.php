<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\WorkflowService;

class ServiceRequestStageHistory extends Model
{
    public $timestamps = false;

    protected $table = 'service_request_stage_history';

    protected $fillable = [
        'service_request_id', 'from_stage', 'to_stage',
        'stage_key', 'status', 'action', 'notes', 'performed_by',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    const ACTION_LABELS = [
        'entered'             => ['Advanced',           'bg-success'],
        'advanced'            => ['Advanced',           'bg-success'],
        'returned'            => ['Returned',           'bg-warning'],
        'status_changed'      => ['Status Updated',     'bg-primary'],
        'rejected'            => ['Rejected',           'bg-danger'],
        'request_rejected'    => ['Rejected',           'bg-danger'],
        'force_transitioned'  => ['Force Transitioned', 'bg-dark'],
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by')->withDefault(['name' => 'System']);
    }

    public function stageConfig(): array
    {
        return WorkflowService::STAGES[$this->to_stage] ?? WorkflowService::STAGES[1];
    }
}
