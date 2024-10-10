<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, Sluggable, SluggableScopeHelpers;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'price',
        'original_price',
        'features',
        'images',
        'child_nav_id',
        'slug',
        'content',
        'phone_number',
    ];

    protected $casts = [
        'features' => 'array',
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

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
