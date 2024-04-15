<?php
    function getPublicIdFromAvatarUrl($url) {
        $url_parts = explode('?', $url);

        $public_id = $url_parts[1];

        return explode('=', $public_id)[1];
    }
?>
