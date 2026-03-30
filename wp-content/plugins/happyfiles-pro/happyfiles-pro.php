<?php
/**
 * Plugin Name:       HappyFiles Pro
 * Plugin URI:        https://happyfiles.io
 * Description:       Organize your WordPress media files in folders/categories via drag and drop.
 * Version:           0.7.2
 * Author:            Codeer
 * Author URI:        https://codeer.io
 * Text Domain:       happyfiles
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'HAPPYFILES_VERSION', '0.7.2' );
define( 'HAPPYFILES_FILE', __FILE__ );
define( 'HAPPYFILES_PATH', plugin_dir_path( __FILE__ ) );
define( 'HAPPYFILES_URL', plugin_dir_url( __FILE__ ) );
define( 'HAPPYFILES_BASENAME', plugin_basename( __FILE__ ) );
define( 'HAPPYFILES_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets' );
define( 'HAPPYFILES_ASSETS_PATH', plugin_dir_path( __FILE__ ) . 'assets' );
define( 'HAPPYFILES_TAXONOMY', 'happyfiles_category' );
define( 'HAPPYFILES_POSITION', 'happyfiles_position' );
define( 'HAPPYFILES_DB_OPTION_WIDTH', 'happyfiles_width' );

define( 'HAPPYFILES_SETTINGS_GROUP', 'happyfiles_settings' );
define( 'HAPPYFILES_SETTING_USER_ROLES', 'happyfiles_user_roles' );
define( 'HAPPYFILES_SETTING_MULTIPLE_CATEGORIES', 'happyfiles_multiple_categories' );

require_once HAPPYFILES_PATH . 'includes/init.php';