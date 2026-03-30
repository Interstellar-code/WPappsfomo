<?php defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WPSE_Formulas_UI' ) ) {

	class WPSE_Formulas_UI {

		private static $instance = null;
		public $formulas_data    = array();

		private function __construct() {
		}

		function init() {

			add_action( 'vg_sheet_editor/editor/before_init', array( $this, 'register_toolbar_items' ) );
			add_action( 'vg_sheet_editor/after_enqueue_assets', array( $this, 'register_assets' ) );
			add_filter( 'vg_sheet_editor/js_data', array( $this, 'add_bulk_selector_column' ) );
			add_action( 'load-edit.php', array( $this, 'handle_bulk_actions' ) );
			add_action( 'current_screen', array( $this, 'add_bulk_actions_hook' ) );
		}

		function add_bulk_actions_hook( $screen ) {
			if ( $screen->base === 'edit' && ! empty( $screen->post_type ) && VGSE()->helpers->is_post_type_allowed( $screen->post_type ) ) {
				add_filter( "bulk_actions-{$screen->id}", array( $this, 'add_bulk_actions' ), 10, 1 );
			}
		}

		function add_bulk_actions( $bulk_actions ) {
			$bulk_actions['wpse_bulk_edit'] = esc_html__( 'Bulk edit with WP Sheet Editor', 'vg_sheet_editor' );
			return $bulk_actions;
		}

		function handle_bulk_actions() {
			if ( ! isset( $_REQUEST['post_type'] ) ) {
				return;
			}
			// 1. get the action
			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action        = $wp_list_table->current_action();

			$allowed_actions = array( 'wpse_bulk_edit' );
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

			$url  = add_query_arg( 'wpse_custom_filters', urlencode_deep( $filters ), $url );
			$url  = add_query_arg( 'wpse_custom_filters_nonce', wp_create_nonce( 'bep-nonce' ), $url );
			$url .= '#after_init:modal-bulk-edit';

			wp_safe_redirect( $url );
			exit();
		}

		function add_bulk_selector_column( $args ) {
			if ( ! apply_filters( 'vg_sheet_editor/formulas/is_bulk_selector_column_allowed', true, $args ) ) {
				return $args;
			}
			$new_columns = array(
				'wpseBulkSelector' => array(
					'type'              => 'checkbox',
					'columnSorting'     => false,
					'checkedTemplate'   => true,
					'uncheckedTemplate' => '',
					'data'              => 'wpseBulkSelector',
				),
			);

			$args['columnsUnformat'] = array_merge( $new_columns, $args['columnsUnformat'] );
			$args['columnsFormat']   = array_merge( $new_columns, $args['columnsFormat'] );
			++$args['startCols'];
			$args['colWidths']  = array_merge( array( 'wpseBulkSelector' => 40 ), $args['colWidths'] );
			$args['colHeaders'] = array_merge( array( 'wpseBulkSelector' => '<input type="checkbox" class="bulk-selector" data-row="0" data-col="0" />' ), $args['colHeaders'] );
			if ( ! empty( $args['custom_handsontable_args'] ) ) {
				$args['custom_handsontable_args'] = json_decode( $args['custom_handsontable_args'], true );
			}
			if ( is_array( $args['custom_handsontable_args'] ) ) {
				if ( ! empty( $args['custom_handsontable_args']['fixedColumnsLeft'] ) ) {
					++$args['custom_handsontable_args']['fixedColumnsLeft'];
				}
				$args['custom_handsontable_args'] = json_encode( $args['custom_handsontable_args'] );
			}

			return $args;
		}

		/**
		 * Render formulas modal html
		 */
		function render_formulas_form( $current_post_type ) {
			$extension      = VGSE()->helpers->get_extension_by_post_type( $current_post_type );
			$tutorials_url  = 'https://wpsheeteditor.com/blog/?utm_source=' . $current_post_type . '&utm_medium=pro-plugin&utm_campaign=formulas-top-help';
			$tutorials_url .= ( ! empty( $extension['extension_id'] ) ) ? '&vg_tax%5Bplugin%5D=' . (int) $extension['extension_id'] : '';

			if ( VGSE()->helpers->user_can_manage_options() ) {
				// Used by the send_email action
				wp_enqueue_editor();
			}
			include_once dirname( __DIR__ ) . '/views/modal.php';
		}

		/**
		 * Register toolbar item
		 */
		function register_toolbar_items( $editor ) {

			$post_types = $editor->args['enabled_post_types'];
			foreach ( $post_types as $post_type ) {
				$editor->args['toolbars']->register_item(
					'run_formula',
					array(
						'type'                  => 'button',
						'allow_in_frontend'     => true,
						// Removed tooltip because there's no position that works well
						// Top: it displays it below the dropdown when the header is fixed
						// At the sides, it hides the other toolbar items
						//                  'help_tooltip' => esc_html__('Edit thousands of rows at once', 'vg_sheet_editor' ),
						'content'               => esc_html__( 'Bulk Edit', 'vg_sheet_editor' ),
						'icon'                  => 'fa fa-terminal',
						'extra_html_attributes' => 'data-remodal-target="modal-bulk-edit"',
						'footer_callback'       => array( $this, 'render_formulas_form' ),
						'footer_callback_cache' => true,
						'css_class'             => 'wpse-disable-if-unsaved-changes',
					),
					$post_type
				);

				$quick_actions = array(
					'edit'   => array(
						'label'                  => esc_html__( 'Edit', 'vg_sheet_editor' ),
						'columns'                => null,
						'allow_to_select_column' => true,
						'type_of_edit'           => null,
						'values'                 => array(),
						'wp_handler'             => false,
					),
					'delete' => array(
						'label'                     => esc_html__( 'Delete', 'vg_sheet_editor' ),
						'columns'                   => array( 'post_status', 'comment_approved', 'wpse_status' ),
						'allow_to_select_column'    => false,
						'type_of_edit'              => 'set_value',
						'values'                    => array( 'delete' ),
						'wp_handler'                => false,
						'hide_slow_execution_field' => true,
					),
				);

				if ( $editor->provider->is_post_type ) {
					$quick_actions['remove_duplicates_by_title_latest']         = array(
						'label'                     => esc_html__( 'Remove duplicates by title (delete the latest)', 'vg_sheet_editor' ),
						'columns'                   => array( 'post_title' ),
						'allow_to_select_column'    => false,
						'type_of_edit'              => 'remove_duplicates',
						'values'                    => array( 'delete_latest' ),
						'wp_handler'                => false,
						'hide_slow_execution_field' => true,
					);
					$quick_actions['remove_duplicates_by_title_oldest']         = array(
						'label'                     => esc_html__( 'Remove duplicates by title (delete the oldest)', 'vg_sheet_editor' ),
						'columns'                   => array( 'post_title' ),
						'allow_to_select_column'    => false,
						'type_of_edit'              => 'remove_duplicates',
						'values'                    => array( 'delete_oldest' ),
						'wp_handler'                => false,
						'hide_slow_execution_field' => true,
					);
					$quick_actions['remove_duplicates_by_title_content_latest'] = array(
						'label'                     => esc_html__( 'Remove duplicates with same title and content (delete the latest)', 'vg_sheet_editor' ),
						'columns'                   => array( 'post_title' ),
						'allow_to_select_column'    => false,
						'type_of_edit'              => 'remove_duplicates_title_content',
						'values'                    => array( 'delete_latest' ),
						'wp_handler'                => false,
						'hide_slow_execution_field' => true,
					);
					$quick_actions['remove_duplicates_by_title_content_oldest'] = array(
						'label'                     => esc_html__( 'Remove duplicates with same title and content (delete the oldest)', 'vg_sheet_editor' ),
						'columns'                   => array( 'post_title' ),
						'allow_to_select_column'    => false,
						'type_of_edit'              => 'remove_duplicates_title_content',
						'values'                    => array( 'delete_oldest' ),
						'wp_handler'                => false,
						'hide_slow_execution_field' => true,
					);
				}

				$quick_actions         = apply_filters( 'vg_sheet_editor/formulas/quick_actions', $quick_actions, $post_type, $editor );
				$quick_actions['more'] = array(
					'label'                  => esc_html__( 'More options', 'vg_sheet_editor' ),
					'columns'                => null,
					'allow_to_select_column' => true,
					'type_of_edit'           => null,
					'values'                 => array(),
					'wp_handler'             => false,
				);
				$i                     = 0;
				foreach ( $quick_actions as $bulk_action => $action ) {
					++$i;
					$action_link = '<button class="quick-bulk-action button" type="button" data-action="' . htmlentities( json_encode( $action ), ENT_QUOTES, 'UTF-8' ) . '"  data-action="' . esc_attr( $bulk_action ) . '">' . esc_html( $action['label'] ) . '</button>';

					$editor->args['toolbars']->register_item(
						'quick_bulk_edits' . $i,
						array(
							'type'              => 'html',
							'content'           => $action_link,
							'allow_in_frontend' => true,
							'parent'            => 'run_formula',
						),
						$post_type
					);
				}
			}
		}

		/**
		 * Register frontend assets
		 */
		function register_assets() {
			if ( ! VGSE()->helpers->is_editor_page() ) {
				return;
			}
			wp_enqueue_style( 'formulas_css', vgse_formulas_init()->plugin_url . 'assets/css/styles.css', '', filemtime( dirname( __DIR__ ) . '/assets/css/styles.css' ), 'all' );
			wp_enqueue_script( 'formulas_js', vgse_formulas_init()->plugin_url . 'assets/js/init.js', array(), filemtime( dirname( __DIR__ ) . '/assets/js/init.js' ), false );

			$formulas_data = $this->get_formulas_data();
			wp_localize_script( 'formulas_js', 'vgse_formulas_data', $formulas_data );
		}
		/**
		 * Register frontend assets
		 */
		function get_formulas_data() {
			$current_post = VGSE()->helpers->get_provider_from_query_string();
			if ( isset( $this->formulas_data[ $current_post ] ) ) {
				return $this->formulas_data[ $current_post ];
			}

			$remove_duplicates_meta_keys = array_filter( array_map( 'trim', explode( ',', VGSE()->get_option( 'allow_formula_remove_duplicates_meta_keys', '' ) ) ) );

			$formulas_data = array(
				'columns_disallowed_preview' => array( 'status', 'wpse_status', 'post_status' ),
				'texts'                      => array(
					'formula_required'          => esc_html__( 'The bulk edit is missing important information, please fill the form.', 'vg_sheet_editor' ),
					'action_select_label'       => esc_html__( 'Select type of edit', 'vg_sheet_editor' ),
					'action_select_placeholder' => esc_html__( '- -', 'vg_sheet_editor' ),
					'wrong_formula'             => esc_html__( 'You entered an invalid formula. Please double check or contact us.', 'vg_sheet_editor' ),
				),
				'default_actions'            =>
				array(
					'remove_terms'                    => array(
						'label'                  => esc_html__( 'Remove terms from posts', 'vg_sheet_editor' ),
						/* translators: %s: term separator symbol */
						'description'            => sprintf( esc_html__( 'Enter one or multiple term names separated with %s or new lines. Enter the full hierarchy like "Parent > Child" to remove subcategories', 'vg_sheet_editor' ), VGSE()->helpers->get_term_separator() ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateSetValueFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'        => 'select',
								'html_attrs' => array(
									'data-placeholder' => '--',
									'multiple'         => 'multiple',
									'class'            => 'select2',
									'data-remote'      => 'true',
									'data-action'      => 'vgse_get_taxonomy_terms',
									'data-extra_ajax_parameters' => '{"taxonomy_key": "{column_key}"}',
								),
							),
						),
					),
					'add_time'                        =>
					array(
						'label'                  => esc_html__( 'Add time to existing dates', 'vg_sheet_editor' ),
						'description'            => __( 'Add hours, days, weeks, months, or years to the existing dates.<br>If the existing date is empty, we will use the current date as a base.', 'vg_sheet_editor' ),
						'jsCallback'             => 'vgseGenerateReplaceFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'fields_relationship'    => 'AND',
						'input_fields'           =>
						array(
							array(
								'tag'        => 'input',
								'html_attrs' => array(
									'type' => 'number',
								),
								'label'      => esc_html__( 'Number', 'vg_sheet_editor' ),
							),
							array(
								'tag'        => 'select',
								'html_attrs' =>
								array(),
								'options'    => '<option value="minute">' . esc_html__( 'Minutes', 'vg_sheet_editor' ) . '</option><option value="day">' . esc_html__( 'Days', 'vg_sheet_editor' ) . '</option><option value="week">' . esc_html__( 'Weeks', 'vg_sheet_editor' ) . '</option><option value="month">' . esc_html__( 'Months', 'vg_sheet_editor' ) . '</option><option value="year">' . esc_html__( 'Years', 'vg_sheet_editor' ) . '</option>',
								'label'      => esc_html__( 'Time unit', 'vg_sheet_editor' ),
							),
							array(
								'tag'         => 'select',
								'html_attrs'  =>
								array(),
								'options'     => '<option value="">' . esc_html__( 'No', 'vg_sheet_editor' ) . '</option><option value="yes">' . esc_html__( 'Yes', 'vg_sheet_editor' ) . '</option>',
								'label'       => esc_html__( 'Make it sequential?', 'vg_sheet_editor' ),
								'description' => __( 'Example when adding sequential days: Row 1 will increase by 1 day, row 2 will increase by 2 days, etc', 'vg_sheet_editor' ),
							),
						),
					),
					'reduce_time'                     =>
					array(
						'label'                  => esc_html__( 'Deduct time from existing dates', 'vg_sheet_editor' ),
						'description'            => __( 'Deduct a number of hours, days, weeks, months, or years to the existing dates.<br>If the existing date is empty, we will use the current date as a base.', 'vg_sheet_editor' ),
						'jsCallback'             => 'vgseGenerateReplaceFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'fields_relationship'    => 'AND',
						'input_fields'           =>
						array(
							array(
								'tag'        => 'input',
								'html_attrs' => array(
									'type' => 'number',
								),
								'label'      => esc_html__( 'Number', 'vg_sheet_editor' ),
							),
							array(
								'tag'        => 'select',
								'html_attrs' =>
								array(),
								'options'    => '<option value="minute">' . esc_html__( 'Minutes', 'vg_sheet_editor' ) . '</option><option value="day">' . esc_html__( 'Days', 'vg_sheet_editor' ) . '</option><option value="week">' . esc_html__( 'Weeks', 'vg_sheet_editor' ) . '</option><option value="month">' . esc_html__( 'Months', 'vg_sheet_editor' ) . '</option><option value="year">' . esc_html__( 'Years', 'vg_sheet_editor' ) . '</option>',
								'label'      => esc_html__( 'Time unit', 'vg_sheet_editor' ),
							),
							array(
								'tag'         => 'select',
								'html_attrs'  =>
								array(),
								'options'     => '<option value="">' . esc_html__( 'No', 'vg_sheet_editor' ) . '</option><option value="yes">' . esc_html__( 'Yes', 'vg_sheet_editor' ) . '</option>',
								'label'       => esc_html__( 'Make it sequential?', 'vg_sheet_editor' ),
								'description' => __( 'Example when deducting sequential days: Row 1 will decrease by 1 day, row 2 will decrease by 2 days, etc', 'vg_sheet_editor' ),
							),
						),
					),
					'math'                            =>
					array(
						'label'                  => esc_html__( 'Math operation', 'vg_sheet_editor' ),
						'description'            => __( 'Update existing value with the result of a math operation.<br>The result is rounded to the 2 nearest decimals. I.e. 3.845602 becomes 3.85', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateMathFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'         => 'input',
								'html_attrs'  => array(
									'type' => 'text',
								),
								'label'       => esc_html__( 'Math formula', 'vg_sheet_editor' ),
								'description' => __( 'Example 1: $current_value$ + 2 * 5. <br/>Example 2: $_regular_price$ * 0.7 (Set regular price - 30%)', 'vg_sheet_editor' ),
								// tooltip is an allowed parameter here
							),
						),
					),
					'decrease_by_percentage'          =>
					array(
						'label'                  => esc_html__( 'Decrease by percentage', 'vg_sheet_editor' ),
						'description'            => __( 'Decrease the existing value by a percentage.<br>The result is rounded to the 2 nearest decimals. I.e. 3.845602 becomes 3.85', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateDecreasePercentageFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'         => 'input',
								'html_attrs'  => array(
									'type' => 'number',
									'step' => '0.01',
								),
								'label'       => esc_html__( 'Decrease by', 'vg_sheet_editor' ),
								'description' => esc_html__( 'Enter the percentage number.', 'vg_sheet_editor' ),
							),
						),
					),
					'decrease_by_number'              =>
					array(
						'label'                  => esc_html__( 'Decrease by number', 'vg_sheet_editor' ),
						'description'            => __( 'Decrease the existing value by a number.<br>The result is rounded to the 2 nearest decimals. I.e. 3.845602 becomes 3.85', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateDecreaseFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'         => 'input',
								'html_attrs'  => array(
									'type' => 'number',
									'step' => '0.01',
								),
								'label'       => esc_html__( 'Decrease by', 'vg_sheet_editor' ),
								'description' => esc_html__( 'Enter the number.', 'vg_sheet_editor' ),
							),
						),
					),
					'increase_by_percentage'          =>
					array(
						'label'                  => esc_html__( 'Increase by percentage', 'vg_sheet_editor' ),
						'description'            => __( 'Increase the existing value by a percentage.<br>The result is rounded to the 2 nearest decimals. I.e. 3.845602 becomes 3.85', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateIncreasePercentageFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'         => 'input',
								'html_attrs'  => array(
									'type' => 'number',
									'step' => '0.01',
								),
								'label'       => esc_html__( 'Increase by', 'vg_sheet_editor' ),
								'description' => esc_html__( 'Enter the percentage number.', 'vg_sheet_editor' ),
							),
						),
					),
					'increase_by_number'              =>
					array(
						'label'                  => esc_html__( 'Increase by number', 'vg_sheet_editor' ),
						'description'            => __( 'Increase the existing value by a number.<br>The result is rounded to the 2 nearest decimals. I.e. 3.845602 becomes 3.85', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateIncreaseFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'         => 'input',
								'html_attrs'  => array(
									'type' => 'number',
									'step' => '0.01',
								),
								'label'       => esc_html__( 'Increase by', 'vg_sheet_editor' ),
								'description' => esc_html__( 'Enter the number.', 'vg_sheet_editor' ),
							),
						),
					),
					'set_value'                       =>
					array(
						'label'                  => esc_html__( 'Set value', 'vg_sheet_editor' ),
						/* translators: %s: documentation URL */
						'description'            => sprintf( __( 'Replace existing value with this value. <a class="formulas-action-tip-link" href="%s" target="_blank">Read more</a>', 'vg_sheet_editor' ), vgse_formulas_init()->documentation_url ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateSetValueFormula',
						'allowed_column_keys'    => null,
						'disallow_preview'       => true,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag' => 'textarea',
							),
						),
					),
					'set_random_value'                =>
					array(
						'label'                  => esc_html__( 'Set random value', 'vg_sheet_editor' ),
						'description'            => esc_html__( 'Replace existing value with a random value from this list. Enter the values separated with | if you want to select from a predefined list, or enter 2 dates separated with > if you want to select any date from that range', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateSetRandomValueFormula',
						'allowed_column_keys'    => null,
						'disallow_preview'       => true,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'        => 'textarea',
								// We need to set html_attrs so the JS won't add the class and add formatting to this field
								'html_attrs' => array(
									'' => '',
								),
							),
						),
					),
					'remove_everything_after'         =>
					array(
						'label'                  => esc_html__( 'Remove everything after some text', 'vg_sheet_editor' ),
						/* translators: %s: documentation URL */
						'description'            => sprintf( esc_html__( 'I.e. A value like "My great title (234343)" can become "My great title" by removing everything after " ("', 'vg_sheet_editor' ), vgse_formulas_init()->documentation_url ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateExcerptFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'   => 'input',
								'label' => esc_html__( 'Remove everything after this text', 'vg_sheet_editor' ),
							),
							array(
								'label'   => esc_html__( 'Remove the text too?', 'vg_sheet_editor' ),
								'tag'     => 'select',
								'options' => '<option value="yes">' . esc_html__( 'Yes, remove everything after the text, including the text', 'vg_sheet_editor' ) . '</option>' . '<option value="no">' . esc_html__( 'No, remove everything after the text, without including the text', 'vg_sheet_editor' ) . '</option>',
							),
						),
					),
					'remove_everything_before'        =>
					array(
						'label'                  => esc_html__( 'Remove everything before some text', 'vg_sheet_editor' ),
						/* translators: %s: documentation URL */
						'description'            => sprintf( esc_html__( 'I.e. A value like "(234343) My great title" can become "My great title" by removing everything before ") "', 'vg_sheet_editor' ), vgse_formulas_init()->documentation_url ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateExcerptFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'   => 'input',
								'label' => esc_html__( 'Remove everything before this', 'vg_sheet_editor' ),
							),
							array(
								'label'   => esc_html__( 'Remove the text from above too?', 'vg_sheet_editor' ),
								'tag'     => 'select',
								'options' => '<option value="yes">' . esc_html__( 'Yes', 'vg_sheet_editor' ) . '</option>' . '<option value="no">' . esc_html__( 'No', 'vg_sheet_editor' ) . '</option>',
							),
						),
					),
					'replace'                         =>
					array(
						'label'                  => esc_html__( 'Replace', 'vg_sheet_editor' ),
						/* translators: %s: documentation URL */
						'description'            => sprintf( __( 'Replace a word, phrase, or number with a new value. <a class="formulas-action-tip-link" href="%s" target="_blank">Read more</a>', 'vg_sheet_editor' ), vgse_formulas_init()->documentation_url ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateReplaceFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'   => 'textarea',
								'label' => esc_html__( 'Replace this', 'vg_sheet_editor' ),
							),
							array(
								'tag'   => 'textarea',
								'label' => esc_html__( 'With this', 'vg_sheet_editor' ),
							),
						),
					),
					'generate_excerpt'                =>
					array(
						'label'                  => esc_html__( 'Generate excerpt', 'vg_sheet_editor' ),
						/* translators: %s: documentation URL */
						'description'            => sprintf( esc_html__( 'If the column has a very long text, we will remove the html and shorten it to a number of words.', 'vg_sheet_editor' ), vgse_formulas_init()->documentation_url ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateExcerptFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'         => 'input',
								'html_attrs'  => array(
									'type' => 'number',
									'step' => '1',
									'min'  => 1,
								),
								'label'       => esc_html__( 'Maximum number of words', 'vg_sheet_editor' ),
								'description' => esc_html__( 'Enter the number.', 'vg_sheet_editor' ),
							),
						),
					),
					'capitalize_words'                =>
					array(
						'label'                  => esc_html__( 'Capitalize words', 'vg_sheet_editor' ),
						/* translators: %s: documentation URL */
						'description'            => sprintf( esc_html__( 'Capitalize the first letter of every word in the field. I.e. convert "my title" into "My Title".', 'vg_sheet_editor' ), vgse_formulas_init()->documentation_url ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateCapitalizeWordsFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'        => 'input',
								'html_attrs' => array(
									// We hide the input because we dont need user input
									// and the JS requires at least one html input to generate the formula
									'style' => 'display: none;',
								),
							),
						),
					),
					'clear_value'                     =>
					array(
						'label'                  => esc_html__( 'Clear value', 'vg_sheet_editor' ),
						/* translators: %s: documentation URL */
						'description'            => sprintf( __( 'Remove the existing value and leave the field empty. <a class="formulas-action-tip-link" href="%s" target="_blank">Read more</a>', 'vg_sheet_editor' ), vgse_formulas_init()->documentation_url ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateClearValueFormula',
						'allowed_column_keys'    => null,
						'disallow_preview'       => true,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'        => 'input',
								'html_attrs' => array(
									// We hide the input because we dont need user input
									// and the JS requires at least one html input to generate the formula
									'style' => 'display: none;',
								),
							),
						),
					),
					'remove_duplicates'               =>
					array(
						'label'                  => esc_html__( 'Remove duplicates', 'vg_sheet_editor' ),

						/* translators: %s: URL for duplicate removal documentation */
							'description'        => sprintf( __( '<a class="formulas-action-tip-link" href="%s" target="_blank">Read more</a>', 'vg_sheet_editor' ), 'https://wpsheeteditor.com/blog/?s=duplicate' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateClearValueFormula',
						'allowed_column_keys'    => array_merge( $remove_duplicates_meta_keys, array( 'post_title', 'post_author', 'post_content', 'post_date', 'post_excerpt', '_sku' ) ),
						'disallowed_column_keys' => array(),
						'disallow_preview'       => true,
						'input_fields'           =>
						array(
							array(
								'label'   => esc_html__( 'Which duplicates do you want to delete?', 'vg_sheet_editor' ),
								'tag'     => 'select',
								'options' => '<option value="delete_latest">' . esc_html__( 'Delete the newest items and keep the oldest item', 'vg_sheet_editor' ) . '</option>' . '<option value="delete_oldest">' . esc_html__( 'Delete the old items and keep the newest item', 'vg_sheet_editor' ) . '</option>',
							),
						),
					),
					'remove_duplicates_title_content' =>
					array(
						'label'                  => esc_html__( 'Remove duplicates with same title and content', 'vg_sheet_editor' ),
						/* translators: %s: URL for duplicate removal documentation */
						'description'            => sprintf( __( '<a class="formulas-action-tip-link" href="%s" target="_blank">Read more</a>', 'vg_sheet_editor' ), 'https://wpsheeteditor.com/blog/?s=duplicate' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateClearValueFormula',
						'allowed_column_keys'    => array( 'post_title', 'post_content' ),
						'disallowed_column_keys' => array(),
						'disallow_preview'       => true,
						'input_fields'           =>
						array(
							array(
								'label'   => esc_html__( 'Which duplicates do you want to delete?', 'vg_sheet_editor' ),
								'tag'     => 'select',
								'options' => '<option value="delete_latest">' . esc_html__( 'Delete the newest items and keep the oldest item', 'vg_sheet_editor' ) . '</option>' . '<option value="delete_oldest">' . esc_html__( 'Delete the old items and keep the newest item', 'vg_sheet_editor' ) . '</option>',
							),
						),
					),
					'append'                          =>
					array(
						'label'                  => esc_html__( 'Append', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateAppendFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'   => 'textarea',
								'label' => esc_html__( 'Enter the value to append to the existing value.', 'vg_sheet_editor' ),
							),
						),
					),
					'prepend'                         =>
					array(
						'label'                  => esc_html__( 'Prepend', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGeneratePrependFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'        => 'input',
								'html_attrs' => array(
									'type' => 'text',
								),
								'label'      => esc_html__( 'Enter the value to prepend to the existing value.', 'vg_sheet_editor' ),
							),
						),
					),
					'custom'                          =>
					array(
						'label'                  => esc_html__( 'Custom formula', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateCustomFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'input_fields'           =>
						array(
							array(
								'tag'        => 'input',
								'html_attrs' => array(
									'type' => 'text',
								),
								/* translators: %s: documentation URL */
								'label'      => sprintf( __( 'Only for advanced users. <a class="formulas-action-tip-link" href="%s" target="_blank">Read more.</a>', 'vg_sheet_editor' ), vgse_formulas_init()->documentation_url ),
							),
						),
					),
					'send_email'                      =>
					array(
						'label'                  => esc_html__( 'Send email', 'vg_sheet_editor' ),
						'description'            => __( '<p>Send email notifications to users in bulk, and create advanced segments using the advanced search.</p><p>For example, you can filter a spreadsheet of customers/users/forum users/affiliates/blog commenters by country, city, state, orders count, etc and send them important emails.</p><p>We recommend that you use a SMTP plugin to improve the email deliverability and take into account the users consent and privacy laws.</p>', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGenerateSetValueFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'disallow_preview'       => true,
						'input_fields'           =>
						array(
							array(
								'tag'        => 'input',
								'html_attrs' => array(
									'type' => 'text',
								),
								'label'      => esc_html__( 'Email subject', 'vg_sheet_editor' ),
								'tooltip'    => esc_html__( 'You can use variables to insert the value of any column.', 'vg_sheet_editor' ),
							),
							array(
								'tag'        => 'textarea',
								'html_attrs' => array(
									'id'    => 'formula-send-email-editor',
									'class' => 'formula-field-tinymce',
								),
								'label'      => esc_html__( 'Email message', 'vg_sheet_editor' ),
								'tooltip'    => esc_html__( 'We recommend that you create a message that looks like it was written personally, you should add your logo, message, and email signature. You can use variables to insert the value of any column.', 'vg_sheet_editor' ),
							),
							array(
								'tag'        => 'input',
								'html_attrs' => array(
									'type' => 'email',
								),
								'label'      => esc_html__( 'Reply to', 'vg_sheet_editor' ),
								'tooltip'    => esc_html__( 'People will reply to this email address', 'vg_sheet_editor' ),
							),
							array(
								'label'   => esc_html__( 'Only send one email per email address?', 'vg_sheet_editor' ),
								'tooltip' => esc_html__( 'One email per user means that if 3 rows have the same email address, only one row will trigger an email and the others will be skipped. One email per row means that if 3 rows have the same email address, we\'ll send 3 emails to the same address (one per row).', 'vg_sheet_editor' ),
								'tag'     => 'select',
								'options' => '<option value="per_user">' . esc_html__( 'Send one email per user', 'vg_sheet_editor' ) . '</option>' . '<option value="per_row">' . esc_html__( 'Send one email per row', 'vg_sheet_editor' ) . '</option>',
							),
						),
					),
					'php_function'                    =>
					array(
						'label'                  => esc_html__( 'PHP Function', 'vg_sheet_editor' ),
						'fields_relationship'    => 'AND',
						'jsCallback'             => 'vgseGeneratePHPFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'disallow_preview'       => true,
						'input_fields'           =>
						array(
							array(
								'tag'        => 'input',
								// We need to set html_attrs so the JS won't add the class and add formatting to this field
								'html_attrs' => array(
									'' => '',
								),
								/* translators: %s: documentation URL */
								'label'      => sprintf( __( 'For advanced users. Enter the name of your PHP function and we will pass 2 parameters: $current_data (string with current value of the column) and $row_id, the function must accept the 2 parameters and return the modified value as a string. We will automatically save it if the value changed. <a class="formulas-action-tip-link" href="%s#php-functions" target="_blank">Read more.</a>', 'vg_sheet_editor' ), vgse_formulas_init()->documentation_url ),
							),
						),
					),
					'merge_columns'                   =>
					array(
						'label'                  => esc_html__( 'Copy from other columns', 'vg_sheet_editor' ),
						'fields_relationship'    => 'OR',
						'jsCallback'             => 'vgseGenerateMergeFormula',
						'allowed_column_keys'    => null,
						'disallowed_column_keys' => array(),
						'description'            => __( 'Copy the value of other fields into this field.<br/>Example, copy "sale price" into the "regular price" field.', 'vg_sheet_editor' ),
						'input_fields'           =>
						array(
							array(
								'tag'        => 'select',
								'html_attrs' =>
								array(
									'class' => 'select2',
								),
								'options'    => '<option value="">(none)</option>',
								'label'      => esc_html__( 'Copy from this column', 'vg_sheet_editor' ),
							),
						// Removed to simplify the form and move this to a separate "type of edit" in the future
						/* array(
							'tag' => 'textarea',
							'label' => esc_html__('Copy from multiple columns', 'vg_sheet_editor' ),
							'description' => esc_html__("Example: 'Articles written by \$post_author\$ on \$post_date\$' = 'Articles written by Adam on 24-12-2017'.<br/>Another example: '\$category\$-\$_regular_price\$ EUR' would be 'Videos - 25 EUR'", 'vg_sheet_editor' ),
							), */
						),
					),
				),
				'columns_actions'            =>
				array(
					'text'                   => array(
						'set_value'                       => 'default',
						'replace'                         => 'default',
						'clear_value'                     => 'default',
						'remove_duplicates'               => 'default',
						'remove_duplicates_title_content' => 'default',
						'append'                          => 'default',
						'prepend'                         => 'default',
						'capitalize_words'                => 'default',
						'generate_excerpt'                => 'default',
						'remove_everything_after'         => 'default',
						'remove_everything_before'        => 'default',
						'merge_columns'                   => 'default',
						'set_random_value'                => 'default',
						'custom'                          => 'default',
					),
					'email'                  => array(
						'set_value'                => 'default',
						'replace'                  => 'default',
						'clear_value'              => 'default',
						'append'                   => 'default',
						'prepend'                  => 'default',
						'remove_everything_after'  => 'default',
						'remove_everything_before' => 'default',
						'merge_columns'            => 'default',
						'set_random_value'         => 'default',
						'custom'                   => 'default',
					),
					'date'                   => array(
						'set_value'        => 'default',
						'add_time'         => 'default',
						'reduce_time'      => 'default',
						'replace'          => 'default',
						'clear_value'      => 'default',
						'merge_columns'    => 'default',
						'set_random_value' => 'default',
						'custom'           => 'default',
					),
					'checkbox'               => array(
						'set_value'        => 'default',
						'replace'          => 'default',
						'clear_value'      => 'default',
						'merge_columns'    => 'default',
						'set_random_value' => 'default',
						'custom'           => 'default',
					),
					'boton_tiny'             => array(
						'set_value'        => 'default',
						'replace'          => 'default',
						'clear_value'      => 'default',
						'append'           => 'default',
						'prepend'          => 'default',
						'capitalize_words' => 'default',
						'generate_excerpt' => 'default',
						'merge_columns'    => 'default',
						'set_random_value' => 'default',
						'custom'           => 'default',
					),
					'boton_gallery_multiple' =>
					array(
						'set_value'        =>
						array(
							'description'         => esc_html__( 'We will replace the existing media file(s) with these file(s).', 'vg_sheet_editor' ),
							'fields_relationship' => 'OR',
							'disallow_preview'    => true,
							'input_fields'        =>
							array(
								array(
									'tag'        => 'a',
									'html_attrs' =>
									array(
										'data-multiple' => true,
										'class'         => 'wp-media button',
									),
									'label'      => esc_html__( 'Upload the files', 'vg_sheet_editor' ),
								),
								array(
									'tag'         => 'input',
									'html_attrs'  => array(
										'type' => 'url',
									),
									'label'       => esc_html__( 'File URLs', 'vg_sheet_editor' ),
									'description' => esc_html__( 'Enter the URLs separated by commas. They can be from your own site.', 'vg_sheet_editor' ),
								),
							),
						),
						'prepend'          =>
						array(
							'description'         => esc_html__( 'We will prepend the new file(s) to the existing media file(s).', 'vg_sheet_editor' ),
							'fields_relationship' => 'OR',
							'disallow_preview'    => true,
							'input_fields'        =>
							array(
								array(
									'tag'        => 'a',
									'html_attrs' =>
									array(
										'data-multiple' => true,
										'class'         => 'wp-media button',
									),
									'label'      => esc_html__( 'Upload the files', 'vg_sheet_editor' ),
								),
								array(
									'tag'         => 'input',
									'html_attrs'  => array(
										'type' => 'url',
									),
									'label'       => esc_html__( 'File URLs', 'vg_sheet_editor' ),
									'description' => esc_html__( 'Enter the URLs separated by commas. They can be from your own site.', 'vg_sheet_editor' ),
								),
							),
						),
						'append'           =>
						array(
							'description'         => esc_html__( 'We will append the new file(s) to the existing media file(s).', 'vg_sheet_editor' ),
							'fields_relationship' => 'OR',
							'disallow_preview'    => true,
							'input_fields'        =>
							array(
								array(
									'tag'        => 'a',
									'html_attrs' =>
									array(
										'data-multiple' => true,
										'class'         => 'wp-media button',
									),
									'label'      => esc_html__( 'Upload the files', 'vg_sheet_editor' ),
								),
								array(
									'tag'         => 'input',
									'html_attrs'  => array(
										'type' => 'url',
									),
									'label'       => esc_html__( 'File URLs', 'vg_sheet_editor' ),
									'description' => esc_html__( 'Enter the URLs separated by commas. They can be from your own site.', 'vg_sheet_editor' ),
								),
							),
						),
						'replace'          =>
						array(
							'description'         => esc_html__( 'Replace a media file with other file', 'vg_sheet_editor' ),
							'fields_relationship' => 'AND',
							'disallow_preview'    => true,
							'input_fields'        =>
							array(
								array(
									'tag'         => 'input',
									'html_attrs'  => array(
										'type' => 'url',
									),
									'label'       => esc_html__( 'Replace these files', 'vg_sheet_editor' ),
									'description' => esc_html__( 'Enter the URLs separated by commas. They must be from your own site.', 'vg_sheet_editor' ),
								),
								array(
									'tag'         => 'input',
									'html_attrs'  => array(
										'type' => 'url',
									),
									'label'       => esc_html__( 'With these files', 'vg_sheet_editor' ),
									'description' => esc_html__( 'Enter the URLs separated by commas. They must be from your own site.', 'vg_sheet_editor' ),
								),
							),
						),
						'clear_value'      => 'default',
						'set_random_value' => 'default',
						'custom'           => 'default',
					),
					'boton_gallery'          =>
					array(
						'set_value'        =>
						array(
							'description'         => esc_html__( 'We will replace the existing media file with this file.', 'vg_sheet_editor' ),
							'fields_relationship' => 'OR',
							'disallow_preview'    => true,
							'input_fields'        =>
							array(
								array(
									'tag'        => 'a',
									'html_attrs' =>
									array(
										'data-multiple' => false,
										'class'         => 'wp-media button',
									),
									'label'      => esc_html__( 'Upload the file', 'vg_sheet_editor' ),
								),
								array(
									'tag'         => 'input',
									'html_attrs'  => array(
										'type' => 'text', // we don't use type=url to allow saving using filenames too
									),
									'label'       => esc_html__( 'File URL', 'vg_sheet_editor' ),
									'description' => esc_html__( 'Enter the URL. It can be an URL from your own site (Example http://site.com/wp-content/uploads/2016/01/file.jpg) or an external URL.', 'vg_sheet_editor' ),
								),
							),
						),
						'replace'          =>
						array(
							'label'               => esc_html__( 'Replace', 'vg_sheet_editor' ),
							'description'         => esc_html__( 'Replace a media file with other file', 'vg_sheet_editor' ),
							'fields_relationship' => 'AND',
							'disallow_preview'    => true,
							'input_fields'        =>
							array(
								array(
									'tag'         => 'input',
									'html_attrs'  => array(
										'type' => 'url',
									),
									'label'       => esc_html__( 'Replace this file', 'vg_sheet_editor' ),
									'description' => esc_html__( 'Enter the URL. It must be an URL from your own site. Example: http://site.com/wp-content/uploads/2016/01/file.jpg', 'vg_sheet_editor' ),
								),
								array(
									'tag'         => 'input',
									'html_attrs'  => array(
										'type' => 'url',
									),
									'label'       => esc_html__( 'With this file', 'vg_sheet_editor' ),
									'description' => esc_html__( 'Enter the URL. It must be an URL from your own site. Example: http://site.com/wp-content/uploads/2016/01/file.jpg', 'vg_sheet_editor' ),
								),
							),
						),
						'clear_value'      => 'default',
						'set_random_value' => 'default',
						'custom'           => 'default',
					),
					'number'                 =>
					array(
						'set_value'              =>
						array(
							'input_fields' =>
							array(
								array(
									'tag'        => 'input',
									'html_attrs' => array(
										'type' => 'number',
										'step' => '0.01',
									),
								),
							),
						),
						'clear_value'            => 'default',
						'increase_by_number'     => 'default',
						'increase_by_percentage' => 'default',
						'decrease_by_number'     => 'default',
						'decrease_by_percentage' => 'default',
						'math'                   => 'default',
						'merge_columns'          => 'default',
						'set_random_value'       => 'default',
						'custom'                 => 'default',
					),
					'post_terms'             =>
					array(
						'merge_columns'    => 'default',
						'set_value'        =>
						array(
							'description'  => esc_html__( 'We will replace the existing terms with these terms.', 'vg_sheet_editor' ),
							'input_fields' =>
							array(
								array(
									'tag'         => 'input',
									/* translators: %s: term separator symbol */
									'description' => sprintf( esc_html__( 'Enter the new terms separated by %s . You can add hierarchy like parent > child > child', 'vg_sheet_editor' ), VGSE()->helpers->get_term_separator() ),
								),
							),
						),
						'replace'          =>
						array(
							/* translators: %s: documentation URL */
							'description'         => sprintf( __( 'Replace some term(s) with new term(s). <a class="formulas-action-tip-link" href="%s" target="_blank">Read more</a>', 'vg_sheet_editor' ), vgse_formulas_init()->documentation_url ),
							'fields_relationship' => 'AND',
							'input_fields'        =>
							array(
								array(
									'label'       => esc_html__( 'Replace this', 'vg_sheet_editor' ),
									'description' => esc_html__( 'You must enter the full hierarchy like parent > child', 'vg_sheet_editor' ),
									'tag'         => 'select',
									'html_attrs'  => array(
										'data-placeholder' => '--',
										'class'            => 'select2',
										'data-remote'      => 'true',
										'data-action'      => 'vgse_get_taxonomy_terms',
										'data-extra_ajax_parameters' => '{"taxonomy_key": "{column_key}"}',
									),
								),
								array(
									'label'       => esc_html__( 'With this', 'vg_sheet_editor' ),
									'description' => esc_html__( 'You must enter the full hierarchy like parent > child', 'vg_sheet_editor' ),
									'tag'         => 'select',
									'html_attrs'  => array(
										'data-placeholder' => '--',
										'data-tags'        => true,
										'class'            => 'select2',
										'data-remote'      => 'true',
										'data-action'      => 'vgse_get_taxonomy_terms',
										'data-extra_ajax_parameters' => '{"taxonomy_key": "{column_key}"}',
									),
								),
							),
						),
						'append'           =>
						array(
							'input_fields' =>
							array(
								array(
									'label'       => esc_html__( 'Terms', 'vg_sheet_editor' ),
									'description' => esc_html__( 'Enter the full hierarchy like parent > child', 'vg_sheet_editor' ),
									'tag'         => 'select',
									'html_attrs'  => array(
										'data-placeholder' => '--',
										'data-tags'        => true,
										'class'            => 'select2',
										'data-remote'      => 'true',
										'data-action'      => 'vgse_get_taxonomy_terms',
										'data-extra_ajax_parameters' => '{"taxonomy_key": "{column_key}"}',
										'multiple'         => 'multiple',
									),
								),
							),
						),
						'clear_value'      => 'default',
						'capitalize_words' => 'default',
						'set_random_value' => 'default',
						'custom'           => 'default',
					),
				),
			);

			if ( VGSE()->helpers->user_can_manage_options() ) {
				foreach ( $formulas_data['columns_actions'] as $key => $actions ) {
					$formulas_data['columns_actions'][ $key ]['php_function'] = 'default';
				}

				$formulas_data['columns_actions']['email']['send_email'] = 'default';
			}
			if ( post_type_exists( $current_post ) ) {
				$formulas_data['columns_actions']['post_terms'] = array_merge(
					array(
						'remove_terms' => 'default',
					),
					$formulas_data['columns_actions']['post_terms']
				);
			}

			$out = apply_filters( 'vg_sheet_editor/formulas/form_settings', $formulas_data, $current_post );

			// Remove empty html_attrs because the JS doesn't expect it and causes UI bugs
			foreach ( $out['default_actions'] as &$action ) {
				if ( isset( $action['input_fields'] ) ) {
					foreach ( $action['input_fields'] as &$field ) {
						if ( isset( $field['html_attrs'] ) && empty( $field['html_attrs'] ) ) {
							unset( $field['html_attrs'] );
						}
					}
				}
			}

			foreach ( $out['columns_actions'] as &$column_type_actions ) {
				foreach ( $column_type_actions as &$action ) {
					if ( is_array( $action ) && isset( $action['input_fields'] ) ) {
						foreach ( $action['input_fields'] as &$field ) {
							if ( isset( $field['html_attrs'] ) && empty( $field['html_attrs'] ) ) {
								unset( $field['html_attrs'] );
							}
						}
					}
				}
			}

			// Cache for performance
			$this->formulas_data[ $current_post ] = $out;
			return $out;
		}

		/**
		 * Creates or returns an instance of this class.
		 * @return WPSE_Formulas_UI
		 */
		static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new WPSE_Formulas_UI();
				self::$instance->init();
			}
			return self::$instance;
		}

		function __set( $name, $value ) {
			$this->$name = $value;
		}

		function __get( $name ) {
			return $this->$name;
		}

	}

}

if ( ! function_exists( 'WPSE_Formulas_UI_Obj' ) ) {

	function WPSE_Formulas_UI_Obj() {
		return WPSE_Formulas_UI::get_instance();
	}
}
WPSE_Formulas_UI_Obj();
