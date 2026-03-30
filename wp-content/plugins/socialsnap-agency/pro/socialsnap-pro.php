<?php
/**
 * SocialSnap Pro.
 * Load Pro specific features/functionality.
 *
 * @since 1.0.0
 * @package Social Snap
 */

class SocialSnap_Pro {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Setup license.
		add_action( 'plugins_loaded', array( $this, 'license' ), 20 );

		// Setup features.
		add_action( 'plugins_loaded', array( $this, 'pro' ), 20 );
	}

	public function pro() {

		if ( ! socialsnap()->pro ) {

			// Change upgrade link to activate.
			add_filter( 'socialsnap_upgrade_link', array( $this, 'upgrade_link' ), 99 );
			add_filter( 'socialsnap_upgrade_button_text', array( $this, 'upgrade_button_text' ), 99 );

			return;
		}

		// Define constants.
		add_action( 'socialsnap_loaded', array( $this, 'constants' ), 1 );

		// Include pro files.
		add_action( 'socialsnap_loaded', array( $this, 'includes' ), 1 );

		// Initialize Updates.
		add_action( 'socialsnap_loaded', array( $this, 'updater' ), 30 );

		// Add Colors for PRO networks.
		add_filter( 'socialsnap_network_colors', array( $this, 'social_networks_colors' ), 10, 1 );

		// Add fields to settings panel.
		add_filter( 'socialsnap_settings_config', array( $this, 'add_settings_config' ), 6, 1 );

		// Settings bottom bar messages.
		add_filter( 'socialsnap_facts', array( $this, 'socialsnap_facts' ) );
	}

	/**
	 * Include files.
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		if ( is_admin() ) {
			require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/admin/class-addons.php';
			require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/admin/class-user-profile.php';
			require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/admin/class-updater.php';
		}

		require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/share/class-social-share-pro.php';
		require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/share/class-view-counter.php';
		require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/share/class-top-posts-widget.php';
		require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/share/class-social-share-count-recovery.php';
		require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/class-meta-tags.php';
		require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/class-analytics-tracking.php';
		require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/follow/class-social-follow-pro.php';

		require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/class-statistics.php';
		require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/class-bitly-shortening.php';
	}

	/**
	 * Setup plugin constants.
	 *
	 * @since 1.2.1
	 */
	public function constants() {

		// Plugin version
		if ( ! defined( 'SOCIALSNAP_UPDATER_URL' ) ) {
			define( 'SOCIALSNAP_UPDATER_URL', 'https://socialsnap.com/' );
		}
	}

	/**
	 * Setup objects.
	 *
	 * @since 1.2.1
	 */
	public function license() {

		require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/admin/class-license.php';

		socialsnap()->license = new SocialSnap_License();

		if ( is_admin() ) {

			if ( ! class_exists( 'SocialSnap_Admin_Page' ) ) {
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/class-admin-page.php';
			}

			require_once SOCIALSNAP_PLUGIN_DIR . SOCIALSNAP_PRO_DIR . 'includes/admin/class-license-page.php';
		}

		socialsnap()->pro = socialsnap()->license->is_valid();
	}

	/**
	 * Register Pro share networks.
	 *
	 * @since 1.0.0
	 */
	public function social_networks_colors( $colors ) {

		$colors = array_merge(
			$colors,
			array(
				'heart'       => '#e1604f',
				'print'       => '#323b43',
				'yahoo'       => '#410093',
				'snapchat'    => '#fffc00',
				'500px'       => '#0099e5',
				'amazon'      => '#323b43',
				'angellist'   => '#3078ca',
				'baidu'       => '#de0f17',
				'behance'     => '#1769ff',
				'blogger'     => '#f57d00',
				'buffer'      => '#323b43',
				'delicious'   => '#3399ff',
				'deviantart'  => '#05cc47',
				'digg'        => '#005be2',
				'dribbble'    => '#ea4c89',
				'etsy'        => '#d5641c',
				'flickr'      => '#ff0084',
				'forrst'      => '#5b9a68',
				'foursquare'  => '#f94877',
				'github'      => '#333333',
				'google'      => '#ea4335',
				'houzz'       => '#7ac142',
				'instagram'   => '#c13584',
				'linkedin'    => '#0077b5',
				'livejournal' => '#004359',
				'medium'      => '#00ab6c',
				'mewe'        => '#007da1',
				'myspace'     => '#333333',
				'newsvine'    => '#055d00',
				'parler'      => '#c63240',
				'pinterest'   => '#bd081c',
				'pocket'      => '#ef4056',
				'reddit'      => '#ff4500',
				'rss'         => '#f26522',
				'skype'       => '#00aff0',
				'soundcloud'  => '#ff8800',
				'spotify'     => '#1db954',
				'tripadvisor' => '#00af87',
				'telegram'    => '#169cdf',
				'tumblr'      => '#36465d',
				'twitch'      => '#6441a5',
				'viber'       => '#665cac',
				'vimeo'       => '#1ab7ea',
				'vkontakte'   => '#45668e',
				'whatsapp'    => '#25D366',
				'xing'        => '#026466',
				'youtube'     => '#cd201f',
				'messenger'   => '#0084ff',
				'yummly'      => '#E16128',
				'evernote'    => '#2dbe60',
				'shareall'    => '#e2e2e2',
				'sms'         => '#323b43',
				'copy'        => '#323b43',
				'patreon'     => '#e7513b',
				'line'        => '#06c755',
			)
		);

		return array_unique( $colors );
	}

	/**
	 * Register settings fields.
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function add_settings_config( $settings ) {

		$settings['ss_social_sharing']['fields']['ss_social_share_floating_sidebar']['fields']['ss_ss_sidebar_position'] = array(
			'id'         => 'ss_ss_sidebar_position',
			'name'       => esc_html__( 'Sidebar Position', 'socialsnap' ),
			'type'       => 'dropdown',
			'options'    => array(
				'left-top'     => __( 'Top Left', 'socialsnap' ),
				'left'         => __( 'Center Left', 'socialsnap' ),
				'left-bottom'  => __( 'Bottom Left', 'socialsnap' ),
				'right-top'    => __( 'Top Right', 'socialsnap' ),
				'right'        => __( 'Center Right', 'socialsnap' ),
				'right-bottom' => __( 'Bottom Right', 'socialsnap' ),
			),
			'default'    => 'left',
			'dependency' => array(
				'element' => 'ss_ss_sidebar_enabled',
				'value'   => 'true',
			),
		);

		// Move offset field below sidebar position.
		$floating_sidebar_fields = $settings['ss_social_sharing']['fields']['ss_social_share_floating_sidebar']['fields'];
		$vertical_offset_field   = $floating_sidebar_fields['ss_ss_sidebar_position_offset'];
		$floating_sidebar_fields = socialsnap_array_insert( $floating_sidebar_fields, array( 'ss_ss_sidebar_position_offset' => $vertical_offset_field ), 'ss_ss_sidebar_position' );

		unset( $settings['ss_social_sharing']['fields']['ss_social_share_floating_sidebar']['fields']['ss_ss_sidebar_position_offset'] );

		$settings['ss_social_sharing']['fields']['ss_social_share_floating_sidebar']['fields'] = $floating_sidebar_fields;

		return $settings;
	}

	/**
	 * Returns one random line for the bottom of the settings screen.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links  Default plugin action links.
	 * @return array $links Amended plugin action links.
	 */
	public function socialsnap_facts( $facts ) {

		$facts = array(
			__( 'Browse plugin documentation, reference materials and tutorials.', 'socialsnap' ),
		);

		return $facts;
	}

	/**
	 * Change Upgrade now button link to redirect to License page.
	 *
	 * @since 1.1.11
	 */
	public function upgrade_link( $upgrade_link ) {
		return admin_url( 'admin.php?page=socialsnap-license' );
	}

	/**
	 * Change Upgrade now button text to redirect to License page.
	 *
	 * @since 1.1.11
	 */
	public function upgrade_button_text( $text ) {

		$text = str_replace( __( 'Upgrade', 'socialsnap' ), __( 'Activate', 'socialsnap' ), $text );

		return $text;
	}

	/**
	 * Load plugin updater.
	 *
	 * @since 1.0.0
	 */
	public function updater() {

		if ( ! is_admin() ) {
			return;
		}

		$key = socialsnap()->license->get();

		// Go ahead and initialize the updater.
		new SocialSnap_Updater(
			array(
				'plugin_name' => 'Social Snap Agency',
				'plugin_slug' => 'socialsnap-agency',
				'plugin_path' => plugin_basename( SOCIALSNAP_PLUGIN_FILE ),
				'plugin_url'  => trailingslashit( SOCIALSNAP_PLUGIN_URL ),
				'remote_url'  => SOCIALSNAP_UPDATER_URL,
				'version'     => socialsnap()->version,
				'key'         => $key,
			)
		);

		// Fire a hook for Addons to register their updater since we know the key is present.
		do_action( 'socialsnap_updater', $key );
	}
}
new SocialSnap_Pro();
