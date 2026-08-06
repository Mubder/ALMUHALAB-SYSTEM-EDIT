<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StageAttachment extends Model
{
    protected $fillable = [
        'service_request_id', 'stage', 'uploaded_by',
        'file_path', 'original_name', 'mime_type', 'size',
        'visibility',
    ];

    const VISIBILITY = [
        'admin'    => ['label' => 'Admin Only',          'color' => 'danger',  'icon' => 'bi-shield-lock-fill'],
        'employee' => ['label' => 'Internal',            'color' => 'primary', 'icon' => 'bi-people-fill'],
        'client'   => ['label' => 'Shared with Client',  'color' => 'success', 'icon' => 'bi-person-check-fill'],
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withDefault(['name' => 'System']);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function humanSize(): string
    {
        $b = $this->size ?? 0;
        if ($b < 1024)        return $b . ' B';
        if ($b < 1_048_576)   return round($b / 1024, 1) . ' KB';
        return round($b / 1_048_576, 1) . ' MB';
    }

    public function fileIcon(): string
    {
        $mime = $this->mime_type ?? '';
        if (str_contains($mime, 'pdf'))   return 'bi-file-earmark-pdf-fill text-danger';
        if (str_contains($mime, 'image')) return 'bi-file-earmark-image-fill text-info';
        if (str_contains($mime, 'word') || str_contains($mime, 'document')) return 'bi-file-earmark-word-fill text-primary';
        if (str_contains($mime, 'sheet') || str_contains($mime, 'excel'))   return 'bi-file-earmark-excel-fill text-success';
        if (str_contains($mime, 'zip') || str_contains($mime, 'compressed')) return 'bi-file-earmark-zip-fill text-warning';
        return 'bi-file-earmark-fill text-secondary';
    }

    public function visibilityConfig(): array
    {
        return self::VISIBILITY[$this->visibility] ?? self::VISIBILITY['employee'];
    }

    public function isVisibleTo(User $user, ServiceRequest $sr): bool
    {
        $uploaderRole        = strtolower($this->uploader->role->name ?? '');
        $isUploadedByOverseas = str_contains($uploaderRole, 'overseas') || str_contains($uploaderRole, 'agent');
        $isUploadedByClient   = $this->uploaded_by === $sr->user_id || $uploaderRole === 'client';

        $userRole        = strtolower($user->role->name ?? '');
        $isOverseasUser  = str_contains($userRole, 'overseas') || str_contains($userRole, 'agent');
        $isAdmin         = $user->hasPermission('manage_users') || $user->hasPermission('transition_stage');

        // Note 4: Attachments uploaded by Overseas Agent are STRICTLY visible to ADMINS & the uploader themselves ONLY
        if ($isUploadedByOverseas) {
            return $isAdmin || $this->uploaded_by === $user->id;
        }

        // Notes 2 & 3: For Overseas Agent user viewing attachments:
        // - Note 2: Attachments by User/Client are NOT exposed to Overseas Agent
        // - Note 3: Attachments by Employee/Admin are visible to Overseas Agent
        if ($isOverseasUser) {
            if ($isUploadedByClient) {
                return false;
            }
            return true; // Employee/Admin attachments are visible
        }

        if ($user->hasPermission('manage_attachments')) return true;

        return match ($this->visibility) {
            'client'   => $sr->user_id === $user->id || $user->hasPermission('view_attachments'),
            'employee' => $user->hasPermission('view_attachments'),
            'admin'    => false,
        };
    }
}
