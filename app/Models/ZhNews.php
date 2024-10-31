<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZhNews extends Model
{
    use HasFactory;

    protected $table = 'zh_news';

    protected $fillable = [
        'title',
        'images',
        'zh_child_nav_id',
        'createdBy',
        'updatedBy',
        'summary',
        'slug',
        'content',
        'isFeatured',
    ];

    protected $casts = [
        'images' => 'array',
        'isFeatured' => 'boolean',
        'views' => 'integer',
    ];

    public function zhChildNav()
    {
        return $this->belongsTo(ZhChildNav::class, 'zh_child_nav_id');
    }
}
