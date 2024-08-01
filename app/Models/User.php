<?php

namespace App\Models;

use App\Share\Pushers\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    private $userStatus;

    public function __construct() {
        $this->userStatus = new UserStatus();

    }

    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'avatar',
        'cover_image',
        'gender',
        'role',
        'phone_number',
        'address',
        'date_of_birth',
        'relationship_status',
        'status',
        'last_activity',
        'is_verified',
        'is_banned'
    ];

    protected $hidden = [
        'password',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'owner_id', 'friend_id')
                    ->wherePivot('status', '=', 'accepted');
    }

    public function isMyFriend($userId)
    {
        return $this->friends()->where('users.id', $userId)->exists();
    }

    public function postImages()
    {
        return $this->hasMany(PostImage::class);
    }

    public function isOnline()
    {
        return $this->status === 'online';
    }


    public function markOnline()
    {
        $this->last_activity = now();
        $this->status = 'online';
        $this->save();

        $this->userStatus->pusherMarkOnline($this);
    }

    public function markOffline()
    {
        $this->status = 'offline';
        $this->save();

        $this->userStatus->pusherMakeOffline($this);
    }

    public function socialLinks()
    {
        return $this->hasOne(SocialLinks::class);
    }

}
