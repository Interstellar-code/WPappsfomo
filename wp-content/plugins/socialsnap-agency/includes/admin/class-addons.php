<?php
/**
 * Addons page class.
 *
 * This page lists and handles available Addons.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Addons extends SocialSnap_Admin_Page {

	/**
	 * Social Snap addons.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $addons;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Call parent constructor
		parent::__construct();

		// Page details
		$this->page_slug = 'addons';
		$this->title     = __( 'Available Addons', 'socialsnap' );

		// Actions
		add_action( 'admin_menu', array( $this, 'register_pages' ), 14 );
		add_action( 'admin_notices', array( $this, 'socialsnap_addons_notice' ) );
	}

	/**
	 * Register the pages to be used for the Settings screen.
	 *
	 * @since 1.0.0
	 */
	public function register_pages() {

		// Add 'Addons' submenu page
		add_submenu_page(
			'socialsnap-settings',
			__( 'Social Snap Addons', 'socialsnap' ),
			'<span style="color: #75bb35">' . __( 'Addons', 'socialsnap' ) . '</span>',
			apply_filters( 'socialsnap_addons_cap', 'manage_options' ),
			'socialsnap-addons',
			array( $this, 'render' )
		);
	}

	/**
	 * Build the output for the plugin addons page.
	 *
	 * @since 1.0.0
	 */
	public function render() {

		// Get list of addons
		$this->addons = $this->get_addon_list();

		?>
		<div id="ss-addons" class="ss-page-wrapper ss-clearfix">

			<?php if ( empty( $this->addons ) ) { ?>

				<div class="error notice">
					<p><?php esc_html_e( 'There was an issue retrieving the addons for this site. Please click on the button above the refresh the addons data.', 'socialsnap' ); ?></p>
				</div>

				<?php
			} else {

				$this->addons_list( $this->addons );

			}
			?>

		</div><!-- END #ss-addons -->
		<?php
	}

	/**
	 * Output of the addons list.
	 *
	 * @since 1.0.0
	 */
	private function addons_list( $addons ) {

		$plugins = get_plugins();

		$addons_list = array(
			'available'   => array(),
			'upgrade'     => array(),
			'coming_soon' => array(),
		);

		// Sort installable and upgradable addons
		if ( is_array( $addons ) && ! empty( $addons ) ) {
			foreach ( $addons as $addon ) {

				if ( '' === $addon['version'] ) {
					$addons_list['coming_soon'][] = $addon;
				} else {

					$valid = false;

					if ( socialsnap()->pro ) {
						$license = socialsnap()->license->info();
						$valid   = ( 'valid' === $license['status'] ) && in_array( $license['slug'], $addon['package'] );
					}

					if ( $valid ) {
						$addons_list['available'][] = $addon;
					} else {
						$addons_list['upgrade'][] = $addon;
					}
				}
			}
		}

		$addons = array_merge( $addons_list['available'], $addons_list['upgrade'], $addons_list['coming_soon'] );
		?>

		<div class="ss-row">

			<?php
			if ( ! empty( $addons ) ) {

				foreach ( $addons as $addon ) {

					$plugin_basename = $this->get_plugin_basename_from_slug( $addon['slug'], $plugins );

					$status        = 'upgrade';
					$status_label  = __( 'Not Installed', 'socialsnap' );
					$button_label  = __( 'Upgrade Now', 'socialsnap' );
					$button_action = 'addon-upgrade';
					$button_url    = '#';

					if ( in_array( $addon, $addons_list['coming_soon'] ) ) {
						$status        = 'coming_soon';
						$status_label  = '';
						$button_label  = __( 'Coming Soon', 'socialsnap' );
						$button_action = '';
						$button_url    = '#';
					} elseif ( in_array( $addon, $addons_list['upgrade'] ) ) {
						$status        = 'upgrade';
						$status_label  = __( 'Not Installed', 'socialsnap' );
						$button_label  = apply_filters( 'socialsnap_upgrade_button_text', __( 'Upgrade Now', 'socialsnap' ) );
						$button_action = 'upgrade';
						$button_url    = socialsnap_upgrade_link();
					} elseif ( ! isset( $plugins[ $plugin_basename ] ) ) {
						$status        = 'download';
						$status_label  = __( 'Not Installed', 'socialsnap' );
						$button_label  = __( 'Install', 'socialsnap' );
						$button_action = 'install_addon';
					} elseif ( is_plugin_inactive( $plugin_basename ) ) {
						$status        = 'inactive';
						$status_label  = __( 'Inactive', 'socialsnap' );
						$button_label  = __( 'Activate', 'socialsnap' );
						$button_action = 'activate_addon';
					} elseif ( is_plugin_active( $plugin_basename ) ) {
						$status        = 'active';
						$status_label  = __( 'Active', 'socialsnap' );
						$button_label  = __( 'Deactivate', 'socialsnap' );
						$button_action = 'deactivate_addon';
					}

					$new_tab = '';
					if ( 'upgrade' === $status ) {
						$new_tab = ' target="_blank"';
					} else {
						$new_tab = '';
					}

					// Only Coming soon addons have disabled button
					$disabled = 'coming_soon' !== $status ? '' : ' disabled="disabled"';
					?>

					<div class="ss-col-4">
						<div class="ss-addon">
							<div class="ss-addon-header">
								<img src="<?php echo esc_url( $addon['image'] ); ?>" />
							</div><!-- END .ss-addon-header -->

							<div class="ss-addon-description">
								<h5><?php echo esc_html( $addon['name'] ); ?></h5>
								<p><?php echo wp_kses_post( $addon['desc'] ); ?></p>
							</div>

							<div class="ss-addon-footer ss-<?php echo esc_attr( $status ); ?>">

								<?php if ( 'upgrade' !== $status ) { ?>
									<div class="ss-addon-status"><?php echo wp_kses_post( $status_label ); ?></div>
								<?php } ?>

								<span class="ss-addon-action">
									<span class="spinner"></span>
									<a href="<?php echo esc_url( $button_url ); ?>" class="ss-button ss-button-secondary <?php echo esc_attr( $button_action ); ?>" <?php echo esc_html( $new_tab ); ?> data-action="<?php echo esc_attr( $button_action ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'socialsnap_addon' ) ); ?>" data-plugin="<?php echo esc_attr( $plugin_basename ); ?>"<?php echo esc_html( $disabled ); ?>><?php echo wp_kses_post( $button_label ); ?></a>
								</span>
							</div>
						</div>
					</div><!-- END .ss-col-4 -->
					<?php
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Retrieve the plugin basename from the plugin slug.
	 *
	 * @since 1.0.0
	 * @param string $slug The plugin slug.
	 * @return string      The plugin basename if found, else the plugin slug.
	 */
	public function get_plugin_basename_from_slug( $slug, $plugins ) {

		$keys = array_keys( $plugins );

		foreach ( $keys as $key ) {
			if ( preg_match( '|^' . $slug . '|', $key ) ) {
				return $key;
			}
		}
		return $slug;
	}

	/**
	 * Get list of addons.
	 *
	 * @since 1.0.0
	 */
	private function get_addon_list( $force = false ) {

		// Get stored addons array
		$addons = get_site_transient( 'socialsnap_addons' );

		// Force refresh the value or fetch if does not exist.
		if ( $force || false === $addons ) {
			$addons = $this->get_addons();
		}

		return $addons;
	}

	/**
	 * Pings the remote server for addons data.
	 *
	 * @since 1.0.0
	 * @return bool|array False if no key or failure, array of addon data otherwise.
	 */
	public function get_addons() {

		$params = apply_filters( 'socialsnap_get_addons_params', array() );
		$addons = socialsnap_perform_remote_request( 'get-addons-data', $params );

		// If there was an API error, set transient for only 10 minutes.
		if ( is_wp_error( $addons ) ) {
			set_site_transient( 'socialsnap_addons', false, 10 * MINUTE_IN_SECONDS );
			return false;
		}

		// If there was an error retrieving the addons, set the error.
		if ( isset( $addons->error ) ) {
			set_site_transient( 'socialsnap_addons', false, 10 * MINUTE_IN_SECONDS );
			return false;
		}

		// Convert to array.
		$addons = json_decode( wp_json_encode( $addons ), true );

		// Otherwise, our request worked. Save the data and return it.
		set_site_transient( 'socialsnap_addons', $addons, DAY_IN_SECONDS );
		return $addons;
	}

	/**
	 * Display a notice that Addons are PRO feature only.
	 *
	 * @since 1.0.0
	 */
	public function socialsnap_addons_notice() {

		// User disabled these notices.
		if ( socialsnap_settings( 'ss_remove_notices' ) ) {
			return;
		}

		if ( ! socialsnap()->pro ) {

			$message = '<p><strong>' . esc_html__( 'Social Snap Addons are a PRO feature.', 'socialsnap' ) . '</strong>' . esc_html__( 'Please upgrade to Social Snap PRO plan to unlock Addons and more awesome features.', 'socialsnap' ) . '<br/><a href="' . socialsnap_upgrade_link() . '" class="ss-button ss-small-button ss-upgrade-button" target="_blank">' . esc_html( apply_filters( 'socialsnap_upgrade_button_text', __( 'Upgrade Now', 'socialsnap' ) ) ) . '</a></p>';

			socialsnap_print_notice(
				array(
					'type'           => 'info',
					'message'        => $message,
					'message_id'     => 'upgrade-to-enable-addons',
					'class'          => 'socialsnap-addons-notice',
					'display_on'     => 'socialsnap-addons',
					'is_dismissible' => false,
				)
			);
		}
	}
}
new SocialSnap_Addons();
