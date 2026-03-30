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

class Ai1wmpe_PCloud_Client {

	/**
	 * API endpoint
	 *
	 * @var string
	 */
	protected $api_endpoint = null;

	/**
	 * OAuth access token
	 *
	 * @var string
	 */
	protected $access_token = null;

	/**
	 * SSL mode
	 *
	 * @var boolean
	 */
	protected $ssl = null;

	public function __construct( $api_endpoint, $access_token, $ssl = true ) {
		$this->api_endpoint = $api_endpoint;
		$this->access_token = $access_token;
		$this->ssl          = $ssl;
	}

	/**
	 * Get account info
	 *
	 * @return array
	 */
	public function get_account_info() {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/userinfo' );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		return $response;
	}

	/**
	 * Get folder ID
	 *
	 * @param  string  $folder_name Folder name
	 * @param  integer $parent_id   Parent ID
	 * @return integer
	 */
	public function get_folder_id_by_name( $folder_name, $parent_id = 0 ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/listfolder' );
		$api->set_query( $this->rawurlencode_query( array( 'folderid' => $parent_id, 'nofiles' => 1 ) ) );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		if ( isset( $response['metadata']['contents'] ) ) {
			foreach ( $response['metadata']['contents'] as $content ) {
				if ( isset( $content['name'] ) && ( $content['name'] === $folder_name ) ) {
					return $content['folderid'];
				}
			}
		}
	}

	/**
	 * Create folder
	 *
	 * @param  string  $folder_name Folder name
	 * @param  integer $parent_id   Parent ID
	 * @return integer
	 */
	public function create_folder( $folder_name, $parent_id = 0 ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/createfolder' );
		$api->set_query( $this->rawurlencode_query( array( 'name' => $folder_name, 'folderid' => $parent_id ) ) );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		if ( isset( $response['metadata']['folderid'] ) ) {
			return $response['metadata']['folderid'];
		}
	}

	/**
	 * Get folder ID by ID
	 *
	 * @param  string $folder_id Folder ID
	 * @return string
	 */
	public function get_folder_id_by_id( $folder_id ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/listfolder' );
		$api->set_query( $this->rawurlencode_query( array( 'folderid' => $folder_id, 'nofiles' => 1 ) ) );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			$response = array();
		}

