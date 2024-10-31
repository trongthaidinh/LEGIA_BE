<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZhChildNav extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'zh_parent_nav_id',
        'slug',
        'createdBy',
        'updatedBy',
        'position'
    ];

    protected $primaryKey = 'id';

    protected $table = 'zh_child_navs';

    public function parentNav()
    {
        return $this->belongsTo(ZhParentNav::class, 'zh_parent_nav_id');
    }

    public function children()
    {
        return $this->hasMany(ZhChildNavsTwo::class, 'zh_parent_nav_id');
    }
}
