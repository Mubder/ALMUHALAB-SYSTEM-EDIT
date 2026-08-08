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

    public function isOverseasAgent(): bool
    {
        $rName = strtolower($this->role->name ?? '');
        return str_contains($rName, 'overseas') || str_contains($rName, 'agent');
    }

    public function isClient(): bool
    {
        $rName = strtolower($this->role->name ?? '');
        return $rName === 'client' || (!$this->role_id && !$this->hasPermission('transition_stage'));
    }
}
