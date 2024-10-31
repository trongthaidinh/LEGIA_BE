<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZhChildNavsTwo extends Model
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

    protected $table = 'zh_child_navs_twos';

    public function parentNav()
    {
        return $this->belongsTo(ZhChildNav::class, 'zh_parent_nav_id');
    }
}
