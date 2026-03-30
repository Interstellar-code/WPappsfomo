<?php
namespace Linksy\Inc\Admin\Partials;
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       laxusgooee@gmail.com
 * @since      1.0.0
 *
 * @package    Linksy
 * @subpackage Linksy/admin/partials
 */

use Linksy\Inc\Admin\Traits\Hooker;

abstract class Base {

	use Hooker;
	
	public $page;
	protected $plugin_name;
	protected $plugin_version;

	public $setup_status;

	/**
	 * The Constructor.
	 */
	public function __construct($plugin_name, $version, $setup_status = -1) {
        $this->plugin_name = $plugin_name;
		$this->plugin_version = $version;
		$this->setup_status = $setup_status;
		
        $this->admin_init();
	}
    
    public function render_view($module, $variables = array(), $print = true) {
		$output = '';
        $filePath = plugin_dir_path( __FILE__ ). $module ."/_build/index.php";

		if(file_exists($filePath)){
            // Extract the variables to a local namespace
            extract($variables);

            // Start output buffering
            ob_start();

            // Include the template file
            include $filePath;

            // End buffering and return its contents
            $output = ob_get_clean();
        }
        
		if ($print) {
            print $output;
        }

        return $output;
    }

	/**
	 * Check if current page.
	 */
    public function is_current_page() {
    	return isset($_GET['page']) && $this->page === $_GET['page'];
    }

    public function enqueue_files($strict = true) {
    	if($strict && !$this->is_current_page()){
    		return;
    	}

		$this->action( 'admin_enqueue_scripts', 'enqueue_styles' );
		$this->action( 'admin_enqueue_scripts', 'enqueue_scripts' );
    }

	/**
	 * styles.
	 */
    public function enqueue_styles(){}

	/**
	 * scripts.
	 */
	public function enqueue_scripts(){
        wp_add_inline_script( $this->plugin_name, "var LINKSY_SECURE_TOKEN = '".wp_create_nonce( $this->page )."'");
    }

    /**
	 * Admin initialize.
	 */
	abstract public function admin_init();

	/**
	 * Register admin page.
	 */
	abstract public function register_admin_page();

	/**
	 * Display admin page.
	 */
    abstract public function display_page();
}