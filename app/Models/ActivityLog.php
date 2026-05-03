<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user','action','subject_type','subject_id','changes'];

    protected $casts = [
        'changes' => 'array',
    ];
}
