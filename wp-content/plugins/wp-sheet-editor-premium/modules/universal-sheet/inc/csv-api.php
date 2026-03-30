<?php defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WPSE_CSV_API' ) ) {

	class WPSE_CSV_API {

		private static $instance        = false;
		public $uploads_dir             = null;
		public $imports_dir             = null;
		public $exports_dir             = null;
		public $long_lived_dir          = null;
		public $current_import_settings = array();
		public $column_mappings_key     = 'vgse_import_column_mappings';
		public $is_import_running       = false;

		private function __construct() {
		}
		function _load_spreadsheet_vendor() {
			if ( ! class_exists( 'PhpOffice\PhpSpreadsheet\Spreadsheet' ) && file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
				require_once dirname( __DIR__ ) . '/vendor/autoload.php';
			}
		}

		function get_current_import_settings() {
			return $this->current_import_settings;
		}

		function remove_duplicates_from_file( $file_path ) {
			$lines = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			$lines = array_unique( $lines );
			file_put_contents( $file_path, implode( PHP_EOL, $lines ) );
		}

		function init() {
			$this->uploads_dir    = wp_normalize_path( apply_filters( 'vg_sheet_editor/csv/base_folder', WP_CONTENT_DIR . '/uploads/wp-sheet-editor' ) );
			$this->imports_dir    = wp_normalize_path( $this->uploads_dir . '/imports/' );
			$this->exports_dir    = wp_normalize_path( $this->uploads_dir . '/exports/' );
			$this->long_lived_dir = wp_normalize_path( $this->uploads_dir . '/long-lived/' );

			if ( is_admin() ) {
				$this->maybe_create_directories();
			}

			add_action( 'vg_sheet_editor/initialized', array( $this, 'late_init' ) );
		}

		function remove_directory() {
			if ( ! is_dir( $this->uploads_dir ) ) {
				return;
			}
			require_once ABSPATH . '/wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . '/wp-admin/includes/class-wp-filesystem-direct.php';
			$fileSystemDirect = new WP_Filesystem_Direct( false );
			$fileSystemDirect->rmdir( $this->uploads_dir, true );
		}

		function delete_old_directory() {
			$old_directory = WP_CONTENT_DIR . '/uploads/wp-sheet-editor-universal-sheet';
			if ( ! is_dir( $old_directory ) ) {
				return;
			}
			require_once ABSPATH . '/wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . '/wp-admin/includes/class-wp-filesystem-direct.php';
			$fileSystemDirect = new WP_Filesystem_Direct( false );
			$fileSystemDirect->rmdir( $old_directory, true );
		}

		function _get_ftp_file_id( $ftp_path, $post_id = null ) {
			global $wpdb;
			$out = null;
			if ( ! VGSE()->helpers->user_can_manage_options() ) {
				return $out;
			}

			//          ftp://user:password@host:port/path or ftp://user@host:port/path or ftp://user@host/path
			preg_match( '/^ftp:\/\/([^@:]*):?([^@]*)@([^:\/]*)(:[^\/]*)?(.*)$/', $ftp_path, $ftp_parts );

			if ( count( $ftp_parts ) !== 6 ) {
				return $out;
			}

			$user      = $ftp_parts[1];
			$pass      = $ftp_parts[2];
			$host      = $ftp_parts[3];
			$port      = (int) $ftp_parts[4];
			$file_path = $ftp_parts[5];

			if ( empty( $file_path ) || empty( $host ) || empty( $user ) ) {
				return $out;
			}

			// Only allow image files
			if ( ! preg_match( '/\.(png|jpg|jpeg|gif|webp|avif)$/', strtolower( $file_path ) ) ) {
				return $out;
			}

			if ( empty( VGSE()->options['allow_ftp_images_duplication'] ) ) {
				$attachment_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'wpse_original_ftp_path' AND meta_value = %s ", $file_path ) );
				if ( $attachment_id ) {
					return $attachment_id;
				}
			}

			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$url_filename  = basename( wp_parse_url( $file_path, PHP_URL_PATH ) );
			$tmp_file_path = wp_tempnam( $url_filename );

			$conn_id = ftp_connect( $host, $port ? $port : 21, 5 );
			if ( ! $conn_id ) {
				return $out;
			}
			$login_status = ftp_login( $conn_id, $user, $pass );
			if ( ! $login_status ) {
				return $out;
			}
			$handle = fopen( $tmp_file_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$mode   = ftp_pasv( $conn_id, true );
			ftp_fget( $conn_id, $handle, $file_path, FTP_BINARY, 0 );
			ftp_close( $conn_id );

			$attachment_id = VGSE()->helpers->add_file_to_gallery_from_path( $tmp_file_path, $url_filename, $post_id );

			if ( is_numeric( $attachment_id ) ) {
				update_post_meta( $attachment_id, 'wpse_original_ftp_path', $file_path );
			}
			return $attachment_id;
		}

		function late_init() {

			add_filter( 'vg_sheet_editor/options_page/options', array( $this, 'add_settings_page_options' ) );
			add_action( 'vg_sheet_editor/on_uninstall', array( $this, 'remove_directory' ) );
			add_filter( 'vg_sheet_editor/load_rows/full_output', array( $this, 'export_csv' ), 10, 4 );
			add_filter( 'vg_sheet_editor/load_rows/raw_incoming_data', array( $this, 'register_export_data_fields' ) );
			add_action( 'wp_ajax_vgse_load_data', array( $this, 'disable_floating_columns_on_export' ), 1 );
			add_action( 'wp_ajax_vgse_import_csv', array( $this, 'import_csv' ) );
			add_action( 'wp_ajax_vgse_upload_file_for_import', array( $this, 'upload_data_for_import' ) );
			add_action( 'wpse_daily_cron', array( $this, 'delete_old_csvs' ) );
			$this->delete_old_directory();

			$this->maybe_download_file();

			add_filter( 'vg_sheet_editor/js_data', array( $this, 'add_settings_js' ), 9, 2 );
		}



		function add_settings_js( $data, $post_type ) {
			$saved_column_mappings = get_option( $this->column_mappings_key, array() );
			if ( empty( $saved_column_mappings ) || ! is_array( $saved_column_mappings ) ) {
				$saved_column_mappings = array();
			}
			if ( ! isset( $saved_column_mappings[ $post_type ] ) ) {
				$saved_column_mappings[ $post_type ] = array();
			}
			$data['import_saved_column_mappings'] = $saved_column_mappings[ $post_type ];
			return $data;
		}

		function disable_floating_columns_on_export() {
			if ( ! empty( $_POST['vgse_csv_export'] ) && ! defined( 'WPSE_ONLY_EXPLICITLY_ENABLED_COLUMNS' ) ) {
				define( 'WPSE_ONLY_EXPLICITLY_ENABLED_COLUMNS', true );
			}
		}

		/**
		 * Add fields to options page
		 * @param array $sections
		 * @return array
		 */
		function add_settings_page_options( $sections ) {
			$sections['customize_features']['fields'][] = array(
				'id'      => 'allow_ftp_images_support',
				'type'    => 'switch',
				'title'   => esc_html__( 'Allow to import images from FTP servers?', 'vg_sheet_editor' ),
				'desc'    => esc_html__( 'By default, the importer allows to save images using full internal URLs, external URLs, file name (from the media library), and file ID. If you activate this option, it will support FTP urls like this: ftp://user:password@host:port/path/to/image.png. This will make the import slower', 'vg_sheet_editor' ),
				'default' => false,
			);
			$sections['customize_features']['fields'][] = array(
				'id'      => 'allow_ftp_images_duplication',
				'type'    => 'switch',
				'title'   => esc_html__( 'FTP Images: Skip images with same file name?', 'vg_sheet_editor' ),
				'desc'    => esc_html__( 'When you import an image using FTP urls, the image is imported every time and you might end up with duplicates in the media library. Activate this option to reuse images in the media library with same file name and avoid downloading the FTP image again on future imports. Deactivate this option if you want to keep the images updated on future imports.', 'vg_sheet_editor' ),
				'default' => false,
			);
			$sections['customize_features']['fields'][] = array(
				'id'      => 'allow_public_download_export_files',
				'type'    => 'switch',
				'title'   => esc_html__( 'Make the URL of export files publicly accessible?', 'vg_sheet_editor' ),
				'desc'    => esc_html__( 'By default, you need to log in to download the exported files, you can enable this option to allow anyone with the link to download the file.', 'vg_sheet_editor' ),
				'default' => false,
			);
			return $sections;
		}


		function maybe_download_file() {
			if ( empty( $_GET['wpseefn'] ) ) {
				return;
			}

			if ( ! VGSE()->get_option( 'allow_public_download_export_files' ) && ! is_user_logged_in() ) {
				wp_safe_redirect( wp_login_url( $_SERVER['REQUEST_URI'] ) );
				exit();
			}

			if ( strpos( $_GET['wpseefn'], '.' ) !== false || strpos( $_GET['wpseefn'], '/' ) !== false || strpos( $_GET['wpseefn'], '\\' ) !== false ) {
				die();
			}
			$file_type                       = ( ! empty( $_GET['wpse_file_type'] ) ) ? sanitize_text_field( wp_unslash( $_GET['wpse_file_type'] ) ) : 'csv';
			$allowed_file_types_for_download = apply_filters( 'vg_sheet_editor/csv/allowed_file_types_for_download', array( 'csv', 'xlsx' ) );
			if ( ! in_array( $file_type, $allowed_file_types_for_download, true ) ) {
				die();
			}
			$file_name = sanitize_file_name( $_GET['wpseefn'] );
			$path      = $this->exports_dir . $file_name . '.' . $file_type;

			if ( ! file_exists( $path ) ) {
				?>
				<h3>WP Sheet Editor</h3>
				<p><?php esc_html_e( 'The exported file does not exist. This could happen for the following reasons:', 'vg_sheet_editor' ); ?></p>
				<ol>
					<li>
					<?php
					/* translators: %s: File expiration hours */
					printf( esc_html__( 'If you are trying to download something that was exported several hours ago, the file might have expired. Files expire after %s hours.', 'vg_sheet_editor' ), $this->file_expiration_hours() );
					?>
					</li>
					<li>
					<?php
					/* translators: %s: Exports directory path */
					printf( esc_html__( 'Maybe your server did not allow us to create the file. It happens if your server is not configured correctly and our folder %s does not have write permissions, so we\'re not able to create the file. You should ask your hosting provider to fix the folder permissions.', 'vg_sheet_editor' ), str_replace( wp_normalize_path( ABSPATH ), '/', $this->exports_dir ) );
					?>
					</li>
				</ol>
				<?php
				die();
			}

			if ( 'xlsx' === $file_type ) {
				header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
				header( "Content-Disposition: attachment; filename=\"$file_name.xlsx\"" );
			} elseif ( 'csv' === $file_type ) {
				// output headers so that the file is downloaded rather than displayed
				header( 'Content-type: text/csv' );
				header( "Content-disposition: attachment; filename = $file_name.csv" );
			}

			do_action( 'vg_sheet_editor/csv/before_download_file', $path, $file_name, $file_type );
			VGSE()->helpers->readfile_chunked( $path );
			die();
		}

		function maybe_create_directories() {
			$directories = array( $this->imports_dir, $this->exports_dir, $this->long_lived_dir, $this->uploads_dir );
			foreach ( $directories as $directory ) {
				$directory = trailingslashit( $directory );
				if ( ! is_dir( $directory ) ) {
					wp_mkdir_p( $directory );
				}
				if ( ! file_exists( $directory . 'index.html' ) ) {
					file_put_contents( $directory . 'index.html', '' );
				}
				if ( ! file_exists( $directory . '.htaccess' ) ) {
					file_put_contents( $directory . '.htaccess', 'deny from all' );
				}
			}
		}

		function delete_old_csvs() {
			$last_check = get_option( 'vgse_last_csv_purge_check' );
			if ( $last_check && ( time() - $last_check < 24 * 60 * 60 ) ) {
				return;
			}
			update_option( 'vgse_last_csv_purge_check', time() );
			$files = array_merge( VGSE()->helpers->get_files_list( $this->imports_dir, '.csv' ), VGSE()->helpers->get_files_list( $this->exports_dir, '.csv' ) );
			if ( empty( $files ) ) {
				return;
			}
			$deleted_files = array();

			foreach ( $files as $file ) {
				// Delete csv files older than X hours to avoid deleting exports in progress.
				$expiration_hours = (int) $this->file_expiration_hours();
				if ( file_exists( $file ) && ( time() - filemtime( $file ) > $expiration_hours * 3600 ) ) {
					$deleted_files[ $file ] = gmdate( 'Y-m-d H:i:s', filemtime( $file ) );
					wp_delete_file( $file );
				}
			}
		}

		function count_rows_in_file( $file_path, $separator ) {
			if ( ! file_exists( $file_path ) ) {
				return 0;
			}
			$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

			$headers = fgetcsv( $handle, 0, $separator, '"', '\\' );
			if ( ! $headers ) {
				return 0;
			}

			if ( strpos( $headers[0], 'sep=' ) !== false ) {
				$headers = fgetcsv( $handle, 0, $separator, '"', '\\' );
			}

			$count = 0;
			while ( $line = fgetcsv( $handle, 0, $separator, '"', '\\' ) ) {

				if ( count( $headers ) > count( $line ) ) {
					$line = array_merge( $line, array_fill( 0, count( $headers ) - count( $line ), '' ) );
				}
				if ( count( $headers ) !== count( $line ) && VGSE_DEBUG ) {
					continue;
				}
				++$count;
			}

			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return $count;
		}

		function replace_in_file( $path, $string, $replace ) {
			if ( ! file_exists( $path ) ) {
				return false;
			}
			$file = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

			$tmp_file_path = $this->imports_dir . sanitize_file_name( 'tmp-' . gmdate( 'Y-m-d-H-i-s' ) . '-' . wp_generate_password( 10, false ) ) . '.csv';
			file_put_contents( $tmp_file_path, '' );

			if ( is_resource( $file ) ) {
				while ( feof( $file ) === false ) {
					file_put_contents( $tmp_file_path, str_replace( $string, $replace, fgets( $file ) ), FILE_APPEND );
				}

				fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			}

			wp_delete_file( $path );

			return rename( $tmp_file_path, $path );
		}

		function get_separator( $file_path, $separator = ',' ) {
			if ( ! file_exists( $file_path ) ) {
				return $separator;
			}
			$handle  = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$headers = fgetcsv( $handle, 0, $separator, '"', '\\' );
			if ( ! $headers ) {
				return $separator;
			}

			if ( strpos( $headers[0], 'sep=' ) !== false ) {
				$headers = fgetcsv( $handle, 0, $separator, '"', '\\' );
			}

			if ( count( $headers ) === 1 ) {
				$separator = $this->_detect_csv_delimiter( $handle );
			}
			return $separator;
		}

		function get_rows( $file_path, $separator = ',', $decode_quotes = false, $per_page = null, $start_position = 0 ) {
			$out = array(
				'rows'          => array(),
				'file_position' => 0,
				'headers'       => array(),
			);
			if ( ! file_exists( $file_path ) ) {
				return $out;
			}

			if ( $decode_quotes ) {
				$this->replace_in_file( $file_path, '&quot;', '"' );
			}
			$handle  = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$headers = fgetcsv( $handle, 0, $separator, '"', '\\' );

			if ( isset( $headers[0] ) && strpos( $headers[0], 'sep=' ) !== false ) {
				$headers = fgetcsv( $handle, 0, $separator, '"', '\\' );
			}

			$headers = array_map( 'trim', $headers );
			// Remove BOM signature from the first item.
			if ( isset( $headers[0] ) ) {
				$headers[0] = $this->remove_utf8_bom( $headers[0] );
				// fgetcsv doesn't handle the quotes correctly when the BOM is present, so we trim them manually after removing the BOM.
				$headers[0] = trim( $headers[0], '"' );
			}
			$out['headers'] = $headers;

			if ( $start_position ) {
				fseek( $handle, $start_position );
			}
			if ( ! $per_page ) {
				$per_page = PHP_INT_MAX;
			}

			while ( count( $out['rows'] ) < $per_page && $line = fgetcsv( $handle, 0, $separator, '"', '\\' ) ) {

				if ( count( $headers ) > count( $line ) ) {
					$line = array_merge( $line, array_fill( 0, count( $headers ) - count( $line ), '' ) );
				}
				if ( count( $headers ) < count( $line ) ) {
					$headers = array_merge( $headers, array_fill( 0, count( $line ) - count( $headers ), '' ) );
				}
				if ( count( $headers ) !== count( $line ) && VGSE_DEBUG ) {
					var_dump( '$headers', $headers, '$line', $line );
					die();
				}
				$out['rows'][] = array_combine( $headers, $line );
			}
			$out['file_position'] = ftell( $handle );

			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return $out;
		}

		function prepare_json_import( $settings ) {

			if ( ! isset( $settings['data'] ) ) {
				return new WP_Error( 'wpse', esc_html__( 'Missing required field "data".', 'vg_sheet_editor' ) );
			}

			$out = array(
				'rows'          => $settings['data'],
				'total'         => $settings['total_rows'],
				'file_position' => 0, // Added only to avoid warnings in places coded for CSV import, not really used for anything
			);
			return $out;
		}

		/**
		 * Remove UTF-8 BOM signature.
		 *
		 * @param  string $string String to handle.
		 * @return string
		 */
		protected function remove_utf8_bom( $string ) {
			if ( 'efbbbf' === substr( bin2hex( $string ), 0, 6 ) ) {
				$string = substr( $string, 3 );
			}

			return $string;
		}

		function _detect_csv_delimiter( $fh ) {
			$delimiters = array( "\t", ';', '|', ',' );
			$data_1     = null;
			$data_2     = array();
			$delimiter  = $delimiters[0];
			foreach ( $delimiters as $d ) {
				$data_1 = fgetcsv( $fh, 4096, $d, '"', '\\' );
				if ( is_array( $data_1 ) && count( $data_1 ) > count( $data_2 ) ) {
					$delimiter = count( $data_1 ) > count( $data_2 ) ? $d : $delimiter;
					$data_2    = $data_1;
				}
				rewind( $fh );
			}

			return $delimiter;
		}

		function prepare_csv_import( $settings ) {

			$separator     = ( empty( $settings['separator'] ) ) ? ',' : sanitize_text_field( $settings['separator'] );
			$decode_quotes = ( empty( $settings['decode_quotes'] ) ) ? false : true;
			$file_path     = $this->imports_dir . $settings['import_file'];
			$per_page      = ( ! empty( $settings['per_page'] ) ) ? (int) $settings['per_page'] : 4;
			$file_position = ! empty( $settings['file_position'] ) ? (int) $settings['file_position'] : 0;
			$separator     = $this->get_separator( $file_path, $separator );
			$start_row     = ( ! empty( $settings['start_row'] ) ) ? (int) $settings['start_row'] : 0;

			// If we are skipping rows (start from a row > 0), we retrieve the skipped rows to get the file position
			// and reset the file position, so the normal import starts from there.
			if ( $start_row > 0 && (int) $settings['page'] === 1 ) {
				$rows_to_skip  = $this->get_rows( $file_path, $separator, $decode_quotes, $start_row - 1, 0 );
				$file_position = $rows_to_skip['file_position'];
				unset( $rows_to_skip );
			}

			if ( empty( $settings['total_rows'] ) ) {
				$total = $this->count_rows_in_file( $file_path, $separator );
			} else {
				$total = (int) $settings['total_rows'];
			}
			$file_content = $this->get_rows( $file_path, $separator, $decode_quotes, $per_page, $file_position );

			$out = array(
				'rows'          => $file_content['rows'],
				'total'         => $total,
				'file_position' => $file_content['file_position'],
			);
			return apply_filters( 'vg_sheet_editor/csv/file_data', $out, $file_path, $settings );
		}

		function import_csv() {
			if ( ! empty( $_REQUEST['form_data'] ) ) {
				$_REQUEST = array_merge( json_decode( wp_unslash( $_REQUEST['form_data'] ), true ), $_REQUEST );
			}
			$required_fields = array(
				'nonce',
				'post_type',
				'page',
				'sheet_editor_column',
				'source_column',
				'writing_type',
				'import_type',
				'total_rows',
				'vgse_plain_mode',
				'vgse_import',
			);

			foreach ( $required_fields as $required_field ) {
				if ( empty( $_REQUEST[ $required_field ] ) ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Missing required field. Please start the process again.', 'vg_sheet_editor' ) ) );
				}
			}
			if ( ! in_array( $_REQUEST['writing_type'], array( 'both', 'all_new', 'only_new', 'only_update' ) ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Writing type not allowed.', 'vg_sheet_editor' ) ) );
			}

			if ( ! VGSE()->helpers->verify_nonce_from_request() || ! VGSE()->helpers->verify_sheet_permissions_from_request( 'edit' ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Not allowed.', 'vg_sheet_editor' ) ) );
			}
			$settings = array(
				'nonce'                        => sanitize_text_field( VGSE()->helpers->get_nonce_from_request() ),
				'post_type'                    => VGSE()->helpers->sanitize_table_key( wp_unslash( $_REQUEST['post_type'] ) ),
				'page'                         => intval( $_REQUEST['page'] ),
				'total_rows'                   => intval( $_REQUEST['total_rows'] ),
				'per_page'                     => intval( $_REQUEST['per_page'] ),
				'start_row'                    => intval( $_REQUEST['start_row'] ),
				'decode_quotes'                => ! empty( $_REQUEST['decode_quotes'] ),
				'remember_column_mapping'      => ! empty( $_REQUEST['remember_column_mapping'] ),
				'pending_post_if_image_failed' => ! empty( $_REQUEST['pending_post_if_image_failed'] ),
				'skip_broken_images'           => ! empty( $_REQUEST['skip_broken_images'] ),
				'auto_column_names'            => ! empty( $_REQUEST['auto_column_names'] ),
				'separator'                    => sanitize_text_field( wp_unslash( $_REQUEST['separator'] ) ),
				'sheet_editor_column'          => wp_unslash( array_map( 'sanitize_text_field', $_REQUEST['sheet_editor_column'] ) ),
				'source_column'                => wp_unslash( array_map( 'sanitize_text_field', $_REQUEST['source_column'] ) ),
				'writing_type'                 => sanitize_text_field( wp_unslash( $_REQUEST['writing_type'] ) ),
				'existing_check_csv_field'     => array_map( 'sanitize_text_field', $_REQUEST['existing_check_csv_field'] ),
				'existing_check_wp_field'      => array_map( 'sanitize_text_field', $_REQUEST['existing_check_wp_field'] ),
				'import_file'                  => sanitize_file_name( $_REQUEST['import_file'] ),
				'vgse_plain_mode'              => sanitize_text_field( wp_unslash( $_REQUEST['vgse_plain_mode'] ) ),
				'import_type'                  => sanitize_text_field( wp_unslash( $_REQUEST['import_type'] ) ),
				'wpse_job_id'                  => sanitize_text_field( VGSE()->helpers->get_job_id_from_request() ),
				'wpse_source_suffix'           => sanitize_text_field( wp_unslash( $_REQUEST['wpse_source_suffix'] ) ),
				'source'                       => sanitize_text_field( wp_unslash( $_REQUEST['source'] ) ),
				'file_position'                => isset( $_REQUEST['file_position'] ) ? intval( $_REQUEST['file_position'] ) : 0,
				'name'                         => isset( $_REQUEST['name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) : '',
				'dynamic_columns'              => isset( $_REQUEST['dynamic_columns'] ) ? wp_unslash( $_REQUEST['dynamic_columns'] ) : array(),

			);
			$out = $this->import_data( apply_filters( 'vg_sheet_editor/csv/import/params', $settings ) );

			if ( is_wp_error( $out ) ) {
				wp_send_json_error( array_merge( array( 'message' => $out->get_error_message() ), (array) $out->get_error_data() ) );
			}

			wp_send_json_success( $out );
		}

		function add_ids_to_rows_with_lookup( $rows, $post_type, $check_wp_fields, $writing_type ) {

			$nonce         = wp_create_nonce( 'bep-nonce' );
			$all_meta_keys = VGSE()->helpers->get_all_meta_keys( $post_type );
			foreach ( $rows as $row_index => $row ) {
				$search_args = array_filter( array_intersect_key( $row, array_combine( $check_wp_fields, array_fill( 0, count( $check_wp_fields ), '' ) ) ) );

				$meta_query = array(
					'meta_query' => array(),
				);
				// If the row has all the wp fields required for the search and they're not empty, make the search
				$rows[ $row_index ]['ID'] = null;
				if ( count( $search_args ) === count( $check_wp_fields ) && ! empty( $check_wp_fields ) ) {
					foreach ( $check_wp_fields as $field_key ) {
						// Allow to search by post name for the update
						if ( $field_key === 'post_name__in' && ! empty( $row[ $field_key ] ) ) {
							$search_value      = basename( $row[ $field_key ] );
							$field_key         = 'post_name';
							$row[ $field_key ] = $search_value;
						}
						$meta_query['meta_query'][] = array(
							'key'     => $field_key,
							'value'   => $row[ $field_key ],
							'source'  => in_array( $field_key, $all_meta_keys ) ? 'meta' : 'post_data',
							'compare' => '=',
						);
					}
					$found_post_id = apply_filters( 'vg_sheet_editor/import/find_post_id', null, $row, $post_type, $meta_query, $writing_type, $check_wp_fields );
					if ( is_null( $found_post_id ) ) {
						$find_row_args       = apply_filters(
							'vg_sheet_editor/import/find_post_id_args',
							array(
								'nonce'              => $nonce,
								'post_type'          => $post_type,
								'return_raw_results' => true,
								'wp_query_args'      => array(
									'posts_per_page' => 1,
									'fields'         => 'ids',
								),
								'filters'            => wp_json_encode( $meta_query ),
								'wpse_source'        => 'load_rows',
							)
						);
						$_REQUEST['filters'] = $find_row_args['filters'];
						$found               = VGSE()->helpers->get_rows( $find_row_args );

						if ( is_array( $found ) && ! empty( $found[0] ) && is_numeric( $found[0] ) ) {
							$found_post_id = $found[0];
						}
					}

					// The find_post_id filter can return a single ID or array of IDs (in case we use a wp_check field
					// that uses a search to match existing rows, so we might need to update multiple IDs
					// In this case we duplicate the import row for every ID found
					if ( ! empty( $found_post_id ) ) {
						if ( is_int( $found_post_id ) ) {
							$rows[ $row_index ]['ID'] = $found_post_id;
						} elseif ( is_array( $found_post_id ) ) {
							unset( $rows[ $row_index ] );
							foreach ( $found_post_id as $found_post_id_single ) {
								$row['ID'] = $found_post_id_single;
								$rows[]    = $row;
							}
						}
						if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
							/* translators: %s: Found ID */
							WPSE_Logger_Obj()->entry( sprintf( esc_html__( 'Found existing ID for the update: %d', 'vg_sheet_editor' ), $found_post_id ), sanitize_text_field( $settings['wpse_job_id'] ) );
						}
					}
				}
			}
			// We used this inside the previous foreach to make the advanced search work with get_rows()
			if ( isset( $_REQUEST['filters'] ) ) {
				unset( $_REQUEST['filters'] );
			}
			return $rows;
		}

		function import_completed( $settings, $total, $processed ) {
			$writing_type             = $settings['writing_type'];
			$global_skipped_rows      = array_filter( array_unique( explode( ',', get_option( 'vgse_impskipped_' . $settings['wpse_job_id'], '' ) ) ) );
			$delete_file_after_import = true;

			if ( $writing_type === 'only_update' ) {
				/* translators: %s: Skipped rows */
				WPSE_Logger_Obj()->entry( sprintf( esc_html__( 'The import is configured to only update existing rows in WordPress. All the rows that did not exist in WordPress and were skipped: %s', 'vg_sheet_editor' ), implode( ', ', $global_skipped_rows ) ), sanitize_text_field( $settings['wpse_job_id'] ) );
			} elseif ( $writing_type === 'only_new' ) {
				/* translators: %s: Skipped rows */
				WPSE_Logger_Obj()->entry( sprintf( esc_html__( 'The import is configured to only create rows that don\'t exist in WordPress already. Rows that exist in WordPress and were skipped: %s', 'vg_sheet_editor' ), implode( ', ', $global_skipped_rows ) ), sanitize_text_field( $settings['wpse_job_id'] ) );
			}

			// Delete import file after the import finished when it was a manual import
			if ( $delete_file_after_import && $total === $processed && ! empty( $settings['source'] ) && in_array( $settings['source'], array( 'csv_upload', 'paste', 'csv_url' ), true ) && file_exists( $this->imports_dir . $settings['import_file'] ) ) {
				wp_delete_file( $this->imports_dir . $settings['import_file'] );
			}

			delete_option( 'vgse_impskipped_' . $settings['wpse_job_id'] );

			if ( ! empty( $settings['name'] ) && VGSE()->helpers->user_can_manage_options() ) {
				$previous_imports = get_option( 'vgse_previous_imports', array() );
				$hash_parts       = array(
					'existing_check_csv_field' => $settings['existing_check_csv_field'],
					'existing_check_wp_field'  => $settings['existing_check_wp_field'],
					'source_column'            => $settings['source_column'],
					'sheet_editor_column'      => $settings['sheet_editor_column'],
					'writing_type'             => $settings['writing_type'],
				);
				foreach ( $hash_parts as $key => $value ) {
					if ( is_array( $value ) ) {
						sort( $value );
						$hash_parts[ $key ] = $value;
					}
				}

				$hash = md5( json_encode( $hash_parts ) );

				unset( $settings['nonce'], $settings['file_position'], $settings['import_file'], $settings['data'] );
				$settings['timestamp'] = time();
				$settings['author']    = get_current_user_id();

				$previous_imports[ sanitize_title( $settings['name'] ) ] = $settings;
				update_option( 'vgse_previous_imports', $previous_imports, false );
			}
		}

		function replace_variables( $string, $row ) {
			return preg_replace_callback(
				'/\$([^$]+)\$/',
				function ( $matches ) use ( $row ) {
					$col_name = trim( $matches[1] );
					return isset( $row[ $col_name ] ) ? $row[ $col_name ] : '';
				},
				$string
			);
		}

		function maybe_preprocess_dynamic_columns( $rows, $settings ) {
			if ( empty( $settings['dynamic_columns'] ) || ! is_array( $settings['dynamic_columns'] ) ) {
				return $rows;
			}
			if ( ! class_exists( '\MathParser\StdMathParser' ) && file_exists( VGSE_DIR . '/vendor/math-parser/vendor/autoload.php' ) ) {
				require_once VGSE_DIR . '/vendor/math-parser/vendor/autoload.php';
			}

			foreach ( $rows as $row_index => &$row ) { // Use reference to modify
				foreach ( $settings['dynamic_columns'] as $dynamic_column ) {
					if ( empty( $dynamic_column['name'] ) || empty( $dynamic_column['type'] ) ) {
						continue;
					}

					$column_name  = trim( sanitize_text_field( $dynamic_column['name'] ) );
					$column_value = '';

					switch ( $dynamic_column['type'] ) {
						case 'set_value':
							$column_value = $this->replace_variables( $dynamic_column['set_value'], $row );
							break;
						case 'text_replace':
							$find          = $dynamic_column['replace_this'];
							$replace       = $this->replace_variables( $dynamic_column['with_this'], $row );
							$source_column = $dynamic_column['replace_source_column'];
							$subject       = '';
							if ( ! empty( $source_column ) && isset( $row[ $source_column ] ) ) {
								$subject = $row[ $source_column ];
							}
							if ( ! empty( $subject ) ) {
								if ( strpos( $find, '/' ) === 0 ) { // Regex
									try {
										$column_value = preg_replace( $find, $replace, $subject );
									} catch ( \Exception $e ) {
										$column_value = $subject; // On error, return original value
									}
								} else {
									$column_value = str_replace( $find, $replace, $subject );
								}
							} else {
								$column_value = $replace;
							}
							break;
						case 'combine_columns':
							$values = array();
							if ( ! empty( $dynamic_column['combine_columns'] ) && is_array( $dynamic_column['combine_columns'] ) ) {
								foreach ( $dynamic_column['combine_columns'] as $col_to_combine ) {
									if ( isset( $row[ $col_to_combine ] ) ) {
										$values[] = $row[ $col_to_combine ];
									}
								}
							}
							$separator    = isset( $dynamic_column['separator'] ) ? $dynamic_column['separator'] : ', ';
							$column_value = implode( $separator, $values );
							break;
						case 'math_calculation':
							if ( ! class_exists( '\MathParser\StdMathParser' ) ) {
								require_once vgse_formulas_init()->plugin_dir . '/vendor/autoload.php';
							}
							if ( ! empty( $dynamic_column['formula'] ) && class_exists( '\MathParser\StdMathParser' ) ) {
								$formula = $this->replace_variables( $dynamic_column['formula'], $row );
								$formula = preg_replace( '/[^0-9\.\+\-\*\/\(\) ]/', '', $formula );
								if ( $formula ) {
									try {
										$parser       = new \MathParser\StdMathParser();
										$ast          = $parser->parse( $formula );
										$evaluator    = new \MathParser\Interpreting\Evaluator();
										$column_value = $ast->accept( $evaluator );
									} catch ( \Exception $e ) {
										$column_value = '';
									}
								}
							}
							break;
						case 'php_preprocessing':
							if ( VGSE()->helpers->user_can_manage_options() && ! empty( $dynamic_column['php_function'] ) ) {
								$function_name = $dynamic_column['php_function'];
								if ( function_exists( $function_name ) ) {
									try {
										$result = call_user_func( $function_name, $row );
										if ( is_string( $result ) || is_numeric( $result ) ) {
											$column_value = (string) $result;
										} else {
											$column_value = '';
										}
									} catch ( \Exception $e ) {
										$column_value = '';
									}
								}
							}
							break;
					}
					$row[ $column_name ] = $column_value;
				}
			}
			unset( $row ); // break reference

			$rows = apply_filters( 'vg_sheet_editor/import/maybe_preprocess_dynamic_columns', $rows, $settings );
			return $rows;
		}

		function import_data( $settings ) {
			$this->is_import_running = true;
			$post_type               = $settings['post_type'];
			$writing_type            = $settings['writing_type'];
			$per_page                = ( empty( $settings['per_page'] ) && ! empty( VGSE()->options ) && ! empty( VGSE()->options['be_posts_per_page_save'] ) ) ? (int) VGSE()->options['be_posts_per_page_save'] : (int) $settings['per_page'];

			if ( empty( $per_page ) ) {
				$per_page = 8;
			}

			$settings['per_page'] = $per_page;

			$editor                  = VGSE()->helpers->get_provider_editor( $post_type );
			VGSE()->current_provider = $editor->provider;

			if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
				WPSE_Logger_Obj()->entry( esc_html__( 'Starting import batch', 'vg_sheet_editor' ), sanitize_text_field( $settings['wpse_job_id'] ) );
			}
			if ( in_array( $settings['import_type'], array( 'xlsx', 'xls' ), true ) ) {
				$excel_file              = $this->imports_dir . $settings['import_file'];
				$file_type               = strtolower( pathinfo( $excel_file, PATHINFO_EXTENSION ) );
				$settings['import_file'] = basename( $this->convert_excel_to_csv( $excel_file, $file_type ) );
				$settings['import_type'] = 'csv';
			}
			if ( $settings['import_type'] === 'csv' ) {
				$prepared_rows = $this->prepare_csv_import( $settings );
			} elseif ( $settings['import_type'] === 'json' ) {
				$prepared_rows = $this->prepare_json_import( $settings );
			}

			// If new import, reset the skipped rows tracker
			if ( empty( $settings['file_position'] ) ) {
				update_option( 'vgse_impskipped_' . $settings['wpse_job_id'], '', false );
			}
			$global_skipped_rows = explode( ',', get_option( 'vgse_impskipped_' . $settings['wpse_job_id'], '' ) );

			$rows = $prepared_rows['rows'];

			$rows = $this->maybe_preprocess_dynamic_columns( $rows, $settings );

			// Remove empty rows before saving
			foreach ( $rows as $index => $row ) {
				$non_empty_values = array_filter( $row );
				if ( empty( $non_empty_values ) ) {
					unset( $rows[ $index ] );
				}
			}

			$total           = $prepared_rows['total'];
			$processed       = ( $per_page >= $total || ( $per_page * $settings['page'] ) >= $total ) ? $total : $per_page * $settings['page'];
			$check_wp_fields = apply_filters( 'vg_sheet_editor/import/existing_check_wp_field', array_filter( $settings['existing_check_wp_field'] ), $settings, $prepared_rows );

			if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
				/* translators: %d: Rows count */
				WPSE_Logger_Obj()->entry( sprintf( esc_html__( '%d rows will be processed in this batch', 'vg_sheet_editor' ), count( $rows ) ), sanitize_text_field( $settings['wpse_job_id'] ) );
			}

			$out = array(
				'message'        => null,
				'updated'        => 0,
				'created'        => 0,
				'total'          => (int) $total,
				'processed'      => (int) $processed,
				'file_position'  => $prepared_rows['file_position'],
				// For the automation plugin
				'processed_rows' => (int) $processed,
			);
			if ( empty( $rows ) ) {
				if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
					WPSE_Logger_Obj()->entry( esc_html__( 'The import is complete.', 'vg_sheet_editor' ), sanitize_text_field( $settings['wpse_job_id'] ) );
				}
				$out['message']        = esc_html__( 'The import is completed.', 'vg_sheet_editor' );
				$out['force_complete'] = true;
				do_action( 'vg_sheet_editor/import/completed', $out, $settings );

				$this->import_completed( $settings, $total, $processed );

				$this->is_import_running = false;
				return $out;
			}
			if ( is_wp_error( $prepared_rows ) ) {
				if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
					/* translators: %s: Error message */
					WPSE_Logger_Obj()->entry( sprintf( esc_html__( 'This batch failed with error: %s', 'vg_sheet_editor' ), $prepared_rows->get_error_message() ), sanitize_text_field( $settings['wpse_job_id'] ) );
				}
				$this->is_import_running = false;
				return $prepared_rows;
			}

			// Prepare headers mapping
			$csv_headers         = array_keys( current( $rows ) );
			$sheet_editor_column = $settings['sheet_editor_column'];
			$final_headers_map   = array();
			foreach ( $csv_headers as $header ) {
				$map_index                    = array_search( $header, $settings['source_column'] );
				$final_headers_map[ $header ] = ( $map_index !== false && isset( $sheet_editor_column[ $map_index ] ) ) ? $sheet_editor_column[ $map_index ] : $header;
			}

			// Save column mapping
			if ( ! empty( $settings['remember_column_mapping'] ) ) {
				$saved_column_mappings = get_option( $this->column_mappings_key, array() );
				if ( empty( $saved_column_mappings ) || ! is_array( $saved_column_mappings ) ) {
					$saved_column_mappings = array();
				}
				if ( ! isset( $saved_column_mappings[ $post_type ] ) ) {
					$saved_column_mappings[ $post_type ] = array();
				}
				$saved_column_mappings[ $post_type ] = wp_parse_args( $final_headers_map, $saved_column_mappings[ $post_type ] );
				update_option( $this->column_mappings_key, $saved_column_mappings, false );
			}

			$use_mb = function_exists( 'mb_convert_encoding' );
			foreach ( $rows as $row_index => $row ) {
				foreach ( $row as $column_key => $column_value ) {
					// Remove string line breaks because we only accept real line breaks
					$column_value = str_replace( '\n', '', $column_value );

					// Convert UTF8.
					if ( $use_mb ) {
						$encoding = mb_detect_encoding( $column_value, mb_detect_order(), true );
						if ( $encoding ) {
							$column_value = mb_convert_encoding( $column_value, 'UTF-8', $encoding );
						} else {
							$column_value = mb_convert_encoding( $column_value, 'UTF-8', 'UTF-8' );
						}
					} else {
						$column_value = wp_check_invalid_utf8( $column_value, true );
					}

					$new_column_key = $final_headers_map[ $column_key ];
					if ( $column_key !== $new_column_key ) {
						// Rename header according to the columns mapping
						$rows[ $row_index ][ $new_column_key ] = $column_value;
						unset( $rows[ $row_index ][ $column_key ] );
					} else {
						$rows[ $row_index ][ $column_key ] = $column_value;
					}
				}

				if ( ! empty( $rows[ $row_index ]['id'] ) && empty( $rows[ $row_index ]['ID'] ) ) {
					$rows[ $row_index ]['ID'] = $rows[ $row_index ]['id'];
				}
			}
			if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
				WPSE_Logger_Obj()->entry( esc_html__( 'Preparing the information for saving based on the column mapping settings and decode invalid characters', 'vg_sheet_editor' ), sanitize_text_field( $settings['wpse_job_id'] ) );
			}

			// If we don't have fields to find existing rows, but we are importing IDs, we auto set the ID as check_wp_field
			if ( empty( $check_wp_fields ) && in_array( $writing_type, array( 'both', 'only_update' ), true ) && array_intersect( $sheet_editor_column, array( 'id', 'ID' ) ) ) {
				$check_wp_fields = array( 'ID' );
			}

			// If we don't have fields to find existing rows, we create everything as new
			if ( empty( $check_wp_fields ) ) {
				$writing_type = 'all_new';
			}

			$rows = apply_filters( 'vg_sheet_editor/import/rows_before_find_existing_id', $rows, $post_type, $check_wp_fields, $writing_type );

			if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
				WPSE_Logger_Obj()->entry( esc_html__( 'Finding existing rows to update.', 'vg_sheet_editor' ), sanitize_text_field( $settings['wpse_job_id'] ) );
			}

			// If writing_type says all rows are new posts
			$id_value_for_new_rows = taxonomy_exists( $post_type ) ? PHP_INT_MAX : null;
			if ( $writing_type === 'all_new' ) {
				foreach ( $rows as $row_index => $row ) {
					$rows[ $row_index ]['ID'] = $id_value_for_new_rows;
				}
			} else {
				$rows = $this->add_ids_to_rows_with_lookup( $rows, $post_type, $check_wp_fields, $writing_type );

				if ( $writing_type === 'only_update' ) {
					$rows_not_updated = wp_list_filter( $rows, array( 'ID' => $id_value_for_new_rows ) );
					$rows             = wp_list_filter( $rows, array( 'ID' => $id_value_for_new_rows ), 'NOT' );
					if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
						$not_updated_indices = array_keys( $rows_not_updated );
						$relative_indices    = array_map(
							function ( $index ) use ( $settings ) {
								return $index + 1 + ( $settings['page'] - 1 ) * (int) $settings['per_page'];
							},
							$not_updated_indices
						);
						if ( empty( $relative_indices ) ) {
							$relative_indices = array( 0 );
						}
						$global_skipped_rows = array_merge( $global_skipped_rows, $relative_indices );
						// WPSE_Logger_Obj()->entry( sprintf( 'The import is configured to only update existing rows in WordPress. Rows that did not exist in WordPress and were skipped: %s', implode( ', ', $relative_indices ) ), sanitize_text_field( $settings['wpse_job_id'] ) );
					}
				} elseif ( $writing_type === 'only_new' ) {
					$rows_not_created = wp_list_filter( $rows, array( 'ID' => $id_value_for_new_rows ), 'NOT' );
					$rows             = wp_list_filter( $rows, array( 'ID' => $id_value_for_new_rows ) );
					if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
						$not_created_indices = array_keys( $rows_not_created );
						$relative_indices    = array_map(
							function ( $index ) use ( $settings ) {
								return $index + 1 + ( $settings['page'] - 1 ) * (int) $settings['per_page'];
							},
							$not_created_indices
						);
						if ( empty( $relative_indices ) ) {
							$relative_indices = array( 0 );
						}
						$global_skipped_rows = array_merge( $global_skipped_rows, $relative_indices );
						// WPSE_Logger_Obj()->entry( sprintf( 'The import is configured to only create rows that don\'t exist in WordPress already. Rows that exist in WordPress and were skipped: %s', implode( ', ', $relative_indices ) ), sanitize_text_field( $settings['wpse_job_id'] ) );
					}
				}
			}

			update_option( 'vgse_impskipped_' . $settings['wpse_job_id'], implode( ',', $global_skipped_rows ), false );

			// If writing_type allows to create (either only new or both)
			$created       = count( wp_list_filter( $rows, array( 'ID' => $id_value_for_new_rows ) ) );
			$total_updated = count( $rows ) - $created;

			if ( ! empty( VGSE()->options['allow_ftp_images_support'] ) ) {
				if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
					WPSE_Logger_Obj()->entry( esc_html__( 'Enabled functionality to save FTP images because it was enabled in the advanced settings', 'vg_sheet_editor' ), sanitize_text_field( $settings['wpse_job_id'] ) );
				}

				add_filter( 'vg_sheet_editor/save/url_to_file_id', array( $this, 'import_ftp_images' ), 10, 3 );
				$settings['skip_broken_images'] = 'yes';
			}

			$this->current_import_settings = $settings;
			if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
				WPSE_Logger_Obj()->entry( esc_html__( 'Start saving', 'vg_sheet_editor' ), sanitize_text_field( $settings['wpse_job_id'] ) );
			}
			$save_result = VGSE()->helpers->save_rows(
				apply_filters(
					'vg_sheet_editor/import/save_rows_args',
					array(
						'data'                 => VGSE()->helpers->sanitize_data_for_db( $rows, $post_type ),
						'post_type'            => $post_type,
						'allow_to_create_new'  => true,
						'wpse_source'          => 'import',
						'wpse_import_settings' => $settings,
					)
				)
			);
			if ( is_wp_error( $save_result ) ) {
				if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
					/* translators: %s: Error message */
					WPSE_Logger_Obj()->entry( sprintf( esc_html__( 'Error found while saving: %s', 'vg_sheet_editor' ), $save_result->get_error_message() ), sanitize_text_field( $settings['wpse_job_id'] ) );
				}
				$this->is_import_running = false;
				return $save_result;
			}

			$out = array_merge(
				$out,
				array(
					/* translators: %1$d: Number of processed items, %2$d: Total number of items in the file, {total_updated}: Number of updated items, {total_created}: Number of created items */
					'message' => '<p>' . sprintf( esc_html__( '%1$d of %2$d items have been processed from the file. {total_updated} items updated and {total_created} items created.', 'vg_sheet_editor' ), $processed, $total, $total_updated, $created ) . '</p>',
					'updated' => (int) $total_updated,
					'created' => (int) $created,
				)
			);

			if ( function_exists( 'WPSE_Logger_Obj' ) && ! empty( $settings['wpse_job_id'] ) ) {
				$out['log_url'] = WPSE_Logger_Obj()->get_log_download_url( $settings['wpse_job_id'] );
				/* translators: %1$d: Processed count, %2$d: Updated count, %3$d: Created count, %4$d: File position */
				WPSE_Logger_Obj()->entry( sprintf( esc_html__( 'Batch completed successfully. Processed from the file: %1$d, Updated in this batch: %2$d, Created in this batch: %3$d, File position: %4$d', 'vg_sheet_editor' ), $processed, $total_updated, $created, $prepared_rows['file_position'] ), sanitize_text_field( $settings['wpse_job_id'] ) );
			}

			if ( $total === $processed ) {
				$this->import_completed( $settings, $total, $processed );
			}

			$this->is_import_running = false;
			return $out;
		}

		function import_ftp_images( $new_id, $url, $post_id ) {
			$new_id = $this->_get_ftp_file_id( $url, $post_id );
			return $new_id;
		}

		function prepend_to_file( $string, $file_path ) {
			$orig_file     = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$temp_filename = tempnam( sys_get_temp_dir(), 'php_prepend_' );

			file_put_contents( $temp_filename, $string . PHP_EOL );

			while ( ! feof( $orig_file ) ) {
				file_put_contents( $temp_filename, fgets( $orig_file ), FILE_APPEND );
			}

			fclose( $orig_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

			wp_delete_file( $file_path );
			rename( $temp_filename, $file_path );
		}

		function maybe_add_auto_column_names_to_file( $file_path, $separator = ',', $decode_quotes = false ) {

			$file_content = $this->get_rows( $file_path, $separator, $decode_quotes, 1 );
			if ( empty( $file_content['headers'] ) ) {
				return false;
			}
			$first_row     = json_encode( $file_content['headers'] );
			$columns_count = count( $file_content['headers'] );
			/* translators: %d: Column number */
			$column_name = esc_html__( 'Column %d', 'vg_sheet_editor' );
			if ( strpos( $first_row, sprintf( $column_name, 1 ) ) === false ) {
				$new_column_names = array();
				for ( $i = 0; $i < $columns_count; $i++ ) {
					$new_column_names[] = sprintf( $column_name, $i + 1 );
				}
				$this->prepend_to_file( $this->_str_putcsv( $new_column_names ), $file_path );
				return true;
			}
			return false;
		}

		function get_remote_file( $url, $target_file_path = null ) {
			if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
				return new WP_Error( 'wrong_file_url', esc_html__( 'Wrong file url', 'vg_sheet_editor' ) );
			}
			$file_type = strtolower( pathinfo( basename( strtok( $url, '?' ) ), PATHINFO_EXTENSION ) );
			if ( ! in_array( $file_type, vgse_universal_sheet()->get_allowed_file_formats() ) ) {
				return new WP_Error( 'wrong_file_url', esc_html__( 'Wrong file extension. We accept CSV, XLSX, XLS only', 'vg_sheet_editor' ) );
			}
			if ( ! function_exists( 'download_url' ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
			}
			$tmp_file = download_url( $url );

			if ( ! is_wp_error( $tmp_file ) && file_exists( $tmp_file ) && $target_file_path ) {
				rename( $tmp_file, $target_file_path );
			}
			return $tmp_file;
		}

		function upload_data_for_import() {
			if ( empty( $_REQUEST['post_type'] ) || ! isset( $_REQUEST['data'] ) || empty( $_REQUEST['data_type'] ) || ! VGSE()->helpers->verify_nonce_from_request() || ! VGSE()->helpers->verify_sheet_permissions_from_request( 'edit' ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Not allowed. Please start the process again.', 'vg_sheet_editor' ) ) );
			}

			$data_type         = sanitize_text_field( wp_unslash( $_REQUEST['data_type'] ) );
			$post_type         = sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) );
			$separator         = sanitize_text_field( wp_unslash( $_REQUEST['separator'] ) );
			$auto_column_names = ! empty( $_REQUEST['auto_column_names'] );
			if ( empty( $separator ) ) {
				$separator = ',';
			}
			$decode_quotes = ( empty( $_REQUEST['decode_quotes'] ) ) ? false : true;
			$data          = $_REQUEST['data'];

			$base_dir = wp_normalize_path( $this->imports_dir );

			$file_path = $base_dir . sanitize_file_name( $post_type . '-' . gmdate( 'Y-m-d-H-i-s' ) . '-' . wp_generate_password( 10, false ) ) . '.csv';

			$allowed_input_type = vgse_universal_sheet()->get_allowed_file_formats();
			if ( $data_type === 'url' ) {
				$download_response = $this->get_remote_file( $data, $file_path );
				if ( is_wp_error( $download_response ) ) {
					wp_send_json_error( array( 'message' => $download_response->get_error_message() ) );
				}
			} elseif ( $data_type === 'local' ) {

				if ( empty( $_FILES ) ) {
					wp_send_json_error( array( 'message' => esc_html__( 'File could not be uploaded. Please start the process again.', 'vg_sheet_editor' ) ) );
				}
				$file_type = strtolower( pathinfo( basename( strtok( $_FILES['file']['name'], '?' ) ), PATHINFO_EXTENSION ) );

				if ( ! in_array( $file_type, $allowed_input_type ) ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Wrong file extension. We accept CSV, XLSX, XLS only', 'vg_sheet_editor' ) ) );
				}

				if ( file_exists( $_FILES['file']['tmp_name'] ) ) {
					$file_path = $base_dir . sanitize_file_name( $post_type . '-' . gmdate( 'Y-m-d-H-i-s' ) . '-' . wp_generate_password( 10, false ) ) . '.' . $file_type;
					move_uploaded_file( $_FILES['file']['tmp_name'], $file_path );
				}
			} elseif ( $data_type === 'json' ) {
				if ( ! is_array( $data ) ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Wrong data format', 'vg_sheet_editor' ) ) );
				}
				$headers = $data[0];
				unset( $data[0] );

				$filtered = array();
				foreach ( $data as $row ) {
					$only_filled_cells = array_filter( $row );
					if ( ! empty( $only_filled_cells ) ) {
						$filtered[] = $row;
					}
				}

				$this->_array_to_csv( $filtered, $file_path, $this->_str_putcsv( $headers, $separator ), $separator );
			} elseif ( $data_type === 'server_file' ) {
				$file_name = basename( $data );
				$file_path = $base_dir . $file_name;

				if ( ! file_exists( $file_path ) ) {
					/* translators: %s: File path */
					wp_send_json_error( array( 'message' => sprintf( esc_html__( 'The file doesn\'t exist. Invalid path: %s', 'vg_sheet_editor' ), $file_path ) ) );
				}
				$file_type = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
				if ( ! in_array( $file_type, $allowed_input_type ) ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Wrong file extension. We accept CSV, XLSX, XLS only', 'vg_sheet_editor' ) ) );
				}
			}

			do_action( 'vg_sheet_editor/import/prepare_data_for_import', $file_path, $data, $data_type, $post_type, $separator, $decode_quotes );

			if ( ! file_exists( $file_path ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'File could not be uploaded. Please start the process again.', 'vg_sheet_editor' ) ) );
			}
			$file_type = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
			if ( in_array( $file_type, array( 'xlsx', 'xls' ), true ) ) {
				$worksheet_name = ! empty( $_REQUEST['sheet'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sheet'] ) ) : '';
				$file_path      = $this->convert_excel_to_csv( $file_path, $file_type, $worksheet_name );

				if ( is_wp_error( $file_path ) ) {
					wp_send_json_error( array( 'message' => $file_path->get_error_message() ) );
				}
			}

			$separator    = $this->get_separator( $file_path, $separator );
			$total        = $this->count_rows_in_file( $file_path, $separator );
			$file_content = $this->get_rows( $file_path, $separator, $decode_quotes, 5 );
			$first_rows   = $file_content['rows'];

			if ( $auto_column_names ) {
				$added_column_names = $this->maybe_add_auto_column_names_to_file( $file_path, $separator, $decode_quotes );
				if ( $added_column_names ) {
					$total        = $this->count_rows_in_file( $file_path, $separator );
					$file_content = $this->get_rows( $file_path, $separator, $decode_quotes, 5 );
					$first_rows   = $file_content['rows'];
				}
			}

			if ( empty( $first_rows ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'File uploaded succesfully but it\'s not a valid CSV file or it uses the wrong encoding. If you edited the file in Excel, verify it was saved as UTF-8 and keep in mind that, sometimes copy pasting from external places adds invalid characters. So make sure you paste only the values and not paste the formatting to avoid pasting invalid characters.', 'vg_sheet_editor' ) ) );
			}

			$row_headers        = array_map( 'strval', array_keys( current( $first_rows ) ) );
			$translated_headers = array();
			foreach ( $row_headers as $row_header ) {
				$translation1 = __( $row_header, 'woocommerce' );
				$translation2 = __( $row_header, 'vg_sheet_editor' );
				if ( $row_header !== $translation1 ) {
					$translated_headers[] = $translation1;
				} elseif ( $row_header !== $translation2 ) {
					$translated_headers[] = $translation2;
				} else {
					$translated_headers[] = $row_header;
				}
			}
			$out = array(
				'rowHeaders'        => $row_headers,
				'translatedHeaders' => $translated_headers,
				'firstRows'         => array_values( $first_rows ),
				'totalRows'         => $total,
				'fileName'          => basename( $file_path ),
				'fileUrl'           => str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $file_path ),
				'postType'          => $post_type,
				'separator'         => $separator,
			);
			do_action( 'vg_sheet_editor/import/data_uploaded_for_import', $out );
			wp_send_json_success( $out );
		}

		/**
		 * Convert Excel file to CSV.
		 *
		 * @param string      $file_path      Path to the excel file.
		 * @param string      $file_type      File extension (xlsx or xls).
		 * @param string|null $worksheet_name Specific worksheet to convert, otherwise use the first one.
		 * @return string|WP_Error Path to the new CSV file on success, or WP_Error on failure.
		 */
		function convert_excel_to_csv( $file_path, $file_type, $worksheet_name = null ) {
			if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
				return new WP_Error( 'excel_error', esc_html__( 'Excel functionality requires PHP 8.1 or higher. Please ask your hosting provider to upgrade your PHP version.', 'vg_sheet_editor' ) );
			}

			$this->_load_spreadsheet_vendor();
			if ( class_exists( 'PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
				try {
					$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile( $file_path );
					$reader->setReadDataOnly( true );
					$sheet_names = $reader->listWorksheetNames( $file_path );

					if ( count( $sheet_names ) > 1 && empty( $worksheet_name ) ) {
						wp_send_json_success(
							array(
								'sheets'    => $sheet_names,
								'file_name' => basename( $file_path ),
							)
						);
					}

					$sheet_to_load = $worksheet_name ? $worksheet_name : $sheet_names[0];
					$reader->setLoadSheetsOnly( $sheet_to_load );
					$spreadsheet = $reader->load( $file_path );

					$csv_file_path = preg_replace( '/\.' . $file_type . '$/i', '.csv', $file_path );
					$writer        = new \PhpOffice\PhpSpreadsheet\Writer\Csv( $spreadsheet );
					$writer->save( $csv_file_path );

					$file_path = $csv_file_path; // Use the converted CSV file path
					unset( $spreadsheet, $writer, $reader );
					return $file_path;
				} catch ( \Exception $e ) {
					return new WP_Error( 'excel_error', esc_html__( 'Error processing Excel file: ', 'vg_sheet_editor' ) . $e->getMessage() );
				}
			} else {
				return new WP_Error( 'excel_error', esc_html__( 'Excel processing library not found.', 'vg_sheet_editor' ) );
			}
		}

		function get_saved_exports( $post_type ) {

			$saved_exports = get_option( 'vgse_saved_exports' );
			if ( empty( $saved_exports ) ) {
				$saved_exports = array();
			}

			if ( ! isset( $saved_exports[ $post_type ] ) ) {
				$saved_exports[ $post_type ] = array();
			}
			usort(
				$saved_exports[ $post_type ],
				function ( $a, $b ) {
					return strcmp( $a['name'], $b['name'] );
				}
			);

			return $saved_exports[ $post_type ];
		}

		function save_export( $data ) {
			if ( empty( $data['name'] ) ) {
				return;
			}
			$post_type     = $data['post_type'];
			$saved_exports = get_option( 'vgse_saved_exports' );
			if ( empty( $saved_exports ) ) {
				$saved_exports = array();
			}

			if ( ! isset( $saved_exports[ $post_type ] ) ) {
				$saved_exports[ $post_type ] = array();
			}

			$same_name = wp_list_filter( $saved_exports[ $post_type ], array( 'name' => $data['name'] ) );
			foreach ( $same_name as $index => $same_name_export ) {
				unset( $saved_exports[ $post_type ][ $index ] );
			}
			$saved_exports[ $post_type ][] = $data;
			update_option( 'vgse_saved_exports', $saved_exports, false );
		}

		function register_export_data_fields( $settings ) {
			if ( empty( $_REQUEST['vgse_csv_export'] ) ) {
				return $settings;
			}
			$settings['wpse_job_id']              = sanitize_text_field( VGSE()->helpers->get_job_id_from_request() );
			$settings['vgse_csv_export']          = sanitize_text_field( wp_unslash( $_REQUEST['vgse_csv_export'] ) );
			$settings['custom_enabled_columns']   = sanitize_text_field( wp_unslash( $_REQUEST['custom_enabled_columns'] ) );
			$settings['line_items_separate_rows'] = ! empty( $_REQUEST['line_items_separate_rows'] );
			$settings['target_software']          = in_array( $_REQUEST['target_software'], array_keys( vgse_universal_sheet()->get_target_software_options() ), true ) ? sanitize_text_field( wp_unslash( $_REQUEST['target_software'] ) ) : '';
			if ( ! empty( $_REQUEST['save_for_later'] ) && VGSE()->helpers->user_can_manage_options() ) {
				$settings['save_for_later'] = array(
					'name'            => sanitize_text_field( wp_unslash( $_REQUEST['save_for_later']['name'] ) ),
					'columns'         => $settings['custom_enabled_columns'],
					'target_software' => $settings['target_software'],
					'filters'         => $settings['filters'],
					'post_type'       => $settings['post_type'],
				);
			}
			return $settings;
		}

		function export_csv( $out, $wp_query_args, $spreadsheet_columns, $clean_data ) {
			if ( empty( $clean_data['vgse_csv_export'] ) ) {
				return $out;
			}

			if ( ! empty( $clean_data['save_for_later'] ) && VGSE()->helpers->user_can_manage_options() && apply_filters( 'vg_sheet_editor/exports/allow_to_save_for_later', true, $out, $clean_data, $wp_query_args, $spreadsheet_columns ) ) {
				$this->save_export( $clean_data['save_for_later'] );
			}

			$base_dir = $this->exports_dir;
			$csv_file = $base_dir . sanitize_file_name( $clean_data['wpse_job_id'] ) . '.csv';

			$cleaned_rows         = apply_filters( 'vg_sheet_editor/export/pre_cleanup', array_values( $out['rows'] ), $clean_data, $wp_query_args, $spreadsheet_columns );
			$allowed_column_keys  = apply_filters( 'vg_sheet_editor/export/allowed_column_keys', array_keys( $spreadsheet_columns ), $cleaned_rows, $clean_data, $wp_query_args );
			$required_column_keys = array_keys( $spreadsheet_columns );

			// If this is not the first page, we make sure that columns from page 1 are allowed
			if ( (int) $wp_query_args['paged'] > 1 ) {
				$first_rows_raw      = $this->get_rows( $csv_file, ',', false, 3 );
				$first_rows          = $first_rows_raw['rows'];
				$headers_with_labels = wp_list_pluck( $spreadsheet_columns, 'key', 'title' );
				unset( $headers_with_labels['ID'] );
				$headers_with_labels['record_id'] = 'ID';
				$existing_file_keys               = array();

				foreach ( $first_rows[0] as $column_key => $column_value ) {
					$existing_file_keys[] = isset( $headers_with_labels[ $column_key ] ) ? $headers_with_labels[ $column_key ] : $column_key;
				}

				$existing_file_keys = apply_filters( 'vg_sheet_editor/export/existing_file_keys', $existing_file_keys, $first_rows[0], $cleaned_rows, $clean_data, $wp_query_args );

				$allowed_column_keys  = array_unique( array_merge( $allowed_column_keys, $existing_file_keys ) );
				$required_column_keys = array_unique( array_merge( $existing_file_keys, $required_column_keys ) );
			} elseif ( file_exists( $csv_file ) ) {
				// this is a new export if we're exporting the page 1 again, so delete the file
				wp_delete_file( $csv_file );
			}

			foreach ( $cleaned_rows as $row_index => $row ) {

				// Remove all fields from the row that are not found in the list of fields to export
				$row                  = array_intersect_key( $row, array_fill_keys( $allowed_column_keys, '' ) );
				$required_column_keys = array_unique( array_merge( $required_column_keys, array_keys( $row ) ) );

				foreach ( $required_column_keys as $column_key ) {
					if ( ! isset( $row[ $column_key ] ) ) {
						$row[ $column_key ] = '';
					}
					// Convert line breaks to p and br tags before we strip the line breaks
					if ( isset( $spreadsheet_columns[ $column_key ] ) && in_array( $spreadsheet_columns[ $column_key ]['type'], array( 'boton_tiny' ) ) && isset( $row[ $column_key ] ) ) {
						$row[ $column_key ] = wpautop( $row[ $column_key ] );
					}

					// Fields with objects as value aren't compatible, so we export them as an empty string
					if ( is_object( $row[ $column_key ] ) || is_array( $row[ $column_key ] ) ) {
						$row[ $column_key ] = '';
					}

					// Remove line breaks from all values because they dont work well with csv editors
					// Update: Deactivated because some fields require line breaks and Google sheets/excel
					// support line breaks in cells as long as they're properly encoded (which we do)
					if ( empty( VGSE()->options['allow_line_breaks_export_import'] ) ) {
						$row[ $column_key ] = preg_replace( '~[\r\n]+~', '', $row[ $column_key ] );
					} elseif ( strpos( $row[ $column_key ], '\n' ) !== false ) {
						$row[ $column_key ] = str_replace( '\n', PHP_EOL, $row[ $column_key ] );
					}
					// Fields with empty spaces as value are returned as empty string
					$trimmed = trim( $row[ $column_key ] );
					if ( empty( $trimmed ) ) {
						$row[ $column_key ] = $trimmed;
					}
				}
				$cleaned_rows[ $row_index ] = $row;
			}

			// If the column used in the first page of the export, is not found on the next pages,
			// we'll add an empty value. All pages must include the same columns.
			// This also helps sort the columns. All items must have same values in same order.
			$all_column_keys = array_fill_keys( $required_column_keys, '' );
			foreach ( $cleaned_rows as $row_index => $row ) {
				$cleaned_rows[ $row_index ] = array_merge( $all_column_keys, $row );
			}

			$headers_with_labels = wp_list_pluck( $spreadsheet_columns, 'title', 'key' );
			$headers             = array_values( array_merge( array_combine( $required_column_keys, $required_column_keys ), $headers_with_labels ) );

			// Replace ID column with record_id to prevent issue with Excel
			$id_column_index             = array_search( 'ID', $headers );
			$headers[ $id_column_index ] = 'record_id';

			$final_rows    = apply_filters( 'vg_sheet_editor/export/final_rows', $cleaned_rows, $clean_data, $wp_query_args );
			$final_headers = apply_filters( 'vg_sheet_editor/export/final_headers', array_filter( $headers ), $clean_data, $wp_query_args );
			$this->_array_to_csv( $final_rows, $csv_file, $this->_str_putcsv( $final_headers ) );
			$out['rows']          = $cleaned_rows;
			$out['final_headers'] = $final_headers;

			$processed_rows        = ( $out['total'] > ( $wp_query_args['posts_per_page'] * $wp_query_args['paged'] ) ) ? $wp_query_args['posts_per_page'] * $wp_query_args['paged'] : $out['total'];
			$out['processed_rows'] = $processed_rows;
			/* translators: %1$d: Processed rows count, %2$d: Total rows count */
			$out['message'] = sprintf( esc_html__( 'Processing: %1$d of %2$d rows have been exported.', 'vg_sheet_editor' ), $processed_rows, $out['total'] );

			$out['force_complete']   = false;
			$out['export_complete']  = false;
			$out['export_file_name'] = sanitize_file_name( $clean_data['wpse_job_id'] );
			if ( ( $wp_query_args['posts_per_page'] * $wp_query_args['paged'] ) >= $out['total'] ) {
				$file_type = 'csv';
				if ( 'excel' === $clean_data['target_software'] ) {
					$conversion_result = $this->convert_csv_to_excel( $csv_file, ',' );
					$file_type         = $conversion_result['file_type'];
				}

				if ( empty( VGSE()->options['allow_line_breaks_export_import'] ) && 'csv' === $file_type ) {
					$this->remove_duplicates_from_file( $csv_file );
				}
				$out['export_file_url'] = add_query_arg(
					array(
						'wpseefn'        => sanitize_file_name( $clean_data['wpse_job_id'] ),
						'wpse_file_type' => $file_type,
					),
					admin_url( 'index.php' )
				);
				$expiration_hours       = (int) $this->file_expiration_hours();
				$out['message']        .= __( '<br><br>The export finished.<br class="file-download-message-start"><br>The download should start automatically.', 'vg_sheet_editor' );
				if ( VGSE()->helpers->user_can_manage_options() ) {
					/* translators: %d: File expiration hours */
					$out['message'] .= sprintf( __( ' If it doesn\'t start automatically you can find the file in the folder /wp-content/uploads/wp-sheet-editor/exports/ on your server.<br><br>The export files are deleted automatically after %d hours.', 'vg_sheet_editor' ), $expiration_hours );
				}
				$out['force_complete']  = true;
				$out['export_complete'] = true;
			}

			return $out;
		}

		/**
		 * Convert CSV file to Excel (XLSX) format.
		 * @param string $csv_file Path to the CSV file.
		 * @return array|bool|Exception Returns an array with 'xlsx_file_path' and 'file_type' on success, false if PhpSpreadsheet is not available, Exception is thrown if an error occurs.
		 */
		function convert_csv_to_excel( $csv_file, $separator = ',' ) {
			if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
				throw new Exception( esc_html__( 'Excel functionality requires PHP 8.1 or higher. Please ask your hosting provider to upgrade your PHP version.', 'vg_sheet_editor' ) );
			}
			$this->_load_spreadsheet_vendor();
			if ( ! class_exists( 'PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
				return false;
			}
			$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
			$reader->setDelimiter( $separator );
			$spreadsheet    = $reader->load( $csv_file );
			$xlsx_file_path = str_replace( '.csv', '.xlsx', $csv_file );
			$writer         = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
			$writer->save( $xlsx_file_path );
			wp_delete_file( $csv_file );
			$file_type = 'xlsx';
			return compact( 'xlsx_file_path', 'file_type' );
		}

		function file_expiration_hours() {
			return apply_filters( 'vg_sheet_editor/csv/file_expiration_hours', 48 );
		}

		function _str_putcsv( $input, $delimiter = ',', $enclosure = '"' ) {
			// Open a memory "file" for read/write...
			$fp = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			// ... write the $input array to the "file" using fputcsv()...
			fputcsv( $fp, $input, $delimiter, $enclosure, '\\' );
			// ... rewind the "file" so we can read what we just wrote...
			rewind( $fp );
			// ... read the entire line into a variable...
			$data = fread( $fp, 1048576 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			// ... close the "file"...
			fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			// ... and return the $data to the caller, with the trailing newline from fgets() removed.
			return rtrim( $data, "\n" );
		}

		function _array_to_csv( $data, $filepath, $csv_headers = null, $delimiter = ',' ) {
			if ( empty( $data ) ) {
				return false;
			}

			// Create the csv headers if missing
			if ( empty( $csv_headers ) ) {
				$first_row   = current( $data );
				$csv_headers = $this->_str_putcsv( array_keys( $first_row ), $delimiter );
			}
			// If file exists, we update the headers on every update
			// If file not exists, we create empty file with just the csv headers
			if ( file_exists( $filepath ) ) {
				$file = file( $filepath, FILE_IGNORE_NEW_LINES );
			} else {
				$file = array();
			}

			$file[0] = $csv_headers;
			file_put_contents( $filepath, $this->add_utf8_bom( implode( PHP_EOL, array_filter( $file ) ) ) . PHP_EOL );

			// Append the data
			$fp = fopen( $filepath, 'a' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			foreach ( $data as $row ) {
				if ( empty( $row ) || ! is_array( $row ) ) {
					continue;
				}
				fputcsv( $fp, $row, $delimiter, '"', '\\' );
			}

			fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}

		// chr( 239 ) . chr( 187 ) . chr( 191 ) .  Excel requires this to read the utf-8 file properly
		function add_utf8_bom( $text ) {
			// We remove it first to avoid adding it twice
			$text = $this->remove_utf8_bom( $text );
			return chr( 239 ) . chr( 187 ) . chr( 191 ) . $text;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new WPSE_CSV_API();
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

if ( ! function_exists( 'WPSE_CSV_API_Obj' ) ) {

	/**
	 * @return WPSE_CSV_API
	 */
	function WPSE_CSV_API_Obj() {
		return WPSE_CSV_API::get_instance();
	}
}

WPSE_CSV_API_Obj();
