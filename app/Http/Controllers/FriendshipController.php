<?php

namespace App\Http\Controllers;

use App\Events\FriendRequestAccepted;
use App\Events\FriendRequestSent;
use App\Models\Friendship;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Query\Builder;
use App\Share\Pushers\NotificationAdded;

class FriendshipController extends Controller
{
    private $NotificationAdded;

    public function __construct() {
        $this->NotificationAdded = new NotificationAdded();
    }


    public function getAcceptedList() {
        try{
            $user = auth()->userOrFail();

            $friendships = DB::table('friendships')
                ->where('status', 'accepted')
                ->join('users', function ($join) use ($user) {
                    $join->on('users.id', '=', DB::raw('(CASE WHEN friendships.friend_id= '.$user->id.' THEN  friendships.owner_id ELSE friendships.friend_idEND)'));
                })
                ->where(function ($query) use ($user) {
                    $query->orWhere('friendships.owner_id', $user->id)
                          ->orWhere('friendships.friend_id', $user->id);
                })
                ->select('friendships.*', 'users.avatar', 'users.last_name', 'users.first_name', 'users.id')
                ->get()
                ->map(function($friendship) use ($user) {
                    if($friendship->owner == $user->id) {
                        $friendship->friend_info = (object) [
                            'id' => $friendship->friend,
                            'first_name' => $friendship->first_name,
                            'last_name' => $friendship->last_name,
                            'avatar' => $friendship->avatar,
                        ];
                    } else if($friendship->friend == $user->id) {
                        $friendship->owner_info = (object) [
                            'id' => $friendship->owner,
                            'first_name' => $friendship->first_name,
                            'last_name' => $friendship->last_name,
                            'avatar' => $friendship->avatar,
                        ];
                    }
                    unset($friendship->first_name);
                    unset($friendship->last_name);
                    unset($friendship->avatar);
                    return $friendship;
                });


            if($friendships->isEmpty()){
                return responseJson(null, 404, 'Bạn không có bạn bè :(');
            }

            return responseJson($friendships);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function getPendingList() {
        try{
            $user = auth()->userOrFail();

            $friendships = DB::table('friendships')
                ->where('status', 'pending')
                ->join('users', function ($join) use ($user) {
                    $join->on('users.id', '=', DB::raw('(CASE WHEN friendships.friend_id = '.$user->id.' THEN  friendships.owner_id ELSE friendships.friend_id END)'));
                })
                ->where(function ($query) use ($user) {
                    $query->orWhere('friendships.owner_id', $user->id)
                          ->orWhere('friendships.friend_id', $user->id);
                })
                ->select('friendships.*', 'users.avatar', 'users.last_name', 'users.first_name', 'users.id')
                ->get()
                ->map(function($friendship) use ($user) {
                    if($friendship->owner == $user->id) {
                        $friendship->friend_info = (object) [
                            'id' => $friendship->friend,
                            'first_name' => $friendship->first_name,
                            'last_name' => $friendship->last_name,
                            'avatar' => $friendship->avatar,
                        ];
                    } else if($friendship->friend == $user->id) {
                        $friendship->owner_info = (object) [
                            'id' => $friendship->owner,
                            'first_name' => $friendship->first_name,
                            'last_name' => $friendship->last_name,
                            'avatar' => $friendship->avatar,
                        ];
                    }
                    unset($friendship->first_name);
                    unset($friendship->last_name);
                    unset($friendship->avatar);
                    return $friendship;
                });

            if($friendships->isEmpty()){
                return responseJson(null, 404, 'Lời mời kết bạn trống');
            }

            return responseJson($friendships);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function add($friend) {
        try{
            $user = auth()->userOrFail();

            if($friend == $user->id){
                return responseJson(null, 400, 'Không thể kết bạn với chính mình!');
            }

            $validator = Validator::make([
                'friend_id' => $friend
            ], [
                'friend_id' => 'required|exists:users,id',
            ], userValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $isFriend = DB::table('friendships')
                ->where('owner_id', $user->id)
                ->where('friend_id', $friend)
                ->where('status', 'accepted')
                ->first();

            if($isFriend){
                return responseJson(null, 400, 'Cả 2 đã là bạn bè từ trước!');
            }

            $isSent = DB::table('friendships')
                ->where('owner_id', $user->id)
                ->where('friend_id', $friend)
                ->where('status', 'pending')
                ->first();

            if($isSent){
                return responseJson(null, 400, 'Đã gửi lời mời kết bạn từ trước!');
            }

            $friendship = Friendship::create(array_merge(
                $validator->validated(),
                ['owner_id' => $user->id],
                ['friend_id' => (int)$friend],
            ));

            $notification = Notification::create([
                'owner_id' => $friend,
                'emitter_id' => $user->id,
                'type' => 'friend_request',
                'content' => "đã gửi cho bạn lời mời kết bạn.",
                'read' => false,
            ]);

            $notification->user = [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar' => $user->avatar
            ];

            $this->NotificationAdded->pusherNotificationAdded($notification, $user->id);

            return responseJson($friendship, 201, 'Gửi lời mời kết bạn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function accept($senderId) {
        try{
            $user = auth()->userOrFail();

            $validator = Validator::make([
                'senderId' => $senderId
            ], [
                'senderId' => 'exists:users,id',
            ], [
                'senderId.exists' => 'Không tìm thấy người cần hủy kết bạn!',
            ]);

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $friendship = Friendship::where('friend_id', $user->id)
                ->where('owner_id', $senderId)
                ->first();

            if(!$friendship){
                return responseJson(null, 404);
            }

            if($friendship->status == 'accepted'){
                return responseJson(null, 400, 'Cả 2 đã là bạn bè rồi!');
            }

            $friendship->update(['status' => 'accepted']);


            $friend = $friendship->friends;

            $notification = Notification::create([
                'owner_id' => $user->id,
                'emitter_id' => $senderId,
                'type' => 'friend_request_accept',
                'content' => "đã chấp nhận lời mời kết bạn.",
                'read' => false,
            ]);

            $notification->user = [
                'first_name' => $friend->first_name,
                'last_name' => $friend->last_name,
                'avatar' => $friend->avatar
            ];

            $this->NotificationAdded->pusherNotificationAdded($notification, $user->id);


            return responseJson(null, 200, 'Chấp nhận lời mời kết bạn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function delete($userId) {
        try{
            $user = auth()->userOrFail();

            $validator = Validator::make([
                'userId' => $userId
            ], [
                'userId' => 'exists:users,id',
            ], [
                'userId.exists' => 'Không tìm thấy người cần hủy kết bạn!',
            ]);

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $friendship = Friendship::where(function ($query) use ($userId, $user) {
                    $query->where(function ($query) use ($userId, $user) {
                        $query->where('friend_id', $userId)
                              ->where('owner_id', $user->id);
                    })
                    ->orWhere(function ($query) use ($userId, $user) {
                        $query->where('friend_id', $user->id)
                              ->where('owner_id', $userId);
                    });
                })
                ->first();

            if(! $friendship){
                return responseJson(null, 400, 'Cả 2 chưa tương tác bạn bè!');

            }


            if ($friendship->status == 'accepted') {
                DB::table('friendships')->where('id', $friendship->id)->delete();

                return responseJson(null, 200, 'Hủy kết bạn thành công!');
            }


            DB::table('friendships')->where('id', $friendship->id)->delete();

            return responseJson(null, 200, 'Từ chối lời mời kết bạn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function getFriendListOfUser($userId){
        try{

            $friendships = Friendship::where(function ($query) use ($userId) {
                $query->where('friend_id', $userId)
                      ->orWhere('owner_id', $userId);
            })
            ->where('status', 'accepted')
            ->with(['friend' => function ($query) use ($userId) {
                $query->where('id', '!=', $userId);
            }])
            ->with(['owner' => function ($query) use ($userId) {
                $query->where('id', '!=', $userId);
            }])
            ->limit(9)
            ->get();

            $friendships->transform(function ($friendship) {
                $friendship->user_info = $friendship->friend ? $friendship->friend : $friendship->owner;
                unset($friendship->friend);
                unset($friendship->owner);
                return $friendship;
            });


        if($friendships->isEmpty()){
            return responseJson(null, 404, 'Người dùng chưa có bạn bè');
        }

        $data = [
            'list' => $friendships,
            'count' => $friendships->count()
        ];

        return responseJson($data, 200);


        } catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }





}
