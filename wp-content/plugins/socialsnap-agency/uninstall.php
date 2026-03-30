<?php
/**
 * Uninstall Social Snap.
 *
 * Deletes all the plugin data.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load Social Snap file.
require_once 'socialsnap.php';

/**
 * Remove Social Snap data.
 *
 * @since 1.0.0
 */
function socialsnap_uninstall() {

	global $wpdb;

	/** Delete all the Plugin Options */
	delete_option( 'socialsnap_settings' );
	delete_option( 'socialsnap_version' );
	delete_option( 'socialsnap_activated' );
	delete_option( 'socialsnap_bitly_access_token' );
	delete_option( 'socialsnap_bitly_user_date' );
	delete_option( 'socialsnap_bitly_username' );
	delete_option( 'socialsnap_cached_bitly_links' );
	delete_option( 'socialsnap_authorized_networks' );
	delete_option( 'socialsnap_follow_counts' );
	delete_option( 'socialsnap_authorized_networks' );
	delete_option( 'socialsnap_license_updates' );
	delete_option( 'socialsnap_license' );

	// Remove all database tables.
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'socialsnap_stats' );

	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'socialsnap\_share\_count\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'socialsnap\_homepage\_share\_count\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'socialsnap\_homepage\_click\_share\_count\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'socialsnap\_follow\_count\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_socialsnap\_addons%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'widget\_socialsnap-popular-posts-widget%'" );

	// Remove post meta.
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'ss\_view\_count'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'ss\_ss\_share\_count\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'ss\_ss\_click\_share\_count\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'ss\_total\_share\_count'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'ss\_bitly\_link'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'ss\_smt\_title'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'ss\_smt\_description'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'ss\_smt\_image'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'ss\_share\_recovery\_url'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'ss\_social\_share\_disable'" );

	// Remove any transients we've left behind.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_socialsnap\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_socialsnap\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_socialsnap\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_socialsnap\_%'" );
}

// Check if option to delete on uninstall is enabled.
if ( socialsnap_settings( 'ss_uninstall_delete' ) ) {

	// Check if we are on multisite.
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {

		// Multisite - go through each subsite and run the uninstaller.
		if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {

			$sites = get_sites();

			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				socialsnap_uninstall();
				restore_current_blog();
			}
		} else {

			$sites = wp_get_sites( array( 'limit' => 0 ) ); // phpcs:ignore

			foreach ( $sites as $site ) {
				switch_to_blog( $site['blog_id'] );
				socialsnap_uninstall();
				restore_current_blog();
			}
		}
	} else {
		// Normal single site.
		socialsnap_uninstall();
	}

	// Clear any cached data that has been removed.
	wp_cache_flush();
}
