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
        $uploaderRole         = strtolower($this->uploader->role->name ?? '');
        $isUploadedByOverseas = str_contains($uploaderRole, 'overseas') || str_contains($uploaderRole, 'agent');
        $isUploadedByClient   = $this->uploaded_by === $sr->user_id || $uploaderRole === 'client';

        $isAdmin        = $user->isAdminOrFounder();
        $isOverseasUser = $user->isOverseasAgent();

        // 1. OVERSEAS UPLOADS: Strictly visible ONLY to Admins/Founders and the uploader themselves
        if ($isUploadedByOverseas) {
            return $isAdmin || $this->uploaded_by === $user->id;
        }

        // 2. OVERSEAS USER VIEWING: Cannot see Client uploads; can see Employee/Admin uploads
        if ($isOverseasUser) {
            return !$isUploadedByClient;
        }

        // 3. REGULAR USERS / STAFF / CLIENTS VIEWING:
        // Uploader can always view their own uploaded file
        if ($this->uploaded_by === $user->id) {
            return true;
        }

        // Admins/Founders can view all non-overseas attachments
        if ($isAdmin) {
            return true;
        }

        // Regular staff/employees or clients check visibility level
        return match ($this->visibility) {
            'client'   => $sr->user_id === $user->id || $user->hasPermission('view_attachments'),
            'employee' => $user->hasPermission('view_attachments') && $sr->user_id !== $user->id,
            'admin'    => false, // Only accessible if $isAdmin is true
        };
    }
}
