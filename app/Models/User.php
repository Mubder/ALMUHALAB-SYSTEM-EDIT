<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'phone_number', 'address', 'whatsapp_number', 'notify_email', 'notify_whatsapp'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'notify_email'      => 'boolean',
            'notify_whatsapp'   => 'boolean',
        ];
    }

    public function notificationChannels(): array
    {
        $channels = ['database'];
        if ($this->notify_email && $this->email) {
            $channels[] = 'mail';
        }
        if ($this->notify_whatsapp && $this->whatsapp_number && config('services.twilio.sid')) {
            $channels[] = \App\Channels\WhatsAppChannel::class;
        }
        return $channels;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function hasPermission(string $permissionName): bool
    {
        if (!$this->role) return false;
        return $this->role->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Permissions that mark a role as staff, i.e. allowed to work on
     * service requests the user does not own. A role holding only
     * client-level permissions (create_request / view_request) is a
     * client role and gets full data isolation.
     */
    const STAFF_PERMISSIONS = [
        'edit_request', 'delete_request', 'view_trash', 'restore_request',
        'force_delete_request', 'manage_users', 'manage_followups',
        'view_audit_log', 'update_status', 'manage_services',
        'manage_service_catalog', 'manage_attachments', 'view_attachments',
        'manage_pages', 'transition_stage', 'force_transition',
        'manage_assignments', 'view_all_comments', 'view_all_requests',
    ];

    public function isStaff(): bool
    {
        if (!$this->role) return false;
        $rolePermissions = $this->role->permissions()->pluck('name')->all();
        return !empty(array_intersect($rolePermissions, self::STAFF_PERMISSIONS));
    }

    public function assignRole($role)
    {
        if ($role instanceof Role) {
            $this->role()->associate($role);
        } else {
            $r = Role::where('name', $role)->first();
            $this->role()->associate($r);
        }
        $this->save();
    }

    public function isAdminOrFounder(): bool
    {
        if ($this->hasPermission('manage_users')) {
            return true;
        }
        $rName = strtolower($this->role->name ?? '');
        return str_contains($rName, 'admin') || str_contains($rName, 'founder');
    }

    public function isFounder(): bool
    {
        return str_contains(strtolower($this->role->name ?? ''), 'founder');
    }

    public function isOverseasAgent(): bool
    {
        $rName = strtolower($this->role->name ?? '');
        return str_contains($rName, 'overseas') || str_contains($rName, 'agent');
    }

    public function isClient(): bool
    {
        // Anyone who is not staff (no workflow/manage permissions) is a client,
        // regardless of their role name — clients only ever see their own data.
        return !$this->isStaff();
    }
}
