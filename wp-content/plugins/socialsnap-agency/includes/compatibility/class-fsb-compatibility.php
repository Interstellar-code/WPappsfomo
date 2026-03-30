<?php
/**
 * Handles compatibiliy with Floating Social Bar plugin.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.9
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */

// Check if Social Warfare was installed & settings exist in database.
$fsb_options = get_option( 'fsb_global_option' );

if ( empty( $fsb_options ) ) {
	return;
}

class SocialSnap_FSB_Compatibility {

	/**
	 * Options array.
	 *
	 * @since 1.0.9
	 * @var array
	 */
	private $options = array();

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.9
	 */
	public function __construct( $options ) {

		$this->options = $options;

		// Add import button to the settings panel.
		add_filter( 'socialsnap_plugin_migration', array( $this, 'add_to_settings' ) );

		// AJAX callback to import settings.
		add_action( 'wp_ajax_socialsnap_fsb_migrate', array( $this, 'import_settings' ) );
	}

	/**
	 * Add button to the settings panel which imports settings via AJAX.
	 *
	 * @since 1.0.9
	 */
	public function add_to_settings( $fields ) {

		$fields['ss_fsb_migrate'] = array(
			'id'      => 'ss_fsb_migrate',
			'name'    => __( 'Floating Social Bar', 'socialsnap' ),
			'desc'    => __( 'Import Floating Social Bar settings', 'socialsnap' ),
			'text'    => __( 'Import Settings', 'socialsnap' ),
			'type'    => 'button',
			'action'  => 'socialsnap_fsb_migrate',
			'confirm' => esc_html__( 'Are you sure you want to import Floating Social Bar settings? Please make a full backup of your website before you start the migration process.', 'socialsnap' ),
		);

		return $fields;
	}

	/**
	 * Populates Social Snap default settings from Floating Social Bar settings.
	 *
	 * @since 1.0.9
	 */
	public function import_settings() {

		check_ajax_referer( 'socialsnap-admin' );

		if ( ! current_user_can( apply_filters( 'socialsnap_manage_cap', 'manage_options' ) ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error. Access denied.', 'socialsnap' ),
				)
			);
		}

		$fsb_options = $this->options;
		$ss_settings = get_option( SOCIALSNAP_SETTINGS );

		// Get default social networks.
		$default = array(
			'order' => '',
		);

		if ( isset( $fsb_options['services'] ) && is_array( $fsb_options['services'] ) && ! empty( $fsb_options['services'] ) ) {
			foreach ( $fsb_options['services'] as $network => $network_settings ) {
				if ( $network_settings['on'] ) {

					$default[ $network ] = array(
						'text'               => socialsnap_get_network_name( $network ),
						'desktop_visibility' => 'on',
						'mobile_visibility'  => 'on',
					);
					$default['order']   .= $network . ',';
				}
			}
			$default['order'] = trim( $default['order'], ',' );

			$ss_settings['ss_social_share_networks'] = $default;
		}

		// Get default post types.
		$default_post_types = array();

		if ( isset( $fsb_options['show_on'] ) && is_array( $fsb_options['show_on'] ) && ! empty( $fsb_options['show_on'] ) ) {
			foreach ( $fsb_options['show_on'] as $key => $value ) {

				if ( 'media' !== $value ) {
					$default_post_types[ $value ] = 'on';
				} else {
					$ss_settings['ss_ss_on_media_enabled'] = true;
				}
			}

			$ss_settings['ss_ss_inline_content_post_types'] = $default_post_types;
		}

		// Get Twitter username.
		if ( isset( $fsb_options['twitter'] ) && $fsb_options['twitter'] ) {
			$ss_settings['ss_twitter_username'] = $fsb_options['twitter'];
		}

		// Get share label.
		if ( isset( $fsb_options['label'] ) && $fsb_options['label'] ) {
			$ss_settings['ss_ss_inline_content_share_label'] = $fsb_options['label'];
		}

		update_option( SOCIALSNAP_SETTINGS, $ss_settings );

		// Send success message.
		wp_send_json_success( array( 'message' => esc_html__( 'Social Warfare settings imported successfully.', 'sinatra' ) ) );
	}

}

new SocialSnap_FSB_Compatibility( $fsb_options );
