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

class Ai1wmpe_Import_Database {

	public static function execute( $params ) {

		$model = new Ai1wmpe_Settings;

		// Set progress
		Ai1wm_Status::info( __( 'Updating pCloud settings...', AI1WMPE_PLUGIN_NAME ) );

		// Read settings.json file
		$handle = ai1wm_open( ai1wm_settings_path( $params ), 'r' );

		// Parse settings.json file
		$settings = ai1wm_read( $handle, filesize( ai1wm_settings_path( $params ) ) );
		$settings = json_decode( $settings, true );

		// Close handle
		ai1wm_close( $handle );

		// Update PCloud settings
		$model->set_cron_timestamp( $settings['ai1wmpe_pcloud_cron_timestamp'] );
		$model->set_cron( $settings['ai1wmpe_pcloud_cron'] );
		$model->set_hostname( $settings['ai1wmpe_pcloud_hostname'] );
		$model->set_token( $settings['ai1wmpe_pcloud_token'] );
		$model->set_ssl( $settings['ai1wmpe_pcloud_ssl'] );
		$model->set_folder_id( $settings['ai1wmpe_pcloud_folder_id'] );
		$model->set_backups( $settings['ai1wmpe_pcloud_backups'] );
		$model->set_total( $settings['ai1wmpe_pcloud_total'] );
		$model->set_days( $settings['ai1wmpe_pcloud_days'] );
		$model->set_file_chunk_size( $settings['ai1wmpe_pcloud_file_chunk_size'] );
		$model->set_notify_ok_toggle( $settings['ai1wmpe_pcloud_notify_toggle'] );
		$model->set_notify_error_toggle( $settings['ai1wmpe_pcloud_notify_error_toggle'] );
		$model->set_notify_error_subject( $settings['ai1wmpe_pcloud_notify_error_subject'] );
		$model->set_notify_email( $settings['ai1wmpe_pcloud_notify_email'] );
		$model->set_lock_mode( $settings['ai1wmpe_pcloud_lock_mode'] );

		// Set progress
		Ai1wm_Status::info( __( 'Done updating pCloud settings.', AI1WMPE_PLUGIN_NAME ) );

		return $params;
	}
}
