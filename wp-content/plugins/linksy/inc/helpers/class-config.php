<?php
namespace Linksy\Inc\Helpers;

/**
 * WP options wrapper.
 *
 * @since      1.0.1
 * @package    Linksy
 * @subpackage Linksy\admin
 * @author     Gbenga Medunoye <laxusgooee@gmail.com>
 **/

/**
 * Config class.
 */
class Config {
	public static function exists($name){
		global $wpdb;
		return $wpdb->query("SELECT * FROM options WHERE option_name ='$name' LIMIT 1");
	}

	public static function get( $key, $defaults = null ) {
		return get_option( $key, $defaults );
	}

	public static function set( $key, $data = [], $reset = false ) {
		$saved = get_option( $key, null );

		if ( is_null( $saved )) {
			add_option($key, $data);
			return $data;
		}
		
		$data = $reset? $data :  wp_parse_args( $data, $saved );
		update_option( $key, $data );

		return $data;
	}

	public static function delete($key) {
		delete_option( $key );
		return false;
	}

	public static function get_where($key, $value, $defaults = null) {
		$data = self::get( $key, $defaults );
		return $data[$value];
	}

	public static function equals($key, $value) {
		$data = self::get( $key );
		return $data === $value;
	}

	/**
     * delete config after use.
     * 
     * @param string $key The key of the data to be flashed
	 * @param mixed $defaults The value of the key if empty
     * @return mixed Returns data 
     **/
	public static function flash($key, $defaults = null) {
		$data =  self::get( $key, $defaults );
		self::delete($key);

		return $data;
	}

    /**
     * Compresses and base64's the given data so it can be saved in the db.
     * 
     * @param string $data The data to be compressed
     * @return null|string Returns a string of compressed and base64 encoded data 
     **/
    public static function compress($data = false){
        return base64_encode(gzdeflate(serialize($data)));
    }

    /**
     * Decompresses stored data that was compressed with compress.
     * 
     * @param string $data The data to be decompressed
     * @return mixed $data 
     **/
    public static function decompress($data){
        if(empty($data) || !is_string($data)){
            return $data;
        }

        return unserialize(gzinflate(base64_decode($data)));
    }
}
