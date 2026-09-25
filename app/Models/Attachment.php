<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'service_request_id', 'file_path', 'original_name',
        'file_size', 'visibility', 'required_permission',
    ];

    /**
     * visibility values: all | employee | admin
     *
     * 'admin' + required_permission set = confidential (specific permission required)
     * 'admin' + required_permission null = admin/manage_attachments only
     */
    const VISIBILITY = [
        'all' => [
            'label' => 'Public',
            'color' => 'success',
            'icon'  => 'bi-globe',
        ],
        'employee' => [
            'label' => 'Internal',
            'color' => 'primary',
            'icon'  => 'bi-people-fill',
        ],
        'admin' => [
            'label' => 'Restricted',
            'color' => 'danger',
            'icon'  => 'bi-shield-lock-fill',
        ],
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function visibilityConfig(): array
    {
        return self::VISIBILITY[$this->visibility] ?? self::VISIBILITY['employee'];
    }

    /**
     * Server-side access check — must be called before every download.
     *
     * all      → the owning client + staff (view_request)
     * employee → staff with view_attachments (never the client)
     * admin    → manage_attachments, OR the specific required_permission if set
     *
     * Clients can never see files attached to another client's request.
     */
    public function isVisibleTo(User $user): bool
    {
        if ($user->hasPermission('manage_attachments')) {
            return true;
        }

        $request = $this->serviceRequest;
        $isOwner = $request && $request->user_id === $user->id;

        return match ($this->visibility) {
            'all'      => $isOwner || ($user->isStaff() && $user->hasPermission('view_request')),
            'employee' => ! $isOwner && $user->hasPermission('view_attachments'),
            'admin'    => $this->required_permission !== null
                            ? ($user->role && $user->role->name === $this->required_permission
                                && ($isOwner || $user->isStaff()))
                            : false,
            default    => false,
        };
    }

    /**
     * Returns the guarded download URL — every download goes through
     * an authorization check, files are never exposed directly.
     */
    public function downloadUrl(): string
    {
        return route('attachments.download', $this);
    }

    public function url(): string
    {
        return $this->downloadUrl();
    }

    public function humanSize(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024)      return $bytes . ' B';
        if ($bytes < 1_048_576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1_048_576, 1) . ' MB';
    }
}
