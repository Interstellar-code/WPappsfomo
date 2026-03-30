<?php
/**
 * Social Snap License Class.
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_License_Page extends SocialSnap_Admin_Page {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Call parent constructor
		parent::__construct();

		// Page details
		$this->page_slug = 'license';
		$this->title     = __( 'License', 'socialsnap' );

		// Add page to Admin menu
		add_action( 'admin_menu', array( $this, 'register_pages' ), 13 );

		// Admin notices
		// if ( ! isset( $_GET['page'] ) || 'socialsnap-license' !== $_GET['page'] ) {
		add_action( 'admin_notices', array( $this, 'notices' ) );
		// }

		// Plugin notice message
		add_action( 'in_plugin_update_message-' . plugin_basename( SOCIALSNAP_PLUGIN_FILE ), array( $this, 'in_plugin_update_message' ) );

		// Ajax actions
		add_action( 'wp_ajax_socialsnap_verify_license', array( $this, 'verify_license' ) );
		add_action( 'wp_ajax_socialsnap_deactivate_license', array( $this, 'deactivate_license' ) );
		add_action( 'wp_ajax_socialsnap_refresh_license', array( $this, 'refresh_license' ) );
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
			__( 'Social Snap License', 'socialsnap' ),
			__( 'License', 'socialsnap' ),
			apply_filters( 'socialsnap_license_cap', 'manage_options' ),
			'socialsnap-license',
			array( $this, 'render' )
		);
	}

	/**
	 * Build the output for the plugin addons page.
	 *
	 * @since 1.0.0
	 */
	public function render() {

		$license  = socialsnap()->license->info();
		$disabled = ' disabled="disabled"';

		if ( 'valid' !== $license['status'] ) {
			$license['type'] = '';
			$license['slug'] = '';
			$disabled        = '';
		}

		?>
		<div id="ss-license" class="ss-page-wrapper ss-clearfix">

			<div class="socialsnap-setting-row socialsnap-setting-row-license socialsnap-clear" id="socialsnap-setting-row-license-key">

				<span class="socialsnap-setting-label">
					<label for="socialsnap-setting-license-key"><?php esc_html_e( 'License Key', 'socialsnap' ); ?></label>
				</span>

				<span class="socialsnap-setting-field">

					<input type="password" placeholder="<?php esc_html_e( 'Enter your Social Snap license key here...', 'socialsnap' ); ?>" id="socialsnap-setting-license-key" value="<?php echo esc_attr( $license['key'] ); ?>"<?php echo esc_html( $disabled ); ?>>

					<button id="ss-setting-license-key-verify" class="ss-button ss-button-primary"<?php echo esc_html( $disabled ); ?>><?php esc_html_e( 'Verify Key', 'scocialsnap' ); ?></button>

					<?php $class = $license['key'] ? '' : ' socialsnap-hide'; ?>

					<button id="ss-setting-license-key-deactivate" class="ss-button ss-button-secondary<?php echo esc_attr( $class ); ?>"><?php esc_html_e( 'Deactivate Key', 'socialsnap' ); ?></button>

					<span class="spinner" style="float:none;margin-top:0;"></span>

					<?php $class = $license['type'] ? '' : ' socialsnap-hide'; ?>

					<p class="type<?php echo esc_attr( $class ); ?>">
						<?php
						/* translators: %s is license type */
						echo wp_kses_post( sprintf( __( 'Your license key type is %s.', 'socialsnap' ), '<strong>' . $license['type'] . '</strong>' ) );
						?>
					</p>

					<?php $class = $license['key'] ? '' : ' socialsnap-hide'; ?>

					<p class="desc<?php echo esc_attr( $class ); ?>">
						<?php
						/* translators: %1$s open anchor tag, %2$s close anchor tag. */
						echo wp_kses_post( sprintf( __( 'If your license has been upgraded or is incorrect, %1$sclick here to force a refresh%2$s.', 'socialsnap' ), '<a href="#" id="ss-setting-license-key-refresh">', '</a>' ) );
						?>
					</p>

					<?php $class = $license['key'] ? ' socialsnap-hide' : ''; ?>

					<p class="account-link<?php echo esc_attr( $class ); ?>">
						<?php
						/* translators: %1$s open anchor tag, %2$s close anchor tag. */
						echo wp_kses_post( sprintf( __( 'To access your license keys, log in to your %1$sSocial Snap account%2$s.', 'socialsnap' ), '<a href="https://socialsnap.com/account/" target="_blank" rel="nofollow noopener">', '</a>' ) );
						?>
					</p>

				</span>

			</div>

		</div><!-- END #ss-license -->
		<?php
	}

	/**
	 * Load the license key.
	 *
	 * @since 1.0.0
	 */
	public function get() {

		// Check for license key.
		$license = socialsnap()->license->info();
		$key     = $license['key'];

		// Allow wp-config constant to pass key.
		if ( ! $key && defined( 'SOCIALSNAP_LICENSE_KEY' ) ) {
			$key = SOCIALSNAP_LICENSE_KEY;
		}

		return $key;
	}

	/**
	 * Load the license info.
	 *
	 * @since 1.0.0
	 */
	public function info() {

		$info = wp_parse_args(
			(array) get_option( 'socialsnap_license' ),
			array(
				'key'    => '',
				'type'   => '',
				'slug'   => '',
				'status' => '',
			)
		);

		return $info;
	}

	/**
	 * Maybe validates a license key entered by the user.
	 *
	 * @since 1.0.0
	 * @return void Return early if the transient has not expired yet.
	 */
	public function maybe_validate_key() {

		$key = socialsnap()->license->get();

		if ( ! $key ) {
			return;
		}

		// Perform a request to validate the key  - Only run every 12 hours.
		$timestamp = get_option( 'socialsnap_license_updates' );

		if ( ! $timestamp ) {
			$timestamp = strtotime( '+24 hours' );
			update_option( 'socialsnap_license_updates', $timestamp );
			socialsnap()->license->validate_key( $key );
		} else {
			$current_timestamp = time();
			if ( $current_timestamp < $timestamp ) {
				return;
			} else {
				update_option( 'socialsnap_license_updates', strtotime( '+24 hours' ) );

				socialsnap()->license->validate_key( $key );
			}
		}
	}

	/**
	 * Shows message on WP Plugins page.
	 *
	 * @since 1.0.0
	 */
	public function in_plugin_update_message() {

		$license = socialsnap()->license->info();

		if ( ! $license['key'] || 'valid' !== $license['status'] ) {
			echo ' ' . sprintf( 'Missing or invalid license key.' );
		}
	}

	/**
	 * Outputs any notices generated by the class.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $below_h2
	 */
	public function notices( $below_h2 = false ) {

		// Grab the option and output any nag dealing with license keys.
		$license  = socialsnap()->license->info();
		$key      = $license['key'];
		$below_h2 = $below_h2 ? 'below-h2' : '';

		$base = get_current_screen()->base;

		if ( false === strpos( $base, 'socialsnap' ) && false === strpos( $base, 'plugins' ) && false === strpos( $base, 'dashboard' ) ) {
			return;
		}

		// If there is no license key, output nag about ensuring key is set for automatic updates.
		if ( ! $key ) :
			?>
			<div class="notice notice-info <?php echo esc_attr( $below_h2 ); ?> socialsnap-license-notice">
				<p>
					<?php
					printf(
						wp_kses(
							/* translators: %s - plugin settings page URL. */
							__( 'Please <a href="%s">enter and activate</a> your license key for Social Snap to enable automatic updates and unlock premium features.', 'socialsnap' ),
							array(
								'a' => array(
									'href' => array(),
								),
							)
						),
						esc_url( add_query_arg( array( 'page' => 'socialsnap-license' ), admin_url( 'admin.php' ) ) )
					);
					?>
				</p>
			</div>
			<?php
		endif;

		// If a key has expired, output nag about renewing the key.
		if ( in_array( $license['status'], array( 'expired' ) ) ) :
			?>
			<div class="error notice <?php echo esc_attr( $below_h2 ); ?> socialsnap-license-notice">
				<p>
					<?php
					printf(
						wp_kses(
							/* translators: %1$s is admin url, %2$s is upgrade url. */
							__( 'Your license key for Social Snap has expired. Please <a href="%1$s">enter a valid license key</a> or <a href="%2$s" target="_blank">purchase a new subscription</a>.', 'socialsnap' ),
							array(
								'a' => array(
									'href'   => array(),
									'target' => array(),
									'rel'    => array(),
								),
							)
						),
						esc_url( admin_url( 'admin.php?page=socialsnap-license' ) ),
						esc_url( socialsnap_upgrade_link() )
					);
					?>
				</p>
			</div>
			<?php
		endif;

		// If a key has been disabled, output nag about using another key.
		if ( in_array( $license['status'], array( 'disabled', 'revoked', 'inactive' ) ) ) :
			?>
			<div class="error notice <?php echo esc_attr( $below_h2 ); ?> socialsnap-license-notice">
				<p><?php esc_html_e( 'Your license key for Social Snap has been disabled. Please try using a different key.', 'socialsnap' ); ?></p>
			</div>
			<?php
		endif;

		// If a key is invalid, output nag about using another key.
		if ( in_array( $license['status'], array( 'invalid', 'missing', 'invalid_item_id', 'item_name_mismatch' ) ) ) :
			?>
			<div class="error notice <?php echo esc_attr( $below_h2 ); ?> socialsnap-license-notice">
				<p><?php esc_html_e( 'Your license key for Social Snap is invalid. The key no longer exists or the user associated with the key has been deleted. Please try using a different key.', 'socialsnap' ); ?></p>
			</div>
			<?php
		endif;
	}

	/**
	 * Ajax handler to verify license.
	 *
	 * @since 1.0.0
	 */
	public function verify_license() {

		// Security check
		check_ajax_referer( 'socialsnap-admin', 'nonce' );

		// Check for license key.
		if ( empty( $_POST['license'] ) ) {
			wp_send_json_error( esc_html__( 'Please enter a license key.', 'socialsnap' ) );
		}

		// Verify key
		socialsnap()->license->verify_key( sanitize_text_field( $_POST['license'] ), true );
	}

	/**
	 * Ajax handler to deactivate license.
	 *
	 * @since 1.0.0
	 */
	public function deactivate_license() {

		// Security check
		check_ajax_referer( 'socialsnap-admin', 'nonce' );

		// Check for license key.
		if ( empty( $_POST['license'] ) ) {
			wp_send_json_error( esc_html__( 'Please enter a license key.', 'socialsnap' ) );
		}

		// Deactivate key
		socialsnap()->license->deactivate_key( sanitize_text_field( $_POST['license'] ), true );
	}

	/**
	 * Ajax handler to refresh license.
	 *
	 * @since 1.0.0
	 */
	public function refresh_license() {

		// Security check
		check_ajax_referer( 'socialsnap-admin', 'nonce' );

		// Check for license key.
		if ( empty( $_POST['license'] ) ) {
			wp_send_json_error( esc_html__( 'Please enter a license key.', 'socialsnap' ) );
		}

		// Refresh key status
		socialsnap()->license->validate_key( sanitize_text_field( $_POST['license'] ), true, true );
	}

	/**
	 * Verifies a license key entered by the user.
	 * Activate the key on socialsnap.com.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 * @param bool   $ajax
	 *
	 * @return bool
	 */
	public function verify_key( $key = '', $ajax = false ) {

		// Key is required.
		if ( empty( $key ) ) {
			return false;
		}

		// Perform a request to verify the key.
		$verify = socialsnap_perform_remote_request( 'verify-key', array( 'tgm-updater-key' => $key ) );

		// If it returns false, send back a generic error message and return.
		if ( is_wp_error( $verify ) ) {

			$msg = $verify->get_error_message();
			if ( $ajax ) {
				wp_send_json_error( $msg );
			} else {
				return false;
			}
		}

		// If an error is returned, set the error and return.
		if ( isset( $verify->success ) && false == $verify->success ) {

			// Get error
			$error = ! empty( $verify->error ) ? $verify->error : '';
			$error = socialsnap()->license->get_error_message( $error );

			// Delete any leftover data
			delete_option( 'socialsnap_license' );

			if ( $ajax ) {
				wp_send_json_error( $error );
			} else {
				return false;
			}
		} elseif ( isset( $verify->success ) && $verify->success ) {

			$message = esc_html__( 'License activated successfully!', 'socialsnap' );

			// Build license data array
			$option           = socialsnap()->license->info();
			$option['key']    = $key;
			$option['type']   = isset( $verify->item_name ) ? $verify->item_name : $option['type'];
			$option['slug']   = sanitize_title( $option['type'] );
			$option['status'] = 'valid';

			// Save license data array
			update_option( 'socialsnap_license', $option );

			// Delete addons transient to force refreshing the addon list
			delete_site_transient( 'socialsnap_addons' );

			wp_clean_plugins_cache( true );

			if ( $ajax ) {
				wp_send_json_success(
					array(
						'type' => $option['type'],
						'msg'  => $message,
					)
				);
			}
		}
	}

	/**
	 * Deactivates a license key entered by the user.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $ajax
	 */
	public function deactivate_key( $ajax = false ) {

		$key = socialsnap()->license->get();

		if ( ! $key ) {
			return;
		}

		// Perform a request to deactivate the key.
		$deactivate = socialsnap_perform_remote_request( 'deactivate-key', array( 'tgm-updater-key' => $key ) );

		// If it returns false, send back a generic error message and return.
		if ( is_wp_error( $deactivate ) ) {

			$msg = $deactivate->get_error_message();
			if ( $ajax ) {
				wp_send_json_error( $msg );
			} else {
				return;
			}
		}

		// If an error is returned, set the error and return.
		if ( ! empty( $deactivate->error ) ) {
			if ( $ajax ) {
				wp_send_json_error( $deactivate->error );
			} else {
				return;
			}
		}

		// Otherwise, our request has been done successfully. Reset the option and set the success message.
		$success = isset( $deactivate->success ) ? $deactivate->success : esc_html__( 'You have deactivated the key from this site successfully.', 'socialsnap' );
		delete_option( 'socialsnap_license' );

		if ( $ajax ) {
			wp_send_json_success( $success );
		}
	}

	/**
	 * Validates a license key entered by the user.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 * @param bool   $forced Force to set contextual messages (false by default).
	 * @param bool   $ajax
	 */
	public function validate_key( $key = '', $forced = false, $ajax = false ) {

		$validate = socialsnap_perform_remote_request( 'validate-key', array( 'tgm-updater-key' => $key ) );

		// If there was a basic API error in validation, only set the transient for 10 minutes before retrying.
		if ( is_wp_error( $validate ) ) {

			// If forced, set contextual success message.
			if ( $forced ) {
				$msg = $validate->get_error_message();
				if ( $ajax ) {
					wp_send_json_error( $msg );
				}
			}

			return;
		}

		// Get license info
		$license        = socialsnap()->license->info();
		$license['key'] = $key;

		$error = '';

		// If an error is returned, set the error and return.
		if ( isset( $validate->success ) && false == $validate->success ) {

			$error = ! empty( $validate->error ) ? $validate->error : '';

		} elseif ( isset( $validate->success, $validate->license ) && $validate->success ) {

			// Check if license is valid
			if ( 'valid' === $validate->license ) {

				$message = esc_html__( 'Your license key has been refreshed successfully.', 'socialsnap' );

				$license['type']   = isset( $validate->item_name ) ? $validate->item_name : $option['type'];
				$license['slug']   = sanitize_title( $license['type'] );
				$license['status'] = 'valid';

				update_option( 'socialsnap_license', $license );

				if ( $ajax ) {
					wp_send_json_success(
						array(
							'type' => $license['type'],
							'msg'  => $message,
						)
					);
				} else {
					return true;
				}
			} else {
				$error = $validate->license;
			}
		}

		// Update license status
		$license['status'] = $error;
		$license['key']    = '';

		update_option( 'socialsnap_license', $license );

		$error = socialsnap()->license->get_error_message( $error );

		if ( $ajax ) {
			wp_send_json_error( $error );
		} else {
			return false;
		}
	}

	/**
	 * Check if currently registered license is valid.
	 *
	 * @since 1.1.11
	 */
	public function is_valid() {

		$license = socialsnap()->license->info();

		return $license['key'] && 'valid' === $license['status'];
	}

	/**
	 * Get error message for error code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code
	 * @return string $error message
	 */
	private function get_error_message( $code ) {

		$error = '';

		switch ( $code ) {

			case 'invalid':
			case 'invalid_item_id':
			case 'item_name_mismatch':
			case 'missing':
				$error = __( 'You entered an invalid license for this product.', 'socialsnap' );
				break;

			case 'expired':
				$error = __( 'Your license key has expired.', 'socialsnap' );
				break;

			case 'inactive':
				$error = __( 'Your license key is inactive.', 'socialsnap' );
				break;

			case 'disabled':
			case 'revoked':
				$error = __( 'Your license key has beed disabled.', 'socialsnap' );
				break;

			case 'site_inactive':
				$error = __( 'Your license key is not active for this URL.', 'socialsnap' );
				break;

			case 'license_not_activable':
				$error = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'socialsnap' );
				break;

			case 'no_activations_left':
				$error = __( 'Your license key has reached its activation limit', 'socialsnap' );
				break;

			default:
				$error = __( 'There was an error with this license key', 'socialsnap' );
				break;
		}

		return $error;
	}
}
new SocialSnap_License_Page();
