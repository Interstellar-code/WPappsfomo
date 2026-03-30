<?php
/**
 * Functionality related to the admin TinyMCE editor.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Admin_Editor {


	/**
	 * Social Snap editor fields array.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $fields;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add Tiny MCE Media button.
		add_action( 'media_buttons', array( $this, 'media_button' ), 20 );
		add_filter( 'admin_init', array( $this, 'add_tinymce_filters' ) );

		// Add Block Editor Support.
		add_action( 'plugins_loaded', array( $this, 'initialize_block_editor' ) );
	}

	/**
	 * Integrate Social Snap with block editor.
	 *
	 * @since 1.0.0
	 */
	public function initialize_block_editor() {

		add_action( 'enqueue_block_editor_assets', array( $this, 'block_editor_assets' ) );
	}

	/**
	 * Enqueue assets for block editor.
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function block_editor_assets() {

		wp_enqueue_script(
			'socialsnap-block-editor-js',
			SOCIALSNAP_PLUGIN_URL . 'assets/js/admin-block-editor.js',
			array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element' ),
			filemtime( SOCIALSNAP_PLUGIN_DIR . 'assets/js/admin-block-editor.js' ),
			true
		);

		wp_enqueue_style(
			'socialsnap-block-editor-style',
			SOCIALSNAP_PLUGIN_URL . 'assets/css/admin-block-editor.css',
			array( 'wp-edit-blocks' ),
			filemtime( SOCIALSNAP_PLUGIN_DIR . 'assets/css/admin-block-editor.css' )
		);

		// Localize variables to be used in plugin JavaScript files.
		$strings = array(
			'follow_networks'   => socialsnap_get_social_follow_networks(),
			'share_networks'    => socialsnap_get_social_share_networks(),
			'ctt_default_style' => socialsnap_settings( 'ss_ctt_preview_style' ),
			'icons'             => socialsnap()->icons->get_all_svg_icons(),
			'is_pro'            => socialsnap()->pro,
		);

		wp_localize_script(
			'socialsnap-block-editor-js',
			'socialsnap_block_editor',
			$strings
		);

		// Include assets for TinyMCE
		$this->enqueue_assets();
	}

	/**
	 * Allow easy shortcode insertion via a custom media button.
	 *
	 * @since 1.0.0
	 * @param string $editor
	 * @return
	 */
	public function media_button( $editor ) {

		// Setup the icon
		$icon = '<span class="wp-media-buttons-icon socialsnap-menu-icon">' . socialsnap()->icons->get_svg( 'socialsnap-icon' ) . '</span>';

		printf(
			'<a href="#" class="button ss-mce-button" data-editor="%s" title="%s">%s %s</a>',
			esc_attr( $editor ),
			esc_attr__( 'Social Snap', 'socialsnap' ),
			$icon, // phpcs:ignore
			esc_html__( 'Social Snap', 'socialsnap' )
		);

		// Include assets for TinyMCE
		$this->enqueue_assets();
	}


	/**
	 * Enqueue assets and add modal to footer if neccessary.
	 *
	 * @since 1.0.0
	 * @param string $editor
	 * @return
	 */
	public function enqueue_assets() {

		$this->fields = require SOCIALSNAP_PLUGIN_DIR . 'includes/admin/settings/config-editor.php';

		if ( ! is_array( $this->fields ) || empty( $this->fields ) ) {
			return;
		}

		add_action( 'admin_footer', array( $this, 'media_modal' ), 99 );

		if ( ! wp_style_is( 'socialsnap-editor-style', 'enqueued' ) ) {
			wp_enqueue_style(
				'socialsnap-editor-style',
				SOCIALSNAP_PLUGIN_URL . 'assets/css/admin-editor.css',
				array(),
				SOCIALSNAP_VERSION,
				'all'
			);
		}

		if ( ! wp_script_is( 'socialsnap-settings-js', 'enqueued' ) && ! wp_script_is( 'socialsnap-editor-js', 'enqueued' ) ) {
			wp_enqueue_script(
				'socialsnap-editor-js',
				SOCIALSNAP_PLUGIN_URL . 'assets/js/admin-editor.js',
				array( 'jquery' ),
				SOCIALSNAP_VERSION,
				true
			);

			// Localize variables to be used in plugin JavaScript files.
			$localize = array(
				'l10n' => array(
					'upload_title' => esc_html__( 'Choose or upload a file', 'socialsnap' ),
					'use_file'     => esc_html__( 'Use this file', 'socialsnap' ),
				),
			);

			wp_localize_script(
				'socialsnap-editor-js',
				'socialsnap_editor',
				$localize
			);
		}
	}

	/**
	 * Add filters to extend TinyMCE toolbar
	 *
	 * @since 1.0.0
	 */
	public function add_tinymce_filters() {

		add_filter( 'mce_external_plugins', array( $this, 'add_mce_button' ) );
		add_filter( 'mce_buttons', array( $this, 'register_mce_button' ) );
	}


	/**
	 * Add filters to extend TinyMCE toolbar
	 *
	 * @since 1.0.0
	 */
	public function add_mce_button( $plugin_array ) {

		$plugin_array['socialsnap'] = SOCIALSNAP_PLUGIN_URL . 'assets/js/admin-editor-mce.js';
		return $plugin_array;
	}


	/**
	 * Allow easy shortcode insertion via a custom media button.
	 *
	 * @since 1.0.0
	 * @param string $editor
	 * @return
	 */
	public function register_mce_button( $buttons ) {

		array_push( $buttons, 'ss_shortcode_generator' );
		return $buttons;
	}

	/**
	 * Modal window for inserting the form shortcode into TinyMCE.
	 *
	 * @since 1.0.0
	 */
	public function media_modal() {

		$this->fields = require SOCIALSNAP_PLUGIN_DIR . 'includes/admin/settings/config-editor.php';

		if ( ! is_array( $this->fields ) || empty( $this->fields ) ) {
			return;
		}
		?>

		<div id="ss-modal-overlay">
			<div id="ss-modal">

				<h4>
					<?php echo socialsnap()->icons->get_svg( 'socialsnap-icon' ); // phpcs:ignore ?>
					<?php esc_html_e( 'Insert Element', 'socialsnap' ); ?>
					<a href="#" class="ss-close-modal"><?php echo socialsnap()->icons->get_svg( 'close' ); // phpcs:ignore ?></a>
				</h4>

				<div class="ss-metabox-wrapper ss-clearfix">

					<ul class="ss-metabox-tabs">

						<?php $first_item = true; ?>
						<?php foreach ( $this->fields as $id => $field ) { ?>

							<?php
							if ( true === $first_item ) {
								$first_item = false;
								?>
								<li class="current-menu-item">
							<?php } else { ?>
								<li>
							<?php } ?>
								<a href="#<?php echo esc_attr( $id ); ?>">
									<?php
									if ( isset( $field['icon'] ) ) {
										echo socialsnap()->icons->get_svg( $field['icon'] ); // phpcs:ignore
									}
									echo esc_html( $field['title'] );
									?>
								</a>
							</li>
						<?php } ?>
					</ul><!-- END .ss-metabox-tabs -->

					<div class="ss-metabox-content">

						<?php $first_item = true; ?>
						<?php foreach ( $this->fields as $id => $field ) { ?>

							<?php $tab_class = true == $first_item ? ' ss-active' : ''; ?>
							<?php $first_item = false; ?>

							<div class="ss-tab<?php echo esc_attr( $tab_class ); ?>" data-id="<?php echo esc_attr( $id ); ?>">

							<?php if ( ! isset( $field['options'] ) || empty( $field['options'] ) ) { ?>
								</div><!-- END .ss-tab -->
							<?php continue; } ?>

							<?php
							foreach ( $field['options'] as $option ) {

								$option['value'] = $option['default'] ? $option['default'] : '';
								echo SocialSnap_Fields::build_field( $option ); // phpcs:ignore
							}
							?>

							</div><!-- END .ss-tab -->
						<?php } ?>

						<div class="ss-actions ss-clearfix">
							<a href="#" id="ss-insert-shortcode"><?php echo socialsnap()->icons->get_svg( 'plus' ); // phpcs:ignore ?><?php esc_html_e( 'Insert', 'socialsnap' ); ?></a>
							<a href="#" class="ss-close-modal"><?php esc_html_e( 'Cancel', 'socialsnap' ); ?></a>
						</div><!-- END .ss-actions -->

					</div><!-- END .ss-metabox-content -->
				</div><!-- END .ss-metabox-wrapper -->

			</div><!-- END #ss-modal -->
		</div><!-- END #ss-modal-overlay -->
		<?php
	}
}
new SocialSnap_Admin_Editor();
