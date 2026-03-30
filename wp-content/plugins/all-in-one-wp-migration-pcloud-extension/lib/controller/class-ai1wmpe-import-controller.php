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

class Ai1wmpe_Import_Controller {

	public static function button() {
		return Ai1wm_Template::get_content(
			'import/button',
			array( 'token' => get_option( 'ai1wmpe_pcloud_token', false ) ),
			AI1WMPE_TEMPLATES_PATH
		);
	}

	public static function picker() {
		Ai1wm_Template::render(
			'import/picker',
			array(),
			AI1WMPE_TEMPLATES_PATH
		);
	}

	public static function browser( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_GET );
		}

		// Set folder ID
		$folder_id = null;
		if ( isset( $params['folder_id'] ) ) {
			$folder_id = trim( $params['folder_id'] );
		}

		// Set pCloud client
		$pcloud = new Ai1wmpe_PCloud_Client(
			get_option( 'ai1wmpe_pcloud_hostname', AI1WMPE_PCLOUD_API_ENDPOINT ),
			get_option( 'ai1wmpe_pcloud_token', false ),
			get_option( 'ai1wmpe_pcloud_ssl', true )
		);

		// List folder
		$items = $pcloud->list_folder( $folder_id );

		// Set folder structure
		$response = array( 'items' => array(), 'num_hidden_files' => 0 );

		// Set folder items
		foreach ( $items as $item ) {
			if ( $item['type'] === 'folder' || pathinfo( $item['name'], PATHINFO_EXTENSION ) === 'wpress' ) {
				$response['items'][] = array(
					'index' => null,
					'id'    => isset( $item['id'] ) ? $item['id'] : null,
					'name'  => isset( $item['name'] ) ? $item['name'] : null,
					'path'  => isset( $item['path'] ) ? $item['path'] : null,
					'unix'  => isset( $item['date'] ) ? $item['date'] : null,
					'date'  => isset( $item['date'] ) ? human_time_diff( $item['date'] ) : null,
					'size'  => isset( $item['bytes'] ) ? ai1wm_size_format( $item['bytes'] ) : null,
					'bytes' => isset( $item['bytes'] ) ? $item['bytes'] : null,
					'type'  => isset( $item['type'] ) ? $item['type'] : null,
				);
			} else {
				$response['num_hidden_files']++;
			}
		}

		// Sort items by type desc and date desc
		Ai1wmve_File_Sorter::sort( $response['items'], Ai1wmve_File_Sorter::by_type_desc_date_desc( 'unix' ) );

		echo json_encode( $response );
		exit;
	}

	public static function incremental( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_GET );
		}

		// Set folder ID
		$folder_id = null;
		if ( isset( $params['folder_id'] ) ) {
			$folder_id = trim( $params['folder_id'] );
		}

		// Set pCloud client
		$pcloud = new Ai1wmpe_PCloud_Client(
			get_option( 'ai1wmpe_pcloud_hostname', AI1WMPE_PCLOUD_API_ENDPOINT ),
			get_option( 'ai1wmpe_pcloud_token', false ),
			get_option( 'ai1wmpe_pcloud_ssl', true )
		);

		try {
			if ( ( $response = $pcloud->list_folder( $folder_id, 'incremental.backups.list' ) ) ) {
				if ( isset( $response[0]['id'] ) ) {
					$file_content = $pcloud->get_file_content( $pcloud->get_file_url( $response[0]['id'] ) );
				}
			}
		} catch ( Ai1wmpe_Error_Exception $e ) {
		}

		$items = array();
		if ( isset( $file_content ) ) {
			foreach ( str_getcsv( $file_content, "\n" ) as $row ) {
				if ( list( $file_index, $file_id, $file_path, $file_size, $file_mtime ) = str_getcsv( $row ) ) {
					$items[] = array(
						'index'  => $file_index,
						'id'     => $file_id,
						'name'   => sprintf( __( 'Restore point %d', AI1WMPE_PLUGIN_NAME ), $file_index ),
						'path'   => $file_path,
						'folder' => $folder_id,
						'date'   => get_date_from_gmt( date( 'Y-m-d H:i:s', $file_mtime ), 'M j, Y g:i a' ),
						'size'   => ai1wm_size_format( $file_size ),
						'bytes'  => $file_size,
						'type'   => 'file',
					);
				}
			}
		}

		echo json_encode( array( 'items' => array_reverse( $items ), 'cursor' => null ) );
		exit;
	}
}
