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
class SocialSnap_License {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Periodic background license check
		if ( $this->get() ) {
			$this->maybe_validate_key();
		}
	}

	/**
	 * Load the license key.
	 *
	 * @since 1.0.0
	 */
	public function get() {

		// Check for license key.
		$license = $this->info();
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

		$key = $this->get();

		if ( ! $key ) {
			return;
		}

		// Perform a request to validate the key  - Only run every 12 hours.
		$timestamp = get_option( 'socialsnap_license_updates' );

		if ( ! $timestamp ) {
			$timestamp = strtotime( '+24 hours' );
			update_option( 'socialsnap_license_updates', $timestamp );
			$this->validate_key( $key );
		} else {
			$current_timestamp = time();
			if ( $current_timestamp < $timestamp ) {
				return;
			} else {
				update_option( 'socialsnap_license_updates', strtotime( '+24 hours' ) );

				$this->validate_key( $key );
			}
		}
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
			$error = $this->get_error_message( $error );

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
			$option           = $this->info();
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

		$key = $this->get();

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
		$license        = $this->info();
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

		$error = $this->get_error_message( $error );

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

	/**
	 * Get error message for error code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code
	 * @return string $error message
	 */
	private function get_error_notice( $code ) {

		$error = '';

		switch ( $code ) {

			case 'expired':
				$error = sprintf(
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
					admin_url( 'admin.php?page=socialsnap-license' ),
					socialsnap_upgrade_link()
				);

				break;

		}

		return $error;
	}
}
