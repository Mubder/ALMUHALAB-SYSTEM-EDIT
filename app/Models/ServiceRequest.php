<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = ['title','description','attachment_path','status'];

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'subject_id')
                    ->where('subject_type', self::class);
    }
}
