<?php

use Pusher\Pusher;

    function pusherMessageSent($secret_key, $message) {
        var_dump(123);
        $pusher = new Pusher("bdf3ac284bdbb6bfabae", "1cea3d48fa3a2c572c2c", "1791163", array('cluster' => 'ap1'));

        $pusher->trigger('chat' . $secret_key, 'MessageSent', $message);
    }
?>
