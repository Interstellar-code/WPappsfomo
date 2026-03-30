<?php defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WP_Sheet_Editor_Advanced_Filters' ) ) {

	/**
	 * Filter rows in the spreadsheet editor.
	 */
	class WP_Sheet_Editor_Advanced_Filters {

		private static $instance       = null;
		public $plugin_url             = null;
		public $plugin_dir             = null;
		public $favorite_search_fields = 'vgse_favorite_search_fields';

		private function __construct() {
		}

		function init() {

			$this->plugin_url = plugins_url( '/', __FILE__ );
			$this->plugin_dir = __DIR__;

			add_action( 'vg_sheet_editor/after_enqueue_assets', array( $this, 'register_assets' ) );
			add_action( 'vg_sheet_editor/filters/after_fields', array( $this, 'add_filters_fields' ), 10, 2 );
			add_action( 'vg_sheet_editor/filters/before_form_closing', array( $this, 'add_advanced_filters_fields' ), 10, 2 );
			// Priority 12 because this needs to run after the filters.php module added its own query parameters, otherwise the post__in parameter doesn't work well
			add_filter( 'vg_sheet_editor/load_rows/wp_query_args', array( $this, 'filter_posts' ), 12, 2 );
			add_filter( 'vg_sheet_editor/filters/allowed_fields', array( $this, 'register_filters' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'exclude_by_keyword' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'add_advanced_post_data_query' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'add_advanced_taxonomy_query' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'add_advanced_duplicate_values_query' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'add_advanced_word_count_query' ), 10, 2 );
			add_action( 'vg_sheet_editor/filters/after_form_closing', array( $this, 'render_save_this_search' ) );
			add_action( 'vg_sheet_editor/editor/before_init', array( $this, 'register_toolbar_items' ) );
			add_action( 'wp_ajax_vgse_save_searches', array( $this, 'save_searches' ) );
			add_action( 'wp_ajax_vgse_mark_search_fields_as_favorite', array( $this, 'mark_search_fields_as_favorite' ) );
			add_filter( 'vg_sheet_editor/js_data', array( $this, 'add_favorite_search_fields_data' ), 10, 2 );
			add_filter( 'vg_sheet_editor/filters/sanitize_request_filters', array( $this, 'register_custom_filters' ), 10, 2 );
			add_filter( 'vg_sheet_editor/options_page/options', array( $this, 'add_settings_page_options' ) );
		}
		function add_advanced_word_count_query( $clauses, $wp_query ) {
			global $wpdb;
			if ( empty( $wp_query->query['wpse_original_filters'] ) || empty( $wp_query->query['wpse_original_filters']['meta_query'] ) || ! is_array( $wp_query->query['wpse_original_filters']['meta_query'] ) ) {
				return $clauses;
			}
			$word_count_queries = wp_list_filter(
				$wp_query->query['wpse_original_filters']['meta_query'],
				array(
					'compare' => 'word_count_greater',
				),
				'OR'
			);
			$word_count_queries = array_merge(
				$word_count_queries,
				wp_list_filter(
					$wp_query->query['wpse_original_filters']['meta_query'],
					array(
						'compare' => 'word_count_less',
					),
					'OR'
				)
			);

			if ( empty( $word_count_queries ) ) {
				return $clauses;
			}

			$post_data_wheres = array();

			foreach ( $word_count_queries as $query ) {
				if ( empty( $query['key'] ) || ! isset( $query['value'] ) || ! is_numeric( $query['value'] ) || (int) $query['value'] < 0 ) {
					continue;
				}
				$field_key = esc_sql( $query['key'] );
				$value     = (int) $query['value'];
				$operator  = ( 'word_count_greater' === $query['compare'] ) ? '>' : '<';
				// This formula is not perfect but it's fast. It considers a word as a set of characters separated by a space.
				// It doesn't handle multiple spaces between words correctly but it's a good approximation for performance.
				$word_count_sql_template = "CASE WHEN TRIM(%s) = '' THEN 0 ELSE (CHAR_LENGTH(TRIM(%s)) - CHAR_LENGTH(REPLACE(TRIM(%s), ' ', ''))) + 1 END";

				if ( 'post_data' === $query['source'] ) {
					$column_identifier  = "$wpdb->posts.$field_key";
					$word_count_sql     = sprintf( $word_count_sql_template, $column_identifier, $column_identifier, $column_identifier );
					$post_data_wheres[] = "({$word_count_sql}) {$operator} {$value}";
				} elseif ( 'meta' === $query['source'] ) {
					$column_identifier = "$wpdb->postmeta.meta_value";
					$word_count_sql    = sprintf( $word_count_sql_template, $column_identifier, $column_identifier, $column_identifier );
					$clauses['where'] .= $wpdb->prepare( " AND $wpdb->posts.ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND ({$word_count_sql}) {$operator} %d)", $query['key'], $value );
				}
			}

			if ( ! empty( $post_data_wheres ) ) {
				$clauses['where'] .= ' AND ' . implode( ' AND ', $post_data_wheres );
			}

			return $clauses;
		}
		/**
		 * Add fields to options page
		 * @param array $sections
		 * @return array
		 */
		public function add_settings_page_options( $sections ) {
			$roles                        = wp_roles();
			$sections['misc']['fields'][] = array(
				'id'         => 'user_roles_allowed_saved_searches',
				'title'      => esc_html__( 'What user roles are allowed to use the saved searches?', 'vg_sheet_editor' ),
				'desc'       => esc_html__( 'By default, only administrator can save and use the saved searches. Use this option to allow other roles to use the saved searches, but only administrators will be able to save new searches.', 'vg_sheet_editor' ),
				'type'       => 'new_select',
				'multi'      => true,
				'options'    => array_combine( array_keys( $roles->roles ), array_keys( $roles->roles ) ),
				'class_name' => 'select2',
			);
			return $sections;
		}

		function add_favorite_search_fields_data( $data, $post_type ) {
			$saved_items = get_option( $this->favorite_search_fields );

			$data['favorite_search_fields'] = ( is_array( $saved_items ) && isset( $saved_items[ $post_type ] ) ) ? $saved_items[ $post_type ] : array();

			// Auto enable the advanced filters as favorite search field when they haven't configured any, if they configure something, don't auto enable it
			if ( $saved_items === false && ! in_array( 'meta_query[0][source]', $data['favorite_search_fields'], true ) ) {
				$data['favorite_search_fields'][] = 'meta_query[0][source]';
			}

			$data['saved_searches'] = $this->get_all_saved_searches_for_ui( $post_type );
			return $data;
		}

		function mark_search_fields_as_favorite() {
			if ( empty( $_POST['post_type'] ) || ! VGSE()->helpers->verify_nonce_from_request() || ! VGSE()->helpers->user_can_manage_options() ) {
				wp_send_json_error( array( 'message' => esc_html__( 'You dont have enough permissions to view this page.', 'vg_sheet_editor' ) ) );
			}

			$post_type = VGSE()->helpers->sanitize_table_key( $_POST['post_type'] );
			$fields    = empty( $_POST['fields'] ) ? array() : array_map( 'sanitize_text_field', $_POST['fields'] );

			$saved_items = get_option( $this->favorite_search_fields );
			if ( empty( $saved_items ) ) {
				$saved_items = array();
			}

			if ( ! isset( $saved_items[ $post_type ] ) ) {
				$saved_items[ $post_type ] = array();
			}

			$saved_items[ $post_type ] = $fields;
			update_option( $this->favorite_search_fields, $saved_items, false );
			wp_send_json_success();
		}

		function get_all_saved_searches_for_ui( $post_type ) {
			if ( ! $this->is_user_allowed_to_use_saved_searches() ) {
				return array();
			}

			// Get global searches
			$global_searches_all = get_option( 'vgse_saved_searches', array() );
			$global_searches     = ( is_array( $global_searches_all ) && isset( $global_searches_all[ $post_type ] ) ) ? $global_searches_all[ $post_type ] : array();

			// Get user-specific searches
			$user_searches_all = get_user_meta( get_current_user_id(), 'vgse_saved_searches', true );
			$user_searches     = ( is_array( $user_searches_all ) && isset( $user_searches_all[ $post_type ] ) ) ? $user_searches_all[ $post_type ] : array();

			$private_keys   = array( 'name', 'post_type' );
			$final_searches = array();

			$process_searches = function ( $searches, $scope ) use ( &$final_searches, $private_keys ) {
				foreach ( $searches as $saved_search ) {
					if ( empty( $saved_search['search_name'] ) ) {
						continue;
					}

					$filters = array_diff_key( $saved_search, array_flip( array_merge( $private_keys, array( 'search_name' ) ) ) );

					$final_searches[ $saved_search['search_name'] ] = array(
						'search_name' => $saved_search['search_name'],
						'scope'       => $scope,
						'filters'     => $filters,
					);
				}
			};

			$process_searches( $global_searches, 'global' );
			$process_searches( $user_searches, 'user' );

			$final_searches = array_values( $final_searches );
			usort(
				$final_searches,
				function ( $a, $b ) {
					return strcmp( $a['search_name'], $b['search_name'] );
				}
			);
			return $final_searches;
		}

		function is_user_allowed_to_use_saved_searches() {
			$allowed_roles = VGSE()->get_option( 'user_roles_allowed_saved_searches', array( 'administrator' ) );
			// Ensure the admin is always allowed, regardless of the setting
			if ( ! in_array( 'administrator', $allowed_roles, true ) ) {
				$allowed_roles[] = 'administrator';
			}
			if ( empty( $allowed_roles ) || ! VGSE()->helpers->user_has_any_role( $allowed_roles ) ) {
				return false;
			}
			return true;
		}
		function register_toolbar_items( $editor ) {
			if ( ! $this->is_user_allowed_to_use_saved_searches() ) {
				return;
			}
			$post_types = $editor->args['enabled_post_types'];
			foreach ( $post_types as $post_type ) {
				$saved_searches = $this->get_all_saved_searches_for_ui( $post_type );
				if ( empty( $saved_searches ) ) {
					continue;
				}
				$editor->args['toolbars']->register_item(
					'saved_searches',
					array(
						'type'                       => 'html', // html | switch | button
						'content'                    => array( $this, 'get_saved_searches_toolbar_html' ),
						'allow_in_frontend'          => true,
						'parent'                     => 'run_filters',
						'container_extra_attributes' => 'x-data="vgseSavedSearches"',
					),
					$post_type
				);
			}
		}

		function get_saved_searches_toolbar_html( $toolbar_item, $post_type ) {
			ob_start();
			?>

	<template x-for="(search, index) in saved_searches" :key="search.search_name + index">

		<div class="button-container" :class="'saved_search' + index + '-container'">
			<button
				type="button"
				:name="'saved_search' + index"
				class="button"
				data-saved-item
				data-saved-type="search"
				:data-item-name="search.search_name"
				@click="loadSearch(search)"
			>
				<i class="fa fa-globe" x-show="search.scope === 'global'" title="<?php esc_attr_e( 'For all users', 'vg_sheet_editor' ); ?>"></i>
				<i class="fa fa-user" x-show="search.scope === 'user'" title="<?php esc_attr_e( 'Only for me', 'vg_sheet_editor' ); ?>"></i>
				<span x-text="search.search_name"></span>
			</button>

			<button
				type="button"
				class="wpse-delete-saved-item wpse-delete-saved-search"
				@click="deleteSearch(search, index)"
			>x</button>
		</div>

	</template>
			<?php
			return ob_get_clean();
		}


		function render_save_this_search() {
			if ( ! is_admin() ) {
				return;
			}
			?>
			<div class="save-search-wrapper">
				<label class="save-search"><?php esc_html_e( 'Save this search', 'vg_sheet_editor' ); ?></label>
				<input name="search_name" x-model="search_name" placeholder="<?php esc_attr_e( 'Enter a name...', 'vg_sheet_editor' ); ?>" class="save-search-input" type="text">
				<select name="save_search_scope" x-model="save_search_scope">
					<option value="global" x-show="vgse_editor_settings.is_administrator"><?php esc_html_e( 'For all users', 'vg_sheet_editor' ); ?></option>
					<option value="user"><?php esc_html_e( 'Only for me', 'vg_sheet_editor' ); ?></option>
				</select>
			</div>
			<?php
		}

		function add_advanced_taxonomy_query( $clauses, $wp_query ) {
			global $wpdb;
			if ( empty( $wp_query->query['wpse_original_filters'] ) || empty( $wp_query->query['wpse_original_filters']['meta_query'] ) || ! is_array( $wp_query->query['wpse_original_filters']['meta_query'] ) ) {
				return $clauses;
			}
			$post_data_query = wp_list_filter(
				$wp_query->query['wpse_original_filters']['meta_query'],
				array(
					'source' => 'taxonomy_keys',
				)
			);
			if ( empty( $post_data_query ) ) {
				return $clauses;
			}

			$wheres = array(
				'IN'     => array(),
				'NOT IN' => array(),
			);
			foreach ( $post_data_query as $post_data_parameters ) {
				if ( empty( $post_data_parameters['key'] ) || empty( $post_data_parameters['compare'] ) ) {
					continue;
				}
				if ( in_array( $post_data_parameters['compare'], array( 'LIKE', 'NOT LIKE' ) ) ) {
					$post_data_parameters['value'] = '%' . $post_data_parameters['value'] . '%';
				}

				if ( $post_data_parameters['compare'] === 'length_less' ) {
					if ( (int) $post_data_parameters['value'] < 1 ) {
						$post_data_parameters['value'] = 1;
					}
					$post_data_parameters['compare'] = 'REGEXP';
					$post_data_parameters['value']   = '^.{0,' . (int) $post_data_parameters['value'] . '}$';
				}
				if ( $post_data_parameters['compare'] === 'length_higher' ) {
					if ( (int) $post_data_parameters['value'] < 1 ) {
						$post_data_parameters['value'] = 1;
					}
					$post_data_parameters['compare'] = 'REGEXP';
					$post_data_parameters['value']   = '^.{' . (int) $post_data_parameters['value'] . ',}$';
				}

				if ( $post_data_parameters['compare'] === 'OR' ) {
					$post_data_parameters['compare'] = 'REGEXP';
					$keywords                        = array_filter( array_map( 'preg_quote', array_map( 'trim', explode( ';', $post_data_parameters['value'] ) ) ) );
					$post_data_parameters['value']   = '^(' . implode( '|', $keywords ) . ')$';
				}
				if ( $post_data_parameters['compare'] === 'starts_with' ) {
					$post_data_parameters['compare'] = 'LIKE';
					$post_data_parameters['value']   = $post_data_parameters['value'] . '%';
				}
				if ( $post_data_parameters['compare'] === 'not_starts_with' ) {
					$post_data_parameters['compare'] = 'NOT REGEXP';
					$post_data_parameters['value']   = '^(' . preg_quote( $post_data_parameters['value'] ) . ')';
				}
				if ( $post_data_parameters['compare'] === 'ends_with' ) {
					$post_data_parameters['compare'] = 'LIKE';
					$post_data_parameters['value']   = '%' . $post_data_parameters['value'];
				}

				$group = 'IN';
				if ( in_array( $post_data_parameters['compare'], array( 'NOT LIKE' ) ) ) {
					$post_data_parameters['compare'] = 'LIKE';
					$group                           = 'NOT IN';
				} elseif ( empty( $post_data_parameters['value'] ) && $post_data_parameters['compare'] === '=' ) {
					$group = 'NOT IN';
				}
				if ( empty( $post_data_parameters['value'] ) ) {
					$sql_where = "tt.taxonomy IN ('" . esc_sql( $post_data_parameters['key'] ) . "')";
				} else {
					$sql_where = "tt.taxonomy IN ('" . esc_sql( $post_data_parameters['key'] ) . "') AND t.name " . esc_sql( $post_data_parameters['compare'] ) . " '" . esc_sql( $post_data_parameters['value'] ) . "' ";
				}
				$sql                = "SELECT tr.object_id
FROM $wpdb->terms AS t 
INNER JOIN $wpdb->term_taxonomy AS tt 
ON t.term_id = tt.term_id
INNER JOIN $wpdb->term_relationships AS tr
ON tr.term_taxonomy_id = tt.term_taxonomy_id
INNER JOIN $wpdb->posts AS p 
ON (p.ID = tr.object_id)

WHERE 1 = 1 AND p.post_type = '" . $wp_query->query['post_type'] . "' 

AND " . $sql_where . ' 
  
GROUP BY tr.object_id';
				$wheres[ $group ][] = $sql;
			}

			foreach ( $wheres as $operator => $queries ) {
				foreach ( $queries as $query ) {
					$clauses['where'] .= " AND $wpdb->posts.ID $operator (" . $query . ')  ';
				}
			}
			return $clauses;
		}

		function add_advanced_duplicate_values_query( $clauses, $wp_query ) {
			global $wpdb;
			if ( empty( $wp_query->query['wpse_original_filters'] ) || empty( $wp_query->query['wpse_original_filters']['meta_query'] ) || ! is_array( $wp_query->query['wpse_original_filters']['meta_query'] ) ) {
				return $clauses;
			}
			$duplicate_values_query = wp_list_filter(
				$wp_query->query['wpse_original_filters']['meta_query'],
				array(
					'compare' => 'contains_duplicate_values',
				)
			);
			if ( empty( $duplicate_values_query ) ) {
				return $clauses;
			}

			foreach ( $duplicate_values_query as $duplicate_query_args ) {
				if ( empty( $duplicate_query_args['key'] ) ) {
					continue;
				}
				$field_key     = $duplicate_query_args['key'];
				$field_key_sql = esc_sql( $field_key );
				if ( $duplicate_query_args['source'] === 'post_data' ) {
					$clauses['join'] .= $wpdb->prepare( " INNER JOIN ( SELECT {$field_key_sql} FROM {$wpdb->posts} WHERE post_type = %s GROUP BY {$field_key_sql} HAVING COUNT(*) > 1 ) AS duplicates ON {$wpdb->posts}.{$field_key_sql} = duplicates.{$field_key_sql} ", $wp_query->query['post_type'] );
				} elseif ( $duplicate_query_args['source'] === 'meta' ) {
					$clauses['where'] .= $wpdb->prepare( " AND $wpdb->posts.ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value IN (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s GROUP BY meta_value HAVING COUNT(*) > 1)) ", $field_key, $field_key );
				}
				// Only allow one filter for duplicates because it's a very expensive query
				break;
			}

			return $clauses;
		}

		function _parse_meta_query_args( $meta_query_args, $allowed_source = 'meta', &$query_args = array() ) {
			global $wpdb;
			// Cache variable that will hold the unfiltered columns that we get and use below
			$columns   = null;
			$post_type = VGSE()->helpers->get_provider_from_query_string();

			foreach ( $meta_query_args as $index => $meta_query ) {
				if ( $allowed_source && $meta_query['source'] !== $allowed_source ) {
					unset( $meta_query_args[ $index ] );
					continue;
				}
				if ( in_array( $meta_query['compare'], array( 'contains_duplicate_values', 'word_count_greater', 'word_count_less' ), true ) ) {
					unset( $meta_query_args[ $index ] );
					continue;
				}
				if ( in_array( $meta_query['compare'], array( 'last_hours', 'last_days', 'last_weeks', 'last_months', 'older_than_hours', 'older_than_days', 'older_than_weeks', 'older_than_months' ), true ) ) {
					if ( is_null( $columns ) ) {
						$columns = VGSE()->helpers->get_unfiltered_provider_columns( $post_type );
					}
					$is_date_filter = isset( $columns[ $meta_query['key'] ] ) && $columns[ $meta_query['key'] ]['value_type'] === 'date';
					if ( $is_date_filter ) {
						$date_format_for_db = isset( $columns[ $meta_query['key'] ]['formatted']['customDatabaseFormat'] ) ? $columns[ $meta_query['key'] ]['formatted']['customDatabaseFormat'] : $columns[ $meta_query['key'] ]['formatted']['dateFormatPhp'];
					}
					if ( empty( $date_format_for_db ) ) {
						$date_format_for_db = 'Y-m-d H:i:s';
					}
				}

				if ( in_array( $meta_query['compare'], array( 'last_hours', 'last_days', 'last_weeks', 'last_months' ), true ) ) {
					$time_unit                 = str_replace( 'last_', '', $meta_query['compare'] );
					$meta_query['compare']     = '>=';
					$meta_query['value']       = gmdate( $date_format_for_db, strtotime( '-' . (int) $meta_query['value'] . ' ' . $time_unit ) );
					$meta_query_args[ $index ] = $meta_query;
				}
				if ( in_array( $meta_query['compare'], array( 'older_than_hours', 'older_than_days', 'older_than_weeks', 'older_than_months' ), true ) ) {
					$time_unit                 = str_replace( 'older_than_', '', $meta_query['compare'] );
					$meta_query['compare']     = '<';
					$meta_query['value']       = gmdate( $date_format_for_db, strtotime( '-' . (int) $meta_query['value'] . ' ' . $time_unit ) );
					$meta_query_args[ $index ] = $meta_query;
				}

				// If we are searching parent products by a list of product SKUs, convert variation SKUs into parent SKUs
				if ( class_exists( 'WooCommerce' ) && $post_type === 'product' && $meta_query['key'] === '_sku' && $meta_query['compare'] === 'OR' && ! empty( $meta_query['value'] ) ) {
					$filters = WP_Sheet_Editor_Filters::get_instance()->get_raw_filters( array() );

					if ( empty( $filters['search_variations'] ) ) {
						$skus = array_filter( array_map( 'trim', explode( ';', $meta_query['value'] ) ) );
						// Prepare placeholders for the IN clause
						$placeholders = implode( ', ', array_fill( 0, count( $skus ), '%s' ) );

						// Create the SQL query with placeholders
						$query = $wpdb->prepare(
							"SELECT ml.sku 'variation_sku', mlp.sku 'parent_sku'
FROM {$wpdb->prefix}wc_product_meta_lookup ml 
LEFT JOIN {$wpdb->prefix}posts p 
ON p.ID = ml.product_id 
LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup mlp 
ON mlp.product_id = p.post_parent
WHERE ml.sku IN ($placeholders) AND p.post_type = 'product_variation'",
							$skus
						);

						// Execute the query
						$skus_result = $wpdb->get_results( $query );
						if ( ! empty( $skus_result ) ) {
							$variation_skus      = wp_list_pluck( $skus_result, 'parent_sku', 'variation_sku' );
							$skus                = array_diff( $skus, array_keys( $variation_skus ) );
							$skus                = array_merge( $skus, array_values( $variation_skus ) );
							$meta_query['value'] = implode( ';', array_unique( $skus ) );
						}
					}
				}

				// When searching for non-empty featured images, it's more accurate the query field > 0
				if ( $meta_query['key'] === '_thumbnail_id' && $meta_query['compare'] === '!=' && $meta_query['value'] === '' ) {
					$meta_query['compare'] = '>';
					$meta_query['value']   = '0';
				}

				if ( $meta_query['compare'] === 'length_less' ) {
					if ( (int) $meta_query['value'] < 1 ) {
						$meta_query['value'] = 1;
					}
					$meta_query_args[ $index ]['compare'] = 'REGEXP';
					$meta_query_args[ $index ]['value']   = '^.{0,' . (int) $meta_query['value'] . '}$';
				}
				if ( $meta_query['compare'] === 'length_higher' ) {
					if ( (int) $meta_query['value'] < 1 ) {
						$meta_query['value'] = 1;
					}
					$meta_query_args[ $index ]['compare'] = 'REGEXP';
					$meta_query_args[ $index ]['value']   = '^.{' . (int) $meta_query['value'] . ',}$';
				}
				if ( $meta_query['compare'] === 'OR' ) {
					$meta_query_args[ $index ]['compare'] = 'REGEXP';
					$keywords                             = array_filter( array_map( 'trim', explode( ';', $meta_query['value'] ) ) );
					if ( $meta_query['key'] === $wpdb->prefix . 'capabilities' ) {
						$meta_query_args[ $index ]['value'] = '"(' . implode( '|', $keywords ) . ')"';
					} else {
						$meta_query_args[ $index ]['value'] = '^(' . implode( '|', $keywords ) . ')$';
					}
				}
				if ( $meta_query['compare'] === 'starts_with' ) {
					$meta_query_args[ $index ]['compare'] = 'REGEXP';
					$meta_query_args[ $index ]['value']   = '^' . $meta_query['value'];
				}
				if ( $meta_query['compare'] === 'not_starts_with' ) {
					$meta_query_args[ $index ]['compare'] = 'NOT REGEXP';
					$meta_query_args[ $index ]['value']   = '^(' . preg_quote( $meta_query['value'] ) . ')';
				}
				if ( $meta_query['compare'] === 'ends_with' ) {
					$meta_query_args[ $index ]['compare'] = 'REGEXP';
					$meta_query_args[ $index ]['value']  .= '$';
				}

				if ( class_exists( 'WooCommerce' ) && in_array( $meta_query['key'], array( '_sale_price_dates_from', '_sale_price_dates_to' ), true ) && ! empty( $meta_query['value'] ) ) {
					if ( $meta_query['key'] === '_sale_price_dates_to' ) {
						$meta_query['value'] .= ' 23:59:59';
					}
					$meta_query_args[ $index ]['value'] = wp_date( 'U', get_gmt_from_date( $meta_query['value'], 'U' ) );
				}

				if ( in_array( $meta_query['compare'], array( '>', '>=', '<', '<=' ) ) && is_numeric( $meta_query['value'] ) ) {
					$meta_query_args[ $index ]['type'] = 'NUMERIC';
				}
				if ( empty( $meta_query['value'] ) && in_array( $meta_query['compare'], array( '=', 'LIKE' ) ) && $allowed_source === 'meta' ) {
					$not_exists                = $meta_query;
					$not_exists['compare']     = 'NOT EXISTS';
					$meta_query_args[ $index ] = array(
						'relation' => 'OR',
						$meta_query,
						$not_exists,
					);
				}

				if ( ! empty( $meta_query_args[ $index ]['value'] ) ) {
					$meta_query_args[ $index ]['value'] = $this->_maybe_convert_username_to_user_id( 'meta', $meta_query_args[ $index ] );
					$meta_query_args[ $index ]['value'] = $this->_maybe_convert_post_to_ids( $meta_query_args[ $index ] );
				}
			}
			return $meta_query_args;
		}

		/**
		 * This function is necessary to convert the friendly post titles received from the search form into the post IDs saved in the database to be used for the searches.
		 * Ideally, this function should work automatically on any column with "post dropdown" format, however we have many columns with post dropdowns that don't use the columns manager and we need to update all those columns to use the columns manager for this to work on all the post dropdown columns.
		 * We're hardcoding support for 2 meta keys for now as a workaround.
		 *
		 * @param  array $meta_query
		 * @return string
		 */
		function _maybe_convert_post_to_ids( $meta_query ) {
			global $wpdb;
			$cell_key = $meta_query['key'];
			if ( $cell_key === '_EventVenueID' ) {
				$meta_query['value'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'tribe_venue'", html_entity_decode( $meta_query['value'] ) ) );
			}
			if ( $cell_key === '_EventOrganizerID' ) {
				$meta_query['value'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'tribe_organizer'", html_entity_decode( $meta_query['value'] ) ) );
			}

			return $meta_query['value'];
		}

		function _maybe_convert_username_to_user_id( $allowed_source, $meta_query ) {
			$post_type           = VGSE()->helpers->get_provider_from_query_string();
			$spreadsheet_columns = VGSE()->helpers->get_unfiltered_provider_columns( $post_type );
			$cell_key            = $meta_query['key'];

			$column_uses_user_search = in_array( $allowed_source, array( 'meta', 'post_data' ), true ) && ! empty( $spreadsheet_columns ) && isset( $spreadsheet_columns[ $cell_key ] ) && ! empty( $spreadsheet_columns[ $cell_key ]['formatted']['source'] ) && $spreadsheet_columns[ $cell_key ]['formatted']['source'] === 'searchUsers';

			if ( ! empty( $meta_query['value'] ) && $column_uses_user_search ) {
				$column_settings = $spreadsheet_columns[ $cell_key ];
				$username        = $meta_query['value'];
				// If it uses the regular saving (not prepare nor save callback)
				if ( empty( $column_settings['prepare_value_for_database'] ) && empty( $column_settings['save_value_callback'] ) ) {
					$meta_query['value'] = VGSE()->data_helpers->set_post( 'post_author', $username );
				} elseif ( ! empty( $column_settings['prepare_value_for_database'] ) && empty( $column_settings['save_value_callback'] ) ) {
					$meta_query['value'] = call_user_func( $column_settings['prepare_value_for_database'], null, $cell_key, $username, null, $column_settings, $spreadsheet_columns );
				}
			}
			return $meta_query['value'];
		}

		function _build_sql_wheres_for_data_table( $post_data_query, $table_name ) {
			// Cache variable that will hold the unfiltered columns that we get and use below
			$columns   = null;
			$post_type = VGSE()->helpers->get_provider_from_query_string();

			$wheres = array();
			foreach ( $post_data_query as $post_data_parameters ) {
				if ( empty( $post_data_parameters['key'] ) || empty( $post_data_parameters['compare'] ) || in_array( $post_data_parameters['compare'], array( 'contains_duplicate_values', 'word_count_greater', 'word_count_less' ), true ) ) {
					continue;
				}
				if ( ! is_string( $post_data_parameters['key'] ) || ! is_string( $post_data_parameters['compare'] ) ) {
					continue;
				}
				if ( in_array( $post_data_parameters['compare'], array( 'last_hours', 'last_days', 'last_weeks', 'last_months', 'older_than_hours', 'older_than_days', 'older_than_weeks', 'older_than_months' ), true ) ) {
					if ( is_null( $columns ) ) {
						$columns = VGSE()->helpers->get_unfiltered_provider_columns( $post_type );
					}
					$is_date_filter = isset( $columns[ $post_data_parameters['key'] ] ) && $columns[ $post_data_parameters['key'] ]['value_type'] === 'date';
					if ( $is_date_filter ) {
						$date_format_for_db = isset( $columns[ $post_data_parameters['key'] ]['formatted']['customDatabaseFormat'] ) ? $columns[ $post_data_parameters['key'] ]['formatted']['customDatabaseFormat'] : $columns[ $post_data_parameters['key'] ]['formatted']['dateFormatPhp'];
					}
					if ( empty( $date_format_for_db ) ) {
						$date_format_for_db = 'Y-m-d H:i:s';
					}
				}

				if ( in_array( $post_data_parameters['compare'], array( 'last_hours', 'last_days', 'last_weeks', 'last_months' ), true ) ) {
					$time_unit                       = str_replace( 'last_', '', $post_data_parameters['compare'] );
					$post_data_parameters['compare'] = '>=';
					$post_data_parameters['value']   = gmdate( $date_format_for_db, strtotime( '-' . (int) $post_data_parameters['value'] . ' ' . $time_unit ) );
				}
				if ( in_array( $post_data_parameters['compare'], array( 'older_than_hours', 'older_than_days', 'older_than_weeks', 'older_than_months' ), true ) ) {
					$time_unit                       = str_replace( 'older_than_', '', $post_data_parameters['compare'] );
					$post_data_parameters['compare'] = '<';
					$post_data_parameters['value']   = gmdate( $date_format_for_db, strtotime( '-' . (int) $post_data_parameters['value'] . ' ' . $time_unit ) );
				}
				if ( in_array( $post_data_parameters['compare'], array( 'LIKE', 'NOT LIKE' ) ) ) {
					$post_data_parameters['value'] = '%' . $post_data_parameters['value'] . '%';
				}

				if ( $post_data_parameters['compare'] === 'length_less' ) {
					if ( (int) $post_data_parameters['value'] < 1 ) {
						$post_data_parameters['value'] = 1;
					}
					$post_data_parameters['compare']    = '<';
					$post_data_parameters['use_length'] = true;
				}
				if ( $post_data_parameters['compare'] === 'length_higher' ) {
					if ( (int) $post_data_parameters['value'] < 1 ) {
						$post_data_parameters['value'] = 1;
					}
					$post_data_parameters['compare']    = '>';
					$post_data_parameters['use_length'] = true;
				}
				if ( $post_data_parameters['compare'] === 'OR' ) {
					$post_data_parameters['compare'] = 'REGEXP';
					$keywords                        = array_filter( array_map( 'trim', explode( ';', $post_data_parameters['value'] ) ) );
					$post_data_parameters['value']   = '^(' . implode( '|', $keywords ) . ')$';
				}
				if ( $post_data_parameters['compare'] === 'starts_with' ) {
					$post_data_parameters['compare'] = 'LIKE';
					$post_data_parameters['value']   = $post_data_parameters['value'] . '%';
				}
				if ( $post_data_parameters['compare'] === 'not_starts_with' ) {
					$post_data_parameters['compare'] = 'NOT REGEXP';
					$post_data_parameters['value']   = '^(' . preg_quote( $post_data_parameters['value'] ) . ')';
				}
				if ( $post_data_parameters['compare'] === 'ends_with' ) {
					$post_data_parameters['compare'] = 'LIKE';
					$post_data_parameters['value']   = '%' . $post_data_parameters['value'];
				}
				// If the value is a date like Y-m-d 00:00:00 and uses the CONTAINS (LIKE) operator,
				// remove the time part because they always want to get CONTAINS=date without time
				if ( $post_data_parameters['compare'] === 'LIKE' && preg_match( '/^\d{4}-\d{2}-\d{2} 00:00:00$/', $post_data_parameters['value'] ) ) {
					$post_data_parameters['value'] = str_replace( ' 00:00:00', '', $post_data_parameters['value'] );
				}
				$post_data_parameters['value'] = $this->_maybe_convert_username_to_user_id( 'post_data', $post_data_parameters );

				if ( in_array( $post_data_parameters['compare'], array( '<', '>' ), true ) && ! empty( $post_data_parameters['use_length'] ) ) {
					$wheres[] = " LENGTH($table_name." . esc_sql( $post_data_parameters['key'] ) . ') ' . esc_sql( $post_data_parameters['compare'] ) . '  ' . (int) $post_data_parameters['value'];
				} else {
					$where = '';
					if ( in_array( $post_data_parameters['compare'], array( '=', '!=' ), true ) && $post_data_parameters['value'] === '' ) {
						$where = '( ';
					}
					$where .= " $table_name." . esc_sql( $post_data_parameters['key'] ) . ' ' . esc_sql( $post_data_parameters['compare'] ) . " '" . esc_sql( $post_data_parameters['value'] ) . "' ";

					if ( $post_data_parameters['compare'] === '=' && $post_data_parameters['value'] === '' ) {
						$where .= " OR $table_name." . esc_sql( $post_data_parameters['key'] ) . ' IS NULL )';
					}
					if ( $post_data_parameters['compare'] === '!=' && $post_data_parameters['value'] === '' ) {
						$where .= " AND $table_name." . esc_sql( $post_data_parameters['key'] ) . ' IS NOT NULL )';
					}
					$wheres[] = $where;
				}
			}
			return $wheres;
		}

		function add_advanced_post_data_query( $clauses, $wp_query ) {
			global $wpdb;
			if ( empty( $wp_query->query['wpse_original_filters'] ) || empty( $wp_query->query['wpse_original_filters']['meta_query'] ) || ! is_array( $wp_query->query['wpse_original_filters']['meta_query'] ) ) {
				return $clauses;
			}
			$post_data_query = wp_list_filter(
				$wp_query->query['wpse_original_filters']['meta_query'],
				array(
					'source' => 'post_data',
				)
			);
			if ( empty( $post_data_query ) ) {
				return $clauses;
			}

			// Remove the post_status clause added by wp_query automatically because we
			// have an advanced filter for the same field
			if ( ! empty( $wp_query->query['wpse_original_post_statuses'] ) ) {
				$post_type_object = get_post_type_object( $wp_query->query['post_type'] );

				$statuses_to_remove = $wp_query->query['wpse_original_post_statuses'];
				if ( $post_type_object && WP_Sheet_Editor_Helpers::current_user_can( $post_type_object->cap->edit_published_posts ) ) {
					$allowed_statuses = array_merge( array( 'trash' ), $statuses_to_remove );
				} else {
					$allowed_statuses = $statuses_to_remove;
				}
				$has_allowed_post_status_filter = false;
				foreach ( $post_data_query as $index => $post_data_query_single ) {
					if ( $post_data_query_single['key'] !== 'post_status' ) {
						continue;
					}
					if ( in_array( $post_data_query_single['value'], $allowed_statuses, true ) ) {
						$has_allowed_post_status_filter = true;
					} elseif ( $wp_query->query['post_type'] !== 'shop_order' ) {
						unset( $post_data_query[ $index ] );
					}
				}
				if ( $has_allowed_post_status_filter ) {
					$clauses['where'] = preg_replace( '/AND \(\(' . $wpdb->posts . ".post_status[A-Za-z0-9 ='\._\-]+\)\)/", '', $clauses['where'] );
				}
			}

			$wheres = $this->_build_sql_wheres_for_data_table( $post_data_query, $wpdb->posts );
			if ( ! empty( $wheres ) ) {
				$clauses['where'] .= ' AND ' . implode( ' AND ', $wheres );
			}

			return $clauses;
		}

		function exclude_by_keyword( $clauses, $wp_query ) {
			if ( ! empty( $wp_query->query['wpse_not_contains_keyword'] ) ) {
				$clauses = WP_Sheet_Editor_Filters::get_instance()->add_search_by_keyword_clause( $clauses, $wp_query->query['wpse_not_contains_keyword'], 'NOT LIKE', 'AND' );
			}
			return $clauses;
		}

		/**
		 * Register frontend assets
		 */
		function register_assets() {
			wp_enqueue_script( 'advanced-filters_js', $this->plugin_url . 'assets/js/init.js', array(), filemtime( __DIR__ . '/assets/js/init.js' ), false );
		}

		function register_filters( $filters, $post_type ) {
			if ( VGSE()->helpers->get_current_provider()->is_post_type ) {
				$taxonomies = get_object_taxonomies( $post_type );
				if ( ! empty( $taxonomies ) ) {
					$filters['taxonomy_term'] = array(
						'label'       => '',
						'description' => '',
					);
				}
				$filters['date'] = array(
					'label'       => '',
					'description' => '',
				);
				if ( post_type_supports( $post_type, 'page-attributes' ) && $post_type !== 'attachment' ) {
					$filters['post_parent'] = array(
						'label'       => esc_html__( 'Parent', 'vg_sheet_editor' ),
						'description' => '',
					);
				}
				// Remove the post status field because they can search using the advanced filters
				if ( isset( $filters['post_status'] ) ) {
					unset( $filters['post_status'] );
				}
			}
			return $filters;
		}

		function save_searches() {
			if ( empty( $_POST['post_type'] ) || ! VGSE()->helpers->verify_nonce_from_request() ) {
				wp_send_json_error( array( 'message' => esc_html__( 'You must be logged in to save searches.', 'vg_sheet_editor' ) ) );
			}

			$post_type = VGSE()->helpers->sanitize_table_key( $_POST['post_type'] );
			$searches  = isset( $_POST['saved_searches'] ) ? json_decode( wp_unslash( $_POST['saved_searches'] ), true ) : array();

			if ( ! is_array( $searches ) || ! VGSE()->helpers->user_can_edit_post_type( $post_type ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid data format.', 'vg_sheet_editor' ) ) );
			}

			$all_saved_searches = get_option( 'vgse_saved_searches', array() );
			if ( ! is_array( $all_saved_searches ) ) {
				$all_saved_searches = array();
			}
			$user_saved_searches = get_user_meta( get_current_user_id(), 'vgse_saved_searches', true );
			if ( ! is_array( $user_saved_searches ) ) {
				$user_saved_searches = array();
			}

			$global_searches_for_post_type = array();
			$user_searches_for_post_type   = array();

			$filters_instance = WP_Sheet_Editor_Filters::get_instance();
			foreach ( $searches as $search ) {
				if ( empty( $search['search_name'] ) || empty( $search['filters'] ) || ! is_array( $search['filters'] ) ) {
					continue;
				}

				$search_to_save = array_merge(
					array( 'search_name' => sanitize_text_field( $search['search_name'] ) ),
					$filters_instance->_sanitize_filters( $search['filters'] )
				);

				// Any logged-in user can save a search for themselves.
				if ( isset( $search['scope'] ) && 'user' === $search['scope'] ) {
					$user_searches_for_post_type[] = $search_to_save;
				} else {
					// Only users with 'manage_options' can save global searches.
					if ( VGSE()->helpers->user_can_manage_options() ) {
						$global_searches_for_post_type[] = $search_to_save;
					}
				}
			}

			// If the current user is not an admin, load the existing global searches to avoid overwriting them.
			if ( ! VGSE()->helpers->user_can_manage_options() && ! empty( $all_saved_searches[ $post_type ] ) ) {
				$global_searches_for_post_type = $all_saved_searches[ $post_type ];
			}

			// Create a signature for each global search's filters to easily detect duplicates.
			$global_filter_signatures = array();
			if ( ! empty( $global_searches_for_post_type ) ) {
				foreach ( $global_searches_for_post_type as $global_search ) {
					$filters = $global_search;
					unset( $filters['search_name'] );
					ksort( $filters ); // Sort keys to ensure consistent signature.
					$global_filter_signatures[] = md5( wp_json_encode( $filters ) );
				}
				$global_filter_signatures = array_unique( $global_filter_signatures );
			}

			// If there are global searches, filter out any user-specific searches that are duplicates.
			if ( ! empty( $global_filter_signatures ) ) {
				$user_searches_for_post_type = array_filter(
					$user_searches_for_post_type,
					function ( $user_search ) use ( $global_filter_signatures ) {
						$filters = $user_search;
						unset( $filters['search_name'] );
						ksort( $filters );
						$user_filter_signature = md5( wp_json_encode( $filters ) );
						// Keep the user search only if its filters are NOT found in the global searches.
						return ! in_array( $user_filter_signature, $global_filter_signatures, true );
					}
				);
			}

			// Only admins can modify global searches.
			if ( VGSE()->helpers->user_can_manage_options() ) {
				$all_saved_searches[ $post_type ] = $global_searches_for_post_type;
				update_option( 'vgse_saved_searches', $all_saved_searches, false );
			}

			// Any logged-in user can save their own searches.
			// We use array_values to re-index the array after filtering, ensuring it's saved correctly as a JSON array.
			$user_saved_searches[ $post_type ] = array_values( $user_searches_for_post_type );
			update_user_meta( get_current_user_id(), 'vgse_saved_searches', $user_saved_searches );

			wp_send_json_success();
		}


		function register_custom_filters( $sanitized_filters, $dirty_filters ) {

			if ( isset( $dirty_filters['post_name__in'] ) ) {
				$sanitized_filters['post_name__in'] = sanitize_textarea_field( $dirty_filters['post_name__in'] );
			}
			return $sanitized_filters;
		}

		/**
		 * Apply filters to wp-query args
		 * @param array $query_args
		 * @param array $data
		 * @return array
		 */
		function filter_posts( $query_args, $data ) {

			if ( ! empty( $data['filters'] ) ) {
				$filters = WP_Sheet_Editor_Filters::get_instance()->get_raw_filters( $data );

				$query_args['wpse_original_filters'] = $filters;

				if ( ! empty( $filters['apply_to'] ) && is_array( $filters['apply_to'] ) ) {
					$taxonomies_group = array();

					$filters['apply_to'] = array_map( 'sanitize_text_field', $filters['apply_to'] );
					foreach ( $filters['apply_to'] as $term ) {
						$term_parts = explode( '--', $term );
						if ( count( $term_parts ) !== 2 ) {
							continue;
						}
						$taxonomy = $term_parts[0];
						$term     = $term_parts[1];

						if ( ! isset( $taxonomies_group[ $taxonomy ] ) ) {
							$taxonomies_group[ $taxonomy ] = array();
						}
						$taxonomies_group[ $taxonomy ][] = $term;
					}

					$query_args['tax_query'] = array(
						'relation' => 'AND',
					);

					foreach ( $taxonomies_group as $taxonomy_key => $terms ) {
						$query_args['tax_query'][] = array(
							'taxonomy' => $taxonomy_key,
							'field'    => 'slug',
							'terms'    => $terms,
						);
					}
				}

				if ( ! empty( $filters['keyword_exclude'] ) ) {
					$editor = VGSE()->helpers->get_provider_editor( $query_args['post_type'] );
					if ( $editor->provider->is_post_type ) {
						$query_args['wpse_not_contains_keyword'] = $filters['keyword_exclude'];
					} else {
						$post_id_exclude            = $editor->provider->get_item_ids_by_keyword( $filters['keyword_exclude'], $query_args['post_type'], 'LIKE' );
						$query_args['post__not_in'] = $post_id_exclude;
					}
				}

				if ( ! empty( $filters['post_parent'] ) ) {
					$query_args['post_parent'] = (int) str_replace( 'page--', '', $filters['post_parent'] );
				}
				if ( ! empty( $filters['date_from'] ) || ! empty( $filters['date_to'] ) ) {
					$query_args['date_query'] = array(
						'inclusive' => true,
					);
				}
				if ( ! empty( $filters['post__in'] ) ) {
					$post_ids               = VGSE()->helpers->get_ids_from_text_list( $filters['post__in'] );
					$query_args['post__in'] = ( ! empty( $query_args['post__in'] ) ) ? array_intersect( $query_args['post__in'], $post_ids ) : $post_ids;
				}
				if ( ! empty( $filters['post_name__in'] ) ) {
					$post_slugs = array_unique( array_filter( array_map( 'sanitize_text_field', array_map( 'basename', array_map( 'trim', preg_split( '/\r\n|\r|\n/', $filters['post_name__in'] ) ) ) ) ) );
					if ( ! empty( $post_slugs ) ) {
						$query_args['post_name__in'] = $post_slugs;
					}
				}
				if ( ! empty( $filters['date_from'] ) ) {
					$query_args['date_query']['after'] = sanitize_text_field( $filters['date_from'] );
				}
				if ( ! empty( $filters['date_to'] ) ) {
					$query_args['date_query']['before'] = sanitize_text_field( $filters['date_to'] );
				}
				if ( ! empty( $filters['meta_query'] ) && is_array( $filters['meta_query'] ) ) {

					$all_advanced_filters = json_encode( $filters['meta_query'] );
					// If there is one advanced filter for post_status, we add a flag
					// and we will remove the post_status clause from the sql query later
					if ( strpos( $all_advanced_filters, '"key":"post_status"' ) !== false && isset( $query_args['post_status'] ) && post_type_exists( $query_args['post_type'] ) ) {
						$query_args['wpse_original_post_statuses'] = $query_args['post_status'];
					}
					$filters['meta_query'] = $this->_parse_meta_query_args( $filters['meta_query'], 'meta', $query_args );

					// Never remove the meta clause added by the global sort by meta field
					if ( isset( $query_args['meta_query']['wpse_meta_sort_clause'] ) ) {
						$filters['meta_query']['wpse_meta_sort_clause'] = $query_args['meta_query']['wpse_meta_sort_clause'];
					}
					$query_args['meta_query'] = $filters['meta_query'];
				}
			}

			return $query_args;
		}

		function add_filters_fields( $current_post_type, $filters ) {
			?>

			<?php
			if ( isset( $filters['taxonomy_term'] ) ) {
				?>
				<li class="
				<?php
				$labels = apply_filters( 'vg_sheet_editor/advanced_filters/taxonomy_labels', VGSE()->helpers->get_post_type_taxonomies_single_data( $current_post_type, 'label' ), $current_post_type );
				if ( empty( $labels ) ) {
					echo ' hidden';
				}

				if ( count( $labels ) > 1 ) {
					$labels[ count( $labels ) - 1 ] = ' or ' . end( $labels );
				}
				?>
				">
					<label>
					<?php
					/* translators: %s: taxonomy label */
					printf( esc_html__( 'Enter %s', 'vg_sheet_editor' ), esc_html( implode( ', ', array_unique( $labels ) ) ) );
					?>
					<a href="#" data-wpse-tooltip="up" aria-label="
					<?php
					// translators: %s: taxonomy labels
					echo esc_attr( sprintf( esc_html__( 'Enter the names of %s', 'vg_sheet_editor' ), esc_html( implode( ', ', $labels ) ) ) );
					?>
					">( ? )</a></label>
					<select data-placeholder="<?php esc_html_e( 'Category name...', 'vg_sheet_editor' ); ?>" name="apply_to[]" class="select2"  x-init="initSelect2($el)" multiple data-remote="true" data-action="vgse_search_taxonomy_terms" data-min-input-length="4" x-model="activeFilters.apply_to">

					</select>
				</li>
			<?php } ?>

			<?php if ( isset( $filters['post_parent'] ) ) { ?>
				<li>
					<label><?php echo wp_kses_post( $filters['post_parent']['label'] ); ?>  <?php
					if ( ! empty( $filters['post_parent']['description'] ) ) {
						?>
						<a href="#" data-wpse-tooltip="right" aria-label="<?php echo esc_attr( $filters['post_parent']['description'] ); ?>">( ? )</a><?php } ?></label>
					<select name="post_parent" data-remote="true" data-min-input-length="4" data-action="vgse_find_post_by_name" data-post-type="<?php echo esc_attr( $current_post_type ); ?>" data-placeholder="<?php esc_attr_e( 'Select...', 'vg_sheet_editor' ); ?> " class="select2" x-init="initSelect2($el)" multiple x-model="activeFilters.post_parent">
					</select> 									
				</li>
				<?php
			}
		}

		function get_advanced_filters_fields( $current_post_type, $filters ) {
			global $wpdb;

			$cache_key = 'vgse_advanced_filter_fields' . $current_post_type;
			$out       = get_transient( $cache_key );
			if ( method_exists( VGSE()->helpers, 'can_rescan_db_fields' ) && VGSE()->helpers->can_rescan_db_fields( $current_post_type ) ) {
				$out = false;
			}

			if ( ! $out ) {
				$maximum_fields = ( ! empty( VGSE()->options['maximum_advanced_filters_fields'] ) ) ? (int) VGSE()->options['maximum_advanced_filters_fields'] : 1000;
				$all_meta_keys  = apply_filters( 'vg_sheet_editor/advanced_filters/all_meta_keys', VGSE()->helpers->get_all_meta_keys( $current_post_type, $maximum_fields ), $current_post_type, $filters );

				// post data and taxonomy advanced filters are available for post types only
				if ( VGSE()->helpers->get_current_provider()->is_post_type ) {
					$taxonomy_keys = VGSE()->helpers->get_post_type_taxonomies_single_data( $current_post_type, 'name' );
					$item_raw      = $wpdb->get_row( "SELECT * FROM $wpdb->posts LIMIT 1", ARRAY_A );
					$item          = ( is_array( $item_raw ) ) ? array_keys( $item_raw ) : array();
				} else {
					$item          = array();
					$taxonomy_keys = array();
				}

				$all_fields = array(
					'meta'          => array_unique( $all_meta_keys ),
					'post_data'     => array_unique( $item ),
					'taxonomy_keys' => array_unique( $taxonomy_keys ),
				);
				$out        = apply_filters( 'vg_sheet_editor/advanced_filters/all_fields_groups', $all_fields, $current_post_type, $filters );
				set_transient( $cache_key, $out, DAY_IN_SECONDS );
			}

			return $out;
		}

		function _render_advanced_filter_row( $current_post_type, $filters, $selected_values = array(), $wrapper = 'li' ) {
			?>
			<<?php echo esc_html( $wrapper ); ?> class="advanced-field">
			<div class="fields-wrap" :data-advanced-filter-index="index">
				<div class="field-wrap search-field-wrap">
					<label><?php esc_html_e( 'Field', 'vg_sheet_editor' ); ?></label>
					<input type="hidden" x-model="filter.source" :name="'meta_query['+index+'][source]'">
					<select :data-full-model-path="'activeFilters.meta_query['+index+'].key'" :name="'meta_query['+index+'][key]'" data-placeholder="<?php esc_html_e( 'Select...', 'vg_sheet_editor' ); ?>" x-init="initSelect2($el)" class="select2 wpse-advanced-filters-field-selector" x-model="filter.key" x-effect="if(filter){advancedFilterKeyChanged(filter.key, index, $el)}">
						<option value="">- -</option>
						<?php
						$all_fields = $this->get_advanced_filters_fields( $current_post_type, $filters );
						if ( ! empty( $all_fields ) && is_array( $all_fields ) ) {
							$columns              = VGSE()->helpers->get_unfiltered_provider_columns( $current_post_type );
							$default_field_labels = array(
								'_edit_last'          => esc_html__( 'Last edit', 'vg_sheet_editor' ),
								'_wp_old_slug'        => esc_html__( 'Old slug', 'vg_sheet_editor' ),
								'post_author'         => esc_html__( 'Author', 'vg_sheet_editor' ),
								'post_date_gmt'       => esc_html__( 'Date (GMT)', 'vg_sheet_editor' ),
								'post_modified_gmt'   => esc_html__( 'Modified Date (GMT)', 'vg_sheet_editor' ),
								'comment_status'      => esc_html__( 'Comments', 'vg_sheet_editor' ),
								'ping_status'         => esc_html__( 'Ping status', 'vg_sheet_editor' ),
								'post_name'           => esc_html__( 'URL Slug', 'vg_sheet_editor' ),
								'post_parent'         => esc_html__( 'Page Parent', 'vg_sheet_editor' ),
								'post_mime_type'      => esc_html__( 'Mime type', 'vg_sheet_editor' ),
								'comment_count'       => esc_html__( 'Comment count', 'vg_sheet_editor' ),
								'edd_variable_prices' => esc_html__( 'EDD Variable Prices', 'vg_sheet_editor' ),
								'edd_download_files'  => esc_html__( 'EDD Download Files', 'vg_sheet_editor' ),
							);
							if ( ! empty( VGSE()->options['exclude_non_visible_columns_from_tools'] ) ) {
								$visible_columns = VGSE()->helpers->get_provider_columns( $current_post_type );
							}
							foreach ( $all_fields as $group_key => $group_fields ) {
								foreach ( $group_fields as $field_key ) {
									if ( isset( $columns[ $field_key ] ) && empty( $columns[ $field_key ]['allow_direct_search'] ) ) {
										continue;
									}
									if ( ! empty( $visible_columns ) && ! isset( $visible_columns[ $field_key ] ) ) {
										continue;
									}

									$field_label = '';
									if ( isset( $columns[ $field_key ] ) && isset( $columns[ $field_key ]['title'] ) ) {
										$field_label = $columns[ $field_key ]['title'];
									} elseif ( isset( $default_field_labels[ $field_key ] ) ) {
										$field_label = $default_field_labels[ $field_key ];
									} else {
										$field_label = VGSE()->helpers->convert_key_to_label( $field_key );
									}
									$label = ( $field_label && $field_label !== $field_key ) ? $field_label . " ($field_key)" : $field_key;

									echo '<option value="' . esc_attr( $field_key ) . '" data-source="' . esc_attr( $group_key ) . '" ';
									echo '>' . esc_html( $label ) . '</option>';
								}
							}
						}
						?>
					</select>

					<?php if ( is_admin() && VGSE()->helpers->user_can_manage_options() && empty( VGSE()->options['enable_simple_mode'] ) ) { ?>
						<br /><span class="search-tool-missing-column-tip"><small>
							<?php esc_html_e( 'A field is missing?', 'vg_sheet_editor' ); ?>
							<a :href="vgse_editor_settings.scandb_url" class="scandb-url"><?php esc_html_e( 'Click here', 'vg_sheet_editor' ); ?></a>
							</small></span>
					<?php } ?>
				</div>
				<div class="field-wrap search-operator-wrap" :style="'width: '+ (filter.compare === 'contains_duplicate_values' ? '60%' : '')">
					<label><?php esc_html_e( 'Operator', 'vg_sheet_editor' ); ?></label>
					<select :name="'meta_query['+index+'][compare]'" data-placeholder="<?php esc_html_e( 'Select...', 'vg_sheet_editor' ); ?>" class=" wpse-advanced-filters-operator-selector" x-model="filter.compare" @change="if( filter.compare === 'contains_duplicate_values'){ filter.value = '' }">
						<?php $this->render_operator_options(); ?>
					</select>
					<a class="small" @click.prevent="" href="#" x-show="filter.compare === 'contains_duplicate_values'" data-wpse-tooltip="right" aria-label="<?php esc_attr_e( 'This is a heavy query for your database, it might not work if you have a huge database or your server is not powerful enough', 'vg_sheet_editor' ); ?>"><?php esc_html_e( 'Warning', 'vg_sheet_editor' ); ?></a>
				</div>
				<div class="field-wrap search-value-wrap" x-show="!isValueFieldHidden(filter.compare, $el)">
					<label><?php esc_html_e( 'Value', 'vg_sheet_editor' ); ?></label>

					<template x-if="getAdvancedFilterValueType(filter) === 'text'">
						<input :name="'meta_query['+index+'][value]'" type="text" class="wpse-advanced-filters-value-selector" x-model.lazy="filter.value" />
					</template>
					<template x-if="getAdvancedFilterValueType(filter) === 'number'">
						<input :name="'meta_query['+index+'][value]'" step="0.01" type="number" class="wpse-advanced-filters-value-selector" x-model.lazy="filter.value" />
					</template>
					<template x-if="getAdvancedFilterValueType(filter) === 'checkbox'">
						<div>
							<input type="checkbox" class="wpse-advanced-filters-value-selector" :checked="filter.value === getColumnSettings(filter.key).formatted.checkedTemplate" @change="filter.value = $event.target.checked ? getColumnSettings(filter.key).formatted.checkedTemplate : getColumnSettings(filter.key).formatted.uncheckedTemplate">
							<input type="hidden" :name="'meta_query['+index+'][value]'" x-model="filter.value">
						</div>
					</template>
					<template x-if="getAdvancedFilterValueType(filter) === 'date'">
						<div>
							<input :type="getColumnSettings(filter.key).formatted.editor === 'wp_datetime' ? 'datetime-local' : 'date'" class="wpse-advanced-filters-value-selector" @change="updateDateFilterValue(index, $event.target.value)">
							<input type="hidden" :name="'meta_query['+index+'][value]'" x-model="filter.value">
						</div>
					</template>
					<template x-if="getAdvancedFilterValueType(filter) === 'select'">
						<select class="wpse-advanced-filters-value-selector" :name="'meta_query['+index+'][value]'" x-model="filter.value">
							<option value="">(<?php echo esc_html_e( 'empty', 'vg_sheet_editor' ); ?>)</option>
							<template x-for="[key, label] in Object.entries(getColumnSettings(filter.key).formatted.selectOptions || getColumnSettings(filter.key).formatted.source)">
								<option :value="isNaN(parseInt(key)) ? key : label" x-text="label"></option>
							</template>
						</select>
					</template>
					<template x-if="getAdvancedFilterValueType(filter) === 'user'">
						<input class="wpse-advanced-filters-value-selector" :name="'meta_query['+index+'][value]'" x-model="filter.value" :list="'wpse-bulk-edit-users-list-' + filter.key" type="text" @keyup.debounce.1500ms="searchUsers($event.target)">
						<datalist :id="'wpse-bulk-edit-users-list-' + filter.key"></datalist>
					</template>
				</div>

				<div class="fields-wrap search-row-add-new">
					<a href="#" @click.prevent="addNewAdvancedFilter" class="button new-advanced-filter"><?php esc_html_e( 'Add new', 'vg_sheet_editor' ); ?></a>
				</div>
				<div class="fields-wrap search-row-remove-wrap">
					<a href="#" @click.prevent="removeAdvancedFilter(index)" class="button remove-advanced-filter"><?php esc_html_e( 'X', 'vg_sheet_editor' ); ?></a>
				</div>
			</div>
			<template x-if="index === 0">
				<button class="wpse-favorite-search-field" type="button" data-key="meta_query[0][source]"><i :class="vgse_editor_settings.favorite_search_fields.indexOf('meta_query[0][source]') > -1 ? 'fa fa-star' : 'fa fa-star-o'"></i></button>
			</template>
			</<?php echo esc_html( $wrapper ); ?>>
			<?php
		}

		function add_advanced_filters_fields( $current_post_type, $filters ) {
			?>

			<p class="wpse-advanced-filters-toggle"><label><input type="checkbox" x-model="showAdvancedFilters" class="advanced-filters-toggle"> <?php esc_html_e( 'Enable advanced filters', 'vg_sheet_editor' ); ?></label></p>
			<div class="advanced-filters" x-show="showAdvancedFilters">
				<?php if ( empty( VGSE()->options['enable_simple_mode'] ) ) { ?>
					<h3><?php esc_html_e( 'Advanced search', 'vg_sheet_editor' ); ?></h3>
				<?php } ?>
				<p class="advanced-filters-message"><?php esc_html_e( 'You can search by any field using operators. I.e. price > 100, image != (empty)', 'vg_sheet_editor' ); ?></p>
				<ul class="unstyled-list advanced-filters-list">
					<template x-for="(filter, index) in activeFilters.meta_query" :key="index">
						<template x-if="activeFilters.meta_query && activeFilters.meta_query.length">
							<?php $this->_render_advanced_filter_row( $current_post_type, $filters ); ?>
						</template>						
					</template>
					<?php do_action( 'vg_sheet_editor/filters/after_advanced_fields', $current_post_type ); ?>
				</ul>

				<hr>
				<ul class="unstyled-list bottom-advanced-filters">
					<?php
					do_action( 'vg_sheet_editor/filters/after_advanced_fields_section', $current_post_type );

					if ( empty( VGSE()->options['enable_simple_mode'] ) ) {
						?>
						<li class="exclude-keyword">
							<label><?php echo esc_html__( 'NOT Contains this keyword', 'vg_sheet_editor' ); ?>  <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_attr_e( 'Enter a keyword to exclude posts, separate multiple keywords with a semicolon (;)', 'vg_sheet_editor' ); ?>">( ? )</a></label>
							<input type="text" name="keyword_exclude" x-model="activeFilters.keyword_exclude">
						</li>
						<li class="post--in">
							<label><?php esc_html_e( 'Find these IDs:', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'Enter IDs separated by commas, spaces, new lines, or tabs. You can use ID ranges like 20-50 as a shortcut.', 'vg_sheet_editor' ); ?>">( ? )</a></label>
							<textarea name="post__in" x-model="activeFilters.post__in"></textarea>
						</li>
						<?php if ( VGSE()->helpers->get_current_provider()->is_post_type ) { ?>
							<li class="post-name--in">
								<label><?php esc_html_e( 'Find these URLs:', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'Enter one URL per line', 'vg_sheet_editor' ); ?>">( ? )</a></label>
								<textarea name="post_name__in" x-model="activeFilters.post_name__in"></textarea>
							</li>
							<li class="date-range">
								<label><?php esc_html_e( 'Date range from', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'Show items published between these dates', 'vg_sheet_editor' ); ?>">( ? )</a></label><input type="date" name="date_from" x-model="activeFilters.date_from" /><br /> <?php esc_html_e( 'to', 'vg_sheet_editor' ); ?><br /> <input type="date" name="date_to" x-model="activeFilters.date_to" />
							</li>
							<?php
						}
					}
					?>
				</ul>
			</div>
			<?php
		}

		function render_operator_options( $selected = '' ) {
			?>
			<option value="=" <?php selected( in_array( $selected, array( '', '=' ) ) ); ?>>=</option>
			<option value="!=" <?php selected( $selected, '!=' ); ?>>!=</option>
			<option value="<" <?php selected( $selected, '<' ); ?> ><</option>
			<option value="<=" <?php selected( $selected, '<=' ); ?> ><=</option>
			<option value=">"  <?php selected( $selected, '>' ); ?>>></option>
			<option value=">="  <?php selected( $selected, '>=' ); ?>>>=</option>
			<option value="OR" data-value-field-type="text"  <?php selected( $selected, 'OR' ); ?> data-custom-label="ANY"><?php esc_html_e( 'Any of these values (Enter multiple values separated by ;)', 'vg_sheet_editor' ); ?></option>
			<option value="LIKE"  <?php selected( $selected, 'LIKE' ); ?>><?php esc_html_e( 'CONTAINS', 'vg_sheet_editor' ); ?></option>
			<option value="NOT LIKE"  <?php selected( $selected, 'NOT LIKE' ); ?>><?php esc_html_e( 'NOT CONTAINS', 'vg_sheet_editor' ); ?></option>
			<option value="starts_with" <?php selected( $selected, 'starts_with' ); ?> ><?php esc_html_e( 'STARTS WITH', 'vg_sheet_editor' ); ?></option>
			<option value="not_starts_with" <?php selected( $selected, 'not_starts_with' ); ?> ><?php esc_html_e( 'NOT STARTS WITH', 'vg_sheet_editor' ); ?></option>
			<option value="ends_with"  <?php selected( $selected, 'ends_with' ); ?>><?php esc_html_e( 'ENDS WITH', 'vg_sheet_editor' ); ?></option>
			<option value="length_less"  <?php selected( $selected, 'length_less' ); ?>><?php esc_html_e( 'CHARACTER LENGTH <', 'vg_sheet_editor' ); ?></option>
			<option value="length_higher"  <?php selected( $selected, 'length_higher' ); ?>><?php esc_html_e( 'CHARACTER LENGTH >', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="text" value="REGEXP"  <?php selected( $selected, 'REGEXP' ); ?>><?php esc_html_e( 'REGEXP', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="number" value="word_count_greater" <?php selected( $selected, 'word_count_greater' ); ?>><?php esc_html_e( 'Word count >', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="number" value="word_count_less" <?php selected( $selected, 'word_count_less' ); ?>><?php esc_html_e( 'Word count <', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="text" data-hide-value="1" value="contains_duplicate_values"  <?php selected( $selected, 'contains_duplicate_values' ); ?>><?php esc_html_e( 'Contains duplicate values', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="text" value="NOT REGEXP"  <?php selected( $selected, 'NOT REGEXP' ); ?>><?php esc_html_e( 'NOT REGEXP', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="number" data-value-type="date" value="last_hours"  <?php selected( $selected, 'last_hours' ); ?>><?php esc_html_e( 'In the last x hours', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="number" data-value-type="date" value="last_days"  <?php selected( $selected, 'last_days' ); ?>><?php esc_html_e( 'In the last x days', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="number" data-value-type="date" value="last_weeks"  <?php selected( $selected, 'last_weeks' ); ?>><?php esc_html_e( 'In the last x weeks', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="number" data-value-type="date" value="last_months"  <?php selected( $selected, 'last_months' ); ?>><?php esc_html_e( 'In the last x months', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="number" data-value-type="date" value="older_than_hours"  <?php selected( $selected, 'older_than_hours' ); ?>><?php esc_html_e( 'Older than x hours', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="number" data-value-type="date" value="older_than_days"  <?php selected( $selected, 'older_than_days' ); ?>><?php esc_html_e( 'Older than x days', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="number" data-value-type="date" value="older_than_weeks"  <?php selected( $selected, 'older_than_weeks' ); ?>><?php esc_html_e( 'Older than x weeks', 'vg_sheet_editor' ); ?></option>
			<option data-value-field-type="number" data-value-type="date" value="older_than_months"  <?php selected( $selected, 'older_than_months' ); ?>><?php esc_html_e( 'Older than x months', 'vg_sheet_editor' ); ?></option>

			<?php
		}

		/**
		 * Creates or returns an instance of this class.
		 * @return WP_Sheet_Editor_Advanced_Filters
		 */
		static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new WP_Sheet_Editor_Advanced_Filters();
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

	add_action( 'vg_sheet_editor/initialized', 'vgse_advanced_filters_init' );

	function vgse_advanced_filters_init() {
		return WP_Sheet_Editor_Advanced_Filters::get_instance();
	}
}
