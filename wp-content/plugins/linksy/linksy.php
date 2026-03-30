<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Linksy
 *
 * @wordpress-plugin
 * Plugin Name:       Linksy
 * Plugin URI:        https://plugli.com/linksy/
 * Description:       Linksy is a smart link-building plugin that uses Artificial Intelligence for link suggestions and Natural Language Processing for text analysis.
 * Version:           1.0.7
 * Author:            Plugli
 * Author URI:        https://plugli.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       linksy
 * Domain Path:       /languages
 */

namespace Linksy;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// ini_set('memory_limit', '128M'); todo: set in function

/**
 * Define Constants
 */

define('LINKSY_PLUGIN_NAME', 'Linksy' );

define('LINKSY_PLUGIN_VERSION', '1.0.1' );

define('LINKSY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

define('LINKSY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define('LINKSY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

define('LINKSY_PLUGIN_TEXT_DOMAIN', 'linksy' );

require_once( LINKSY_PLUGIN_DIR . 'constants.php' );

/**
 * Autoload Classes
 */

require_once( LINKSY_PLUGIN_DIR . 'inc/libraries/autoloader.php' );

/**
 * Register Activation and Deactivation Hooks
 * This action is documented in inc/core/class-activator.php
 */

register_activation_hook( __FILE__, array('Linksy\Inc\Core\Activator', 'activate' ) );

/**
 * The code that runs during plugin deactivation.
 * This action is documented inc/core/class-deactivator.php
 */

register_deactivation_hook( __FILE__, array('Linksy\Inc\Core\Deactivator', 'deactivate' ) );


/**
 * Plugin Singleton Container
 *
 * Maintains a single copy of the plugin app object
 *
 * @since    1.0.0
 */
class Linksy {

	/**
	 * The instance of the plugin.
	 *
	 * @since    1.0.0
	 * @var      Init $init Instance of the plugin.
	 */
	private static $init;
	/**
	 * Loads the plugin
	 *
	 * @access    public
	 */
	public static function init() {

		if ( null === self::$init ) {
			self::$init = new Inc\Core\Init();
			self::$init->run();
		}
		return self::$init;
	}
}

/**
 * Begins execution of the plugin
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * Also returns copy of the app object so 3rd party developers
 * can interact with the plugin's hooks contained within.
 **/
function wp_plugin_name_init() {
	return Linksy::init();
}

// Check the minimum required PHP version and run the plugin.
if ( version_compare( PHP_VERSION, '5.6.0', '>=' ) ) {
	wp_plugin_name_init();
}
