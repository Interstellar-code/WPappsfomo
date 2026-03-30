<?php

namespace Linksy\Inc\Core;

/**
 * Fired during plugin deactivation
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @link       http://laxusgee.com
 * @since      1.0.0
 *
 * @author     Linksy
 **/
class Deactivator {

	/**
	 * Short Description.
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// todo: feedback form	

		self::clearSchedules();
	}

	private static function clearSchedules() {
		$timestamp = wp_get_schedule( 'linksy_anchor_cloud_get_keywords_cron' );
		wp_get_schedule( $timestamp, 'linksy_anchor_cloud_get_keywords_cron' );
	}
}
