<?php
/**
 * Plugin Name: Social Snap
 * Plugin URI:  https://socialsnap.com
 * Description: Social Share Buttons, Social Sharing Icons, Click to Tweet — Social Media Plugin by Social Snap
 * Author:      Social Snap
 * Author URI:  https://socialsnap.com
 * Version:     1.3.6
 * Text Domain: socialsnap
 * Domain Path: languages
 *
 * Social Snap is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Social Snap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Social Snap. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Don't allow multiple versions to be active.
if ( class_exists( 'SocialSnap' ) ) {

	/**
	 * Deactivate if Social Snap already activated.
	 *
	 * @since 1.0.0
	 */
	function socialsnap_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
	add_action( 'admin_init', 'socialsnap_deactivate' );

	/**
	 * Display notice after deactivation.
	 *
	 * @since 1.0.0
	 */
	function socialsnap_lite_notice() {

		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Please deactivate Social Snap Lite before activating the premium version of Social Snap.', 'socialsnap' ) . '</p></div>';

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

	}
	add_action( 'admin_notices', 'socialsnap_lite_notice' );

} else {

	/**
	 * Main Social Snap class.
	 *
	 * @since 1.0.0
	 * @package Social Snap
	 */
	final class SocialSnap {

		/**
		 * Singleton instance of the class.
		 *
		 * @since 1.0.0
		 * @var object
		 */
		private static $instance;

		/**
		 * Plugin version for enqueueing, etc.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $version = '1.3.6';

		/**
		 * Paid returns true, free (Lite) returns false.
		 *
		 * @var boolean
		 * @since 1.0.0
		 */
		public $pro = false;

		/**
		 * User defined settings.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		public $settings = array();
		
		/**
		 * Social Snap License.
		 *
		 * @since 1.0.0
		 * @var object
		 */
		public $license;
		
		/**
		 * Social Snap Stats.
		 *
		 * @since 1.0.0
		 * @var object
		 */
		public $stats;
		
		/**
		 * Social Snap Icons.
		 *
		 * @since 1.0.0
		 * @var object
		 */
		public $icons;

		/**
		 * Main Social Snap Instance.
		 *
		 * Insures that only one instance of Social Snap exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0.0
		 * @return SocialSnap
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SocialSnap ) ) {

				self::$instance = new SocialSnap();
				self::$instance->constants();
				self::$instance->load_textdomain();
				self::$instance->includes();

				// Determine plugin type.
				if ( self::$instance->pro ) {
					require_once SOCIALSNAP_PLUGIN_DIR . 'pro/socialsnap-pro.php';
				}

				add_action( 'plugins_loaded', array( self::$instance, 'objects' ), 20 );
			}
			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 *
		 * @since 1.0.0
		 */
		private function constants() {

			// Plugin version.
			if ( ! defined( 'SOCIALSNAP_VERSION' ) ) {
				define( 'SOCIALSNAP_VERSION', $this->version );
			}

			// Plugin Folder Path.
			if ( ! defined( 'SOCIALSNAP_PLUGIN_DIR' ) ) {
				define( 'SOCIALSNAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Folder URL.
			if ( ! defined( 'SOCIALSNAP_PLUGIN_URL' ) ) {
				define( 'SOCIALSNAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Root File.
			if ( ! defined( 'SOCIALSNAP_PLUGIN_FILE' ) ) {
				define( 'SOCIALSNAP_PLUGIN_FILE', __FILE__ );
			}

			// Plugin Settings.
			if ( ! defined( 'SOCIALSNAP_SETTINGS' ) ) {
				define( 'SOCIALSNAP_SETTINGS', 'socialsnap_settings' );
			}

			// Plugin API.
			if ( ! defined( 'SOCIALSNAP_API' ) ) {
				define( 'SOCIALSNAP_API', 'https://socialsnap.com/' );
			}

			// Plugin Pro Dir Settings.
			if ( ! defined( 'SOCIALSNAP_PRO_DIR' ) ) {
				if ( file_exists( SOCIALSNAP_PLUGIN_DIR . 'pro/socialsnap-pro.php' ) ) {
					self::$instance->pro = true;
					define( 'SOCIALSNAP_PRO_DIR', 'pro/' );
				} else {
					define( 'SOCIALSNAP_PRO_DIR', '' );
				}
			}
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @since 1.0.0
		 */
		public function load_textdomain() {

			load_plugin_textdomain( 'socialsnap', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Include files.
		 *
		 * @since 1.0.0
		 */
		private function includes() {

			// Global includes.
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/functions.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/class-install.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/class-db.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/class-db-stats.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/icon-functions.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/share/class-social-share.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/share/functions.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/share/actions.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/follow/class-follow.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/follow/class-follow-widget.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/class-click-to-tweet.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/class-click-to-tweet-widget.php';

			// Compatibility.
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/compatibility/class-fsb-compatibility.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/compatibility/class-sw-compatibility.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/compatibility/class-mashshare-compatibility.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/compatibility/class-dpsp-compatibility.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/compatibility/class-oxygen-compatibility.php';
			require_once SOCIALSNAP_PLUGIN_DIR . 'includes/compatibility/class-bricksbuilder-compatibility.php';

			// Admin/Dashboard only includes.
			if ( is_admin() ) {
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/class-admin-page.php';
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/settings/class-settings-fields.php';
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/settings/class-settings.php';
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/settings/class-metaboxes.php';
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/settings/class-editor.php';
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/class-statistics.php';
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/class-welcome.php';
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/class-addons.php';
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/class-rating.php';
				require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/class-post-list-table.php';
			}
		}

		/**
		 * Setup objects to be used throughout the plugin.
		 *
		 * @since 1.0.0
		 */
		public function objects() {

			// Initialize settings.
			socialsnap()->settings = get_option( SOCIALSNAP_SETTINGS );

			// Create an instance of SocialSnap_Stats.
			socialsnap()->stats = new SocialSnap_Stats();

			// Create an instance of SocialSnap_Icons.
			socialsnap()->icons = new SocialSnap_Icons();

			// Hook now that all of the Social Snap stuff is loaded.
			do_action( 'socialsnap_loaded' );
		}
	}

	/**
	 * The function which returns the one SocialSnap instance.
	 *
	 * Use this function like you would a global variable, except without needing
	 * to declare the global.
	 *
	 * Example: <?php $socialsnap = socialsnap(); ?>
	 *
	 * @since 1.0.0
	 * @return object
	 */
	function socialsnap() {
		return SocialSnap::instance();
	}

	socialsnap();
}
