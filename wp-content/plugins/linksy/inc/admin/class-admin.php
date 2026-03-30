<?php

namespace Linksy\Inc\Admin;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Notices;
use Linksy\Inc\Admin\Partials\Post\Post;
use Linksy\Inc\Admin\Partials\Reports\Reports;
use Linksy\Inc\Admin\Partials\Settings\Settings;
use Linksy\Inc\Admin\Partials\Dashboard\Dashboard;
use Linksy\Inc\Admin\Partials\Playground\Playground;
use Linksy\Inc\Admin\Partials\Setup_Wizard\SetupWizard;
use Linksy\Inc\Admin\Partials\Anchor_Cloud\AnchorCloud;
use Linksy\Inc\Admin\Partials\Inbound_Links\InboundLinks;
use Linksy\Inc\Admin\Partials\Keywords_Rating\KeywordsRating;

define('LINKSY_PLUGIN_ADMIN_URL', plugin_dir_url( __FILE__ ) );
define('LINKSY_PLUGIN_ADMIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       http://laxusgee.com
 * @since      1.0.0
 *
 * @author    Linksy
 */
class Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The text domain of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_text_domain    The text domain of this plugin.
	 */
	private $plugin_text_domain;


	/**
	 * Current stage of setup.
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      integer     $setup_status    Current stage of setup -1 , 0 , 1.
	 */
	private $setup_status = -1;
	

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since       1.0.0
	 * @param       string $plugin_name        The name of this plugin.
	 * @param       string $version            The version of this plugin.
	 * @param       string $plugin_text_domain The text domain of this plugin.
	 */
	public function __construct( $plugin_name, $version, $plugin_text_domain ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_text_domain = $plugin_text_domain;

		$this->post = new Post($this->plugin_name, $this->version);
		$this->setup = new SetupWizard($this->plugin_name, $this->version);
		$this->dashboard = new Dashboard($this->plugin_name, $this->version);
		$this->reports = new Reports($this->plugin_name, $this->version);
		$this->settings = new Settings($this->plugin_name, $this->version);
		$this->inbound_links = new InboundLinks($this->plugin_name, $this->version, $this->setup_status);
		$this->anchor_cloud = new AnchorCloud($this->plugin_name, $this->version);
		$this->keywords_rating = new KeywordsRating($this->plugin_name, $this->version);
		$this->playground = new Playground($this->plugin_name, $this->version);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$dir = plugin_dir_url( __FILE__ ).'assets/';

		if($this->is_current_page() || $this->post->is_current_page()) {
			wp_enqueue_style( $this->plugin_name.'-toast', $dir . 'css/toast.vue.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-animations', $dir . 'css/linksy-animations.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-fontawesome', $dir . 'fontawesome/css/all.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-daterangepicker', $dir . 'css/daterangepicker.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-bootstrap-grid', $dir . 'css/bootstrap-grid.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-bootstrap-utilities', $dir . 'css/bootstrap-utilities.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name, $dir . 'css/linksy-admin.css', array(), $this->version, 'all' );
		}
		wp_enqueue_style( $this->plugin_name.'-general', $dir . 'css/linksy-general.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$dir = plugin_dir_url( __FILE__ ).'assets/js/';

		if($this->is_current_page() || $this->post->is_current_page()) {
			wp_enqueue_script( $this->plugin_name. '-timer', $dir . 'timer.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-storage', $dir . 'storage.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-event-bus', $dir . 'event-bus.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-dateparser', $dir . 'dateparser.js', array(), $this->version, false );

			wp_enqueue_script( $this->plugin_name. '-moment', $dir . 'moment.min.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-socket.io', $dir . 'socket.io.min.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-vue', $dir . 'vue.global.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-axios', $dir . 'axios.min.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-daterangepicker', $dir . 'daterangepicker.min.js', array(), $this->version, false );

			wp_enqueue_script( $this->plugin_name, $dir . 'linksy-admin.js', array( 'jquery', 'underscore' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name.'-socket', $dir . 'linksy-socket.js', array( $this->plugin_name. '-socket.io', $this->plugin_name ), $this->version, false );

			wp_enqueue_script( $this->plugin_name. '-toast', $dir . 'toast.vue.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-search', $dir . 'search.vue.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-tab', $dir . 'tab.vue.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-tabbar', $dir . 'tabbar.vue.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-dropdown', $dir . 'dropdown.vue.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name. '-form', $dir . 'form.vue.js', array(), $this->version, false );

			wp_localize_script( $this->plugin_name, 'LINKSY', [
				'admin_url'      => admin_url('admin.php'),
				'post_url'       => admin_url('post.php'), 
				'site_url'       => get_site_url(),
				'socket_url'     => LINKSY_SOCKET_URL,
				// 'plugin_url_dir' => plugin_dir_url( __FILE__ ),
				'date_format'    => get_option('date_format'),
				'token'          => get_option(LINKSY_OPTION_API_KEY),
				'status'         => $this->setup_status
			]);
		}
	}

	public function add_plugin_admin_menu() {
		$this->initialize();

		add_menu_page( 
			$this->plugin_name,
			'Linksy',
			'manage_options', 
			$this->plugin_name,
            '',
            'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+DQo8IS0tIENyZWF0b3I6IENvcmVsRFJBVyAyMDIwICg2NC1CaXQgRXZhbHVhdGlvbiBWZXJzaW9uKSAtLT4NCjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWw6c3BhY2U9InByZXNlcnZlIiB3aWR0aD0iMjAwcHgiIGhlaWdodD0iMjAwcHgiIHZlcnNpb249IjEuMSIgc3R5bGU9InNoYXBlLXJlbmRlcmluZzpnZW9tZXRyaWNQcmVjaXNpb247IHRleHQtcmVuZGVyaW5nOmdlb21ldHJpY1ByZWNpc2lvbjsgaW1hZ2UtcmVuZGVyaW5nOm9wdGltaXplUXVhbGl0eTsgZmlsbC1ydWxlOmV2ZW5vZGQ7IGNsaXAtcnVsZTpldmVub2RkIg0Kdmlld0JveD0iMCAwIDQ0Mi45OSA0NDIuOTkiDQogeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiDQogeG1sbnM6eG9kbT0iaHR0cDovL3d3dy5jb3JlbC5jb20vY29yZWxkcmF3L29kbS8yMDAzIj4NCiA8ZGVmcz4NCiAgPHN0eWxlIHR5cGU9InRleHQvY3NzIj4NCiAgIDwhW0NEQVRBWw0KICAgIC5maWwwIHtmaWxsOiNBN0FBQUR9DQogICBdXT4NCiAgPC9zdHlsZT4NCiA8L2RlZnM+DQogPGcgaWQ9IkxheWVyX3gwMDIwXzEiPg0KICA8bWV0YWRhdGEgaWQ9IkNvcmVsQ29ycElEXzBDb3JlbC1MYXllciIvPg0KICA8cGF0aCBjbGFzcz0iZmlsMCIgZD0iTTg3LjcgOTUuNjVjLTE5LjEzLDE5Ljk1IC0zMC45Miw0Ni45MyAtMzAuOTIsNzYuNTJsMCAxMDMuMzFjMCwzMC41NCAxMi41OCw1OC4zIDMyLjg0LDc4LjQ0IDIwLjI1LDIwLjEzIDQ4LjE5LDMyLjYzIDc4LjkxLDMyLjYzbDEwMy45NCAwYzI3Ljk1LDAgNTMuNTgsLTEwLjM0IDczLjIzLC0yNy4zNSAxOS43NSwtMTcuMTEgMzMuNDUsLTQwLjk0IDM3LjM4LC02Ny43OGwwIC0xMzUuMzkgNTcuOTMgMCAwIDc5LjkzIC0wLjA0IDAgMCA1MS4wMSAwLjA0IDBjMCw0Mi45MiAtMTcuNjYsODEuOTIgLTQ2LjExLDExMC4yIC0yOC40NCwyOC4yNyAtNjcuNjksNDUuODIgLTExMC44Niw0NS44MmwtMTI3LjA3IDBjLTQzLjE4LDAgLTgyLjQyLC0xNy41NSAtMTEwLjg3LC00NS44MiAtMjguNDQsLTI4LjI4IC00Ni4xLC02Ny4yOCAtNDYuMSwtMTEwLjJsMCAtMTI2LjNjMCwtNDIuNzEgMTcuNTIsLTgxLjU2IDQ1Ljc3LC0xMDkuODIgMjguMjQsLTI4LjI1IDY3LjI0LC00NS44OSAxMTAuMjIsLTQ2LjE2bDAuMDMgLTAuMDMgMTMyLjcgMCAwIDU3LjQgLTkuMTkgLTAuMDZjLTIyLjYxLC0wLjEzIC01Mi4zNywtMC4zMSAtNzYuODMsLTAuNDggLTE3LjQxLC0wLjEyIC0zMi4xMywtMC4yMyAtMzkuNjMsLTAuMzEgLTI5LjUzLDEuNDIgLTU2LjE1LDE0LjM5IC03NS4zNywzNC40NHptMTkzLjgzIDU1LjI0bC0xLjYxIDAuMjUgMCAtMC4wMiAxLjYxIC0wLjIzem0tNC45OSAwLjg3bC0wLjc4IDAuMiAwIC0wLjAyIDAuNzggLTAuMTh6bS0xMS4yNyAzLjQybC0wLjU4IDAuMjUgLTIuMDYgMC44NiAwIDAuMDIgMi4wNiAtMC44OCAwIC0wLjAxIDAuNTggLTAuMjR6bS0zLjY0IDEuNTVsLTAuNjMgMC4zMSAwIC0wLjAxIDAuNjMgLTAuM3ptLTAuNzUgMC4zNmwtMC41MyAwLjI2IC0wLjc2IDAuMzggMCAwLjAxIDAuNzYgLTAuMzkgLTAuMDEgLTAuMDEgMC41NCAtMC4yNXptLTMuODYgMS45OGwtMC40NCAwLjI2IC0wLjAxIC0wLjAyIDAuNDUgLTAuMjR6bS0xLjYzIDAuOTFsLTAuOTkgMC42IC0wLjY3IDAuNCAwLjAxIDAuMDEgMC42NiAtMC40MSAtMC4wMSAtMC4wMSAxIC0wLjU5em0tMi4zIDEuNDNjLTAuMjksMC4xOSAtMC41OSwwLjM5IC0wLjg5LDAuNTdsLTAuMDEgLTAuMDJjMC4zMSwtMC4xNyAwLjYsLTAuMzYgMC45LC0wLjU1em0tMi4xOCAxLjQybC0wLjA4IDAuMDcgLTAuMDEgLTAuMDEgMC4wOSAtMC4wNnptLTEuNjMgMS4xNmwtMS4yMiAwLjkyIC0wLjAxIC0wLjAxIDEuMjMgLTAuOTF6bS0yLjUzIDEuOTJsLTAuMDMgMC4wNCAtMC42NSAwLjUzIDAuMDEgMC4wMSAtMS45NSAxLjY3IC0wLjAxIC0wLjAxIDEuOTYgLTEuNjYgMC42NCAtMC41NCAtMC4wMSAtMC4wMSAwLjA0IC0wLjAzem0tMjUuNTEgLTAuMDNsOC4yNyAtOSAwLjYgLTAuNTkgLTAuMDEgLTAuMDFjMC41NCwtMC41NCAxLjIyLC0xLjExIDEuOCwtMS42MyAwLjIzLC0wLjExIDIuMTIsLTEuODIgMi40NSwtMi4xIDAuMjMsLTAuMTEgMC42NiwtMC41MiAwLjg2LC0wLjY3bDAuMTUgLTAuMSAtMC4wMSAtMC4wMiAxLjU1IC0xLjIxIDAuMDcgLTAuMDQgMC44NiAtMC42NCAtMC4wMSAtMC4wMiAwLjU3IC0wLjQxIC0wLjAyIC0wLjAyIDAuMTcgLTAuMTMgMC4xMSAtMC4wM2MwLjMxLC0wLjIyIDAuNiwtMC40NiAwLjkyLC0wLjY3bC0wLjAxIC0wLjAxIDAuNzYgLTAuNTQgMC4wMyAwIDAuMjcgLTAuMiAwLjcxIC0wLjQ3IC0wLjAxIC0wLjAyIDAuNzkgLTAuNTEgMC4wNCAwYzAuOTIsLTAuNjEgMS43OSwtMS4xOCAyLjc0LC0xLjc1bDAuMDQgMGMwLjksLTAuNTUgMS43OSwtMS4xIDIuNzEsLTEuNjJsMC4wMyAwIDEuMTYgLTAuNjYgMC4wNyAwIDEuMzQgLTAuNzIgMC4wMSAwLjAxIDAuODYgLTAuNDQgMCAtMC4wNCAxLjk0IC0wLjkxYzAuMDUsLTAuMDQgMC4xMywtMC4wOSAwLjE5LC0wLjA5bDAuNjggLTAuMzIgMCAtMC4wMyAwLjEyIC0wLjA2IDAuMDcgMCAwLjQ1IC0wLjIgMC4wNCAtMC4wNGMwLjA3LC0wLjAzIDAuNjIsLTAuMyAwLjcsLTAuM2wyLjM4IC0xLjAyIDIuOCAtMS4wNiAwLjAzIC0wLjA0IDAuMzIgLTAuMTEgMC4wNyAwIDAuNzQgLTAuMjcgMC4wNCAtMC4wM2MwLjI4LC0wLjEgMC42MSwtMC4yMiAwLjg5LC0wLjI5bC0wLjAxIC0wLjAyIDIuNzMgLTAuODggMC4wNyAwIDAuMjMgLTAuMDcgMC4wNCAtMC4wNCAwLjYxIC0wLjE4IDAuMDcgMGMwLjE3LC0wLjA1IDAuMzQsLTAuMDkgMC40OSwtMC4xN2wwLjcgLTAuMiAwLjEgMCAxLjk1IC0wLjUyYzAuMzMsLTAuMTYgMS42NSwtMC40MiAyLjA3LC0wLjUyIDAuMjYsMCAwLjc4LC0wLjE1IDEuMDMsLTAuMjFsMC4wNCAtMC4wMyAyIC0wLjQyYzAuMDMsMCAwLjIsMC4wMiAwLjE5LC0wLjAxIDAuMiwtMC4wNCAyLjE5LC0wLjQzIDIuMiwtMC4zOWwwLjg4IC0wLjE0IDAgLTAuMDJjNy4yNCwtMS4xIDExLjcxLDAuMyAxOC45NSwtMi44NCA0LjU1LC0xLjk3IDguNywtNS4wNiAxMi4wMiwtOS4yMSAxNi4wNywtMjAuMDggMzIuMTEsLTQwLjE4IDQ4LjE3LC02MC4yN2wtMjUuMzUgLTIwLjAxIDExMS4yNCAtNDAuNyAtMTYuMTMgMTE1LjggLTIzLjY4IC0xOC43MWMtMTcuMDcsMjEuMzUgLTQwLjQ3LDU1LjU0IC01OS42LDc0LjU2IC01LjYsNi44NCAtMTIuNTYsMTEuOTQgLTIwLjE2LDE1LjIgLTcuODYsMy4zOCAtMTYuNDUsNC44MSAtMjQuOTgsNC4ybC0wLjkzIC0wLjFjLTAuNTYsLTAuMSAtMS4xNCwtMC4xOSAtMS43NCwtMC4yNiAtMC42NywtMC4wOSAtMS4yNCwtMC4xNCAtMS42NiwtMC4xN2wtMC4wNCAwYy01LjA0LC0wLjM0IC0xMC4wOSwwLjUxIC0xNC43LDIuNWwwLjAxIDAuMDFjLTQuNDcsMS45MyAtOC41NSw0LjkyIC0xMS44Myw4LjkybC0wLjMzIDAuMzkgMC4wMyAwLjAyYy0xNC4wMiwxNy45MiAtMjguNTEsMzUuNjYgLTQyLjcyLDUzLjQ1bC0wLjAxIC0wLjAyIC0wLjE3IDAuMjEgMCAwLjAzIC0xNC43NiAxOC40OCAtNDYuMDkgLTM2LjM4IDU3Ljc0IC03Mi4yNXoiLz4NCiA8L2c+DQo8L3N2Zz4NCg==',
            65
        );

		if ($this->setup_status !== -1) {
			$this->dashboard->register_admin_page();
			$this->reports->register_admin_page();
			$this->inbound_links->register_admin_page();
			$this->anchor_cloud->register_admin_page();
			$this->keywords_rating->register_admin_page();
			$this->playground->register_admin_page();
			$this->settings->register_admin_page();
			$this->post->register_admin_page();
		}

		// todo: only load if setup not complete
		$this->setup->register_admin_page();
	}

    private function is_current_page() {
    	if(isset($_GET['page']) && strpos(strtolower($_GET['page']),  strtolower(LINKSY_PLUGIN_NAME)) !== false) {
    		return true;
    	}

    	return false;
    }

	private function initialize() {
		if (!Config::get(LINKSY_OPTION_VIRGIN, false)) {
			Config::set(LINKSY_OPTION_VIRGIN, true);
			exit( wp_redirect(admin_url("admin.php?page={$this->plugin_name}-setup")) );
		}

		if ($this->is_setup_complete()) {
			$this->setup_status = 1;
		} else {
			if (Config::get(LINKSY_OPTION_SETUP_STARTED, false)) {
				$this->setup_status = 0;
			}
		}

		if($this->is_current_page()) {
			if ($this->setup_status == -1) {
				if (!$this->setup->is_current_page()) {
					exit( header("Location:".admin_url("admin.php?page={$this->plugin_name}-setup")) );
				}
			} else {
				if ($this->setup_status == 0) {
					if (!$this->setup->is_current_page()) {
						new Notices("Setup still in progress", 'error');
					}
				} else {
					if (!Config::get(LINKSY_OPTION_ANCHORS_SETUP_COMPLETE, false) || !Config::get(LINKSY_OPTION_KEYWORDS_SETUP_COMPLETE, false)) {
						new Notices("Setup still in progress", 'info');
					}
				}

				if (!Config::get(LINKSY_OPTION_PLUGIN_ACTIVE, false) && !$this->settings->is_current_page()) {
					exit( header("Location:".admin_url("admin.php?page={$this->plugin_name}-settings#licensing")) );
				}
			}
		}
	}

	private function is_setup_complete() {
    	try {
			$token = Config::get(LINKSY_OPTION_API_KEY);

			if (empty($token)) {
				throw new Exception("Token not generated yet");
			}

			if (Config::get(LINKSY_OPTION_SETUP_COMPLETE, false)) {
				return true;
			}

            $api = new Api( $token );
            $res = $api->get(LINKSY_API_URL.'user/site/');

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1); 
            }

			if ($res['setup_complete']) {
				Config::set(LINKSY_OPTION_SETUP_COMPLETE, true, true);
				return true;
			}

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

	private function clear_options() {
		$settingOptions = array(
			LINKSY_OPTION_VIRGIN,
			LINKSY_OPTION_API_KEY,
			LINKSY_OPTION_PLUGIN_ACTIVE,
			LINKSY_OPTION_SETUP_STARTED,
			LINKSY_OPTION_SETUP_COMPLETE,
			LINKSY_OPTION_PLUGIN_VERSION
		);
	 
		// Clear up our settings
		foreach ( $settingOptions as $settingName ) {
			delete_option( $settingName );
		}
	}

	private function clear_remote() {
		// clear remote table
		try {
			wp_remote_request( LINKSY_API_URL.'posts/',  [
				'timeout' => 20,
				'method'  => 'DELETE',
				'headers' => [
					'Accept' => 'application/json',
					'Authorization' => 'Bearer ' . get_option(LINKSY_OPTION_API_KEY),
					'X-WP-Site' => get_site_url(),
				],
			]);
		} catch (\Exception $e) {
			error_log($e->getMessage());
		}  
	}
}
