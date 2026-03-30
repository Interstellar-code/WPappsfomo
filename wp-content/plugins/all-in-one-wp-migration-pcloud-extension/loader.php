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

// Include all the files that you want to load in here
if ( defined( 'WP_CLI' ) ) {
	require_once AI1WMPE_VENDOR_PATH .
				DIRECTORY_SEPARATOR .
				'servmask' .
				DIRECTORY_SEPARATOR .
				'command' .
				DIRECTORY_SEPARATOR .
				'ai1wm-wp-cli.php';

	require_once AI1WMPE_VENDOR_PATH .
				DIRECTORY_SEPARATOR .
				'servmask' .
				DIRECTORY_SEPARATOR .
				'command' .
				DIRECTORY_SEPARATOR .
				'class-ai1wmpe-pcloud-wp-cli-command.php';

	require_once AI1WMPE_VENDOR_PATH .
				DIRECTORY_SEPARATOR .
				'servmask' .
				DIRECTORY_SEPARATOR .
				'command' .
				DIRECTORY_SEPARATOR .
				'class-ai1wmpe-pcloud-wp-cli-incremental-command.php';
}

require_once AI1WMPE_VENDOR_PATH .
			DIRECTORY_SEPARATOR .
			'servmask' .
			DIRECTORY_SEPARATOR .
			'pro' .
			DIRECTORY_SEPARATOR .
			'ai1wmve.php';

require_once AI1WMPE_CONTROLLER_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-main-controller.php';

require_once AI1WMPE_CONTROLLER_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-export-controller.php';

require_once AI1WMPE_CONTROLLER_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-import-controller.php';

require_once AI1WMPE_CONTROLLER_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-settings-controller.php';

require_once AI1WMPE_EXPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-export-done.php';

require_once AI1WMPE_EXPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-export-pcloud.php';

require_once AI1WMPE_EXPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-export-incremental-backups.php';

require_once AI1WMPE_EXPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-export-incremental-content.php';

require_once AI1WMPE_EXPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-export-incremental-media.php';

require_once AI1WMPE_EXPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-export-incremental-plugins.php';

require_once AI1WMPE_EXPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-export-incremental-themes.php';

require_once AI1WMPE_EXPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-export-retention.php';

require_once AI1WMPE_EXPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-export-upload.php';

require_once AI1WMPE_IMPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-import-database.php';

require_once AI1WMPE_IMPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-import-download.php';

require_once AI1WMPE_IMPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-import-pcloud.php';

require_once AI1WMPE_IMPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-import-incremental-download.php';

require_once AI1WMPE_IMPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-import-incremental-pcloud.php';

require_once AI1WMPE_IMPORT_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-import-settings.php';

require_once AI1WMPE_MODEL_PATH .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-settings.php';

require_once AI1WMPE_VENDOR_PATH .
			DIRECTORY_SEPARATOR .
			'pcloud-client' .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-pcloud-client.php';

require_once AI1WMPE_VENDOR_PATH .
			DIRECTORY_SEPARATOR .
			'pcloud-client' .
			DIRECTORY_SEPARATOR .
			'class-ai1wmpe-pcloud-curl.php';
