<?php
/**
 * Social Snap social share counters. Returns share counts.
 *
 * @since 1.0.0
 * @package SocialSnap
 */

class SocialSnap_View_Counter {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Increment post view count on single post page
		add_action( 'wp', array( $this, 'count_post_view' ) );
	}

	/**
	 * Count post view on single post page
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function count_post_view() {

		// Views are only counted on single pages
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		// No post ID found
		if ( ! isset( $post->ID ) ) {
			return;
		}

		// Stop if user already viewed the page
		if ( isset( $_COOKIE[ 'socialsnap_viewed_' . $post->ID ] ) && $_COOKIE[ 'socialsnap_viewed_' . $post->ID ] ) {
			return;
		}

		$current_view_count = intval( get_post_meta( $post->ID, 'ss_view_count', true ) );

		// Don't increment for logged in users with certain roles
		if ( $this->block_user_role( apply_filters( 'socialsnap_view_counter_roles', array( 'administrator' ) ) ) && $current_view_count ) {
			return;
		}

		// Increment the view counter
		$this->increment_counter( $post->ID );
	}


	/**
	 * Increment post view counter
	 *
	 * @param  integer $post_id Post ID
	 * @return void
	 * @since 1.0.0
	 */
	public function increment_counter( $post_id = 0 ) {

		if ( ! $post_id ) {
			return;
		}

		// Get saved view count from post meta
		$views         = intval( get_post_meta( $post_id, 'ss_view_count', true ) );
		$views_updated = $views + 1;

		// Update post meta with the new view count
		update_post_meta( $post_id, 'ss_view_count', $views_updated, $views );

		// Set cookie if not disabled for GDPR compliance
		if ( ! socialsnap_settings( 'ss_remove_cookies' ) ) {
			setcookie( 'socialsnap_viewed_' . $post_id, 1, 0, '', '', true );
		}
	}


	/**
	 * Check whether a specified (or the current) user role should not be counted
	 *
	 * @param  array $roles User roles to check
	 * @return boolean       True is user role must be blocked from counting hits
	 */
	public function block_user_role( $roles = array() ) {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( is_array( $roles ) && ! empty( $roles ) ) {
			foreach ( $roles as $role ) {
				if ( current_user_can( $role ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
new SocialSnap_View_Counter();
