<?php defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WP_Sheet_Editor_Universal_Sheet' ) ) {

	/**
	 * Filter rows in the spreadsheet editor.
	 */
	class WP_Sheet_Editor_Universal_Sheet {

		private static $instance = false;
		public $plugin_url       = null;
		public $plugin_dir       = null;
		public $buy_link         = null;
		public $settings         = null;
		public $args             = null;
		public $vg_plugin_sdk    = null;

		private function __construct() {
		}

		function init() {
			$this->plugin_url = plugins_url( '/', __FILE__ );
			$this->plugin_dir = __DIR__;

			require __DIR__ . '/inc/csv-api.php';
			add_action( 'vg_sheet_editor/initialized', array( $this, 'late_init' ) );
		}



		function late_init() {

			add_action( 'vg_sheet_editor/editor/before_init', array( $this, 'register_toolbar_items' ) );

			// Enqueue metabox css and js
			add_action( 'vg_sheet_editor/after_enqueue_assets', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_ajax_vgse_delete_saved_export', array( $this, 'delete_saved_export' ) );
			add_action( 'wp_ajax_vgse_delete_saved_import', array( $this, 'delete_saved_import' ) );
			add_action( 'load-edit.php', array( $this, 'handle_bulk_actions' ) );
			add_action( 'current_screen', array( $this, 'add_bulk_actions_hook' ) );
		}

		function render_previous_imports_modal( $post_type ) {
			include __DIR__ . '/views/previous-imports-modal.php';
		}

		function delete_saved_import() {
			if ( empty( $_REQUEST['hash'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Missing parameters.', 'vg_sheet_editor' ) ) );
			}

			if ( ! VGSE()->helpers->verify_nonce_from_request() || ! VGSE()->helpers->user_can_manage_options() ) {
				wp_send_json_error( array( 'message' => esc_html__( 'You dont have enough permissions to view this page.', 'vg_sheet_editor' ) ) );
			}

			$hash             = sanitize_text_field( wp_unslash( $_REQUEST['hash'] ) );
			$previous_imports = get_option( 'vgse_previous_imports', array() );

			if ( isset( $previous_imports[ $hash ] ) && (int) $previous_imports[ $hash ]['author'] === get_current_user_id() ) {
				unset( $previous_imports[ $hash ] );
				update_option( 'vgse_previous_imports', $previous_imports, false );
			} else {
				wp_send_json_error( array( 'message' => esc_html__( 'Only the user who created the import can delete it.', 'vg_sheet_editor' ) ) );
			}
			wp_send_json_success();
		}

		function add_bulk_actions_hook( $screen ) {
			if ( $screen->base === 'edit' && ! empty( $screen->post_type ) && VGSE()->helpers->is_post_type_allowed( $screen->post_type ) ) {
				add_filter( "bulk_actions-{$screen->id}", array( $this, 'add_bulk_actions' ), 10, 1 );
			}
		}

		function add_bulk_actions( $bulk_actions ) {
			$bulk_actions['wpse_export'] = esc_html__( 'Export with WP Sheet Editor', 'vg_sheet_editor' );
			return $bulk_actions;
		}

		function handle_bulk_actions() {
			if ( ! isset( $_REQUEST['post_type'] ) ) {
				return;
			}
			// 1. get the action
			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action        = $wp_list_table->current_action();

			$allowed_actions = array( 'wpse_export' );
			if ( ! in_array( $action, $allowed_actions, true ) ) {
				return;
			}

			// 2. security check
			check_admin_referer( 'bulk-posts' );

			// 3. get the post ids
			if ( isset( $_REQUEST['post'] ) ) {
				$post_ids = array_map( 'intval', $_REQUEST['post'] );
			}

			if ( empty( $post_ids ) ) {
				return;
			}

			// 4. build the URL and redirect
			$post_type = VGSE()->helpers->sanitize_table_key( $_REQUEST['post_type'] );
			$url       = VGSE()->helpers->get_editor_url( $post_type );

			$filters = array(
				'post__in' => implode( ',', $post_ids ),
			);

			$url = add_query_arg( 'wpse_custom_filters', urlencode_deep( $filters ), $url );
			$url = add_query_arg( 'wpse_custom_filters_nonce', wp_create_nonce( 'bep-nonce' ), $url );
			$url = add_query_arg( 'wpse_auto_export', 1, $url );
			wp_safe_redirect( $url );
			exit();
		}

		/**
		 * Render modal for exporting csv
		 * @param str $post_type
		 * @return void
		 */
		function render_export_csv_modal( $post_type ) {
			include __DIR__ . '/views/export-modal.php';
		}

		/**
		 * Render modal for importing csv
		 * @param str $post_type
		 * @return void
		 */
		function render_import_csv_modal( $post_type ) {
			include __DIR__ . '/views/import-modal.php';
		}

		function get_import_sources_options() {
			$out = array(
				'--'          => esc_html__( '--', 'vg_sheet_editor' ),
				'csv_upload'  => esc_html__( 'CSV/Excel file from my computer', 'vg_sheet_editor' ),
				'csv_url'     => esc_html__( 'CSV/Excel file from url', 'vg_sheet_editor' ),
				'paste'       => esc_html__( 'Copy/paste from another spreadsheet or table', 'vg_sheet_editor' ),
				'server_file' => esc_html__( 'CSV/Excel file in the server', 'vg_sheet_editor' ),
			);
			return apply_filters( 'vg_sheet_editor/universal_sheet/import_sources_options', $out );
		}
		function get_target_software_options() {
			$target = array(
				''      => array(
					''              => esc_html__( '--', 'vg_sheet_editor' ),
					'csv'           => esc_html__( 'CSV file', 'vg_sheet_editor' ),
					'google_sheets' => esc_html__( 'Google Sheets', 'vg_sheet_editor' ),
					'other'         => esc_html__( 'Other', 'vg_sheet_editor' ),
				),
				'excel' => array(
					'excel' => esc_html__( 'Microsoft Excel (XLSX)', 'vg_sheet_editor' ),
				),
			);
			return apply_filters( 'vg_sheet_editor/universal_sheet/target_software_options', $target );
		}

		// Function to list allowed file formats (just keys) for export/import with a filter
		function get_allowed_file_formats() {
			$formats = array( 'csv', 'json', 'xlsx', 'xls' );
			return apply_filters( 'vg_sheet_editor/universal_sheet/allowed_file_formats', $formats );
		}


		function get_export_options( $post_type ) {
			$columns = VGSE()->helpers->get_post_type_columns_options(
				$post_type,
				array(
					'conditions' => array(
						'allow_plain_text' => true,
					),
				),
				false,
				false,
				true
			);
			return apply_filters( 'vg_sheet_editor/export/columns', $columns, $post_type );
		}

		function render_wp_fields_export_options( $post_type ) {
			$columns = $this->get_export_options( $post_type );
			do_action( 'vg_sheet_editor/export/before_available_columns_options', $post_type );
			foreach ( $columns as $key => $column ) {
				echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $column['title'] ) . '</option>';
			}
			do_action( 'vg_sheet_editor/export/after_available_columns_options', $post_type );
		}

		function get_wp_fields_export_options( $post_type ) {
			ob_start(); // Start output buffering
			$this->render_wp_fields_export_options( $post_type );
			$output = ob_get_clean(); // Get the buffered output and clean the buffer

			$options = array();
			preg_match_all( '/<option value="([^"]+)">([^<]+)<\/option>/', $output, $matches );

			if ( isset( $matches[1] ) && isset( $matches[2] ) ) {
				for ( $i = 0; $i < count( $matches[1] ); $i++ ) {
					$options[ $matches[1][ $i ] ] = $matches[2][ $i ];
				}
			}

			return $options;
		}

		function get_import_options( $post_type ) {
			// we don't use allow_to_save => true here because we need
			// readonly columns like the ID to find items to update
			$columns             = VGSE()->helpers->get_post_type_columns_options(
				$post_type,
				array(
					'conditions' => array(
						'allow_plain_text' => true,
					),
				),
				false,
				false,
				true
			);
			$not_allowed_columns = VGSE()->helpers->get_post_type_columns_options(
				$post_type,
				array(
					'conditions' => array(
						'allow_to_import' => false,
					),
				),
				false,
				false,
				true
			);
			if ( $not_allowed_columns ) {
				$columns = array_diff_key( $columns, $not_allowed_columns );
			}
			return apply_filters( 'vg_sheet_editor/import/columns', $columns, $post_type );
		}

		function render_wp_fields_import_options( $post_type ) {
			$columns = $this->get_import_options( $post_type );
			?>
			<option value=""><?php esc_html_e( 'Ignore this column', 'vg_sheet_editor' ); ?></option>
			<?php
			do_action( 'vg_sheet_editor/import/before_available_columns_options', $post_type );
			foreach ( $columns as $key => $column ) {
				echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $column['title'] ) . '</option>';
			}

			if ( post_type_exists( $post_type ) ) {
				echo '<option value="post_name__in">' . esc_html__( 'Full URL', 'vg_sheet_editor' ) . '</option>';
			}
			do_action( 'vg_sheet_editor/import/after_available_columns_options', $post_type );
		}

		function get_wp_fields_import_options( $post_type ) {
			ob_start(); // Start output buffering
			$this->render_wp_fields_import_options( $post_type );
			$output = ob_get_clean(); // Get the buffered output and clean the buffer

			$options = array();
			preg_match_all( '/<option value="([^"]+)">([^<]+)<\/option>/', $output, $matches );

			if ( isset( $matches[1] ) && isset( $matches[2] ) ) {
				for ( $i = 0; $i < count( $matches[1] ); $i++ ) {
					$options[ $matches[1][ $i ] ] = $matches[2][ $i ];
				}
			}

			return $options;
		}

		function delete_saved_export() {
			if ( empty( $_REQUEST['post_type'] ) || empty( $_REQUEST['search_name'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Missing parameters.', 'vg_sheet_editor' ) ) );
			}

			if ( ! VGSE()->helpers->verify_nonce_from_request() || ! VGSE()->helpers->user_can_manage_options() ) {
				wp_send_json_error( array( 'message' => esc_html__( 'You dont have enough permissions to view this page.', 'vg_sheet_editor' ) ) );
			}

			$post_type = VGSE()->helpers->sanitize_table_key( wp_unslash( $_REQUEST['post_type'] ) );
			$name      = sanitize_text_field( wp_unslash( $_REQUEST['search_name'] ) );

			$saved_items = get_option( 'vgse_saved_exports' );
			if ( empty( $saved_items ) ) {
				wp_send_json_success();
			}

			if ( ! isset( $saved_items[ $post_type ] ) ) {
				wp_send_json_success();
			}

			$same_name = wp_list_filter( $saved_items[ $post_type ], array( 'name' => $name ) );
			foreach ( $same_name as $index => $same_name_search ) {
				unset( $saved_items[ $post_type ][ $index ] );
			}
			update_option( 'vgse_saved_exports', $saved_items, false );
			wp_send_json_success();
		}

		function register_toolbar_items( $editor ) {
			$post_types = $editor->args['enabled_post_types'];
			foreach ( $post_types as $post_type ) {
				$editor->args['toolbars']->register_item(
					'export_csv',
					array(
						'type'                  => 'button', // html | switch | button
						'content'               => esc_html__( 'Export', 'vg_sheet_editor' ),
						'id'                    => 'export_csv',
						'allow_in_frontend'     => true,
						'toolbar_key'           => 'secondary',
						'extra_html_attributes' => 'data-remodal-target="export-csv-modal"',
						'footer_callback'       => array( $this, 'render_export_csv_modal' ),
						'footer_callback_cache' => true,
					),
					$post_type
				);

				if ( VGSE()->helpers->user_can_manage_options() ) {
					$saved_exports = WPSE_CSV_API_Obj()->get_saved_exports( $post_type );
					foreach ( $saved_exports as $index => $saved_export ) {
						$editor->args['toolbars']->register_item(
							'save_export' . $index,
							array(
								'type'                  => 'button',
								'content'               => esc_html( $saved_export['name'] ),
								'toolbar_key'           => 'secondary',
								'allow_in_frontend'     => true,
								'parent'                => 'export_csv',
								'extra_html_attributes' => 'data-saved-type="export" data-saved-item data-item-name="' . esc_attr( $saved_export['name'] ) . '" data-start-saved-export="' . esc_attr( json_encode( $saved_export ) ) . '"',
							),
							$post_type
						);
					}
				}

				$editor->args['toolbars']->register_item(
					'share_export',
					array(
						'type'                  => 'button',
						'content'               => esc_html__( 'Download CSV file', 'vg_sheet_editor' ),
						'extra_html_attributes' => 'data-remodal-target="export-csv-modal"',
						'toolbar_key'           => 'primary',
						'allow_in_frontend'     => false,
						'parent'                => 'share',
					),
					$post_type
				);
				$editor->args['toolbars']->register_item(
					'import_csv',
					array(
						'type'                  => 'button', // html | switch | button
						'content'               => esc_html__( 'Import', 'vg_sheet_editor' ),
						'id'                    => 'import_csv',
						'allow_in_frontend'     => true,
						'toolbar_key'           => 'secondary',
						'extra_html_attributes' => 'data-remodal-target="import-csv-modal"',
						'footer_callback'       => array( $this, 'render_import_csv_modal' ),
					),
					$post_type
				);
				$editor->args['toolbars']->register_item(
					'run_previous_import',
					array(
						'type'                  => 'button',
						'content'               => esc_html__( 'Run a previous import again', 'vg_sheet_editor' ),
						'id'                    => 'run_previous_import',
						'allow_in_frontend'     => true,
						'toolbar_key'           => 'secondary',
						'parent'                => 'import_csv',
						'extra_html_attributes' => 'data-remodal-target="previous-imports-modal"',
						'footer_callback'       => array( $this, 'render_previous_imports_modal' ),
					),
					$post_type
				);
			}
		}

		/**
		 * Enqueue metabox assets
		 * @global obj $post
		 * @param str $hook
		 */
		function enqueue_assets( $hook = null ) {
			if ( ! VGSE()->helpers->is_editor_page() ) {
				return;
			}
			$post_type = VGSE()->helpers->get_provider_from_query_string();

			wp_enqueue_script( 'vgse-universal-sheet-init', $this->plugin_url . 'assets/js/init.js', array( 'jquery' ), filemtime( __DIR__ . '/assets/js/init.js' ), false );

			$all_previous_imports = get_option( 'vgse_previous_imports', array() );
			$previous_imports     = array();
			if ( is_array( $all_previous_imports ) ) {
				foreach ( $all_previous_imports as $hash => $import_data ) {
					if ( $import_data['post_type'] === $post_type ) {
						$user                      = get_userdata( $import_data['author'] );
						$import_data['user_login'] = $user ? $user->user_login : '';
						$previous_imports[ $hash ] = $import_data;
					}
				}
			}

			wp_localize_script(
				'vgse-universal-sheet-init',
				'vgse_universal_sheet_data',
				array(
					'rest_base_url'             => rest_url(),
					'post_type'                 => VGSE()->helpers->get_provider_from_query_string(),
					'export_columns'            => $this->get_wp_fields_export_options( $post_type ),
					'allowed_target_software'   => array_keys( vgse_universal_sheet()->get_target_software_options() ),
					'import_wp_mapping_options' => $this->get_wp_fields_import_options( $post_type ),
					'import_sources'            => vgse_universal_sheet()->get_import_sources_options(),
					'previous_imports'          => $previous_imports,
					'current_user_id'           => get_current_user_id(),
				)
			);
		}


		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if ( null == WP_Sheet_Editor_Universal_Sheet::$instance ) {
				WP_Sheet_Editor_Universal_Sheet::$instance = new WP_Sheet_Editor_Universal_Sheet();
				WP_Sheet_Editor_Universal_Sheet::$instance->init();
			}
			return WP_Sheet_Editor_Universal_Sheet::$instance;
		}

		function __set( $name, $value ) {
			$this->$name = $value;
		}

		function __get( $name ) {
			return $this->$name;
		}

	}

}

add_action( 'plugins_loaded', 'vgse_universal_sheet', 99 );

if ( ! function_exists( 'vgse_universal_sheet' ) ) {

	/**
	 * @return WP_Sheet_Editor_Universal_Sheet
	 */
	function vgse_universal_sheet() {
		return WP_Sheet_Editor_Universal_Sheet::get_instance();
	}

}
