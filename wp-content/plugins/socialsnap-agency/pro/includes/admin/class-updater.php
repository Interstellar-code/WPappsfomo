<?php
/**
 * Updater class.
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Updater {

	/**
	 * Plugin name.
	 *
	 * @since 2.0.0
	 *
	 * @var bool|string
	 */
	public $plugin_name = false;

	/**
	 * Plugin slug.
	 *
	 * @since 2.0.0
	 *
	 * @var bool|string
	 */
	public $plugin_slug = false;

	/**
	 * Plugin path.
	 *
	 * @since 2.0.0
	 *
	 * @var bool|string
	 */
	public $plugin_path = false;

	/**
	 * URL of the plugin.
	 *
	 * @since 2.0.0
	 *
	 * @var bool|string
	 */
	public $plugin_url = false;

	/**
	 * Remote URL for getting plugin updates.
	 *
	 * @since 2.0.0
	 *
	 * @var bool|string
	 */
	public $remote_url = false;

	/**
	 * Version number of the plugin.
	 *
	 * @since 2.0.0
	 *
	 * @var bool|int
	 */
	public $version = false;

	/**
	 * License key for the plugin.
	 *
	 * @since 2.0.0
	 *
	 * @var bool|string
	 */
	public $key = false;

	/**
	 * Holds the update data returned from the API.
	 *
	 * @since 2.1.3
	 *
	 * @var bool|object
	 */
	public $update = false;

	/**
	 * Holds the plugin info details for the update.
	 *
	 * @since 2.1.3
	 *
	 * @var bool|object
	 */
	public $info = false;

	/**
	 * Primary class constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param array $config Array of updater config args.
	 */
	public function __construct( array $config ) {

		// Set class properties.
		$accepted_args = array(
			'plugin_name',
			'plugin_slug',
			'plugin_path',
			'plugin_url',
			'remote_url',
			'version',
			'key',
		);

		foreach ( $accepted_args as $arg ) {
			$this->$arg = isset( $config[ $arg ] ) ? $config[ $arg ] : false;
		}

		// If the user cannot update plugins, stop processing here.
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		// Load the updater hooks and filters.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_plugins_filter' ) );
		add_filter( 'http_request_args', array( $this, 'http_request_args' ), 10, 2 );
		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );

	}

	/**
	 * Infuse plugin update details when WordPress runs its update checker.
	 *
	 * @since 2.0.0
	 *
	 * @param object $value The WordPress update object.
	 *
	 * @return object $value Amended WordPress update object on success, default if object is empty.
	 */
	public function update_plugins_filter( $value ) {

		// If no update object exists, return early.
		if ( empty( $value ) ) {
			return $value;
		}

		// Run update check by pinging the external API. If it fails, return the default update object.
		if ( ! $this->update ) {

			$this->update = get_site_transient( 'socialsnap_plugin_update_' . $this->plugin_slug );

			if ( false === $this->update || isset( $_GET['force-check'] ) ) {

				$this->update = socialsnap_perform_remote_request(
					'get-plugin-update',
					array(
						'tgm-updater-plugin' => $this->plugin_slug,
						'tgm-updater-key'    => $this->key,
					)
				);

				$this->update->sections = isset( $this->update->sections ) && is_string( $this->update->sections ) ? unserialize( $this->update->sections ) : '';
				$this->update->banners  = isset( $this->update->banners ) && is_string( $this->update->banners ) ? unserialize( $this->update->banners ) : '';

			}

			if ( is_wp_error( $this->update ) || ! $this->update || ! empty( $this->update->error ) ) {

				$this->update = false;
				return $value;
			}

			set_site_transient( 'socialsnap_plugin_update_' . $this->plugin_slug, $this->update, 6 * 60 * 60 );
		}

		// Infuse the update object with our data if the version from the remote API is newer.
		if ( isset( $this->update->new_version ) && version_compare( $this->version, $this->update->new_version, '<' ) ) {
			// The $plugin_update object contains new_version, package, slug and last_update keys.

			if ( isset( $this->update->icons ) ) {
				$this->update->icons = (array) $this->update->icons;
			}

			if ( isset( $this->update->banners ) ) {
				$this->update->banners = (array) $this->update->banners;
			}

			$value->response[ $this->plugin_path ] = $this->update;
		}

		// Return the update object.
		return $value;
	}

	/**
	 * Disables SSL verification to prevent download package failures.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $args Array of request args.
	 * @param string $url The URL to be pinged.
	 *
	 * @return array $args Amended array of request args.
	 */
	public function http_request_args( $args, $url ) {
		return $args;
	}

	/**
	 * Filters the plugins_api function to get our own custom plugin information
	 * from our private repo.
	 *
	 * @since 2.0.0
	 *
	 * @param object $api The original plugins_api object.
	 * @param string $action The action sent by plugins_api.
	 * @param array  $args Additional args to send to plugins_api.
	 *
	 * @return object $api   New stdClass with plugin information on success, default response on failure.
	 */
	public function plugins_api( $api, $action = '', $args = null ) {

		$plugin = ( 'plugin_information' === $action ) && isset( $args->slug ) && ( $this->plugin_slug === $args->slug );

		// If our plugin matches the request, set our own plugin data, else return the default response.
		if ( $plugin ) {
			return $this->set_plugins_api( $api );
		} else {
			return $api;
		}
	}

	/**
	 * Pings a remote API to retrieve plugin information for WordPress to display.
	 *
	 * @since 2.0.0
	 *
	 * @param object $default_api The default API object.
	 *
	 * @return object $api        Return custom plugin information to plugins_api.
	 */
	public function set_plugins_api( $default_api ) {

		// Perform the remote request to retrieve our plugin information. If it fails, return the default object.
		if ( ! $this->info ) {

			$this->info = get_site_transient( 'socialsnap_plugin_update_' . $this->plugin_slug );

			if ( false === $this->info || isset( $_GET['force-check'] ) ) {

				$this->info = socialsnap_perform_remote_request(
					'get-plugin-info',
					array(
						'tgm-updater-plugin' => $this->plugin_slug,
						'tgm-updater-key'    => $this->key,
					)
				);
			}

			if ( is_wp_error( $this->info ) || ! $this->info || ! empty( $this->info->error ) ) {

				$this->info = false;
				return $default_api;
			}

			set_site_transient( 'socialsnap_plugin_update_' . $this->plugin_slug, $this->info, 6 * 60 * 60 );
		}

		// Create a new stdClass object and populate it with our plugin information.
		$api                 = new stdClass();
		$api->name           = isset( $this->info->name ) ? $this->info->name : '';
		$api->slug           = isset( $this->info->slug ) ? $this->info->slug : '';
		$api->version        = isset( $this->info->version ) ? $this->info->version : '';
		$api->author         = isset( $this->info->author ) ? $this->info->author : '';
		$api->author_profile = isset( $this->info->author_profile ) ? $this->info->author_profile : '';
		$api->requires       = isset( $this->info->requires ) ? $this->info->requires : '';
		$api->tested         = isset( $this->info->tested ) ? $this->info->tested : '';
		$api->last_updated   = isset( $this->info->last_updated ) ? $this->info->last_updated : '';
		$api->homepage       = isset( $this->info->homepage ) ? $this->info->homepage : '';
		$api->sections       = isset( $this->info->sections ) ? (array) $this->info->sections : '';
		$api->download_link  = isset( $this->info->download_link ) ? $this->info->download_link : '';
		$api->banners        = isset( $this->info->banners ) ? (array) $this->info->banners : '';

		// Return the new API object with our custom data.
		return $api;
	}
}
