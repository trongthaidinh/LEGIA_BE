<?php

namespace App\Http\Controllers;

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
                ->where('owner', $user->id)
                ->where('status', 'accepted')
                ->orWhere(function (Builder $query) use ($user) {
                    $query->where('friend', $user->id)
                          ->where('owner', $user->id);
                })
                ->get();

            if($friendships->isEmpty()){
                return responseJson(null, 404, 'Bạn không có bạn bè :(');
            }

            return responseJson($friendships);

        }catch(\Tymon\JWTAuth\Exceptions\TokenExpiredException $e){
            return responseJson(null, 404, 'Không tìm thấy người dùng!');
        }
    }

    public function getPendingList() {
        try{
            $user = auth()->userOrFail();

            $friendships = DB::table('friendships')
                ->where('owner', $user->id)
                ->where('friend', $user->id)
                ->where('status', 'pending')
                ->get();

            if($friendships->isEmpty()){
                return responseJson(null, 404, 'Lời mời kết bạn trống');
            }

            return responseJson($friendships);

        }catch(\Tymon\JWTAuth\Exceptions\TokenExpiredException $e){
            return responseJson(null, 404, 'Không tìm thấy người dùng!');
        }
    }

    public function add($friend) {
        try{
            $user = auth()->userOrFail();

            if($friend == $user->id){
                return responseJson(null, 400, 'Không thể kết bạn với chính mình!');
            }

            $validator = Validator::make([
                'friend' => $friend
            ], [
                'friend' => 'required|exists:users,id',
            ], userValidatorMessages());

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $isFriend = DB::table('friendships')
                ->where('owner', $user->id)
                ->where('friend', $friend)
                ->where('status', 'accepted')
                ->first();

            if($isFriend){
                return responseJson(null, 400, 'Cả 2 đã là bạn bè từ trước!');
            }

            $isSent = DB::table('friendships')
                ->where('owner', $user->id)
                ->where('friend', $friend)
                ->where('status', 'pending')
                ->first();

            if($isSent){
                return responseJson(null, 400, 'Đã gửi lời mời kết bạn từ trước!');
            }

            $friendship = Friendship::create(array_merge(
                $validator->validated(),
                ['owner' => $user->id],
                ['friend' => (int)$friend],
            ));

            return responseJson($friendship, 201, 'Gửi lời mời kết bạn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\TokenExpiredException $e){
            return responseJson(null, 404, 'Không tìm thấy người dùng!');
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
                ->orWhere(function (Builder $query) use ($user) {
                    $query->where('friend', $user->id)
                          ->where('owner', $user->id);
                })
                ->first();

            if($isFriend){
                return responseJson(null, 400, 'Cả hai đã là bạn bè rồi!');
            }

            $friendship = DB::table('friendships')
                ->where('id', $id)
                ->where('friend', $user->id)
                ->update(['status' => 'accepted']);


            return responseJson($friendship, 200, 'Chấp nhận lời mời kết bạn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\TokenExpiredException $e){
            return responseJson(null, 404, 'Không tìm thấy người dùng!');
        }
    }

    public function delete($id) {
        try{
            $user = auth()->userOrFail();

            $validator = Validator::make([
                'id' => $id
            ], [
                'id' => 'exists:friendships,id',
            ], [
                'id.exists' => 'Không tìm thấy lời mời kết bạn hoặc hai bạn chưa thành bạn bè!',
            ]);

            if($validator->fails()){
                return responseJson(null, 400, $validator->errors());
            }

            $friendship = DB::table('friendships')
                ->where('id', $id)
                ->orWhere(function (Builder $query) use ($user) {
                    $query->where('friend', $user->id)
                          ->where('owner', $user->id);
                })
                ->first();


            if ($friendship->status == 'accepted') {
                $deleted = DB::table('friendships')->where('id', $id)->delete();

                return responseJson($deleted, 200, 'Hủy kết bạn thành công!');
            }


            $deleted = DB::table('friendships')->where('id', $id)->delete();

            return responseJson($deleted, 200, 'Từ chối lời mời kết bạn thành công!');

        }catch(\Tymon\JWTAuth\Exceptions\TokenExpiredException $e){
            return responseJson(null, 404, 'Không tìm thấy người dùng!');
        }
    }

}
