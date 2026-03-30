<?php
/**
 * Handles compatibiliy with Oxygen Page Builder plugin.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.1.9
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2020, Social Snap LLC
 */
class SocialSnap_Oxygen_Compatibility {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.1.9
	 */
	public function __construct() {

		if ( ! defined( 'CT_VERSION' ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'hide_on_builder_mode' ) );
	}

	/**
	 * Hide sharing positions on builder mode.
	 *
	 * @since 1.1.9
	 */
	public function hide_on_builder_mode() {

		if ( ! defined( 'SHOW_CT_BUILDER' ) ) {
			return;
		}

		if ( isset( $_GET['ct_builder'] ) && $_GET['ct_builder'] ) {

			if ( ! isset( $_GET['oxygen_iframe'] ) || ! $_GET['oxygen_iframe'] ) {

				$css = '
					.oxygen-builder-body #ss-share-hub,
					.oxygen-builder-body #ss-floating-bar,
					.oxygen-builder-body #ss-sticky-bar {
						display: none !important;
					}
				';

				wp_add_inline_style( 'socialsnap-styles', $css );
			}
		}
	}
}

new SocialSnap_Oxygen_Compatibility();
