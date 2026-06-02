<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'meta_description', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function sections()
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function visibleSections()
    {
        return $this->hasMany(PageSection::class)->where('is_visible', true)->orderBy('sort_order');
    }
}
