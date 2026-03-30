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

class Ai1wmpe_Import_Download {

	public static function execute( $params, Ai1wmpe_PCloud_Client $pcloud = null ) {

		$params['completed'] = false;

		// Validate file URL
		if ( ! isset( $params['file_url'] ) ) {
			throw new Ai1wm_Import_Exception( __( 'pCloud File URL is not specified.', AI1WMPE_PLUGIN_NAME ) );
		}

		// Validate file size
		if ( ! isset( $params['file_size'] ) ) {
			throw new Ai1wm_Import_Exception( __( 'pCloud File Size is not specified.', AI1WMPE_PLUGIN_NAME ) );
		}

		// Set file chunk size for download
		$file_chunk_size = get_option( 'ai1wmpe_pcloud_file_chunk_size', AI1WMPE_DEFAULT_FILE_CHUNK_SIZE );

		// Set archive offset
		if ( ! isset( $params['archive_offset'] ) ) {
			$params['archive_offset'] = 0;
		}

		// Set file range start
		if ( ! isset( $params['file_range_start'] ) ) {
			$params['file_range_start'] = 0;
		}

		// Set file range end
		if ( ! isset( $params['file_range_end'] ) ) {
			$params['file_range_end'] = $file_chunk_size - 1;
		}

		// Set download retries
		if ( ! isset( $params['download_retries'] ) ) {
			$params['download_retries'] = 0;
		}

		// Set download backoff
		if ( ! isset( $params['download_backoff'] ) ) {
			$params['download_backoff'] = 1;
		}

		// Set pCloud client
		if ( is_null( $pcloud ) ) {
			$pcloud = new Ai1wmpe_PCloud_Client(
				get_option( 'ai1wmpe_pcloud_hostname', AI1WMPE_PCLOUD_API_ENDPOINT ),
				get_option( 'ai1wmpe_pcloud_token', false ),
				get_option( 'ai1wmpe_pcloud_ssl', true )
			);
		}

		// Open the archive file for writing
		$archive = fopen( ai1wm_archive_path( $params ), 'cb' );

		// Write file chunk data
		if ( ( fseek( $archive, $params['archive_offset'] ) !== -1 ) ) {
			try {

				$params['download_retries'] += 1;
				$params['download_backoff'] *= 2;

				// Download file chunk data
				$pcloud->download_file_chunk( $archive, $params['file_url'], $params['file_range_start'], $params['file_range_end'] );

				// Unset download retries
				unset( $params['download_retries'] );
				unset( $params['download_backoff'] );

			} catch ( Ai1wmpe_Connect_Exception $e ) {
				sleep( $params['download_backoff'] );
				if ( $params['download_retries'] <= 5 ) {
					return $params;
				}

				throw $e;
			}
		}

		// Set archive offset
		$params['archive_offset'] = ftell( $archive );

		// Set file range start
		if ( $params['file_size'] <= ( $params['file_range_start'] + $file_chunk_size ) ) {
			$params['file_range_start'] = $params['file_size'] - 1;
		} else {
			$params['file_range_start'] = $params['file_range_start'] + $file_chunk_size;
		}

		// Set file range end
		if ( $params['file_size'] <= ( $params['file_range_end'] + $file_chunk_size ) ) {
			$params['file_range_end'] = $params['file_size'] - 1;
		} else {
			$params['file_range_end'] = $params['file_range_end'] + $file_chunk_size;
		}

		// Get progress
		$progress = (int) ( ( $params['file_range_start'] / $params['file_size'] ) * 100 );

		// Set progress
		if ( defined( 'WP_CLI' ) ) {
			WP_CLI::log( sprintf( __( 'Downloading [%d%% complete]', AI1WMPE_PLUGIN_NAME ), $progress ) );
		} else {
			Ai1wm_Status::progress( $progress );
		}

		// Completed?
		if ( $params['file_range_start'] === ( $params['file_size'] - 1 ) ) {

			// Unset file URL
			unset( $params['file_url'] );

			// Unset file size
			unset( $params['file_size'] );

			// Unset archive offset
			unset( $params['archive_offset'] );

			// Unset file range start
			unset( $params['file_range_start'] );

			// Unset file range end
			unset( $params['file_range_end'] );

			// Unset completed
			unset( $params['completed'] );
		}

		// Close the archive file
		fclose( $archive );

		return $params;
	}
}
