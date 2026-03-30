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

class Ai1wmpe_Export_Upload {

	public static function execute( $params, Ai1wmpe_PCloud_Client $pcloud = null ) {

		$params['completed'] = false;

		// Validate folder ID
		if ( ! isset( $params['folder_id'] ) ) {
			throw new Ai1wm_Import_Exception( __( 'pCloud Folder ID is not specified.', AI1WMPE_PLUGIN_NAME ) );
		}

		// Validate upload ID
		if ( ! isset( $params['upload_id'] ) ) {
			throw new Ai1wm_Import_Exception( __( 'pCloud Upload ID is not specified.', AI1WMPE_PLUGIN_NAME ) );
		}

		// Set archive offset
		if ( ! isset( $params['archive_offset'] ) ) {
			$params['archive_offset'] = 0;
		}

		// Set archive size
		if ( ! isset( $params['archive_size'] ) ) {
			$params['archive_size'] = ai1wm_archive_bytes( $params );
		}

		// Set upload retries
		if ( ! isset( $params['upload_retries'] ) ) {
			$params['upload_retries'] = 0;
		}

		// Set upload backoff
		if ( ! isset( $params['upload_backoff'] ) ) {
			$params['upload_backoff'] = 1;
		}

		// Set pCloud client
		if ( is_null( $pcloud ) ) {
			$pcloud = new Ai1wmpe_PCloud_Client(
				get_option( 'ai1wmpe_pcloud_hostname', AI1WMPE_PCLOUD_API_ENDPOINT ),
				get_option( 'ai1wmpe_pcloud_token', false ),
				get_option( 'ai1wmpe_pcloud_ssl', true )
			);
		}

		// Open the archive file for reading
		$archive = fopen( ai1wm_archive_path( $params ), 'rb' );

		// Set file chunk size for upload
		$file_chunk_size = get_option( 'ai1wmpe_pcloud_file_chunk_size', AI1WMPE_DEFAULT_FILE_CHUNK_SIZE );

		// Read file chunk data
		if ( ( fseek( $archive, $params['archive_offset'] ) !== -1 )
			&& ( $file_chunk_data = fread( $archive, $file_chunk_size ) ) ) {

			try {

				$params['upload_retries'] += 1;
				$params['upload_backoff'] *= 2;

				// Upload file chunk data
				$pcloud->upload_file_chunk( $file_chunk_data, $params['upload_id'], $params['archive_offset'] );

				// Unset upload retries
				unset( $params['upload_retries'] );
				unset( $params['upload_backoff'] );

			} catch ( Ai1wmpe_Connect_Exception $e ) {
				sleep( $params['upload_backoff'] );
				if ( $params['upload_retries'] <= 5 ) {
					return $params;
				}

				throw $e;
			}

			// Set archive offset
			$params['archive_offset'] = ftell( $archive );

			// Set archive details
			$name = ai1wm_archive_name( $params );
			$size = ai1wm_archive_size( $params );

			// Get progress
			$progress = (int) ( ( $params['archive_offset'] / $params['archive_size'] ) * 100 );

			// Set progress
			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::log(
					sprintf(
						__( 'Uploading %s (%s) [%d%% complete]', AI1WMPE_PLUGIN_NAME ),
						$name,
						$size,
						$progress
					)
				);
			} else {
				Ai1wm_Status::info(
					sprintf(
						__(
							'<i class="ai1wmpe-icon-pcloud"></i> ' .
							'Uploading <strong>%s</strong> (%s)<br />%d%% complete',
							AI1WMPE_PLUGIN_NAME
						),
						$name,
						$size,
						$progress
					)
				);
			}
		} else {

			// Complete upload file chunk data
			if ( ai1wmpe_is_incremental() ) {
				$response = $pcloud->upload_complete( $params['archive'], $params['upload_id'], $params['incremental_folder_id'] );
			} else {
				$response = $pcloud->upload_complete( $params['archive'], $params['upload_id'], $params['folder_id'] );
			}

			if ( isset( $response['metadata']['fileid'] ) ) {
				$params['file_id'] = $response['metadata']['fileid'];
			}

			// Set last backup date
			update_option( 'ai1wmpe_pcloud_timestamp', time() );

			// Unset upload ID
			unset( $params['upload_id'] );

			// Unset archive offset
			unset( $params['archive_offset'] );

			// Unset archive size
			unset( $params['archive_size'] );

			// Unset completed
			unset( $params['completed'] );
		}

		// Close the archive file
		fclose( $archive );

		return $params;
	}
}
