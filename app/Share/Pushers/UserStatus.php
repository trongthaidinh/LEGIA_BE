<?php

namespace App\Share\Pushers;

use Pusher\Pusher;

class UserStatus {
    private $pusher;

    public function __construct(
        $APP_KEY = "e253ed618d0a8b50eff6",
        $APP_SECRET = "c49e9a9f3a21f75a8b24",
        $APP_ID = "1855676",
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
