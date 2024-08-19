<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Share\Pushers\NotificationAdded;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    private $NotificationAdded;

    public function __construct() {
        $this->NotificationAdded = new NotificationAdded();
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

            if ($user->id != $friend) {
                $notification = Notification::create([
                    'owner_id' => $friend,
                    'emitter_id' => $user->id,
                    'type' => 'friend_request',
                    'content' => "friendRequest",
                    'read' => false,
                    'icon' => 'FRIEND_REQUEST',
                    'target_id' => $user->id
                ]);

                $notification->user = [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'avatar' => $user->avatar
                ];
            }

            $this->NotificationAdded->pusherNotificationAdded($notification, $friend);

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


            $friend = $friendship->friend;

            if ($user->id != $senderId) {
                $notification = Notification::create([
                    'owner_id' => $senderId,
                    'emitter_id' => $user->id,
                    'type' => 'friend_request_accept',
                    'content' => "friendRequestAccept",
                    'read' => false,
                    'icon' => 'FRIEND_REQUEST_ACCEPT',
                    'target_id' => $user->id
                ]);

                $notification->user = [
                    'first_name' => $friend->first_name,
                    'last_name' => $friend->last_name,
                    'avatar' => $friend->avatar
                ];
            }

            $this->NotificationAdded->pusherMakeReadNotification($notification->id, $friend->id);
            $this->NotificationAdded->pusherNotificationAdded($notification, $senderId);


            return responseJson(null, 200, 'Chấp nhận lời mời kết bạn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function delete($userId) {
        try {
            $user = auth()->userOrFail();

            $validator = Validator::make([
                'userId' => $userId
            ], [
                'userId' => 'exists:users,id',
            ], [
                'userId.exists' => 'Không tìm thấy người cần hủy kết bạn!',
            ]);

            if ($validator->fails()) {
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

            if (! $friendship) {
                return responseJson(null, 400, 'Cả 2 chưa tương tác bạn bè!');
            }

            if ($friendship->status !== 'accepted') {
                $notification = Notification::where(function ($query) use ($userId, $user) {
                    $query->where('owner_id', $userId)
                          ->where('emitter_id', $user->id)
                          ->where('type', 'friend_request')
                          ->orWhere(function ($query) use ($userId, $user) {
                              $query->where('owner_id', $user->id)
                                    ->where('emitter_id', $userId)
                                    ->where('type', 'friend_request');
                          });
                })->first();

                if ($notification) {
                    $notificationId = $notification->id;
                    $notification->delete();

                    $notificationPusher = new NotificationAdded();
                    $notificationPusher->pusherNotificationDeleted($notificationId, $userId);
                }
            }

            DB::table('friendships')->where('id', $friendship->id)->delete();

            if ($friendship->status == 'accepted') {
                return responseJson(null, 200, 'Hủy kết bạn thành công!');
            }

            return responseJson(null, 200, 'Từ chối lời mời/Hủy yêu cầu kết bạn thành công!');

        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function getFriendListOfUser(Request $request, $userId){
        try{
            $user = auth()->userOrFail();

            $validator = Validator::make($request->all(), [
                'userId' => 'exists:users,id',
                'status' => 'required|in:accepted,sent,received',
            ], [
                'userId.exists' => 'Không tìm thấy người dùng!',
                'status.in' => 'Không tìm thấy trạng thái yêu cầu!',
            ]);

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $status = $request->status;
            $limit = $request->per_page;

            if ($user->id != $userId && $status != 'accepted') {
                return responseJson(null, 400, 'Không thể xem danh sách chờ kết bạn của người dùng khác!');
            }

            $friendships = [];

            if($status == 'accepted'){
                $friendships = Friendship::where(function ($query) use ($userId) {
                    $query->where('friend_id', $userId)
                          ->orWhere('owner_id', $userId);
                })
                ->where('status', 'accepted')
                ->with('partner')
                ->limit($limit)
                ->get();
            }else if ($status == 'sent'){
                $friendships = Friendship::where(function ($query) use ($userId) {
                    $query->where('owner_id', $userId);
                })
                ->where('status', 'pending')
                ->with('partner')
                ->get();

            }else if($status == 'received'){
                $friendships = Friendship::where(function ($query) use ($userId) {
                    $query->where('friend_id', $userId);
                })
                ->where('status', 'pending')
                ->with('partner')
                ->get();
            }


        if($friendships->isEmpty()){
            return responseJson(null, 404);
        }

        $data = [
            'list' => $friendships,
            'count' => $friendships->where('status', 'accepted')->count()
        ];

        return responseJson($data, 200);


        } catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function findFriends(Request $request){
        try{
            $user = auth()->userOrFail();
            $limit = $request->per_page;

            $validator = Validator::make($request->all(), [
                'q' => 'required',
            ]);

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $q = $request->q;
            $userId = $user->id;

            $friendships = Friendship::where(function ($query) use ($userId, $q) {
                $query->where('friend_id', $userId)
                      ->orWhere('owner_id', $userId);
            })
            ->where('status', 'accepted')
            ->where(function ($query) use ($q) {
                $query->whereHas('friend', function ($query) use ($q) {
                    $query->where('first_name', 'LIKE', "%{$q}%")
                          ->orWhere('last_name', 'LIKE', "%{$q}%");
                })->orWhereHas('owner', function ($query) use ($q) {
                    $query->where('first_name', 'LIKE', "%{$q}%")
                          ->orWhere('last_name', 'LIKE', "%{$q}%");
                });
            })
            ->with(['friend' => function ($query) use ($q) {
                $query->where('first_name', 'LIKE', "%{$q}%")
                      ->orWhere('last_name', 'LIKE', "%{$q}%");
            }])
            ->with(['owner' => function ($query) use ($q) {
                $query->where('first_name', 'LIKE', "%{$q}%")
                      ->orWhere('last_name', 'LIKE', "%{$q}%");
            }])
            ->limit($limit)
            ->get();


            if($friendships->isEmpty()){
                return responseJson(null, 404);
            }

            return responseJson($friendships);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

}
