<?php
/**
 * Created by PhpStorm.
 * User: ovidiu
 * Date: 08.07.2021
 * Time: 20:24
 */

namespace Wpcb\ConditionBuilder;


use Wpcb\ConditionBuilder\Condition\Location;
use Wpcb\ConditionBuilder\Condition\PageUrl;

class WordPressContext
{
    public function get_current_url()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    public function is_frontend()
    {
        return !is_admin();
    }

    public function get_day_of_week()
    {
        return date('w');
    }

    public function get_logged_in_user_id()
    {
        return get_current_user_id();
    }

    public function get_logged_in_user_roles()
    {
        $user = wp_get_current_user();

        if($user) {
            return (array)$user->roles;
        }

        return [];
    }

    public function get_current_post_id()
    {
        return get_queried_object_id();
    }

    public function get_post_parent()
    {
        $post_parent = get_post_parent($this->get_current_post_id());

        return $post_parent->ID;
    }

    public function get_post_type()
    {
        return get_post_type($this->get_current_post_id());
    }

    public function get_current_post_terms($taxonomy)
    {
        $current_post_id = $this->get_current_post_id();

        if(!$current_post_id) {
            return [];
        }

        return get_the_terms($current_post_id, $taxonomy);
    }

    function is_login(){


        if(isset($GLOBALS['pagenow'])) {
            $is_login = in_array(
                $GLOBALS['pagenow'],
                array('wp-login.php', 'wp-register.php'),
                true
            );

            return $is_login;
        }

        return false;
    }
}