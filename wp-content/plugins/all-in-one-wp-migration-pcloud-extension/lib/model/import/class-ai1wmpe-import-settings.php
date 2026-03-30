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

class Ai1wmpe_Import_Settings {

	public static function execute( $params ) {

		// Set progress
		Ai1wm_Status::info( __( 'Getting pCloud settings...', AI1WMPE_PLUGIN_NAME ) );

		$settings = array(
			'ai1wmpe_pcloud_cron_timestamp'       => get_option( 'ai1wmpe_pcloud_cron_timestamp', time() ),
			'ai1wmpe_pcloud_cron'                 => get_option( 'ai1wmpe_pcloud_cron', array() ),
			'ai1wmpe_pcloud_hostname'             => get_option( 'ai1wmpe_pcloud_hostname', AI1WMPE_PCLOUD_API_ENDPOINT ),
			'ai1wmpe_pcloud_token'                => get_option( 'ai1wmpe_pcloud_token', false ),
			'ai1wmpe_pcloud_ssl'                  => get_option( 'ai1wmpe_pcloud_ssl', false ),
			'ai1wmpe_pcloud_folder_id'            => get_option( 'ai1wmpe_pcloud_folder_id', false ),
			'ai1wmpe_pcloud_backups'              => get_option( 'ai1wmpe_pcloud_backups', false ),
			'ai1wmpe_pcloud_total'                => get_option( 'ai1wmpe_pcloud_total', false ),
			'ai1wmpe_pcloud_days'                 => get_option( 'ai1wmpe_pcloud_days', false ),
			'ai1wmpe_pcloud_file_chunk_size'      => get_option( 'ai1wmpe_pcloud_file_chunk_size', AI1WMPE_DEFAULT_FILE_CHUNK_SIZE ),
			'ai1wmpe_pcloud_notify_toggle'        => get_option( 'ai1wmpe_pcloud_notify_toggle', false ),
			'ai1wmpe_pcloud_notify_error_toggle'  => get_option( 'ai1wmpe_pcloud_notify_error_toggle', false ),
			'ai1wmpe_pcloud_notify_error_subject' => get_option( 'ai1wmpe_pcloud_notify_error_subject', false ),
			'ai1wmpe_pcloud_notify_email'         => get_option( 'ai1wmpe_pcloud_notify_email', false ),
			'ai1wmpe_pcloud_lock_mode'            => get_option( 'ai1wmpe_pcloud_lock_mode', false ),
		);

		// Save settings.json file
		$handle = ai1wm_open( ai1wm_settings_path( $params ), 'w' );
		ai1wm_write( $handle, json_encode( $settings ) );
		ai1wm_close( $handle );

		// Set progress
		Ai1wm_Status::info( __( 'Done getting pCloud settings.', AI1WMPE_PLUGIN_NAME ) );

		return $params;
	}
}
