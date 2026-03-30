<?php
/**
 * Social Follow Class.
 * Supports shortcode, block editor element, automatic and manual follower counts.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Social_Follow_PRO {

	/**
	 * Singleton instance of the class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance;

	/**
	 * Configured Social Networks array.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $configured_networks;

	/**
	 * Authorized Social Networks array (only for networks that support API Counts).
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $authorized_networks = null;

	/**
	 * Main SocialSnap_Social_Follow_PRO Instance.
	 *
	 * @since 1.1.6
	 * @return SocialSnap_Social_Follow_PRO
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SocialSnap_Social_Follow_PRO ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add new networks
		add_filter( 'socialsnap_social_follow_networks', array( $this, 'social_follow_networks' ) );
		add_filter( 'socialsnap_social_follow_networks_with_api', array( $this, 'social_follow_networks_with_api' ) );
		add_filter( 'socialsnap_social_follow_networks_automatic', array( $this, 'social_follow_networks_automatic' ) );

		add_filter( 'socialsnap_filter_social_follow_authorized_networks', array( $this, 'social_follow_authorized_network' ) );
		add_filter( 'socialsnap_filter_social_follow_authorized_networks', array( $this, 'social_follow_disconnect_network' ) );

		add_filter( 'socialsnap_automatic_follow_count', array( $this, 'is_network_automatic' ), 10, 2 );

		add_action( 'init', array( $this, 'redirect' ), 999 );
		add_action( 'init', array( $this, 'init' ) );

		add_filter( 'socialsnap_social_follow_count_api', array( $this, 'social_follow_counts_api' ), 10, 4 );

		add_filter( 'socialsnap_social_follow_count_automatic', array( $this, 'social_follow_counts_automatic' ), 10, 3 );
	}

	/**
	 * Initialize class variables.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Get authorized networks.
		$this->authorized_networks = $this->get_authorized_networks();

		// Get only networks configured in options.
		$this->configured_networks = apply_filters(
			'socialsnap_configured_networks',
			array()
		);
	}

	public function get_authorized_networks() {

		if ( is_null( $this->authorized_networks ) ) {

			$authorized_networks = (array) get_option( 'socialsnap_authorized_networks' );
			$networks_with_api   = socialsnap_social_follow_networks_with_api();

			$this->authorized_networks = $authorized_networks;

			foreach ( $authorized_networks as $network => $settings ) {
				if ( ! in_array( $network, $networks_with_api, true ) ) {
					unset( $this->authorized_networks[ $network ] );
				}
			}

			if ( $this->authorized_networks != $authorized_networks ) {
				update_option( 'socialsnap_authorized_networks', $this->authorized_networks );
			}
		}

		return apply_filters(
			'socialsnap_filter_social_follow_authorized_networks',
			$this->authorized_networks
		);
	}

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function redirect() {

		// Redirect to the same page without the network_disconnect parameter
		if ( isset( $_GET['ss_network_authorized'], $_GET['access_token'], $_GET['access_token_secret'], $_GET['network_key_index'], $_GET['profile'] ) || isset( $_GET['network_disconnect'] ) ) {

			wp_redirect( add_query_arg( array( 'page' => 'socialsnap-settings#ss_social_follow_networks_display-ss' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Add Social Follow networks.
	 *
	 * @since 1.0.0
	 * @return array, array of post types
	 */
	public function social_follow_networks( $networks = array() ) {

		// Add new networks
		$networks = array_unique(
			array_merge(
				$networks,
				array(
					'subscribers' => 'Subscribers',
					'snapchat'    => 'Snapchat',
					'500px'       => '500px',
					'amazon'      => 'Amazon',
					'baidu'       => 'Baidu',
					'behance'     => 'Behance',
					'blogger'     => 'Blogger',
					'deviantart'  => 'DeviantArt',
					'digg'        => 'Digg',
					'discord'     => 'Discord',
					'dribbble'    => 'Dribbble',
					'etsy'        => 'Etsy',
					'flickr'      => 'Flickr',
					'flipboard'   => 'Flipboard',
					'foursquare'  => 'FourSquare',
					'github'      => 'Github',
					'houzz'       => 'Houzz',
					'linkedin'    => 'LinkedIn',
					'livejournal' => 'LiveJournal',
					'medium'      => 'Medium',
					'mewe'        => 'MeWe',
					'myspace'     => 'MySpace',
					'newsvine'    => 'NewsVine',
					'parler'      => 'Parler',
					'patreon'     => 'Patreon',
					'pocket'      => 'Pocket',
					'reddit'      => 'Reddit',
					'rss'         => 'RSS',
					'soundcloud'  => 'SoundCloud',
					'spotify'     => 'Spotify',
					'twitch'      => 'Twitch',
					'telegram'    => 'Telegram',
					'tiktok'      => 'TikTok',
					'vimeo'       => 'Vimeo',
					'vkontakte'   => 'VKontakte',
					'youtube'     => 'YouTube',
					'yummly'      => 'Yummly',
					'line'        => 'Line',
				)
			)
		);

		return $networks;
	}

	/**
	 * Filter networks that have follow count API support
	 *
	 * @since 1.0.0
	 */
	public function social_follow_networks_with_api( $networks = array() ) {

		$networks = array_merge(
			$networks,
			array(
				// 'subscribers',
				// '500px',
				// 'deviantart',
				// 'facebook',
				// 'instagram',
				// 'linkedin',
				// 'soundcloud',
				'tumblr',
				'twitch',
				'twitter',
				// 'vimeo',
				// 'vkontakte',
				// 'youtube',
				'patreon',
			)
		);

		return $networks;
	}

	/**
	 * Filter networks that have follow count support, but don't require authentication
	 *
	 * @since 1.0.0
	 */
	public function social_follow_networks_automatic( $networks = array() ) {

		$networks = array_merge(
			$networks,
			array(
				'github',
			)
		);

		return $networks;
	}

	/**
	 * Follow Network authorized. Store information about the user.
	 *
	 * @since 1.0.0
	 */
	public function social_follow_authorized_network( $networks = array() ) {

		// Security check.
		if ( ! current_user_can( apply_filters( 'socialsnap_manage_cap', 'manage_options' ) ) ) {
			return $networks;
		}

		if ( isset( $_GET['ss_network_authorized'], $_GET['access_token'], $_GET['access_token_secret'], $_GET['network_key_index'], $_GET['profile'] ) ) {

			$network = sanitize_text_field( $_GET['ss_network_authorized'] );

			if ( ! array_key_exists( $network, socialsnap_get_social_follow_networks() ) ) {
				return $networks;
			}

			$profile = array(
				'id'       => sanitize_text_field( $_GET['profile']['id'] ),
				'username' => sanitize_text_field( $_GET['profile']['username'] ),
				'url'      => esc_url( $_GET['profile']['url'] ),
			);

			if ( is_array( $_GET['access_token'] ) ) {
				$_GET['access_token'] = array_map( 'sanitize_text_field', wp_unslash( $_GET['access_token'] ) );
			} else {
				$_GET['access_token'] = sanitize_text_field( $_GET['access_token'] );
			}

			if ( is_array( $_GET['access_token_secret'] ) ) {
				$_GET['access_token_secret'] = array_map( 'sanitize_text_field', wp_unslash( $_GET['access_token_secret'] ) );
			} else {
				$_GET['access_token_secret'] = sanitize_text_field( $_GET['access_token_secret'] );
			}

			$_GET['network_key_index'] = sanitize_text_field( $_GET['network_key_index'] );

			$networks[ $network ] = array(
				'profile'             => $profile,
				'access_token'        => $_GET['access_token'],
				'access_token_secret' => $_GET['access_token_secret'],
				'network_key_index'   => $_GET['network_key_index'],
				'authorized'          => true,
			);

			if ( isset( $_GET['accounts'] ) ) {

				$accounts = $_GET['accounts'];

				if ( is_array( $accounts ) && ! empty( $accounts ) ) {
					foreach ( $accounts as $key => $value ) {

						$key = sanitize_text_field( $key );

						$accounts[ $key ]['name'] = sanitize_text_field( urldecode( $value['name'] ) );
						$accounts[ $key ]['id']   = sanitize_text_field( urldecode( $value['id'] ) );
						$accounts[ $key ]['slug'] = sanitize_text_field( urldecode( $value['slug'] ) );
						$accounts[ $key ]['url']  = esc_url( urldecode( $value['url'] ) );
					}
				}

				$networks[ $network ]['accounts'] = $accounts;
			}

			// Update authorized networks
			update_option( 'socialsnap_authorized_networks', $networks );
		}

		return $networks;
	}

	/**
	 * Follow Network disconnect.
	 *
	 * @since 1.0.0
	 */
	public function social_follow_disconnect_network( $networks = array() ) {

		// Disconnect a network
		if ( isset( $_GET['network_disconnect'] ) ) {

			$network = $_GET['network_disconnect'];

			// Update authorized networks
			unset( $networks[ $network ] );
			update_option( 'socialsnap_authorized_networks', $networks );

			// Update options panel
			$settings                        = socialsnap_settings( 'ss_social_follow_connect_networks' );
			$profile                         = $settings[ $network ]['profile'];
			$settings[ $network ]['profile'] = array();
			update_socialsnap_settings( 'ss_social_follow_connect_networks', $settings );

			// Update network follow counts.
			$counts = apply_filters(
				'socialsnap_filter_social_follow_counts',
				(array) get_option( 'socialsnap_follow_counts' )
			);
			unset( $counts[ $network . '_' . $profile['username'] ] );
			update_option( 'socialsnap_follow_counts', $counts );

			// Remove transient
			delete_site_transient( 'socialsnap_follow_count_' . $network . '_' . $profile['username'] );
		}

		return $networks;
	}

	/**
	 * Ajax handler to retrieve follow counts for networks that have expired counts.
	 *
	 * @since 1.0.0
	 */
	public function social_follow_counts_api( $count, $network, $authorized_networks, $configured_networks ) {

		$default_settings = array(
			'access_token'        => '',
			'access_token_secret' => '',
			'network_key_index'   => '',
			'manual_followers'    => '',
			'label'               => '',
			'authorized'          => false,
		);

		$authorized_networks[ $network ] = wp_parse_args( $authorized_networks[ $network ], $default_settings );
		$settings                        = wp_parse_args( $configured_networks[ $network ], $authorized_networks[ $network ] );

		if ( ! $settings['access_token'] || ! $settings['access_token_secret'] || ! is_numeric( $settings['network_key_index'] ) || ! isset( $settings['profile'] ) || ! isset( $settings['profile']['username'] ) ) {
			return;
		}

		// Build request URL
		$url = add_query_arg(
			array(
				'network'             => $network,
				'network_id'          => $settings['profile']['username'],
				'profile_id'          => isset( $settings['profile']['id'] ) ? $settings['profile']['id'] : $settings['profile']['username'],
				'client_url'          => esc_url( get_bloginfo( 'url' ) ),
				'network_key_index'   => $settings['network_key_index'],
				'access_token'        => $settings['access_token'],
				'access_token_secret' => $settings['access_token_secret'],
			),
			'https://socialsnap.com/wp-json/api/v1/follow'
		);

		$args = array(
			'user-agent' => 'SocialSnap/' . SOCIALSNAP_VERSION . '; ' . esc_url( home_url() ),
			'timeout'    => 60,
		);

		$request = wp_remote_get( $url, $args );

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( isset( $response['code'] ) && 'error' == $response['code'] ) {
			return new WP_Error( 'error', sprintf( 'Error retrieving followers for %s.', $network ) );
		}

		if ( is_numeric( $response ) ) {
			return $response;
		}
	}

	/**
	 * Check if network supports automatic follow counts.
	 *
	 * @since 1.0.0
	 */
	public function is_network_automatic( $automatic, $network ) {

		if ( in_array( $network, (array) $this->authorized_networks, true ) ) {
			return true;
		}

		return $automatic;
	}

	/**
	 * Get network followers for automatic networks.
	 *
	 * @since 1.0.0
	 */
	public function social_follow_counts_automatic( $count, $network, $data ) {

		if ( ! isset( $data['profile']['username'] ) || ! $data['profile']['username'] ) {
			return $count;
		}

		if ( 'github' == $network ) {

			$request = wp_remote_get( 'https://api.github.com/users/' . sanitize_user( $data['profile']['username'] ) );

			if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
				return $count;
			}

			$request = wp_remote_retrieve_body( $request );
			$request = json_decode( $request );

			if ( isset( $request->followers ) ) {
				$count = (int) $request->followers;
			}
		}

		return $count;
	}

}

/**
 * The function which returns the one SocialSnap_Social_Follow_PRO instance.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $socialsnap_pro_social_follow = socialsnap_pro_social_follow(); ?>
 *
 * @since  1.1.6
 * @return object
 */
function socialsnap_pro_social_follow() {
	return SocialSnap_Social_Follow_PRO::instance();
}

socialsnap_pro_social_follow();
