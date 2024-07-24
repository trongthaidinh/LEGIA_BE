<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Pusher\Pusher;

class PusherAuthController extends Controller
{
    private $pusher;

    public function __construct(
        $APP_KEY = "bdf3ac284bdbb6bfabae",
        $APP_SECRET = "1cea3d48fa3a2c572c2c",
        $APP_ID = "1791163",
        $APP_CLUSTER = "ap1"
    ) {
        $this->pusher = new Pusher($APP_KEY, $APP_SECRET, $APP_ID,
            array('cluster' => $APP_CLUSTER));
    }

    public function userAuth(Request $request)
    {
        try {
            $user = auth()->userOrFail();

            $socketId = $request->input('socket_id');

            $userData = [
                'id' => $user->id,
                'watchlist' => [2,3]
            ];


            $authResponse = $this->pusher->authenticateUser($socketId, $userData);

            return $authResponse;

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

    public function channelAuth(Request $request)
    {
        try {
            $user = auth()->userOrFail();

            $socketId = $request->input('socket_id');
            $channel = $request->input('channel_name');
            $userId = $user->id;
            $userModel = User::find($userId);
            $userInfo = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar' => $user->avatar,
                'friend_list' => $userModel->friends()
            ];

            $authResponse = $this->pusher->authorizePresenceChannel($channel, $socketId, $userId, $userInfo);

            return $authResponse;

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

}
