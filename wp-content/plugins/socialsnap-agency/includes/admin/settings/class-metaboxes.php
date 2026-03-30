<?php
/**
 * Register Social Snap metaboxes.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Metabox {

	/**
	 * Social Snap metabox settings array.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $metabox;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $metabox ) {

		$this->metabox = $metabox;

		// Call the register function.
		add_action( 'load-post.php', array( $this, 'register' ), 95 );
		add_action( 'load-post-new.php', array( $this, 'register' ), 95 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
	}

	/**
	 * Registration callback.
	 *
	 * @since  1.1.1
	 */
	public function register() {

		// Add metaboxes.
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ), 5, 2 );

		// Save metaboxes.
		add_action( 'save_post', array( $this, 'save_metaboxes' ) );
		add_action( 'edit_attachment', array( $this, 'save_metaboxes' ) );
	}

	/**
	 * Load required assets on the admin page(s).
	 *
	 * @since 1.0.0
	 */
	public function load_assets( $hook ) {

		global $post;

		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			if ( in_array( $post->post_type, $this->metabox['post_type'] ) ) {
				wp_enqueue_style(
					'socialsnap-editor-style',
					SOCIALSNAP_PLUGIN_URL . 'assets/css/admin-editor.css',
					null,
					SOCIALSNAP_VERSION
				);

				wp_enqueue_media();

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
	}

	/**
	 * Register Metaboxes
	 *
	 * @since 1.0.0
	 */
	public function add_metaboxes( $post_type, $post ) {

		if ( ! function_exists( 'add_meta_box' ) ) {
			return;
		}

		if ( ! isset( $this->metabox['id'] ) || ! isset( $this->metabox['title'] ) ) {
			return;
		}

		if ( ! isset( $this->metabox['post_type'] ) || empty( $this->metabox['post_type'] ) ) {
			$this->metabox['post_type'] = 'post';
		}

		if ( ! isset( $this->metabox['context'] ) ) {
			$this->metabox['context'] = 'normal';
		}

		if ( ! isset( $this->metabox['priority'] ) ) {
			$this->metabox['priority'] = 'high';
		}

		if ( in_array( $post_type, $this->metabox['post_type'] ) ) {

			if ( socialsnap_is_block_editor( $post->ID ) ) {
				add_meta_box(
					$this->metabox['id'] . '_block_editor',
					$this->metabox['title'],
					array( $this, 'render_block_editor' ),
					$post_type,
					'side',
					$this->metabox['priority']
				);
			} else {
				add_meta_box(
					$this->metabox['id'],
					$this->metabox['title'],
					array( $this, 'render_classic' ),
					$post_type,
					$this->metabox['context'],
					$this->metabox['priority']
				);
			}
		}
	}

	public function save_metaboxes( $post_id ) {

		// Security check
		if ( ! isset( $_POST[ $this->metabox['id'] . '_noncename' ] ) ) {
			return $post_id;
		}

		// Security check
		if ( ! wp_verify_nonce( $_POST[ $this->metabox['id'] . '_noncename' ], 'socialsnap-metaboxes' ) ) {
			return $post_id;
		}

		// User does not have permission to change metabox values
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		// Already saving
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! is_array( $this->metabox['options'] ) || empty( $this->metabox['options'] ) ) {
			return $post_id;
		}

		// Loop through tabs
		foreach ( $this->metabox['options'] as $id => $option_group ) {

			if ( ! isset( $option_group['options'] ) || empty( $option_group['options'] ) ) {
				continue;
			}

			// Loop through options in each tab
			foreach ( $option_group['options'] as $option ) {

				if ( ! isset( $option['id'] ) || empty( $option['id'] ) ) {
					continue;
				}

				$type = isset( $option['type'] ) ? $option['type'] : '';

				if ( 'editor_textarea' === $type ) {
					$value = isset( $_POST[ $option['id'] ] ) ? sanitize_textarea_field( $_POST[ $option['id'] ] ) : false;
				} else {
					$value = isset( $_POST[ $option['id'] ] ) ? sanitize_text_field( $_POST[ $option['id'] ] ) : false;
				}

				if ( get_post_meta( $post_id, $option['id'] ) == '' ) {
					add_post_meta( $post_id, $option['id'], $value, true );
				} else {
					update_post_meta( $post_id, $option['id'], $value );
				}
			}
		}
	}


	/**
	 * Render the metabox options for Classic Editor
	 *
	 * @since 1.0.0
	 */
	public function render_classic( $post ) {

		// No Options are set
		if ( ! is_array( $this->metabox['options'] ) || empty( $this->metabox['options'] ) ) {
			return;
		}

		$screen = get_current_screen();

		$output = '<div class="ss-metabox-wrapper ss-clearfix">';

		// Tabs
		$output .= '<ul class="ss-metabox-tabs">';
		$first   = true;
		foreach ( $this->metabox['options'] as $id => $option_group ) {

			$option_post_type = isset( $option_group['post_type'] ) ? $option_group['post_type'] : '';

			// Should we display this option group on current post type?
			if ( is_array( $option_post_type ) && ! in_array( $screen->post_type, $option_post_type ) ) {
				continue;
			}

			// Add icon to tab menu item
			if ( isset( $option_group['icon'] ) ) {
				$option_group['icon'] = socialsnap()->icons->get_svg( $option_group['icon'] );
			} else {
				$option_group['icon'] = '';
			}

			// First tab hould be open by default on page load
			$current_class = true == $first ? ' class="current-menu-item"' : '';
			$first         = false;

			$output .= '<li' . $current_class . '><a href="#' . $id . '">' . $option_group['icon'] . $option_group['title'] . '</a></li>';
		}
		$output .= '</ul><!-- END .ss-metabox-tabs -->';

		// Content of tabs
		$output .= '<div class="ss-metabox-content">';
		$first   = true;
		foreach ( $this->metabox['options'] as $id => $option_group ) {

			// First tab content should be open by default on page load
			$active_class = true == $first ? ' ss-active' : '';
			$first        = false;

			$output .= '<div class="ss-tab' . $active_class . '" data-id="' . $id . '">';

			if ( ! isset( $option_group['options'] ) || empty( $option_group['options'] ) ) {
				$output .= '</div><!-- END .ss-tab -->';
				continue;
			}

			foreach ( $option_group['options'] as $option ) {

				$value = get_post_meta( $post->ID, $option['id'], true );
				if ( '' !== $value ) {
					$option['value'] = $value;
				} elseif ( metadata_exists( 'post', $post->ID, $option['id'] ) ) {
					$option['value'] = '';
				} else {
					$option['value'] = isset( $option['default'] ) ? $option['default'] : '';
				}

				$output .= SocialSnap_Fields::build_field( $option );
			}

			$output .= '</div><!-- END .ss-tab -->';
		}
		$output .= '</div><!-- END .ss-metabox-content -->';

		$output .= '<input type="hidden" name="' . $this->metabox['id'] . '_noncename" id="' . $this->metabox['id'] . '_noncename" value="' . wp_create_nonce( 'socialsnap-metaboxes' ) . '" />';

		$output .= '</div><!-- END .ss-metabox-wrapper -->';

		echo $output; // phpcs:ignore
	}

	/**
	 * Render the metabox options for Block Editor
	 *
	 * @since 1.0.0
	 */
	public function render_block_editor( $post ) {

		// No Options are set
		if ( ! is_array( $this->metabox['options'] ) || empty( $this->metabox['options'] ) ) {
			return;
		}

		$screen = get_current_screen();

		$output = '';

		foreach ( $this->metabox['options'] as $id => $option_group ) {

			$output .= '<div class="ss-meta-gb-title">';
			if ( isset( $option_group['icon'] ) ) {
				$output .= socialsnap()->icons->get_svg( $option_group['icon'] );
			}
			$output .= 'Social Snap ' . $option_group['title'] . '</div>';

			if ( is_array( $option_group['options'] ) && ! empty( $option_group['options'] ) ) {
				foreach ( $option_group['options'] as $option ) {

					$value = get_post_meta( $post->ID, $option['id'], true );
					if ( '' !== $value ) {
						$option['value'] = $value;
					} elseif ( metadata_exists( 'post', $post->ID, $option['id'] ) ) {
						$option['value'] = '';
					} else {
						$option['value'] = isset( $option['default'] ) ? $option['default'] : '';
					}

					$output .= SocialSnap_Fields::build_field( $option );
				}
			}
		}

		$output .= '<input type="hidden" name="' . esc_attr( $this->metabox['id'] ) . '_noncename" id="' . esc_attr( $this->metabox['id'] ) . '_noncename" value="' . esc_attr( wp_create_nonce( 'socialsnap-metaboxes' ) ) . '" />';

		echo $output; // phpcs:ignore
	}
}

/**
 * Read metaboxes config and create multiple metaboxes.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Metaboxes {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 20 );
	}

	/**
	 * Read the config file and create an instance of SocialSnap_Metabox for each metabox group.
	 */
	public function init() {

		$metaboxes = require_once SOCIALSNAP_PLUGIN_DIR . 'includes/admin/settings/config-metaboxes.php';

		if ( ! is_array( $metaboxes ) || empty( $metaboxes ) ) {
			return;
		}

		foreach ( $metaboxes as $metabox ) {
			new SocialSnap_Metabox( $metabox );
		}
	}
}
new SocialSnap_Metaboxes();
