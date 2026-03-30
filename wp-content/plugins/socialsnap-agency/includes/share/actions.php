<?php
/**
 * Share Actions.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.2.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2021, Social Snap LLC
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update share network click counter for a post/page.
 * Cache the values to post meta.
 *
 * @since 1.0.0
 */
function socialsnap_update_share_count_click() {

	// Security check.
	if ( ! isset( $_POST['nonce'] ) ) {
		wp_send_json_error();
	}

	$nonce = sanitize_text_field( $_POST['nonce'] );

	$nonce_verified = wp_verify_nonce( $nonce, 'socialsnap-nonce' );

	if ( false === $nonce_verified ) {
		wp_send_json_error();
	}

	// Data is required.
	if ( ! isset( $_POST['ss_click_data'] ) ) {
		wp_send_json_error();
	}

	// Parse data.
	$click_data = str_replace( '\\', '', sanitize_text_field( $_POST['ss_click_data'] ) );
	$click_data = json_decode( $click_data, true );

	// Sanitize data.
	$network = isset( $click_data['network'] ) ? sanitize_text_field( $click_data['network'] ) : '';
	$post_id = isset( $click_data['post_id'] ) ? intval( sanitize_text_field( $click_data['post_id'] ) ) : '';

	$click_data['type'] = 'heart' == $network ? 'like' : 'share';

	// Add to Stats DB. This function will validate and sanitize data.
	$share_count = socialsnap_add_to_stats_db( $click_data );

	if ( is_null( $share_count ) ) {
		wp_send_json_error();
	}

	// Store new share count values.
	if ( -1 == $post_id ) {
		update_option( 'socialsnap_homepage_click_share_count_' . $network, $share_count );
	} else {
		update_post_meta( $post_id, 'ss_ss_click_share_count_' . $network, $share_count );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_ss_social_share_clicks', 'socialsnap_update_share_count_click' );
add_action( 'wp_ajax_nopriv_ss_social_share_clicks', 'socialsnap_update_share_count_click' );
