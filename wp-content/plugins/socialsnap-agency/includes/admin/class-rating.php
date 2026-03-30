<?php
/**
 * Ask for some love.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Rating {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Admin notice requesting review.
		// add_action( 'admin_notices',     array( $this, 'review_request' ) );

		// Admin footer text.
		add_filter( 'update_footer', array( $this, 'filter_update_footer' ), 999 );
		add_filter( 'admin_footer_text', array( $this, 'filter_admin_footer_text' ), 999 );
	}

	/**
	 * Add admin notices as needed for reviews.
	 *
	 * @since 1.0.0
	 */
	public function review_request() {

		// Only consider showing the review request to admin users.
		if ( ! is_super_admin() ) {
			return;
		}

		// User disabled these notices.
		if ( socialsnap_settings( 'ss_remove_notices' ) ) {
			return;
		}

		// Verify that we can do a check for reviews.
		$rating = get_option( 'socialsnap_rating' );
		$time   = time();
		$load   = false;

		if ( ! $rating ) {
			$rating = array(
				'time'      => $time,
				'dismissed' => false,
			);
			$load   = true;
		} else {
			// Check if it has been dismissed or not.
			if ( ( isset( $rating['dismissed'] ) && ! $rating['dismissed'] ) && ( isset( $rating['time'] ) && ( ( $rating['time'] ) <= $time ) ) ) {
				$load = true;
			}
		}

		// If we cannot load, return early.
		if ( ! $load ) {
			return;
		}

		// Update the review option now.
		update_option( 'socialsnap_rating', $rating );

		// Fetch total shares.
		$shares = intval(
			socialsnap()->stats->get_stats(
				array(
					'type' => 'share',
				),
				true
			)
		);

		// Don't display the notice if share count is less than 30.
		if ( $shares < 50 ) {
			return;
		}

		$message = '<p>' . esc_html__( 'Hey, you just crossed 50 shares via Social Snap - that’s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress? Just to help us spread the word and boost our motivation.', 'socialsnap' ) . '</p>';

		$message .= '<p><strong>~ Branko Conjic<br>' . __( 'Co-Founder of Social Snap', 'socialsnap' ) . '</strong></p>';

		$message .= '<p>
				<a href="https://wordpress.org/support/plugin/socialsnap/reviews/?filter=5#new-post" class="socialsnap-notice-dismiss-button socialsnap-review-out" target="_blank" rel="noopener">' . esc_html__( 'Ok, you deserve it', 'socialsnap' ) . '</a><br>
				<a href="#" class="socialsnap-notice-dismiss-button" rel="noopener noreferrer">' . esc_html__( 'Nope, maybe later', 'socialsnap' ) . '</a><br>
				<a href="#" class="socialsnap-notice-dismiss-button" rel="noopener noreferrer">' . esc_html__( 'I already did', 'socialsnap' ) . '</a>
			</p>';

		socialsnap_print_notice(
			array(
				'type'           => 'info',
				'message'        => $message,
				'message_id'     => 'request-review-notice',
				'is_dismissible' => true,
			)
		);
	}

	/**
	 * Filter WordPress footer right text to display our text.
	 *
	 * @since 1.0.0
	 */
	public function filter_update_footer( $old ) {

		// Only do this if we are on one of our plugin pages
		if ( strpos( get_current_screen()->base, 'socialsnap' ) !== false ) {
			return false;
		} else {
			return $old;
		}
	}

	/**
	 * Filter WordPress footer left text to display our text.
	 *
	 * @since 1.0
	 */
	public function filter_admin_footer_text( $text ) {

		// Only do this if we are on one of our plugin pages
		if ( strpos( get_current_screen()->base, 'socialsnap' ) !== false ) {

			$url = 'https://wordpress.org/support/plugin/socialsnap/reviews/?filter=5#new-post';

			// translators: %s = review link.
			$text = sprintf( __( 'If you like <strong>Social Snap</strong> please leave us a <a href="%s" target="_blank" rel="noopener">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating to help us spread the word. Thank you!', 'socialsnap' ), $url );
		}

		return $text;
	}
}
new SocialSnap_Rating();
