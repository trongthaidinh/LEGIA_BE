<?php

use Pusher\Pusher;

class MessageSent {
    private $pusher;

    public function __construct(
        $APP_KEY = "bdf3ac284bdbb6bfabae",
        $APP_SECRET = "1cea3d48fa3a2c572c2c",
        $APP_ID = "1791163",
        $APP_CLUSTER = "ap1"
    ) {
        $this->pusher = new Pusher($APP_KEY, $APP_SECRET, $APP_ID, array('cluster' => $APP_CLUSTER));
    }

    public function pusherConversationIdGetNewMessage($userId, $messageInfo) {
        $this->pusher->trigger('new-message-' . $userId, 'NewMessage', $messageInfo);
    }
    public function pusherConversationIdGetNewMessageGroup($userId, $messageInfo) {
        $this->pusher->trigger('new-message-group-' . $userId, 'NewMessageGroup', $messageInfo);
    }

    public function pusherMessageSent($secret_key, $message) {
        $this->pusher->trigger('chat-' . $secret_key, 'MessageSent', $message);
    }

    public function pusherMessageIsRead($message_id, $seen) {
        $this->pusher->trigger('chat-read-' . $message_id, 'MessageIsRead', $seen);
    }
}

?>
