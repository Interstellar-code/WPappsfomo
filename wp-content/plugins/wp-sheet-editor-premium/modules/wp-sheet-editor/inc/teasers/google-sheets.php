<?php
if ( ! class_exists( 'WPSE_Google_Sheets_Teaser' ) ) {

	class WPSE_Google_Sheets_Teaser {

		private static $instance = null;

		private function __construct() {
		}

		public function init() {
			if ( class_exists( 'WPSE_Google_Sheets' ) || ! is_admin() ) {
				return;
			}
			add_filter( 'vg_sheet_editor/universal_sheet/import_sources_options', array( $this, 'add_google_sheets_import_option' ) );
			add_action( 'vg_sheet_editor/import/after_data_sources_fields', array( $this, 'render_gs_teaser_on_import' ) );
			add_filter( 'vg_sheet_editor/universal_sheet/target_software_options', array( $this, 'add_google_sheets_option_on_export' ) );
			add_action( 'vg_sheet_editor/export/after_target_software_field', array( $this, 'render_gs_teaser_on_export' ) );
		}
		public function render_gs_teaser_on_export() {
			?>
			<template x-if="taskArgs.targetSoftware === 'google_sheets_addon'">
			<div class="google_sheets_addon">
				<p><?php esc_html_e( 'This addon lets you export data from WordPress into Google Sheets directly. We can detect changes made in WordPress and sync those changes into your Google Sheet automatically. Export manually, or save exports for later, or schedule them to run on recurring intervals (hourly, daily, etc). Generate Google Sheet reports on autopilot. We also support bidirectional sync so changes made in Google Sheet are synced to WordPress automatically.', 'vg_sheet_editor' ); ?> <a href="https://wpsheeteditor.com/go/gs-export" target="_blank" class="button button-primary"><?php esc_html_e( 'Watch a demo video', 'vg_sheet_editor' ); ?></a></p>
			</div>
			</template>
			<?php
		}
		/**
		* Add Google Sheets option to target software options
		*
		* @param array $target_options Current target software options
		* @return array Modified target software options
		*/
		public function add_google_sheets_option_on_export( $target_options ) {
			// Add Google Sheets option to the existing options
			if ( isset( $target_options['']['google_sheets'] ) ) {
				$target_options['']['google_sheets'] .= ' ' . esc_html__( '(CSV file)', 'vg_sheet_editor' );
			}
			$target_options[''] = array_merge(
				$target_options[''],
				array(
					'google_sheets_addon' => esc_html__( 'Google Sheets Sync (Recommended Addon)', 'vg_sheet_editor' ),
				)
			);

			return $target_options;
		}
		public function render_gs_teaser_on_import() {
			?>
			<template x-if="taskArgs.source === 'google_sheets_addon'">
			<div class="data-input google_sheets_addon">	
				<p><?php esc_html_e( 'This addon lets you import data from Google Sheets into WordPress directly. Import manually, or save imports for later, or schedule them to run on recurring intervals (hourly, daily, etc). Detect any changes made in Google Sheets and automatically sync those changes into WordPress.', 'vg_sheet_editor' ); ?> <a href="https://wpsheeteditor.com/go/gs-import" target="_blank" class="button button-primary"><?php esc_html_e( 'Watch a demo video', 'vg_sheet_editor' ); ?></a></p>
			</div>
			</template>
			<?php
		}
		/**
		 * Add Google Sheets as an import source option.
		 *
		 * @param array $options The current import source options.
		 * @return array The modified import source options.
		 */
		public function add_google_sheets_import_option( $options ) {
			$options['google_sheets_addon'] = esc_html__( 'Google Sheets Sync (Recommended Addon)', 'vg_sheet_editor' );
			return $options;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		public static function get_instance() {
			if ( null === WPSE_Google_Sheets_Teaser::$instance ) {
				WPSE_Google_Sheets_Teaser::$instance = new WPSE_Google_Sheets_Teaser();
				WPSE_Google_Sheets_Teaser::$instance->init();
			}
			return WPSE_Google_Sheets_Teaser::$instance;
		}

		public function __set( $name, $value ) {
			$this->name = $value;
		}

		public function __get( $name ) {
			return $this->name;
		}

	}
}

if ( ! function_exists( 'WPSE_Google_Sheets_Teaser_Obj' ) ) {
	/**
	 * @return WPSE_Google_Sheets_Teaser
	 */
	function WPSE_Google_Sheets_Teaser_Obj() {
		return WPSE_Google_Sheets_Teaser::get_instance();
	}
}
add_action( 'vg_sheet_editor/initialized', 'WPSE_Google_Sheets_Teaser_Obj' );
