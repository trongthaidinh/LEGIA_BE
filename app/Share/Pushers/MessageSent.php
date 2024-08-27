<?php

use Pusher\Pusher;

class MessageSent {
    private $pusher;

    public function __construct(
        $APP_KEY = "e253ed618d0a8b50eff6",
        $APP_SECRET = "c49e9a9f3a21f75a8b24",
        $APP_ID = "1855676",
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

    public function pusherMessageSent($conversationId, $message) {
        $this->pusher->trigger('chat-' . $conversationId, 'MessageSent', $message);
    }

    public function pusherMessageIsRead($messageId, $seen) {
        $this->pusher->trigger('chat-read-' . $messageId, 'MessageIsRead', $seen);
    }

    public function pusherUnreadMessagesCount($userId, $count) {
        $this->pusher->trigger('unread-messages-count-' . $userId, 'UnreadMessagesCount', $count);
    }

}

?>
