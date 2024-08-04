<?php

namespace App\Http\Controllers;

use App\Models\UsersSearchRecent;
use Illuminate\Support\Facades\Validator;


class UsersSearchRecentController extends Controller
{
    public function create()
    {
        try{
            $user = auth()->userOrFail();


            $validator = Validator::make(request()->only(['ref_id']), [
                "ref_id" => "required|exists:users,id",
            ],
            [
                "ref_id.exists" => "Không tìm thấy người dùng!",
                "ref_id.required" => "Vui lòng chọn người dùng!",
            ]);

            if ($validator->fails()) {
                return responseJson(null, 400, $validator->errors());
            }

            $userSearchExisted = UsersSearchRecent::where('user_id', $user->id)
            ->where('ref_id', $validator->validated()['ref_id'])
            ->first();


            if($userSearchExisted){
                return responseJson(null, 400, 'Người này đã có trong danh sách tìm kiếm gần đây');
            }


            $recentCount = UsersSearchRecent::where('user_id', $user->id)->count();

            if ($recentCount >= 5) {
                UsersSearchRecent::where('user_id', $user->id)
                    ->orderBy('created_at', 'asc')
                    ->first()
                    ->delete();
            }

            $res = UsersSearchRecent::create(array_merge(
                $validator->validated(),
                ['user_id' => $user->id]
            ));

            $userSearch = UsersSearchRecent::with("ref")
            ->where('user_id', $res->user_id)
            ->where('ref_id', $res->ref_id)
            ->first();


            return responseJson($userSearch, 201);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }
    }

    public function get(){
        try{
            $user = auth()->userOrFail();


            $res = UsersSearchRecent::where('user_id', $user->id)
            ->with("ref")
            ->get();

            return responseJson($res, 200);

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, 'Người dùng chưa xác thực!');
        }

    }

}
