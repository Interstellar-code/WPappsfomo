<?php
/**
 * The escape functions.
 *
 * @since      1.0.0
 * @package    Linksy
 * @subpackage Linksy\Helpers\Database
 * @author     Linksy <laxusgooee@gmail.com>
 */

namespace Linksy\Inc\Helpers\Database;

/**
 * Escape class.
 */
trait Escape {

	/**
	 * Escape array values for sql
	 *
	 * @param array $arr Array to escape.
	 *
	 * @return array
	 */
	public function esc_array( $arr ) {
		return array_map( [ $this, 'esc_value' ], $arr );
	}

	/**
	 * Escape value for sql
	 *
	 * @param mixed $value Value to escape.
	 *
	 * @return mixed
	 */
	public function esc_value( $value ) {
		global $wpdb;

		if ( is_int( $value ) ) {
			return $wpdb->prepare( '%d', $value );
		}

		if ( is_float( $value ) ) {
			return $wpdb->prepare( '%f', $value );
		}

		if ( 'null' === $value ) {
			return $value;
		}

		if((isset($value[0]) && $value[0] == '-') && (isset($value[1])  && $value[1] == '-')) {
			return substr($value, 2);
		}

		if((isset($value[0]) && $value[0] == '%') || (isset($value[strlen($value) - 1])  && $value[strlen($value) - 1] == '%')) {
		}

		return $wpdb->prepare( '%s', $value );
	}

	/**
	 * Escape value for like statement
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $value  Value for like statement.
	 * @param string $start  (Optional) The start of like query.
	 * @param string $end    (Optional) The end of like query.
	 *
	 * @return string
	 */
	public function esc_like( $value, $start = '%', $end = '%' ) {
		global $wpdb;
		return $start . $wpdb->esc_like( $value ) . $end;
	}
}
