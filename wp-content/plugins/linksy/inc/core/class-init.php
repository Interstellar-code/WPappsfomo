<?php

namespace Linksy\Inc\Core;


use Linksy\Inc\Admin as Admin;
use Linksy\Inc\Helpers\Config;

/**
 * The core plugin class.
 * Defines internationalization, admin-specific hooks, and public-facing site hooks.
 *
 * @link       http://laxusgee.com
 * @since      1.0.0
 *
 * @author     Linksy
 */
class Init {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_base_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_basename;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The text domain of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $plugin_text_domain;

	/**
	 * Initialize and define the core functionality of the plugin.
	 */
	public function __construct() {

		$this->plugin_name = LINKSY_PLUGIN_NAME;
		$this->version = LINKSY_PLUGIN_VERSION;
				$this->plugin_basename = LINKSY_PLUGIN_BASENAME;
				$this->plugin_text_domain = LINKSY_PLUGIN_TEXT_DOMAIN;

		$this->load_dependencies();

		$this->set_locale();
		$this->set_schedules();
		$this->define_public_hooks();
		$this->define_admin_hooks();
	}

	/**
	 * Loads the following required dependencies for this plugin.
	 *
	 * - Loader - Orchestrates the hooks of the plugin.
	 * - Internationalization_I18n - Defines internationalization functionality.
	 * - Admin - Defines all hooks for the admin area.
	 * - Frontend - Defines all hooks for the public side of the site.
	 *
	 * @access    private
	 */
	private function load_dependencies() {
		$this->loader = new Loader();
		$this->loader->add_filter( "plugin_action_links_{$this->plugin_basename}", $this, 'add_plugin_action_links', 10, 4 );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Internationalization_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @access    private
	 */
	private function set_locale() {
		$plugin_i18n = new Internationalization_I18n( $this->plugin_text_domain );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Define the schedules for the plugin.
	 *
	 * @access    private
	 */
	private function set_schedules() {
		add_filter('cron_schedules', [$this, 'add_plugin_cron_schedules']);
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @access    private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Admin\Admin(
			$this->get_plugin_name(),
			$this->get_version(),
			$this->get_plugin_text_domain()
		);

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @access    private
	 */
	private function define_public_hooks() { }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();

		if ($this->is_plugin_updated()) {
			// todo: run migration
			Config::set(LINKSY_OPTION_PLUGIN_VERSION, $this->version);
		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieve the text domain of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The text domain of the plugin.
	 */
	public function get_plugin_text_domain() {
		return $this->plugin_text_domain;
	}

	public function add_plugin_cron_schedules( $schedules ) {
		if(!isset($schedules['linksy_anchor_cloud_interval'])){
            $schedules['linksy_anchor_cloud_interval'] = array(
                'interval' => 60 * 2,
                'display' => __('Every two Minutes', 'linksy')
            );
        }

		if(!isset($schedules['linksy_keywords_rating_interval'])){
            $schedules['linksy_keywords_rating_interval'] = array(
                'interval' => 60 * 2,
                'display' => __('Every two Minutes', 'linksy')
            );
        }
        
        return $schedules;
	}

	/**
	 * Adds items to the plugin's action links on the Plugins listing screen.
	 *
	 * @param array<string,string> $actions     Array of action links.
	 * @param string               $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param mixed[]              $plugin_data An array of plugin data.
	 * @param string               $context     The plugin context.
	 * @return array<string,string> Array of action links.
	 */
	function add_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		$new = array(
			'linksy-settings'    => sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=Linksy-settings' ) ),
				esc_html__( 'Settings', $this->plugin_text_domain )
			),
		);

		return array_merge( $actions, $new );
	}

	public function is_plugin_updated() {
		$registerd_version = Config::get(LINKSY_OPTION_PLUGIN_VERSION, false);

    	if ($registerd_version && $this->version != $registerd_version) {
    		return true;
    	}

    	return false;
    }

}