		if ( empty( $response['metadata']['isdeleted'] ) ) {
			if ( isset( $response['metadata']['folderid'] ) ) {
				return $response['metadata']['folderid'];
			}
		}
	}

	/**
	 * Get folder name by ID
	 *
	 * @param  string $folder_id Folder ID
	 * @return string
	 */
	public function get_folder_name_by_id( $folder_id ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/listfolder' );
		$api->set_query( $this->rawurlencode_query( array( 'folderid' => $folder_id, 'nofiles' => 1 ) ) );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			$response = array();
		}

		if ( empty( $response['metadata']['isdeleted'] ) ) {
			if ( isset( $response['metadata']['name'] ) ) {
				return $response['metadata']['name'];
			}
		}
	}

	/**
	 * List folder
	 *
	 * @param  integer $parent_id       Parent ID
	 * @param  string  $filter_by_name  Filter by name
	 * @param  string  $exclude_by_name Exclude by name
	 * @return array
	 */
	public function list_folder( $parent_id = 0, $filter_by_name = null, $exclude_by_name = null ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/listfolder' );
		$api->set_query( $this->rawurlencode_query( array( 'folderid' => $parent_id ) ) );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		$items = array();
		if ( isset( $response['metadata']['contents'] ) ) {
			foreach ( $response['metadata']['contents'] as $content ) {
				if ( $filter_by_name ) {
					if ( isset( $content['name'] ) && ( $content['name'] !== $filter_by_name ) ) {
						continue;
					}
				} elseif ( $exclude_by_name ) {
					if ( isset( $content['name'] ) && ( $content['name'] === $exclude_by_name ) ) {
						continue;
					}
				}

				$items[] = array(
					'id'    => isset( $content['fileid'] ) ? $content['fileid'] : ( isset( $content['folderid'] ) ? $content['folderid'] : null ),
					'name'  => isset( $content['name'] ) ? $content['name'] : null,
					'path'  => isset( $content['name'] ) ? $content['name'] : null,
					'date'  => isset( $content['created'] ) ? strtotime( $content['created'] ) : null,
					'bytes' => isset( $content['size'] ) ? $content['size'] : null,
					'type'  => isset( $content['isfolder'] ) && $content['isfolder'] === true ? 'folder' : 'file',
				);
			}
		}

		return $items;
	}

	/**
	 * Create upload ID
	 *
	 * @return integer
	 */
	public function create_upload() {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/upload_create' );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		if ( isset( $response['uploadid'] ) ) {
			return $response['uploadid'];
		}
	}

	/**
	 * Upload a file in chunks
	 *
	 * @param  string  $file_chunk_data  File chunk data
	 * @param  integer $upload_id        Upload ID
	 * @param  integer $file_range_start File range start
	 * @return array
	 */
	public function upload_file_chunk( $file_chunk_data, $upload_id, $file_range_start = 0 ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/upload_write' );
		$api->set_query( $this->rawurlencode_query( array( 'uploadid' => $upload_id, 'uploadoffset' => $file_range_start ) ) );
		$api->set_option( CURLOPT_CUSTOMREQUEST, 'PUT' );
		$api->set_option( CURLOPT_POSTFIELDS, $file_chunk_data );
		$api->set_header( 'Content-Type', 'application/octet-stream' );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		return $response;
	}

	/**
	 * Complete the upload
	 *
	 * @param  string  $file_name File name
	 * @param  integer $upload_id Upload ID
	 * @param  integer $folder_id Folder ID
	 * @return boolean
	 */
	public function upload_complete( $file_name, $upload_id, $folder_id ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/upload_save' );
		$api->set_query( $this->rawurlencode_query( array( 'name' => $file_name, 'uploadid' => $upload_id, 'folderid' => $folder_id ) ) );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		return $response;
	}

	/**
	 * Upload a file
	 *
	 * @param  resource $file_stream File stream
	 * @param  string   $file_name   File name
	 * @param  integer  $file_size   File size
	 * @param  integer  $folder_id   Folder ID
	 * @return array
	 */
	public function upload_file( $file_stream, $file_name, $file_size, $folder_id ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/uploadfile' );
		$api->set_query( $this->rawurlencode_query( array( 'filename' => $file_name, 'folderid' => $folder_id ) ) );
		$api->set_option( CURLOPT_PUT, 1 );
		$api->set_option( CURLOPT_INFILE, $file_stream );
		$api->set_option( CURLOPT_INFILESIZE, $file_size );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		return $response;
	}

	/**
	 * Get File URL
	 *
	 * @param  integer $file_id File ID
	 * @return string
	 */
	public function get_file_url( $file_id ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/getfilelink' );
		$api->set_query( $this->rawurlencode_query( array( 'fileid' => $file_id ) ) );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		if ( isset( $response['hosts'][0] ) ) {
			if ( isset( $response['path'] ) ) {
				return 'https://' . $response['hosts'][0] . $response['path'];
			}
		}
	}

	/**
	 * Get file content from pCloud
	 *
	 * @param  string $file_url File URL
	 * @return string
	 */
	public function get_file_content( $file_url ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_base_url( $file_url );

		try {
			$response = $api->make_request();
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		return $response;
	}

	/**
	 * Download file chunk
	 *
	 * @param  resource $file_stream      File stream
	 * @param  string   $file_url         File URL
	 * @param  integer  $file_range_start File range start
	 * @param  integer  $file_range_end   File range end
	 * @return boolean
	 */
	public function download_file_chunk( $file_stream, $file_url, $file_range_start = 0, $file_range_end = 0 ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_base_url( $file_url );
		$api->set_option( CURLOPT_RANGE, sprintf( '%d-%d', $file_range_start, $file_range_end ) );

		try {
			$file_chunk_data = $api->make_request();
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		// Copy file chunk data into file stream
		if ( fwrite( $file_stream, $file_chunk_data ) === false ) {
			throw new Ai1wmpe_Error_Exception( __( 'Unable to save the file from pCloud URL address', AI1WMPE_PLUGIN_NAME ) );
		}

		return true;
	}

	/**
	 * Download file
	 *
	 * @param  resource $file_stream File stream
	 * @param  string   $file_url    File URL
	 * @return boolean
	 */
	public function download_file( $file_stream, $file_url ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_base_url( $file_url );
		$api->set_option( CURLOPT_FILE, $file_stream );

		try {
			$api->make_request();
		} catch ( Ai1wmpe_Error_Exception $e ) {
			throw $e;
		}

		return true;
	}

	/**
	 * Delete a file
	 *
	 * @param  integer $file_id File ID
	 * @return boolean
	 */
	public function delete_file( $file_id ) {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/deletefile' );
		$api->set_query( $this->rawurlencode_query( array( 'fileid' => $file_id ) ) );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Revoke token
	 *
	 * @return boolean
	 */
	public function revoke() {
		$api = new Ai1wmpe_PCloud_Curl;
		$api->set_access_token( $this->access_token );
		$api->set_ssl( $this->ssl );
		$api->set_base_url( 'https://' . $this->api_endpoint );
		$api->set_path( '/logout' );

		try {
			$response = $api->make_request( true );
		} catch ( Ai1wmpe_Error_Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Encode URL query
	 *
	 * @param  array  $query Base query
	 * @return string
	 */
	public function rawurlencode_query( $query ) {
		return str_replace( '%7E', '~', array_map( 'rawurlencode', array_filter( $query, 'is_scalar' ) ) );
	}
}
