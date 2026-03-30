<?php
/**
 * Connect sharing buttons with Google Analytics to gain additional insight about how users interact with your website.
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Analytics_Tracking {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add fields to settings panel
		add_filter( 'socialsnap_settings_config', array( $this, 'add_settings_config' ), 6, 1 );

		add_filter( 'socialsnap_js_localized_strings', array( $this, 'add_localized_string' ) );

		// UTM Parameters
		add_filter( 'socialsnap_add_utm_parameters', array( $this, 'add_utm_parameters' ), 10, 2 );
		add_filter( 'socialsnap_remove_utm_parameters', array( $this, 'remove_utm_parameters' ), 10, 1 );

	}

	/**
	 * Register settings fields.
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function add_settings_config( $settings ) {

		$tracking_fields = array(
			'ss_click_tracking'        => array(
				'id'      => 'ss_click_tracking',
				'name'    => esc_html__( 'Enable Click Tracking', 'socialsnap' ),
				'desc'    => esc_html__( 'Notify Google Analytics when a user clicks one of the social buttons. Requires Google Analytics on your website.', 'socialsnap' ),
				'type'    => 'toggle',
				'default' => false,
			),
			'ss_utm_tracking'          => array(
				'id'      => 'ss_utm_tracking',
				'name'    => esc_html__( 'Enable UTM Tracking', 'socialsnap' ),
				'desc'    => esc_html__( 'Add UTM parameteres to share button links.', 'socialsnap' ),
				'type'    => 'toggle',
				'default' => false,
			),
			'ss_utm_tracking_source'   => array(
				'id'          => 'ss_utm_tracking_source',
				'name'        => esc_html__( 'UTM Source', 'socialsnap' ),
				'desc'        => esc_html__( 'This tag identifies the source of your traffic. Use {{social_network}} to use social network id for each share button.', 'socialsnap' ),
				'type'        => 'text',
				'placeholder' => '{{social_network}}',
				'default'     => '{{social_network}}',
				'dependency'  => array(
					'element' => 'ss_utm_tracking',
					'value'   => 'true',
				),
			),
			'ss_utm_tracking_medium'   => array(
				'id'          => 'ss_utm_tracking_medium',
				'name'        => esc_html__( 'UTM Medium', 'socialsnap' ),
				'desc'        => esc_html__( 'This tag specifies the medium, like website or newsletter.', 'socialsnap' ),
				'type'        => 'text',
				'placeholder' => 'website',
				'default'     => 'website',
				'dependency'  => array(
					'element' => 'ss_utm_tracking',
					'value'   => 'true',
				),
			),
			'ss_utm_tracking_campaign' => array(
				'id'          => 'ss_utm_tracking_campaign',
				'name'        => esc_html__( 'UTM Campaign', 'socialsnap' ),
				'desc'        => esc_html__( 'This tag indicates the campaign that the URL is a part of.', 'socialsnap' ),
				'type'        => 'text',
				'placeholder' => 'SocialSnap',
				'default'     => 'SocialSnap',
				'dependency'  => array(
					'element' => 'ss_utm_tracking',
					'value'   => 'true',
				),
			),
		);

		$settings['ss_advanced_settings']['fields']['ss_analytics_tracking']['fields'] = $tracking_fields;

		return $settings;
	}

	/**
	 * Add localization strings for the main JS file.
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function add_localized_string( $strings ) {

		$strings['click_tracking'] = socialsnap_settings( 'ss_click_tracking' ) ? true : false;

		return $strings;
	}



	/**
	 * Add UTM Parameters to share links
	 *
	 * @since 1.0.0
	 */
	public function add_utm_parameters( $url, $network ) {

		if ( ! socialsnap_settings( 'ss_utm_tracking' ) ) {
			return $url;
		}

		$utm_source   = str_replace( ' ', '', socialsnap_settings( 'ss_utm_tracking_source' ) );
		$utm_medium   = str_replace( ' ', '', socialsnap_settings( 'ss_utm_tracking_medium' ) );
		$utm_campaign = str_replace( ' ', '', socialsnap_settings( 'ss_utm_tracking_campaign' ) );

		if ( '{{social_network}}' === $utm_source || '' == $utm_source ) {
			$utm_source = $network;
		}

		if ( '' == $utm_medium || '' == $utm_campaign ) {
			return $url;
		}

		$url = add_query_arg(
			array(
				'utm_source'   => $utm_source,
				'utm_medium'   => $utm_medium,
				'utm_campaign' => $utm_campaign,
			),
			$url
		);

		return $url;
	}

	/**
	 * Remove UTM Parameters from links
	 *
	 * @since 1.0.0
	 */
	public function remove_utm_parameters( $url ) {
		return remove_query_arg(
			array(
				'utm_source',
				'utm_medium',
				'utm_campaign',
			),
			$url
		);
	}
}
new SocialSnap_Analytics_Tracking();
