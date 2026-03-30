<?php

namespace Linksy\Inc\Helpers;

/**
 * Ajax
 */
class Ajax {
	public static function prefix($name, $nopriv = false){
		$prefix = $nopriv? 'wp_ajax_nopriv' : 'wp_ajax';
		return $prefix.'_linksy_'.$name;
	}

	public static function success($data){
		wp_send_json_success($data);
		wp_die();
	}

	public static function error($data, $code = 500){
		wp_send_json_error( $data, $code);
		wp_die();
	}

	public static function authorise($key, $nonce = null)
    {
        $user = wp_get_current_user();
        if(empty($nonce) || !wp_verify_nonce( $nonce, $user->ID . $key)){
            wp_send_json_error( 'There was an error in processing the data, please reload the page and try again.', 401);
			wp_die();
        }
    }
}