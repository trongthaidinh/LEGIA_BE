<?php

namespace App\Share\Pushers;

use Pusher\Pusher;

class UserStatus {
    private $pusher;

    public function __construct(
        $APP_KEY = "bdf3ac284bdbb6bfabae",
        $APP_SECRET = "1cea3d48fa3a2c572c2c",
        $APP_ID = "1791163",
        $APP_CLUSTER = "ap1"
    ) {
        $this->pusher = new Pusher($APP_KEY, $APP_SECRET, $APP_ID, array('cluster' => $APP_CLUSTER));
    }

    public function pusherMarkOnline($user) {
        $this->pusher->trigger('presence-user-status', 'MarkUserOnline' , $user);
    }

    public function pusherMakeOffline($user) {
        $this->pusher->trigger('presence-user-status', 'MakeUserOffline', $user);
    }
}
