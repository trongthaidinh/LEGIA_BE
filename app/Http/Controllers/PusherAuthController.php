<?php

namespace App\Http\Controllers;


class PusherAuthController extends Controller
{
    public function presenceAuth($userId)
    {
        try {
            $user = auth()->userOrFail();

            return $user->id == $userId;

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

}
