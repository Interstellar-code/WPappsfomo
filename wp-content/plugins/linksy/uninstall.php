<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Linksy
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

define('LINKSY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( LINKSY_PLUGIN_DIR . 'constants.php' );

function clear_tables() {
	global $wpdb;

	$table_schema = [ 
		"linksy_posts_migrations",
		"linksy_posts_migrations_failed",
		"linksy_links",
		"linksy_keywords",
		"linksy_settings"
	];

	foreach ( $table_schema as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
	}

	$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE 'linksy_%'");
}

function clear_options() {
	$settingOptions = array(
		LINKSY_OPTION_VIRGIN,
		LINKSY_OPTION_API_KEY,
		LINKSY_OPTION_PLUGIN_ACTIVE,
		LINKSY_OPTION_SETUP_STARTED,
		LINKSY_OPTION_SETUP_COMPLETE,
		LINKSY_OPTION_PLUGIN_VERSION,
		LINKSY_OPTION_ANCHORS_SETUP_COMPLETE,
		LINKSY_OPTION_KEYWORDS_SETUP_COMPLETE,
	);
 
	// Clear up our settings
	foreach ( $settingOptions as $settingName ) {
		delete_option( $settingName );
	}
}

function clear_remote() {
	// clear remote table
	try {
		wp_remote_request( LINKSY_API_URL.'posts/',  [
			'timeout' => 20,
			'method'  => 'DELETE',
			'headers' => [
				'Accept' => 'application/json',
				'Authorization' => 'Bearer ' . get_option(LINKSY_OPTION_API_KEY),
				'X-WP-Site' => get_site_url(),
			],
		]);
	} catch (\Exception $e) {
		error_log($e->getMessage());
	}  
}

clear_remote();
clear_tables();
clear_options();