<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ZhParentNav extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'created_by', 'updated_by', 'position'];

    public function children()
    {
        return $this->hasMany(ZhChildNav::class, 'zh_parent_nav_id');
    }
}
