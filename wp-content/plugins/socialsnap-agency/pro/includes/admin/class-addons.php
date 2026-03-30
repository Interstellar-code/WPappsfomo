<?php
/**
 * Addons page class PRO extension.
 *
 * Extend the Lite Addons page.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Addons_Pro {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Change the header button
		add_filter( 'socialsnap_header_bar_button', array( $this, 'refresh_addons_button' ), 10, 2 );

		// Admin Notice
		add_action( 'admin_notices', array( $this, 'socialsnap_addons_notice' ) );

		// Install Addon
		add_action( 'wp_ajax_socialsnap_install_addon', array( $this, 'install_addon' ) );

		// Activate Addon
		add_action( 'wp_ajax_socialsnap_activate_addon', array( $this, 'activate_addon' ) );

		// Deactivate Addon
		add_action( 'wp_ajax_socialsnap_deactivate_addon', array( $this, 'deactivate_addon' ) );

		// Refresh addons list
		add_action( 'admin_init', array( $this, 'refresh_addons' ) );

		// Add license key to addons remote request
		add_filter( 'socialsnap_get_addons_params', array( $this, 'remote_request_addons_params' ) );
	}

	/**
	 * Button to force refreshing the addons list.
	 *
	 * @since 1.0.0
	 */
	public function refresh_addons_button( $button, $page ) {

		if ( 'socialsnap-addons' === $page ) {
			$addons_button = '<a href="' . esc_url_raw( add_query_arg( array( 'socialsnap_refresh_addons' => '1' ) ) ) . '" class="ss-button ss-small-button">' . __( 'Refresh Addons', 'socialsnap' ) . '</a>';
		}

		return $addons_button;
	}

	/**
	 * Ajax action to Install Addon
	 *
	 * @since 1.0.0
	 */
	public function install_addon() {

		// Run a security check
		check_ajax_referer( 'socialsnap_addon', 'nonce' );

		// Plugin name is required
		if ( empty( $_POST['plugin'] ) ) {
			wp_send_json_error( __( 'Could not install addon. Please download from socialsnap.com and install manually.', 'socialsnap' ) );
		}

		// Set the current screen to avoid undefined notices
		set_current_screen();

		// Prepare variables
		$url = esc_url_raw( add_query_arg( array( 'page' => 'socialsnap-addons' ), admin_url( 'admin.php' ) ) );

		// Check for file system permissions
		$creds = request_filesystem_credentials( $url, '', false, false, null );
		if ( false === $creds ) {
			wp_send_json_error( __( 'Could not install addon. Please download from socialsnap.com and install manually.', 'socialsnap' ) );
		}

		if ( ! WP_Filesystem( $creds ) ) {
			wp_send_json_error( __( 'Could not install addon. Please download from socialsnap.com and install manually.', 'socialsnap' ) );
		}

		// Download URL
		$download_url = '';

		// Get addons list
		$addons = get_site_transient( 'socialsnap_addons' );

		// Check if addons list exist
		if ( is_array( $addons ) && ! empty( $addons ) ) {

			// Go through all addons and check for download URL
			foreach ( $addons as $addon ) {
				if ( isset( $addon['url'], $addon['slug'] ) && '' !== $addon['url'] && $addon['slug'] === $_POST['plugin'] ) {
					$download_url = $addon['url'];
				}
			}
		}

		// No download URL
		if ( ! $download_url ) {
			wp_send_json_error( __( 'Could not install addon. Please download from socialsnap.com and install manually.', 'socialsnap' ) );
		}

		// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once SOCIALSNAP_PLUGIN_DIR . 'pro/includes/admin/class-install-skin.php';

		// Create the plugin upgrader with our custom skin.
		$skin      = new SocialSnap_Install_Skin();
		$installer = new Plugin_Upgrader( $skin );
		$installer->install( $download_url );

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();

		if ( $installer->plugin_info() ) {

			$plugin_basename = $installer->plugin_info();

			wp_send_json_success(
				array(
					'status'          => 'inactive',
					'status_label'    => __( 'Inactive', 'socialsnap' ),
					'button_label'    => __( 'Activate', 'socialsnap' ),
					'button_action'   => 'activate_addon',
					'plugin_basename' => $plugin_basename,
				)
			);
		}

		wp_send_json_error( __( 'Could not install addon. Please download from socialsnap.com and install manually.', 'socialsnap' ) );
	}

	/**
	 * Ajax action to Activate Addon
	 *
	 * @since 1.0.0
	 */
	public function activate_addon() {

		// Run a security check
		check_ajax_referer( 'socialsnap_addon', 'nonce' );

		if ( isset( $_POST['plugin'] ) ) {

			$activate = activate_plugins( $_POST['plugin'] );

			if ( ! is_wp_error( $activate ) ) {
				wp_send_json_success(
					array(
						'status'        => 'active',
						'status_label'  => __( 'Active', 'socialsnap' ),
						'button_label'  => __( 'Deactivate', 'socialsnap' ),
						'button_action' => 'deactivate_addon',
						'addon_url'     => esc_url( $_POST['plugin'] ),
					)
				);
			}
		}

		wp_send_json_error( __( 'Could not activate addon. Please activate from the Plugins page.', 'socialsnap' ) );
	}

	/**
	 * Ajax action to Deactivate Addon
	 *
	 * @since 1.0.0
	 */
	public function deactivate_addon() {

		// Run a security check
		check_ajax_referer( 'socialsnap_addon', 'nonce' );

		if ( isset( $_POST['plugin'] ) ) {

			$deactivate = deactivate_plugins( $_POST['plugin'] );

			wp_send_json_success(
				array(
					'status'        => 'inactive',
					'status_label'  => __( 'Inactive', 'socialsnap' ),
					'button_label'  => __( 'Activate', 'socialsnap' ),
					'button_action' => 'activate_addon',
					'addon_url'     => esc_url( $_POST['plugin'] ),
				)
			);

		} else {
			wp_send_json_error( __( 'Could not deactivate addon. Please deacticate from the Plugins page.', 'socialsnap' ) );
		}
	}

	/**
	 * Display a notice that a license is required.
	 *
	 * @since 1.0.0
	 */
	public function socialsnap_addons_notice() {

		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		// Bail if we are not on the Addons page.
		if ( 'socialsnap-addons' !== $_GET['page'] ) {
			return;
		}

		// User disabled these notices.
		if ( socialsnap_settings( 'ss_remove_notices' ) ) {
			return;
		}

		// Get license
		$license = socialsnap()->license->info();

		// Check license type
		if ( $license['slug'] && ! in_array( $license['slug'], array( 'social-snap-pro', 'social-snap-agency' ) ) ) {

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

			return;
		}

		// Don't display the notice for valid licenses
		if ( 'valid' === $license['status'] ) {
			return;
		}

		$message = '<p><strong>' . esc_html__( 'Social Snap Addons require an activated license.', 'socialsnap' ) . '</strong>' . esc_html__( 'Please enter and activate your license key for Social Snap to get access to Addons.', 'socialsnap' ) . '<br /><a href="' . admin_url( 'admin.php?page=socialsnap-license' ) . '" class="ss-button ss-small-button ss-upgrade-button">' . esc_html__( 'Activate', 'socialsnap' ) . '</a></p>';

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

	/**
	 * Clear cached addons so we force fetching new data.
	 *
	 * @since 1.0.0
	 */
	public function refresh_addons() {

		if ( ! isset( $_GET['socialsnap_refresh_addons'] ) ) {
			return;
		}

		// Delete cached addons data array
		delete_site_transient( 'socialsnap_addons' );

		// Redirect back to addons page
		wp_safe_redirect( admin_url( 'admin.php?page=socialsnap-addons' ) );
	}

	/**
	 * Add license key to remote request to get addons.
	 * Returns addons list with download links for available addons.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params
	 */
	public function remote_request_addons_params( $params ) {

		// Get license
		$license = socialsnap()->license->info();

		// Add license key to request if valid
		if ( 'valid' === $license['status'] ) {
			$params['tgm-updater-key'] = $license['key'];
		}

		return $params;
	}
}
new SocialSnap_Addons_Pro();
