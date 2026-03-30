<?php

namespace Linksy\Inc\Helpers;

/**
 * Hadndle incoming request.
 *
 * @since      1.0.1
 * @package    Linksy
 * @subpackage Linksy\admin
 * @author     Gbenga Medunoye <laxusgooee@gmail.com>
 */

/**
 * Request class.
 */
class Request {

	public static function get( $id, $default = false, $filter = FILTER_DEFAULT, $flag = 0 ) {
		return filter_has_var( INPUT_GET, $id ) ? filter_input( INPUT_GET, $id, $filter, $flag ) : $default;
	}

	public static function getOrFail( $id, $filter = FILTER_DEFAULT, $flag = 0 ) {
		$get = self::get($id, null, $filter, $flag);

		if (is_null($get)) {
			throw new \Exception("{$id} is required", 1);
		}

		return $get;
	}

	public static function post( $id, $default = false, $filter = FILTER_DEFAULT, $flag = 0 ) {
		return filter_has_var( INPUT_POST, $id ) ? filter_input( INPUT_POST, $id, $filter, $flag ) : $default;
	}

	public static function postOrFail( $id, $filter = FILTER_DEFAULT, $flag = 0 ) {
		$get = self::post($id, null, $filter, $flag);

		if (is_null($get)) {
			throw new \Exception("{$id} is required", 1);
		}
		
		return $get;
	}

	public static function request( $id, $default = false, $filter = FILTER_DEFAULT, $flag = 0 ) {
		if(isset( $_REQUEST[ $id ] )){
			return is_array($default)? 
				filter_var_array( $_REQUEST[ $id ], $filter, $flag ) : filter_var( $_REQUEST[ $id ], $filter, $flag );

		} else {
			return $default;
		}
	}

	public static function server( $id, $default = false, $filter = FILTER_DEFAULT, $flag = 0 ) {
		return isset( $_SERVER[ $id ] ) ? filter_var( $_SERVER[ $id ], $filter, $flag ) : $default;
	}
}
