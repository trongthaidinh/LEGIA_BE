<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParentNav extends Model
{
    use HasFactory, Sluggable, SluggableScopeHelpers;

    protected $fillable = ['title', 'slug', 'created_by', 'updated_by', 'position'];

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

    public function children()
    {
        return $this->hasMany(ChildNav::class, 'parent_nav_id');
    }
}
