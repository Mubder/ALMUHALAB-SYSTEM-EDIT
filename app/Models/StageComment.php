<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StageComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_request_id', 'stage_number', 'parent_id',
        'content', 'visibility', 'is_edited', 'edited_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_edited' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    const VISIBILITY = [
        'all'      => ['label' => 'Client Visible',   'icon' => 'bi-eye',       'color' => 'success'],
        'employee' => ['label' => 'Employees Only',   'icon' => 'bi-person-badge','color' => 'primary'],
        'admin'    => ['label' => 'Admin Only',       'icon' => 'bi-shield-lock','color' => 'danger'],
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(['name' => 'Deleted User']);
    }

    public function parent()
    {
        return $this->belongsTo(StageComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(StageComment::class, 'parent_id')
                    ->with('creator')
                    ->orderBy('created_at');
    }

    public function visibilityConfig(): array
    {
        return self::VISIBILITY[$this->visibility] ?? self::VISIBILITY['all'];
    }

    public function isVisibleTo(User $user): bool
    {
        // Creator can ALWAYS view their own posted comment/note!
        if ($this->created_by === $user->id) {
            return true;
        }

        $creatorRole         = strtolower($this->creator->role->name ?? '');
        $isCreatedByOverseas = str_contains($creatorRole, 'overseas') || str_contains($creatorRole, 'agent');
        $isAdmin             = $user->isAdminOrFounder();
        $isOverseasUser      = $user->isOverseasAgent();

        // 1. Comments/notes created by Overseas Agent:
        // STRICTLY visible ONLY to Founders and the creator themselves — Admins cannot see them
        if ($isCreatedByOverseas) {
            return $user->isFounder();
        }

        // 2. Overseas Agent viewing comments:
        // CANNOT read staff or client notes — only his own notes (creator rule above).
        // His permitted scope is the translated documents (staff attachments) plus
        // writing his own Founder-Only note.
        if ($isOverseasUser) {
            return false;
        }

        // 3. Regular users viewing comments:
        if ($this->visibility === 'all') return true;
        if ($this->visibility === 'employee') return $user->hasPermission('transition_stage') || $isAdmin;
        if ($this->visibility === 'admin')    return $isAdmin;
        return false;
    }
}
