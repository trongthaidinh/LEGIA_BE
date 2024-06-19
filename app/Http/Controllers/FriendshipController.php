<?php

namespace App\Http\Controllers;

use App\Events\FriendRequestAccepted;
use App\Events\FriendRequestSent;
use App\Models\Friendship;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Query\Builder;

class FriendshipController extends Controller
{
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

            event(new FriendRequestSent($friendship));

            return responseJson($friendship, 201, 'Gửi lời mời kết bạn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function accept($id) {
        try{
            $user = auth()->userOrFail();

            $validator = Validator::make([
                'id' => $id
            ], [
                'id' => 'exists:friendships,id',
            ], [
                'id.exists' => 'Không tìm thấy lời mời kết bạn!',
            ]);


            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $isFriend = DB::table('friendships')
                ->where('id', '=', $id)
                ->where('status', 'accepted')
                ->where(function (Builder $query) use ($user) {
                    $query->orWhere('friend_id', $user->id)
                          ->orWhere('owner_id', $user->id);
                })
                ->first();

            if($isFriend){
                return responseJson(null, 400, 'Cả hai đã là bạn bè rồi!');
            }

            $update = DB::table('friendships')
                ->where('id', $id)
                ->where('friend_id', $user->id)
                ->update(['status' => 'accepted']);


            if(!$update){
                return responseJson(null, 400, 'Đã xảy ra lỗi, vui lòng thử lại!');
            }

            $friendship = Friendship::find($id);

            event(new FriendRequestAccepted($friendship));

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

    public function getFriendRequestStatus($userId){
        try{
            $user = auth()->userOrFail();


            $friendship = Friendship::where(function ($query) use ($user, $userId) {
                $query->where(function ($query) use ($user, $userId) {
                    $query->where('friend_id', $user->id)
                          ->where('owner_id', $userId);
                })->orWhere(function ($query) use ($user, $userId) {
                    $query->where('friend_id', $userId)
                          ->where('owner_id', $user->id);
                });
            })->first('status');

            if(!$friendship){
                return responseJson(null, 404, 'Chưa có tương tác bạn bè với người dùng này');
            }

            return responseJson($friendship, 200);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

}
