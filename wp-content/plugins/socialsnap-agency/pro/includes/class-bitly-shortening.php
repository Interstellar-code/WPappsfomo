<?php
/**
 * Shorten links to be used in social sharing.
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Bitly_Shortening {

	/**
	 * Bitly Access Token.
	 *
	 * @since 1.0.0
	 */
	public $access_token;

	/**
	 * Cached links for loaded page.
	 *
	 * @since 1.0.0
	 */
	public $cached_links = array();

	/**
	 * IDs for which we've loaded links.
	 *
	 * @since 1.0.0
	 */
	public $cached_ids = array();

	/**
	 * Unched links for loaded page.
	 *
	 * @since 1.0.0
	 */
	public $uncached_links = array();

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add fields to settings panel.
		add_filter( 'socialsnap_settings_config', array( $this, 'add_settings_config' ), 6, 1 );
		add_action( 'socialsnap_settings_field_class', array( $this, 'add_settings_fields' ) );

		// Soft check if user runs plugin on localhost.
		if ( isset( $_SERVER['REMOTE_ADDR'] ) && in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1' ), true ) ) {

			// Display a notice.
			add_action( 'socialsnap_settings_config', array( $this, 'notice_localhost' ) );

			return;
		}

		// Listen for authorization.
		add_action( 'init', array( $this, 'authorization' ) );

		// Bitly not enabled.
		if ( ! socialsnap_settings( 'ss_bitly_enable' ) ) {
			return;
		}

		// Get user data.
		$user_data          = (array) get_option( 'socialsnap_bitly_user_data' );
		$this->access_token = isset( $user_data['access_token'] ) ? $user_data['access_token'] : false;

		// User not authenticated.
		if ( ! $this->access_token ) {
			return;
		}

		// Get shortlink.
		add_filter( 'socialsnap_shorten_link', array( $this, 'shorten_link' ), 99, 2 );

		// Print uncached links.
		add_action( 'wp_footer', array( $this, 'print_uncached_urls' ), 99 );

		// Get fresh shortlinks and cache.
		add_action( 'wp_ajax_ss_cache_links', array( $this, 'cache_shortlinks' ) );
		add_action( 'wp_ajax_nopriv_ss_cache_links', array( $this, 'cache_shortlinks' ) );
	}

	/**
	 * Scan for Bitly Authorization or Disconnect.
	 *
	 * @since 1.0.0
	 */
	public function authorization() {

		// Security check.
		if ( ! current_user_can( apply_filters( 'socialsnap_manage_cap', 'manage_options' ) ) ) {
			return;
		}

		// Bitly controls.
		if ( isset( $_GET['ss_network_authorized'] ) && 'bitly' === $_GET['ss_network_authorized'] ) {

			// Authorized.
			if ( isset( $_GET['access_token'], $_GET['profile'], $_GET['access_token_secret'], $_GET['network_key_index'] ) ) {

				$user_data = array(
					'profile'             => sanitize_text_field( $_GET['profile'] ),
					'access_token'        => sanitize_text_field( $_GET['access_token'] ),
					'access_token_secret' => sanitize_text_field( $_GET['access_token_secret'] ),
					'network_key_index'   => sanitize_text_field( $_GET['network_key_index'] ),
					'authorized'          => true,
				);

				update_option( 'socialsnap_bitly_user_data', $user_data );
			}

			// Disconnected.
			if ( isset( $_GET['error'] ) || isset( $_GET['disconnect'] ) ) {
				delete_option( 'socialsnap_bitly_user_data' );
			}

			// Redirect to bitly page in settings.
			wp_redirect(
				add_query_arg(
					array(
						'page' => 'socialsnap-settings#ss_link_shortening-ss',
					),
					admin_url( 'admin.php' )
				)
			);
			die;
		}
	}

	public function clean_url( $url ) {

		if ( false !== strpos( $url, 'preview' ) ) {
			$url = remove_query_arg( array( 'preview', 'preview_id', 'preview_nonce' ), $url );
		}

		if ( false !== strpos( $url, 'elementor' ) ) {
			$url = remove_query_arg( array( 'elementor-preview', 'ver' ), $url );
		}

		if ( false !== strpos( $url, 'complianz' ) ) {
			$url = remove_query_arg( array( 'complianz_scan_token', 'complianz_id' ), $url );
		}

		return $url;
	}

	/**
	 * Populate cached & uncached URLs.
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function shorten_link( $url, $network ) {

		// Skip networks that do not have a share URL.
		if ( in_array( $network, array( 'pinterest', 'heart', 'print' ), true ) ) {
			return $url;
		}

		$url = $this->clean_url( $url );

		$post_id = socialsnap_get_current_post_id();

		if ( ! in_array( $post_id, $this->cached_ids, true ) ) {

			$this->cached_ids[] = $post_id;

			if ( $post_id > 0 ) {
				$this->cached_links = array_merge( $this->cached_links, (array) get_post_meta( $post_id, 'ss_bitly_link', true ) );
			} elseif ( -1 === $post_id ) {
				$this->cached_links = array_merge( $this->cached_links, (array) get_option( 'socialsnap_cached_bitly_links' ) );
			}
		}

		$cached = $this->get_bitly_cache( $url );

		if ( $cached ) {
			return esc_url( $cached );
		} elseif ( 0 !== $post_id ) {

			$uncached = array(
				'url'     => $url,
				'post_id' => $post_id,
				'title'   => socialsnap_get_current_page_title(),
			);

			if ( ! in_array( $uncached, $this->uncached_links, true ) ) {
				$this->uncached_links[] = $uncached;
			}
		}

		return $url;
	}

	/**
	 * Generate and cache array of Bitly URLs
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function cache_shortlinks() {

		// Security check.
		check_ajax_referer( 'socialsnap_bitly_caching', 'security' );

		if ( ! isset( $_POST['ss_ls_arr'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing URLs to cache', 'socialsnap' ) ) );
		}

		if ( ! $this->access_token ) {
			wp_send_json_error( array( 'message' => __( 'Missing Bitly Access Token.', 'socialsnap' ) ) );
		}

		$url_array = $_POST['ss_ls_arr'];
		$return    = array();

		if ( is_array( $url_array ) && ! empty( $url_array ) ) {

			foreach ( $url_array as $url_to_cache ) {

				$url_to_cache['post_id'] = intval( sanitize_text_field( $url_to_cache['post_id'] ) );

				if ( 0 === $url_to_cache['post_id'] || empty( $url_to_cache['url'] ) ) {
					continue;
				}

				// Request Shortlink from Bitly API.
				$params = json_encode(
					array(
						'long_url' => $url_to_cache['url'],
					)
				);

				$header = array(
					'Authorization: Bearer ' . $this->access_token,
					'Content-Type: application/json',
					'Content-Length: ' . strlen( $params ),
				);

				$ch = curl_init( 'https://api-ssl.bitly.com/v4/shorten' );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );

				$result = curl_exec( $ch );
				$code   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
				curl_close( $ch );
				$result = json_decode( $result, true );

				if ( isset( $result['link'] ) && $result['link'] ) {

					$this->set_bitly_cache( $url_to_cache['url'], $result['link'], $url_to_cache['post_id'] );

					$cached = array(
						'url'     => $url_to_cache['url'],
						'short'   => $result['link'],
						'post_id' => $url_to_cache['post_id'],
					);

					$return[] = array(
						'success' => true,
						'cached'  => $cached,
						'urls'    => $url_array,
					);
				} elseif ( 403 === intval( $code ) ) {

					$this->access_token = '';
					delete_option( 'socialsnap_bitly_user_data' );
					$return[] = array(
						'success' => false,
						'message' => $body['status_txt'],
					);
				}
			}

			wp_send_json_success( array( 'return' => $return ) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to decode JSON', 'socialsnap' ) ) );
	}

	/**
	 * Check if we've already generated Bitly for this url
	 *
	 * @since 1.0.0
	 */
	private function get_bitly_cache( $url ) {

		$hash = md5( $url );

		if ( isset( $this->cached_links[ $hash ] ) ) {
			return $this->cached_links[ $hash ];
		}

		return false;
	}

	/**
	 * Save generated Bitly link
	 *
	 * @since 1.0.0
	 */
	private function set_bitly_cache( $url, $bitly, $post_id ) {

		$this->cached_links[ md5( $url ) ] = esc_url_raw( $bitly );

		if ( $post_id > 0 ) {
			$cached                = (array) get_post_meta( $post_id, 'ss_bitly_link', true );
			$cached[ md5( $url ) ] = esc_url_raw( $bitly );
			update_post_meta( $post_id, 'ss_bitly_link', $cached );
		} elseif ( -1 === $post_id ) {
			$cached                = (array) get_option( 'socialsnap_cached_bitly_links' );
			$cached[ md5( $url ) ] = esc_url_raw( $bitly );
			update_option( 'socialsnap_cached_bitly_links', $cached );
		}
	}

	/**
	 * Print uncached links to be cached by ss_cache_links ajax action.
	 * Should be generated only on first page load or on url change.
	 *
	 * @since 1.0.0
	 */
	public function print_uncached_urls() {

		if ( ! is_array( $this->uncached_links ) || empty( $this->uncached_links ) ) {
			return;
		}

		echo '<!-- Social Snap Uncached Links --><script type="text/javascript">var SocialSnapUncachedBitlyLinks=' . wp_json_encode( $this->uncached_links ) . ';SocialSnapUncachedBitlySecurity="' . esc_html( wp_create_nonce( 'socialsnap_bitly_caching' ) ) . '";</script><!-- Social Snap Uncached Links';
	}

	/**
	 * Register Social Meta settings fields.
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function add_settings_config( $settings ) {

		if ( ! isset( $settings['ss_advanced_settings'] ) ) {
			return $settings;
		}

		$link_shortening = array(
			'ss_bitly_enable'    => array(
				'id'      => 'ss_bitly_enable',
				'name'    => esc_html__( 'Bitly URL Shortener', 'socialsnap' ),
				'type'    => 'toggle',
				'default' => false,
			),
			'ss_bitly_authorize' => array(
				'id'         => 'ss_bitly_authorize',
				'name'       => esc_html__( 'Authorize', 'socialsnap' ),
				'type'       => 'bitly_authorize',
				'default'    => false,
				'dependency' => array(
					'element' => 'ss_bitly_enable',
					'value'   => 'true',
				),
			),
		);

		$settings['ss_advanced_settings']['fields']['ss_link_shortening']['fields'] = $link_shortening;

		return $settings;
	}

	/**
	 * Register Bitly authorize field classe.
	 *
	 * @since 1.0.0
	 */
	public function add_settings_fields() {
		require_once SOCIALSNAP_PLUGIN_DIR . 'pro/includes/admin/settings/fields/field_bitly_authorize.php';
	}

	/**
	 * Localhost notice
	 *
	 * @since 1.0.0
	 */
	public function notice_localhost( $settings ) {

		$settings['ss_advanced_settings']['fields']['ss_link_shortening']['fields']['ss_ss_networks_note'] = array(
			'id'         => 'ss_ss_networks_note',
			'name'       => esc_html__( 'Note: ', 'socialsnap' ),
			'desc'       => esc_html__( 'Bitly authorization and URL shortening will not work on localhost.', 'socialsnap' ),
			'type'       => 'note',
			'default'    => true,
			'dependency' => array(
				'element' => 'ss_bitly_enable',
				'value'   => 'true',
			),
		);

		return $settings;
	}
}
new SocialSnap_Bitly_Shortening();
