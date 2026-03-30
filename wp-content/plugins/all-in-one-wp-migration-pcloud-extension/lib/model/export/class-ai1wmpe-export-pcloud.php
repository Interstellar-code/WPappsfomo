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

class Ai1wmpe_Export_PCloud {

	public static function execute( $params, Ai1wmpe_PCloud_Client $pcloud = null ) {

		// Set progress
		Ai1wm_Status::info( __( 'Connecting to pCloud...', AI1WMPE_PLUGIN_NAME ) );

		// Open archive file for writing
		$archive = new Ai1wm_Compressor( ai1wm_archive_path( $params ) );

		// Append EOF block
		$archive->close( true );

		// Set pCloud client
		if ( is_null( $pcloud ) ) {
			$pcloud = new Ai1wmpe_PCloud_Client(
				get_option( 'ai1wmpe_pcloud_hostname', AI1WMPE_PCLOUD_API_ENDPOINT ),
				get_option( 'ai1wmpe_pcloud_token', false ),
				get_option( 'ai1wmpe_pcloud_ssl', true )
			);
		}

		// Get folder ID
		$params['folder_id'] = get_option( 'ai1wmpe_pcloud_folder_id', false );

		// Create folder
		if ( ! ( $params['folder_id'] = $pcloud->get_folder_id_by_id( $params['folder_id'] ) ) ) {
			if ( ! ( $params['folder_id'] = $pcloud->get_folder_id_by_name( ai1wm_archive_folder() ) ) ) {
				$params['folder_id'] = $pcloud->create_folder( ai1wm_archive_folder() );
			}
		}

		// Create incremental folder
		if ( ai1wmpe_is_incremental() ) {
			if ( ! ( $params['incremental_folder_id'] = $pcloud->get_folder_id_by_name( 'incremental-backups', $params['folder_id'] ) ) ) {
				$params['incremental_folder_id'] = $pcloud->create_folder( 'incremental-backups', $params['folder_id'] );
			}

			if ( ! empty( $params['incremental_folder_id'] ) ) {
				update_option( 'ai1wmpe_pcloud_incremental_folder_id', $params['incremental_folder_id'] );
			}
		}

		// Get upload ID
		$params['upload_id'] = $pcloud->create_upload();

		// Set progress
		Ai1wm_Status::info( __( 'Done connecting to pCloud.', AI1WMPE_PLUGIN_NAME ) );

		return $params;
	}
}
