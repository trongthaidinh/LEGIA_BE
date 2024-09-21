<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildNav extends Model
{
    use HasFactory, Sluggable, SluggableScopeHelpers;

    protected $fillable = [
        'title',
        'parent_nav_id',
        'slug',
        'createdBy',
        'updatedBy',
        'position'
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
                'source' => 'title',
                'onUpdate' => true,
            ],
        ];
    }

    protected $primaryKey = 'id';

    protected $table = 'child_navs';

    public function parentNav()
    {
        return $this->belongsTo(ParentNav::class, 'parent_nav_id');
    }

    public function children()
    {
        return $this->hasMany(ChildNavsTwo::class, 'parent_nav_id');
    }
}
