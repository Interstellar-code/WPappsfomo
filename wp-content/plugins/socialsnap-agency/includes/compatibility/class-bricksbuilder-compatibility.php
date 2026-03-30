<?php
/**
 * Handles compatibiliy with Bricks Builder plugin.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2020, Social Snap LLC
 */
class SocialSnap_BricksBuilder_Compatibility {

	/**
	 * Primary class constructor.
	 */
	public function __construct() {

		add_action( 'wp_footer', array( $this, 'hide_on_builder_mode' ) );
	}

	/**
	 * Hide sharing positions on builder mode.
	 */
	public function hide_on_builder_mode() {

		if ( ! function_exists( 'bricks_is_builder' ) || ! bricks_is_builder() ) {
			return;
		}

		echo '<style type="text/css">
			#ss-share-hub,
			#ss-floating-bar,
			#ss-sticky-bar {
				display: none !important;
			}
		</style>';
	}
}

new SocialSnap_BricksBuilder_Compatibility();
