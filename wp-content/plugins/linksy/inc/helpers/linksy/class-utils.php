<?php

namespace Linksy\Inc\Helpers\Linksy;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Database\Database;

class Utils {
    public static function send_to_slack($key, $data) {
        try {
            $res = $api->post(LINKSY_API_URL.'user/slack/', [
                'key'        => $key,
                'data'       => $data,
            ]);

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1); 
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    public static function insert_link_in_content($content, $source, $phrase, $phrase_replacement) {
        $position = Str::position($content, $source);
        if ($position === false) {
            return false;
        }

        $phrase_position = Str::position($source, $phrase);

        return substr_replace($content, $phrase_replacement, $position + $phrase_position, strlen($phrase));
    }
}