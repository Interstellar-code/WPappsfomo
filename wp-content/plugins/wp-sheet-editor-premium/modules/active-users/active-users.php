<?php

if ( ! class_exists( 'WPSE_Active_Users' ) ) {
	class WPSE_Active_Users {
		private static $instance = null;

		public function __construct() {
			add_action( 'vg_sheet_editor/after_enqueue_assets', array( $this, 'add_viewer_tracker_script' ) );
			add_filter( 'heartbeat_received', array( $this, 'handle_heartbeat_response' ), 10, 2 );
			add_filter( 'vg_sheet_editor/options_page/options', array( $this, 'add_settings_page_options' ) );
			add_action( 'vg_sheet_editor/editor_page/after_content', array( $this, 'add_active_users_modal' ) );
		}
		/**
		 * Add the active users modal HTML to the editor page
		 *
		 * @return void
		 */
		public function add_active_users_modal() {
			if ( ! is_admin() || VGSE()->get_option( 'active_users_tracking' ) === 'disabled' || ! VGSE()->helpers->is_editor_page() ) {
				return;
			}
			if ( get_user_count() === 1 ) {
				return;
			}
			?>
			<div class="remodal active-users-remodal" data-remodal-id="active-users-modal" data-remodal-options="closeOnOutsideClick: false, hashTracking: false" x-data>
				<div class="modal-content">
					<h3><?php esc_html_e( 'Active Users', 'vg_sheet_editor' ); ?></h3>
					<p><?php esc_html_e( 'Other users are viewing this spreadsheet, consider waiting until they finish to avoid overwriting their changes. This is updated every 60 seconds.', 'vg_sheet_editor' ); ?></p>
					<div id="active-users-list" class="active-users-list">
						<template x-for="user in $store.activeUsers.users" :key="user.user_login">
							<div class="active-user-item" style="display: flex; align-items: center; margin-bottom: 10px;">
								<template x-if="user.avatar">
								<img :src="user.avatar" :alt="user.user_login" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
								</template>
								<template x-if="!user.avatar">
								<i x-if="!user.avatar" class="fa fa-user" style="width: 30px; height: 30px; line-height: 30px; text-align: center; border-radius: 50%; margin-right: 10px; background: #eee;"></i>									
								</template>
								<div>
									<span x-text="user.user_login"></span>
									<small x-text="user.last_active" style="display: block; font-size: 12px; color: #666;"></small>
								</div>
							</div>
						</template>
					</div>
				</div>
				<br>
				<button data-remodal-action="confirm" class="remodal-cancel"><?php esc_html_e( 'Close', 'vg_sheet_editor' ); ?></button>
			</div>
			<?php
		}
		/**
		 * Add fields to options page
		 * @param array $sections
		 * @return array
		 */
		function add_settings_page_options( $sections ) {
			$sections['customize_features']['fields'][] = array(
				'id'      => 'active_users_tracking',
				'type'    => 'new_select',
				'title'   => esc_html__( 'Track active users in the sheet', 'vg_sheet_editor_automations' ),
				'desc'    => esc_html__( 'This is useful to know if other WordPress users are using the sheet, so you can avoid making changes to not overwrite their changes. A user is considered active if they viewed the sheet in the last 60 seconds', 'vg_sheet_editor_automations' ),
				'default' => '',
				'options' => array(
					''             => esc_html__( 'Enabled, show popup once, and show active users in the toolbar (Default)', 'vg_sheet_editor' ),
					'only_toolbar' => esc_html__( 'Enabled, but only show active users in the toolbar, no popup', 'vg_sheet_editor' ),
					'disabled'     => esc_html__( 'Disabled entirely', 'vg_sheet_editor' ),
				),
			);
			return $sections;
		}

		/**
		 * Get the singleton instance
		 *
		 * @return WPSE_Active_Users
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Add the viewer tracking script to the editor page
		 *
		 * @return void
		 */
		public function add_viewer_tracker_script() {
			if ( ! is_admin() || VGSE()->get_option( 'active_users_tracking' ) === 'disabled' || ! VGSE()->helpers->is_editor_page() ) {
				return;
			}
			$current_post_type = VGSE()->helpers->get_provider_from_query_string();
			if ( empty( $current_post_type ) ) {
				return;
			}
			if ( get_user_count() === 1 ) {
				return;
			}

			// Enqueue the JavaScript file
			$js_file_path = plugin_dir_path( __FILE__ ) . 'init.js';
			wp_enqueue_script( 'vgse-viewer-tracker', plugin_dir_url( __FILE__ ) . 'init.js', array( 'jquery' ), filemtime( $js_file_path ), true );
		}

		/**
		 * Handle heartbeat response
		 *
		 * @param array $response Heartbeat response data to pass back to front end.
		 * @param array $data     Data received from the front end (unslashed).
		 *
		 * @return array
		 */
		public function handle_heartbeat_response( $response, $data ) {
			// Check if we're tracking viewers for this sheet
			if ( empty( $data['vgse_track_viewer'] ) ||
				empty( $data['vgse_track_viewer_sheet_key'] ) ||
				empty( $data['vgse_track_viewer_nonce'] ) ) {
				return $response;
			}

			// Verify nonce
			if ( ! wp_verify_nonce( $data['vgse_track_viewer_nonce'], 'bep-nonce' ) ) {
				return $response;
			}

			$sheet_key       = sanitize_text_field( $data['vgse_track_viewer_sheet_key'] );
			$current_user_id = get_current_user_id();

			// Get existing viewers
			$viewers = get_option( 'vgse_active_users', array() );
			if ( ! is_array( $viewers ) ) {
				$viewers = array();
			}

			if ( ! isset( $viewers[ $sheet_key ] ) ) {
				$viewers[ $sheet_key ] = array();
			}

			// Update current user's timestamp
			$viewers[ $sheet_key ][ $current_user_id ] = time();

			// Clean up old entries (older than 60 seconds)
			foreach ( $viewers[ $sheet_key ] as $user_id => $timestamp ) {
				if ( time() - $timestamp > 60 ) {
					unset( $viewers[ $sheet_key ][ $user_id ] );
				}
			}
			// Limit to 100 users per sheet key
			if ( count( $viewers[ $sheet_key ] ) > 100 ) {
				// Sort by timestamp (oldest first)
				asort( $viewers[ $sheet_key ] );
				// Remove oldest users to keep only 100
				$viewers[ $sheet_key ] = array_slice( $viewers[ $sheet_key ], -100, null, true );
			}

			// Limit to 100 sheet keys total
			if ( count( $viewers ) > 100 ) {
				// Sort by last activity (oldest first)
				uasort(
					$viewers,
					function ( $a, $b ) {
						$last_a = max( $a );
						$last_b = max( $b );
						return $last_a <=> $last_b;
					}
				);
				// Keep only the most recent 100 sheet keys
				$viewers = array_slice( $viewers, -100, null, true );
			}

			update_option( 'vgse_active_users', $viewers, false );

			// Get users who viewed in the last 60 seconds
			$recent_viewers = array();
			if ( isset( $viewers[ $sheet_key ] ) ) {
				foreach ( $viewers[ $sheet_key ] as $user_id => $timestamp ) {
					if ( time() - $timestamp <= 60 ) {
						$user = get_userdata( $user_id );
						if ( $user ) {
							$recent_viewers[] = array(
								'user_login'  => $user->user_login,
								'avatar'      => get_avatar_url( $user_id, array( 'size' => 20 ) ),
								'last_active' => sprintf( esc_html__( '%s ago', 'vg_sheet_editor' ), esc_html( human_time_diff( time(), $timestamp ) ) ),
							);
						}
					}
				}
			}

			// Add active users data to heartbeat response
			$response['vgse_active_users'] = array(
				'success' => true,
				'data'    => array(
					'users' => $recent_viewers,
				),
			);

			return $response;
		}
	}

	// Initialize the class via hook
	add_action(
		'vg_sheet_editor/initialized',
		function () {
			WPSE_Active_Users::get_instance();
		}
	);
}
