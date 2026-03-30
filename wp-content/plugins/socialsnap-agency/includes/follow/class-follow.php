<?php
/**
 * Social Follow Class.
 * Supports shortcode, block editor element, automatic and manual follewer counts.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Social_Follow {

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
	private $authorized_networks;

	/**
	 * Social Networks array that can obtain automatic followers but do not need API.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $automatic_networks;

	/**
	 * Array of follow counts, for each configured network.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $counts;

	/**
	 * Array of networks that have expired follow counts.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $expired_counts = array();

	/**
	 * Main Social Snap Social Follow instance.
	 *
	 * @since 1.0.0
	 * @return SocialSnap_Social_Follow
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SocialSnap_Social_Follow ) ) {
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

		if ( is_admin() ) {
			add_action( 'init', array( $this, 'init' ), 20 );
		} else {
			add_action( 'wp', array( $this, 'init' ), 20 );
		}

		// Add live preview.
		add_action( 'socialsnap_live_preview', array( $this, 'preview_settings' ) );

		// Add support for Block Editor.
		add_action( 'plugins_loaded', array( $this, 'block_editor_support' ) );

		// Filter only configured networks.
		add_filter( 'socialsnap_configured_networks', array( $this, 'get_configured_networks' ) );

		// Ajax to update automatic follow counts.
		add_action( 'wp_ajax_ss_sf_counts', array( $this, 'get_follow_count_ajax' ) );
		add_action( 'wp_ajax_nopriv_ss_sf_counts', array( $this, 'get_follow_count_ajax' ) );

		// Expired counts flag.
		add_action( 'wp_footer', array( $this, 'expired_counts' ) );
		add_action( 'admin_footer', array( $this, 'expired_counts' ) );

		// Register a shortcode.
		add_shortcode( 'ss_social_follow', array( $this, 'register_shortcodes' ) );
	}

	/**
	 * Initialize class variables.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Get authorized networks.
		$this->authorized_networks = (array) apply_filters(
			'socialsnap_filter_social_follow_authorized_networks',
			(array) get_option( 'socialsnap_authorized_networks' )
		);

		// Synchronize authorized networks.
		$this->synchronize_authorized_networks();

		// Get automatic networks.
		$this->automatic_networks = socialsnap_social_follow_networks_automatic();

		// Get only networks configured in options.
		$this->configured_networks = apply_filters(
			'socialsnap_configured_networks',
			array()
		);
	}

	/**
	 * Register shortcode for social follow.
	 *
	 * @param array $atts Shortcode attributes.
	 * @since 1.0.0
	 */
	public function register_shortcodes( $atts ) {

		$a = shortcode_atts(
			array(
				'networks'         => '',
				'total_followers'  => socialsnap_settings( 'ss_sf_total_followers' ),
				'button_followers' => socialsnap_settings( 'ss_sf_button_followers' ),
				'size'             => socialsnap_settings( 'ss_sf_button_size' ),
				'spacing'          => socialsnap_settings( 'ss_sf_button_spacing' ),
				'columns'          => socialsnap_settings( 'ss_sf_button_columns' ),
				'vertical'         => socialsnap_settings( 'ss_sf_button_vertical' ),
				'scheme'           => socialsnap_settings( 'ss_sf_button_scheme' ),
				'labels'           => socialsnap_settings( 'ss_sf_button_labels' ),
			),
			$atts
		);

		$allowed_networks = array_keys( socialsnap_get_social_follow_networks() );

		if ( ! isset( $a['networks'] ) || '' == $a['networks'] ) {
			$a['networks'] = $this->configured_networks;
		} else {

			$networks      = explode( ';', strtolower( str_replace( ' ', '', $a['networks'] ) ) );
			$a['networks'] = array();

			if ( is_array( $networks ) && ! empty( $networks ) ) {
				foreach ( $networks as $network ) {

					if ( ! in_array( $network, $allowed_networks ) ) {
						continue;
					}

					if ( isset( $this->configured_networks[ $network ] ) ) {
						$a['networks'][ $network ] = $this->configured_networks[ $network ];
					} else {
						$a['networks'][ $network ] = array(
							'profile'          => array(
								'username' => '',
								'url'      => '',
							),
							/* translators: $1%s is network name */
							'label'            => wp_kses_post( sprintf( __( 'Follow us on %1$s', 'socialsnap' ), socialsnap_get_network_name( $network ) ) ),
							'manual_followers' => '',
						);
					}
				}
			}
		}

		if ( is_admin() ) {
			$a['networks'] = apply_filters( 'socialsnap_filter_social_follow_networks', socialsnap_settings( 'ss_social_follow_connect_networks' ) );
		}

		if ( ! is_array( $a['networks'] ) || empty( $a['networks'] ) ) {
			return;
		}

		$class = array( 'ss-follow-wrapper', 'ss-clearfix', 'ss-' . $a['size'] . '-buttons' );

		if ( $a['spacing'] ) {
			$class[] = 'ss-with-spacing';
		}

		if ( $a['columns'] ) {
			$class[] = 'ss-columns-' . $a['columns'];
		}

		if ( $a['vertical'] ) {
			$class[] = 'ss-follow-vertical';
		}

		if ( $a['scheme'] ) {
			$class[] = 'ss-' . $a['scheme'] . '-style';
		}

		$class = array_unique( $class );
		$class = apply_filters( 'socialsnap_follow_networks_shortcode_class', $class );
		$class = implode( ' ', $class );

		// Enqueue assets.
		socialsnap_enqueue_assets();

		ob_start();

		?>

		<div>

		<?php
		if ( $a['total_followers'] || is_admin() ) {

			$total_followers = $this->get_total_followers( $a['networks'] );
			?>

			<h4 class="ss-follow-total-counter">
				<strong><?php echo esc_html( socialsnap_format_number( $total_followers ) ); ?></strong> <?php echo esc_html( _n( 'Follower', 'Followers', $total_followers, 'socialsnap' ) ); ?>
			</h4>
		<?php } ?>

		<div class="<?php echo esc_attr( $class ); ?>">

			<?php
			foreach ( $a['networks'] as $network_id => $network_settings ) {

				if ( 'order' == $network_id ) {
					continue;
				}

				$network_url = isset( $network_settings['profile'], $network_settings['profile']['url'] ) ? $network_settings['profile']['url'] : '#';
				$network_url = is_admin() ? '#' : $network_url;

				$additional_data = '';

				$is_automatic = in_array( $network_id, (array) $this->automatic_networks, true );

				if ( apply_filters( 'socialsnap_automatic_follow_count', $is_automatic, $network_id ) ) {
					$additional_data .= ' data-automatic="true"';
				}

				$additional_data .= ' data-ss-sf-network-id="' . esc_attr( $network_id ) . '"';
				?>

				<div class="ss-follow-column"<?php echo esc_html( $additional_data ); ?>>
					<a href="<?php echo esc_url( $network_url ); ?>" class="ss-follow-network ss-<?php echo esc_attr( $network_id ); ?>-color" rel="nofollow noopener" aria-label="<?php echo esc_attr( $network_id ); ?>" target="_blank">
						<span class="ss-follow-icon"><?php echo socialsnap()->icons->get_svg( $network_id ); // phpcs:ignore ?></span>

						<?php if ( $a['labels'] && $network_settings['label'] || is_admin() ) { ?>
							<span class="ss-follow-network-label"><?php echo esc_html( $network_settings['label'] ); ?></span>
						<?php } ?>

						<?php
						if ( $a['button_followers'] || is_admin() ) {

							$count = (int) $this->get_follow_count( $network_id );
							?>

							<span class="ss-follow-network-count">
								<span class="ss-follow-network-count-number"><?php echo esc_html( socialsnap_format_number( $count ) ); ?></span>
								<span class="ss-follow-network-count-label"><?php echo esc_html( apply_filters( 'socialsnap_follower_count_label', _n( 'Follower', 'Followers', $count, 'socialsnap' ), $count, $network_id ) ); ?></span>
							</span>

						<?php } ?>
					</a>
				</div><!-- END .ss-follow-column -->

			<?php } ?>

			</div>

		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Get number of followers for a network.
	 * The count values are refreshed once every 24 hours.
	 *
	 * @param string $network Network name.
	 * @since 1.0.0
	 */
	private function get_follow_count( $network = '' ) {

		// Check if network is configured.
		if ( '' == $network || ! isset( $this->configured_networks[ $network ] ) ) {
			return;
		}

		$network_settings = $this->configured_networks[ $network ];
		$api_networks     = socialsnap_social_follow_networks_with_api();

		// Get stored value.
		if ( isset( $this->configured_networks[ $network ]['profile'] ) && isset( $this->configured_networks[ $network ]['profile']['username'] ) ) {
			$cache = get_site_transient( 'socialsnap_follow_count_' . $network . '_' . $this->configured_networks[ $network ]['profile']['username'] );
		} else {

			// Issue with retrieving user profile, can't proceed.
			return;
		}

		// This network supports API counts and is authorized or is automatic networks.
		if ( in_array( $network, $api_networks, true ) && in_array( $network, array_keys( $this->authorized_networks ), true ) || in_array( $network, $this->automatic_networks, true ) ) {

			// No stored value, we need to refresh counts.
			if ( false === $cache ) {

				$this->expired_counts[] = $network;
				$this->expired_counts   = array_unique( $this->expired_counts );

				// Check if we have some previously stored value.
				$c = get_option( 'socialsnap_follow_count_' . $network . '_' . $network_settings['profile']['username'] );
				if ( $c ) {
					$cache = $c;
				} else {

					// Fallback to manual followers as last resort.
					$cache = isset( $network_settings['manual_followers'] ) ? (int) $network_settings['manual_followers'] : false;
				}
			} elseif ( is_wp_error( $cache ) ) {

				// Check if we have some previously stored value.
				$c = get_option( 'socialsnap_follow_count_' . $network . '_' . $network_settings['profile']['username'] );
				if ( $c ) {
					$cache = $c;
				} else {
					$cache = 0;
				}
			}

			$count = $cache;
		} else {

			// Get manual count.
			$count = isset( $network_settings['manual_followers'] ) ? $network_settings['manual_followers'] : false;

			if ( $count != $cache && ! is_admin() ) {

				// Save new count.
				set_site_transient( 'socialsnap_follow_count_' . $network . '_' . $network_settings['profile']['username'], $count, 24 * 60 * 60 );
			}
		}

		return $count;
	}

	/**
	 * Ajax handler which returns the follow count for network.
	 * The count values are stored for 24 hours.
	 *
	 * @since 1.0.0
	 */
	public function get_follow_count_ajax() {

		// Security check.
		check_ajax_referer( 'socialsnap_social_follow_count', 'security' );

		if ( ! isset( $_POST['sf_networks'] ) ) {
			wp_send_json_error();
		}

		$data = str_replace( '\\', '', sanitize_text_field( $_POST['sf_networks'] ) );
		$data = json_decode( $data, true );

		$network             = $data['network'];
		$authorized_networks = $data['authorized'];
		$configured_networks = $data['configured'];

		if ( ! isset( $configured_networks[ $network ] ) ) {
			wp_send_json_error();
		}

		$result   = false;
		$username = isset( $configured_networks[ $network ]['profile'], $configured_networks[ $network ]['profile']['username'] ) ? $configured_networks[ $network ]['profile']['username'] : false;

		// We can obtain follower count automatically without authentication.
		if ( in_array( $network, socialsnap_social_follow_networks_automatic() ) ) {
			$result = $this->get_automatic_follow_count( $network, $configured_networks[ $network ] );
		} elseif ( in_array( $network, socialsnap_social_follow_networks_with_api() ) ) {

			// Authenticaion is required.
			if ( isset( $authorized_networks[ $network ] ) ) {
				$result = $this->get_api_follow_count( $network, $authorized_networks, $configured_networks );
			}
		}

		// Store values.
		if ( $username ) {

			if ( is_numeric( $result ) ) {

				$current_count = get_option( 'socialsnap_follow_count_' . $network . '_' . $username, 0 );

				if ( $current_count && $current_count < $result ) {
					update_option( 'socialsnap_follow_count_' . $network . '_' . $username, (int) $result );
				}

				set_site_transient( 'socialsnap_follow_count_' . $network . '_' . $username, (int) $result, 24 * 60 * 60 );
				wp_send_json_success( array( 'count' => socialsnap_format_number( (int) $result ) ) );
			} elseif ( is_wp_error( $result ) ) {
				set_site_transient( 'socialsnap_follow_count_' . $network . '_' . $username, $result, 6 * 60 * 60 );
			}
		}

		wp_send_json_error();
	}

	/**
	 * Get number of followers for automatic networks (networks that do not require API but will return follow counts)
	 *
	 * @param string $network Network name.
	 * @param array  $data Network data.
	 * @since 1.0.0
	 */
	public function get_automatic_follow_count( $network, $data ) {

		$count = false;

		// Pinterest followers.
		if ( 'pinterest' == $network ) {

			if ( ! isset( $data['profile']['username'] ) || ! $data['profile']['username'] ) {
				return;
			}

			$count     = false;
			$url       = sprintf( 'http://www.pinterest.com/%1$s/', $data['profile']['username'] );
			$meta_tags = get_meta_tags( esc_url( $url ) );

			if ( is_array( $meta_tags ) && isset( $meta_tags['pinterestapp:followers'] ) ) {
				$count = (int) $meta_tags['pinterestapp:followers'];
			}
		}

		return apply_filters( 'socialsnap_social_follow_count_automatic', $count, $network, $data );
	}

	/**
	 * Get number of followers for automatic networks (networks that do not require API but will return follow counts)
	 *
	 * @param string $network Network name.
	 * @param array  $authorized_networks Array of authorized networks.
	 * @param array  $configured_networks Array of configured networks.
	 * @since 1.0.0
	 */
	public function get_api_follow_count( $network, $authorized_networks, $configured_networks ) {

		return apply_filters( 'socialsnap_social_follow_count_api', false, $network, $authorized_networks, $configured_networks );
	}

	/**
	 * Get total number of followers. This is the total of all configured networks.
	 * This value is recalculated every 24 hours.
	 *
	 * @param array $networks Array of used networks.
	 * @since 1.0.0
	 */
	public function get_total_followers( $networks = array() ) {

		if ( empty( $networks ) ) {
			$networks = $this->configured_networks;
		}

		if ( ! is_array( $networks ) || empty( $networks ) ) {
			return;
		}

		$total = 0;

		foreach ( $networks as $id => $settings ) {

			$total += (int) $this->get_follow_count( $id );
		}

		return $total;
	}

	/**
	 * Render settings preview screen
	 *
	 * @since 1.0.0
	 */
	public function preview_settings() {
		?>

		<div class="ss-preview-screen ss-preview-social_follow">
			<div class="ss-follow-preview-shortcode">

				<div class="ss-follow-configure-note">
					<?php esc_html_e( 'Please configure at least one network to show a preview.', 'socialsnap' ); ?>
				</div>

				<?php echo wp_kses( do_shortcode( '[ss_social_follow]' ), socialsnap_get_allowed_html_tags( 'post' ) ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get only follow networks that are configured.
	 *
	 * @param array $networks Array of configured follow networks.
	 * @since 1.0.0
	 */
	public function get_configured_networks( $networks ) {

		$follow_networks = socialsnap_settings( 'ss_social_follow_connect_networks' );

		if ( ! is_array( $follow_networks ) || empty( $follow_networks ) ) {
			return;
		}

		unset( $follow_networks['order'] );

		$configured = array();

		foreach ( $follow_networks as $id => $options ) {
			if ( isset( $options['profile'] ) && isset( $options['profile']['username'] ) && $options['profile']['username'] ) {
				$configured[ $id ] = $options;
			}
		}

		$configured['order'] = implode( ';', array_keys( $configured ) );

		return apply_filters( 'socialsnap_filter_social_follow_networks', $configured );
	}

	/**
	 * Make sure that options are syncronized with the authorized networks.
	 *
	 * @since 1.0.0
	 */
	private function synchronize_authorized_networks() {

		$follow_networks = socialsnap_settings( 'ss_social_follow_connect_networks' );

		if ( ! is_array( $follow_networks ) || empty( $follow_networks ) ) {
			return;
		}

		// Go to through each network.
		foreach ( $follow_networks as $id => $options ) {

			// If this network is not authorized, continue to next.
			if ( ! isset( $this->authorized_networks[ $id ] ) ) {
				continue;
			}

			// Check if this network supports multiple accounts.
			if ( isset( $follow_networks[ $id ]['accounts'] ) ) {

				// Go trough all accounts to see which one is selected.
				foreach ( $this->authorized_networks[ $id ]['accounts'] as $account ) {

					// This one is selected, fill in the data.
					if ( $account['id'] == $follow_networks[ $id ]['accounts'] ) {
						$follow_networks[ $id ]['profile'] = array(
							'username' => $account['slug'],
							'url'      => $account['url'],
						);
					}
				}
			} else {
				// Fill in the user data.
				$follow_networks[ $id ]['profile'] = $this->authorized_networks[ $id ]['profile'];
			}
		}

		// Update the options.
		update_socialsnap_settings( 'ss_social_follow_connect_networks', $follow_networks );
	}

	/**
	 * Register Block for Social Follow.
	 *
	 * @since 1.0.0
	 */
	public function block_editor_support() {

		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'socialsnap/social-follow',
			array(
				'render_callback' => array( $this, 'block_editor_social_follow' ),
			)
		);
	}

	/**
	 * Social Follow block editor support.
	 *
	 * @param array $attributes Block attributes.
	 * @since 1.0.0
	 */
	public function block_editor_social_follow( $attributes ) {
		ob_start();

		$defaults = array(
			'networks'        => 'twitter;facebook;pinterest',
			'totalFollowers'  => true,
			'buttonFollowers' => true,
			'size'            => 'regular',
			'spacing'         => true,
			'columns'         => '1',
			'vertical'        => false,
			'scheme'          => 'default',
			'labels'          => true,
		);

		$attributes['networks'] = isset( $attributes['networks'] ) ? $attributes['networks'] : 'twitter;facebook;pinterest';
		$attributes['networks'] = preg_replace( '/ |\t/', '', $attributes['networks'] );
		$attributes['networks'] = preg_replace( '/\n/', ';', $attributes['networks'] );
		$attributes['networks'] = strtolower( $attributes['networks'] );

		$attributes = wp_parse_args( $attributes, $defaults );

		$shortcode  = '[ss_social_follow';
		$shortcode .= ' networks="' . $attributes['networks'] . '"';
		$shortcode .= ' total_followers="' . $attributes['totalFollowers'] . '"';
		$shortcode .= ' button_followers="' . $attributes['buttonFollowers'] . '"';
		$shortcode .= ' size="' . $attributes['size'] . '"';
		$shortcode .= ' spacing="' . $attributes['spacing'] . '"';
		$shortcode .= ' columns="' . $attributes['columns'] . '"';
		$shortcode .= ' vertical="' . $attributes['vertical'] . '"';
		$shortcode .= ' scheme="' . $attributes['scheme'] . '"';
		$shortcode .= ' labels="' . $attributes['labels'] . '"';
		$shortcode .= ']';

		echo do_shortcode( $shortcode );

		return ob_get_clean();
	}

	/**
	 * Javascript Indicator that follow count cache has expired.
	 *
	 * @since 1.0.0
	 */
	public function expired_counts() {

		if ( ! is_array( $this->expired_counts ) || empty( $this->expired_counts ) ) {
			return;
		}

		if ( socialsnap_is_amp_page() ) {
			return;
		}

		$networks = array(
			'networks'            => $this->expired_counts,
			'authorized'          => $this->authorized_networks,
			'configured_networks' => $this->configured_networks,
			'security'            => wp_create_nonce( 'socialsnap_social_follow_count' ),
		);

		?>
		<!-- Social Snap Share count cache indicator -->
		<script type="text/javascript">
			var socialsnap_follow_counts = <?php echo wp_json_encode( $networks ); ?>
		</script>
		<!-- Social Snap Share count cache indicator -->
		<?php
	}
}

/**
 * The function which returns the one SocialSnap_Social_Follow instance.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $socialsnap_social_follow = socialsnap_social_follow(); ?>
 *
 * @since 1.0.0
 * @return object
 */
function socialsnap_social_follow() {
	return SocialSnap_Social_Follow::instance();
}

socialsnap_social_follow();
