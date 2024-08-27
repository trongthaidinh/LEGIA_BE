<?php

namespace App\Share\Pushers;

use Pusher\Pusher;

class NotificationAdded {
    private $pusher;

    public function __construct(
        $APP_KEY = "e253ed618d0a8b50eff6",
        $APP_SECRET = "c49e9a9f3a21f75a8b24",
        $APP_ID = "1855676",
        $APP_CLUSTER = "ap1"
    ) {
        $this->pusher = new Pusher($APP_KEY, $APP_SECRET, $APP_ID, array('cluster' => $APP_CLUSTER));
    }

    public function pusherNotificationAdded($notification, $userId) {
        $this->pusher->trigger('notification' . $userId, 'NotificationAdded', $notification);
    }

    public function pusherMakeReadNotification($notificationId, $userId) {
        $this->pusher->trigger('notification' . $userId, 'MakeReadNotification', ['id' => $notificationId]);
    }

    public function pusherMakeAllReadNotification($userId) {
        $this->pusher->trigger('notification' . $userId, 'MakeAllReadNotification', []);
    }

    public function pusherNotificationDeleted($notificationId, $userId) {
        $this->pusher->trigger('notification' . $userId, 'NotificationDeleted', ['id' => $notificationId]);
    }
}
