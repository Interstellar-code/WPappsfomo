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

if ( defined( 'WP_CLI' ) && class_exists( 'Ai1wm_Backup_WP_CLI_Base' ) ) {
	class Ai1wmpe_PCloud_WP_CLI_Command extends Ai1wm_Backup_WP_CLI_Base {
		public function __construct() {
			parent::__construct();

			if ( ! get_option( 'ai1wmpe_pcloud_token', false ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'In order to use All-in-One WP Migration pCloud extension you need to configure it first.', AI1WMPE_PLUGIN_NAME ),
						__( 'Please navigate to WP Admin > All-in-One WP Migration > pCloud Settings and Link your pCloud account.', AI1WMPE_PLUGIN_NAME ),
					)
				);
				exit;
			}
		}

		/**
		 * Creates a new backup and uploads to pCloud.
		 *
		 * ## OPTIONS
		 *
		 * [--sites[=<comma_separated_ids>]]
		 * : Export sites by id (Multisite only). To list sites use: wp site list --fields=blog_id,url
		 *
		 * [--password[=<password>]]
		 * : Encrypt backup with password
		 *
		 * [--exclude-spam-comments]
		 * : Do not export spam comments
		 *
		 * [--exclude-post-revisions]
		 * : Do not export post revisions
		 *
		 * [--exclude-media]
		 * : Do not export media library (files)
		 *
		 * [--exclude-themes]
		 * : Do not export themes (files)
		 *
		 * [--exclude-inactive-themes]
		 * : Do not export inactive themes (files)
		 *
		 * [--exclude-muplugins]
		 * : Do not export must-use plugins (files)
		 *
		 * [--exclude-plugins]
		 * : Do not export plugins (files)
		 *
		 * [--exclude-inactive-plugins]
		 * : Do not export inactive plugins (files)
		 *
		 * [--exclude-cache]
		 * : Do not export cache (files)
		 *
		 * [--exclude-database]
		 * : Do not export database (sql)
		 *
		 * [--exclude-tables[=<comma_separated_names>]]
		 * : Do not export selected database tables (sql)
		 *
		 * [--include-tables[=<comma_separated_names>]]
		 * : Include the selected non‑WP tables (sql)
		 *
		 * [--exclude-email-replace]
		 * : Do not replace email domain (sql)
		 *
		 * [--replace]
		 * : Find and replace text in the database
		 *
		 * [<find>...]
		 * : A string to search for within the database
		 *
		 * [<replace>...]
		 * : Replace instances of the first string with this new string
		 *
		 * ## EXAMPLES
		 *
		 * wp ai1wm pcloud backup --replace "wp" "WordPress"
		 * Backup in progress...
		 * Uploading wordpress-20190509-145422-506.wpress (33 MB) [60% complete]
		 * Uploading wordpress-20190509-145422-506.wpress (33 MB) [100% complete]
		 * Success: Backup complete.
		 * Backup file: wordpress-20190509-145422-506.wpress
		 * Backup location: https://p-ams2.pcloud.com/DLZjKb5l4Zkpiik8sdfaZZiaeDA7Z2ZZwcRZZLuMDkZK0Zl0ZZgk3rA7zpdKJibiAR219UGycbXY27/wordpress-20190509-145422-506.wpress
		 *
		 * @subcommand backup
		 */
		public function backup( $args = array(), $assoc_args = array() ) {
			$params = $this->run_backup(
				$this->build_export_params( $args, $assoc_args )
			);

			WP_CLI::log( sprintf( __( 'Backup location: %s', AI1WMPE_PLUGIN_NAME ), $this->get_backup_location( $params ) ) );
		}

		/**
		 * Get a list of pCloud backup files.
		 *
		 * ## OPTIONS
		 *
		 * [--folder-path=<path>]
		 * : List backups in a specific pCloud folder
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm pcloud list-backups
		 * +------------------------------------------------+--------------+-----------+
		 * | Backup name                                    | Date created | Size      |
		 * +------------------------------------------------+--------------+-----------+
		 * | migration-wp-20170908-152313-435.wpress        | 4 days ago   | 536.77 MB |
		 * | migration-wp-20170908-152103-603.wpress        | 4 days ago   | 536.77 MB |
		 * | migration-wp-20170908-152036-162.wpress        | 4 days ago   | 536.77 MB |
		 * +------------------------------------------------+--------------+-----------+
		 *
		 * $ wp ai1wm pcloud list-backups --folder-path=/backups/daily
		 * +------------------------------------------------+--------------+-----------+
		 * | Backup name                                    | Date created | Size      |
		 * +------------------------------------------------+--------------+-----------+
		 * | migration-wp-20170908-152313-435.wpress        | 4 days ago   | 536.77 MB |
		 * | migration-wp-20170908-152103-603.wpress        | 4 days ago   | 536.77 MB |
		 * +------------------------------------------------+--------------+-----------+
		 *
		 * @subcommand list-backups
		 */
		public function list_backups( $args = array(), $assoc_args = array() ) {
			$backups = new cli\Table;

			$backups->setHeaders(
				array(
					'name' => __( 'Backup name', AI1WMPE_PLUGIN_NAME ),
					'date' => __( 'Date created', AI1WMPE_PLUGIN_NAME ),
					'size' => __( 'Size', AI1WMPE_PLUGIN_NAME ),
				)
			);

			$folder_id = $this->get_folder_id( $assoc_args );
			$items     = $this->list_items( $folder_id );

			// Set folder structure
			$response = array( 'items' => array(), 'num_hidden_files' => 0 );

			foreach ( $items as $item ) {
				if ( pathinfo( $item['name'], PATHINFO_EXTENSION ) === 'wpress' ) {
					$backups->addRow(
						array(
							'name' => $item['name'],
							'date' => sprintf( __( '%s ago', AI1WMPE_PLUGIN_NAME ), human_time_diff( $item['date'] ) ),
							'size' => ai1wm_size_format( $item['bytes'], 2 ),
						)
					);
				}
			}

			$backups->display();
		}

		/**
		 * Restores a backup from pCloud.
		 *
		 * ## OPTIONS
		 *
		 * <file>
		 * : Name of the backup file
		 *
		 * [--folder-path=<path>]
		 * : Download a backup from a specific pCloud folder
		 *
		 * [--yes]
		 * : Automatically confirm the restore operation
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm pcloud restore migration-wp-20170913-095743-931.wpress
		 * Restore in progress...
		 * Restore complete.
		 *
		 * $ wp ai1wm pcloud restore migration-wp-20170913-095743-931.wpress --folder-path=/backups/daily
		 * @subcommand restore
		 */
		public function restore( $args = array(), $assoc_args = array() ) {
			if ( ! isset( $args[0] ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'A backup name must be provided in order to proceed with the restore process.', AI1WMPE_PLUGIN_NAME ),
						__( 'Example: wp ai1wm pcloud restore migration-wp-20170913-095743-931.wpress', AI1WMPE_PLUGIN_NAME ),
					)
				);
				exit;
			}

			$folder_id = $this->get_folder_id( $assoc_args );
			$items     = $this->list_items( $folder_id );

			$file = null;
			foreach ( $items as $item ) {
				if ( $item['name'] === $args[0] ) {
					$file = $item;
					break;
				}
			}

			if ( is_null( $file ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'The backup file could not be located.', AI1WMPE_PLUGIN_NAME ),
						__( 'To list available backups use: wp ai1wm pcloud list-backups', AI1WMPE_PLUGIN_NAME ),
					)
				);
				exit;
			}

			$params = array(
				'archive'    => $args[0],
				'storage'    => ai1wm_storage_folder(),
				'file_id'    => $file['id'],
				'file_size'  => $file['bytes'],
				'cli_args'   => $assoc_args,
				'secret_key' => get_option( AI1WM_SECRET_KEY, false ),
			);

			WP_CLI::log( 'Restore in progress...' );

			try {

				// Disable completed timeout
				add_filter( 'ai1wm_completed_timeout', '__return_zero' );

				// Run import filters
				$params = Ai1wm_Import_Controller::import( $params );

			} catch ( Exception $e ) {
				WP_CLI::error( __( sprintf( 'Unable to import: %s', $e->getMessage() ), AI1WM_PLUGIN_NAME ) );
				exit;
			}

			WP_CLI::success( 'Restore complete.' );
		}

		/**
		 * Get backup items list
		 *
		 * @param  string $folder_id Folder ID where backups located
		 * @return array  $items     Backup items
		 */
		protected function list_items( $folder_id ) {
			// Set pCloud client
			$pcloud = new Ai1wmpe_PCloud_Client(
				get_option( 'ai1wmpe_pcloud_hostname', AI1WMPE_PCLOUD_API_ENDPOINT ),
				get_option( 'ai1wmpe_pcloud_token', false ),
				get_option( 'ai1wmpe_pcloud_ssl', true )
			);

			try {
				// List folder
				$items = $pcloud->list_folder( $folder_id );
				Ai1wmve_File_Sorter::sort( $items, Ai1wmve_File_Sorter::by_date_desc() );
			} catch ( Exception $e ) {
				WP_CLI::error( $e->getMessage() );
				exit;
			}

			return $items;
		}

		/**
		 * Get folder ID from command-line or WP settings
		 *
		 * @param  array  $assoc_args CLI params
		 * @return string Folder ID
		 */
		protected function get_folder_id( $assoc_args ) {
			// Set pCloud client
			$pcloud = new Ai1wmpe_PCloud_Client(
				get_option( 'ai1wmpe_pcloud_hostname', AI1WMPE_PCLOUD_API_ENDPOINT ),
				get_option( 'ai1wmpe_pcloud_token', false ),
				get_option( 'ai1wmpe_pcloud_ssl', true )
			);

			if ( isset( $assoc_args['folder-path'] ) ) {
				$path   = explode( '/', trim( $assoc_args['folder-path'], '/' ) );
				$parent = 0;
				foreach ( $path as $folder_name ) {
					try {
						$folder_id = $pcloud->get_folder_id_by_name( $folder_name, $parent );
					} catch ( Ai1wmpe_Error_Exception $e ) {
						$folder_id = null;
					}
					if ( empty( $folder_id ) ) {
						WP_CLI::error( sprintf( __( 'Folder \'%s\' not found', AI1WMPE_PLUGIN_NAME ), $folder_name ) );
						exit;
					}

					$parent = $folder_id;
				}
				return $folder_id;
			}
			if ( ! ( $folder_id = $pcloud->get_folder_id_by_name( ai1wm_archive_folder() ) ) ) {
				$folder_id = $pcloud->create_folder( ai1wm_archive_folder() );
			}
			return $folder_id;
		}

		/**
		 * Get backup location string
		 *
		 * @param  array  $params Params
		 * @return string Human-readable backup file location
		 */
		protected function get_backup_location( $params ) {
			// Set pCloud client
			$pcloud = new Ai1wmpe_PCloud_Client(
				get_option( 'ai1wmpe_pcloud_hostname', AI1WMPE_PCLOUD_API_ENDPOINT ),
				get_option( 'ai1wmpe_pcloud_token', false ),
				get_option( 'ai1wmpe_pcloud_ssl', true )
			);

			return $pcloud->get_file_url( $params['file_id'] );
		}
	}
}
