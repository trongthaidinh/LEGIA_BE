<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use Sluggable, SluggableScopeHelpers;

    protected $fillable = [
        'name',
        'images',
        'child_nav_id',
        'created_by',
        'updated_by',
        'summary',
        'slug',
        'content',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => true,
            ],
        ];
    }

    public function parentNav()
    {
        return $this->belongsTo(ParentNav::class);
    }

    public function childNav()
    {
        return $this->belongsTo(ChildNav::class);
    }
}
