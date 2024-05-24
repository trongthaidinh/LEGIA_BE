<?php

use App\Models\Notification;
use Pusher\Pusher;

class NotificationService {
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

    public function sendNotification(int $userId, Notification $noti)
    {
        $this->pusher->trigger('notification' . $userId, 'NotificationSent', $noti);
        Notification::create($noti);
    }
}

?>
