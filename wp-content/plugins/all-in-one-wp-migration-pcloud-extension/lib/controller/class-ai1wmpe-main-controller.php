<?php
/**
 * Copyright (C) 2014-2020 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

class Ai1wmpe_Main_Controller extends Ai1wmve_Main_Controller {

	/**
	 * Register plugin menus
	 *
	 * @return void
	 */
	public function admin_menu() {
		if ( ai1wmpe_is_admin() ) {
			// Sub-level Settings menu
			add_submenu_page(
				'ai1wm_export',
				__( 'pCloud Settings', AI1WMPE_PLUGIN_NAME ),
				__( 'pCloud Settings', AI1WMPE_PLUGIN_NAME ),
				'export',
				'ai1wmpe_settings',
				'Ai1wmpe_Settings_Controller::index'
			);
		}
	}

	/**
	 * Enqueue scripts and styles for Export Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_export_scripts_and_styles( $hook ) {
		if ( stripos( 'toplevel_page_ai1wm_export', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmpe_export',
				Ai1wm_Template::asset_link( 'css/export.min.rtl.css', 'AI1WMPE' ),
				array( 'ai1wm_export' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmpe_export',
				Ai1wm_Template::asset_link( 'css/export.min.css', 'AI1WMPE' ),
				array( 'ai1wm_export' )
			);
		}

		wp_enqueue_script(
			'ai1wmpe_export',
			Ai1wm_Template::asset_link( 'javascript/export.min.js', 'AI1WMPE' ),
			array( 'ai1wm_export' )
		);

		wp_localize_script(
			'ai1wmpe_export',
			'ai1wmpe_dependencies',
			array( 'messages' => $this->get_missing_dependencies() )
		);
	}

	/**
	 * Enqueue scripts and styles for Import Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_import_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_import', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmpe_import',
				Ai1wm_Template::asset_link( 'css/import.min.rtl.css', 'AI1WMPE' ),
				array( 'ai1wm_import' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmpe_import',
				Ai1wm_Template::asset_link( 'css/import.min.css', 'AI1WMPE' ),
				array( 'ai1wm_import' )
			);
		}

		wp_enqueue_script(
			'ai1wmpe_import',
			Ai1wm_Template::asset_link( 'javascript/import.min.js', 'AI1WMPE' ),
			array( 'ai1wm_import' )
		);

		wp_localize_script(
			'ai1wmpe_import',
			'ai1wmpe_import',
			array(
				'ajax' => array(
					'browser_url'     => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmpe_pcloud_browser' ) ),
					'incremental_url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmpe_pcloud_incremental' ) ),
				),
			)
		);

		wp_localize_script(
			'ai1wmpe_import',
			'ai1wmpe_dependencies',
			array( 'messages' => $this->get_missing_dependencies() )
		);
	}

	/**
	 * Enqueue scripts and styles for Settings Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_settings_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wmpe_settings', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmpe_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.rtl.css', 'AI1WMPE' ),
				array( 'ai1wm_servmask' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmpe_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.css', 'AI1WMPE' ),
				array( 'ai1wm_servmask' )
			);
		}

		wp_enqueue_script(
			'ai1wmpe_settings',
			Ai1wm_Template::asset_link( 'javascript/settings.min.js', 'AI1WMPE' ),
			array( 'ai1wm_settings' )
		);

		wp_localize_script(
			'ai1wmpe_settings',
			'ai1wm_feedback',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wmpe_settings',
			'ai1wmpe_settings',
			array(
				'ajax'  => array(
					'folder_url'   => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmpe_pcloud_folder' ) ),
					'account_url'  => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmpe_pcloud_account' ) ),
					'selector_url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmpe_pcloud_selector' ) ),
				),
				'token' => get_option( 'ai1wmpe_pcloud_token' ),
			)
		);
	}

	/**
	 * Register listeners for actions
	 *
	 * @return void
	 */
	protected function activate_actions() {
		add_action( 'admin_init', array( $this, 'init' ) );

		add_action( 'plugins_loaded', array( $this, 'ai1wm_notification' ), 20 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_export_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts_and_styles' ), 20 );
	}

	/**
	 * Enable notifications
	 *
	 * @return void
	 */
	public function ai1wm_notification() {
		if ( ai1wmpe_is_running() ) {
			add_filter( 'ai1wm_notification_ok_toggle', 'Ai1wmpe_Settings_Controller::notify_ok_toggle' );
			add_filter( 'ai1wm_notification_ok_email', 'Ai1wmpe_Settings_Controller::notify_email' );
			add_filter( 'ai1wm_notification_error_toggle', 'Ai1wmpe_Settings_Controller::notify_error_toggle' );
			add_filter( 'ai1wm_notification_error_subject', 'Ai1wmpe_Settings_Controller::notify_error_subject' );
			add_filter( 'ai1wm_notification_error_email', 'Ai1wmpe_Settings_Controller::notify_email' );
		}
	}

	/**
	 * Export and import commands
	 *
	 * @return void
	 */
	public function ai1wm_commands() {
		if ( ai1wmpe_is_running() ) {
			add_filter( 'ai1wm_export', 'Ai1wmpe_Export_PCloud::execute', 250 );
			add_filter( 'ai1wm_export', 'Ai1wmpe_Export_Upload::execute', 260 );
			add_filter( 'ai1wm_export', 'Ai1wmpe_Export_Retention::execute', 280 );
			add_filter( 'ai1wm_export', 'Ai1wmpe_Export_Done::execute', 290 );

			add_filter( 'ai1wm_import', 'Ai1wmpe_Import_PCloud::execute', 20 );
			add_filter( 'ai1wm_import', 'Ai1wmpe_Import_Download::execute', 30 );
			add_filter( 'ai1wm_import', 'Ai1wmpe_Import_Settings::execute', 290 );
			add_filter( 'ai1wm_import', 'Ai1wmpe_Import_Database::execute', 310 );

			remove_filter( 'ai1wm_export', 'Ai1wm_Export_Download::execute', 250 );
			remove_filter( 'ai1wm_import', 'Ai1wm_Import_Upload::execute', 5 );
		}

		if ( ai1wmpe_is_incremental() ) {
			add_filter( 'ai1wm_export', 'Ai1wmpe_Export_Incremental_Content::execute', 105 );
			add_filter( 'ai1wm_export', 'Ai1wmpe_Export_Incremental_Media::execute', 115 );
			add_filter( 'ai1wm_export', 'Ai1wmpe_Export_Incremental_Plugins::execute', 125 );
			add_filter( 'ai1wm_export', 'Ai1wmpe_Export_Incremental_Themes::execute', 135 );
			add_filter( 'ai1wm_export', 'Ai1wmpe_Export_Incremental_Backups::execute', 270 );

			add_filter( 'ai1wm_import', 'Ai1wmpe_Import_Incremental_PCloud::execute', 20 );
			add_filter( 'ai1wm_import', 'Ai1wmpe_Import_Incremental_Download::execute', 30 );

			remove_filter( 'ai1wm_export', 'Ai1wmpe_Export_Retention::execute', 280 );
			remove_filter( 'ai1wm_import', 'Ai1wmpe_Import_PCloud::execute', 20 );
			remove_filter( 'ai1wm_import', 'Ai1wmpe_Import_Download::execute', 30 );
		}
	}

	public function get_missing_dependencies() {
		$messages = array();
		if ( ! extension_loaded( 'curl' ) ) {
			$messages[] = __( 'Your PHP is missing cURL extension. <a href="https://help.servmask.com/knowledgebase/curl-missing-in-php-installation/" target="_blank">Technical details</a>', AI1WMPE_PLUGIN_NAME );
		}

		return $messages;
	}

	/**
	 * Check whether All-in-One WP Migration has been loaded
	 *
	 * @return void
	 */
	public function ai1wm_loaded() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ), 20 );
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		}

		// pCloud init cron
		add_action( 'init', 'Ai1wmpe_Settings_Controller::init_cron' );

		if ( ai1wmpe_is_admin() ) {
			// pCloud settings
			add_action( 'admin_post_ai1wmpe_pcloud_settings', 'Ai1wmpe_Settings_Controller::settings' );

			// pCloud revoke
			add_action( 'admin_post_ai1wmpe_pcloud_revoke', 'Ai1wmpe_Settings_Controller::revoke' );

			// Folder picker
			add_action( 'ai1wmpe_settings_left_end', 'Ai1wmpe_Settings_Controller::picker' );
		}

		// Cron settings
		add_action( 'ai1wmpe_pcloud_hourly_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'ai1wmpe_pcloud_daily_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'ai1wmpe_pcloud_weekly_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'ai1wmpe_pcloud_monthly_export', 'Ai1wm_Export_Controller::export' );

		// Picker
		add_action( 'ai1wm_import_left_end', 'Ai1wmpe_Import_Controller::picker' );

		// Add export button
		add_filter( 'ai1wm_export_pcloud', 'Ai1wmpe_Export_Controller::button' );

		// Add import button
		add_filter( 'ai1wm_import_pcloud', 'Ai1wmpe_Import_Controller::button' );
	}

	/**
	 * WP CLI commands: extension
	 *
	 * @return void
	 */
	public function wp_cli_extension() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command(
				'ai1wm pcloud',
				'Ai1wmpe_PCloud_WP_CLI_Command',
				array(
					'shortdesc'     => __( 'All-in-One WP Migration Command for pCloud', AI1WMPE_PLUGIN_NAME ),
					'before_invoke' => array( $this, 'activate_extension_commands' ),
				)
			);
			WP_CLI::add_command(
				'ai1wm pcloud incremental',
				'Ai1wmpe_PCloud_WP_CLI_Incremental_Command',
				array(
					'shortdesc'     => __( 'All-in-One WP Migration Command for pCloud incremental backups', AI1WMPE_PLUGIN_NAME ),
					'before_invoke' => array( $this, 'activate_extension_incremental_commands' ),
				)
			);
		}
	}

	/**
	 * Activates extension specific commands
	 *
	 * @return void
	 */
	public function activate_extension_commands() {
		$_GET['pcloud'] = 1;
		$this->ai1wm_commands();
	}

	/**
	 * Activates extension specific commands
	 *
	 * @return void
	 */
	public function activate_extension_incremental_commands() {
		$_GET['incremental'] = 1;
		$this->activate_extension_commands();
	}

	/**
	 * Display All-in-One WP Migration notice
	 *
	 * @return void
	 */
	public function ai1wm_notice() {
		?>
		<div class="error">
			<p>
				<?php
				_e(
					'pCloud Extension requires <a href="https://wordpress.org/plugins/all-in-one-wp-migration/" target="_blank">All-in-One WP Migration plugin</a> to be activated. ' .
					'<a href="https://help.servmask.com/knowledgebase/install-instructions-for-pcloud-extension/" target="_blank">pCloud Extension install instructions</a>',
					AI1WMPE_PLUGIN_NAME
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add links to plugin list page
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === AI1WMPE_PLUGIN_BASENAME ) {
			$links[] = __( '<a href="https://help.servmask.com/knowledgebase/pcloud-extension-user-guide/" target="_blank">User Guide</a>', AI1WMPE_PLUGIN_NAME );
			$links[] = __( '<a href="https://servmask.com/contact-support" target="_blank">Contact Support</a>', AI1WMPE_PLUGIN_NAME );
		}

		return $links;
	}

	/**
	 * Register initial parameters
	 *
	 * @return void
	 */
	public function init() {
		if ( isset( $_GET['ai1wmpe_hostname'], $_GET['ai1wmpe_token'], $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'] ) && current_user_can( 'export' ) ) {
			update_option( 'ai1wmpe_pcloud_hostname', urldecode( $_GET['ai1wmpe_hostname'] ) );
			update_option( 'ai1wmpe_pcloud_token', urldecode( $_GET['ai1wmpe_token'] ) );

			// Redirect to settings page
			wp_redirect( network_admin_url( 'admin.php?page=ai1wmpe_settings' ) );
			exit;
		}
	}

	/**
	 * Register initial router
	 *
	 * @return void
	 */
	public function router() {
		if ( current_user_can( 'export' ) ) {
			if ( ai1wmpe_is_admin() ) {
				add_action( 'wp_ajax_ai1wmpe_pcloud_folder', 'Ai1wmpe_Settings_Controller::folder' );
				add_action( 'wp_ajax_ai1wmpe_pcloud_account', 'Ai1wmpe_Settings_Controller::account' );
				add_action( 'wp_ajax_ai1wmpe_pcloud_selector', 'Ai1wmpe_Settings_Controller::selector' );
			}
		}

		if ( current_user_can( 'import' ) ) {
			add_action( 'wp_ajax_ai1wmpe_pcloud_browser', 'Ai1wmpe_Import_Controller::browser' );
			add_action( 'wp_ajax_ai1wmpe_pcloud_incremental', 'Ai1wmpe_Import_Controller::incremental' );
		}
	}
}
