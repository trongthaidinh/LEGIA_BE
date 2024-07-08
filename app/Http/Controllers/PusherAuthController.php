<?php

namespace App\Http\Controllers;

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
                'user_info'=> [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'avatar' => $user->avatar,
                ],
                'watchlist' => []
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
            auth()->userOrFail();

            $socketId = $request->input('socket_id');
            $channel = $request->input('channel_name');

            $authResponse = $this->pusher->authorizeChannel($channel, $socketId);

            return $authResponse;

        }catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
            return responseJson(null, 404, "Người dùng chưa xác thực!");
        }
    }

}
