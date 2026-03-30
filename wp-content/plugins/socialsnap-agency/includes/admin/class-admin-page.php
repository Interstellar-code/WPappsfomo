<?php
/**
 * Admin page class.
 *
 * This is a general admin page class.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Admin_Page {

	/**
	 * Admin page slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $page_slug;
	
	/**
	 * Admin page title.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $title;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Load plugin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );

		// Add filters for WordPress header and footer text
		add_action( 'in_admin_header', array( $this, 'admin_header' ), 100 );
	}

	/**
	 * Load our required assets on the admin page(s).
	 *
	 * @since 1.0.0
	 * @param $hook, it holds the information about the current page.
	 */
	public function load_assets( $hook ) {

		if ( strpos( $hook, 'socialsnap' ) === false || strpos( $hook, 'socialsnap-settings' ) !== false ) {
			return;
		}

		wp_enqueue_style(
			'socialsnap-admin-page',
			SOCIALSNAP_PLUGIN_URL . 'assets/css/admin-page.css',
			null,
			SOCIALSNAP_VERSION
		);

		wp_enqueue_script(
			'socialsnap-page-js',
			SOCIALSNAP_PLUGIN_URL . 'assets/js/admin-page.js',
			array( 'jquery' ),
			SOCIALSNAP_VERSION,
			true
		);

		// Localize variables to be used in plugin JavaScript files.
		$strings = array(
			'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'socialsnap-admin' ),
			'check_email'   => esc_html__( 'Invalid email address. Please check.', 'socialsnap' ),
			'thanks_email'  => esc_html__( 'Thank you for subscribing!', 'socialsnap' ),
			'error_email'   => esc_html__( 'Something went wrong. Please try again later.', 'socialsnap' ),
			'uploaded_file' => esc_html__( 'Uploaded File', 'socialsnap' ),
		);

		wp_localize_script(
			'socialsnap-page-js',
			'socialsnap_admin',
			$strings
		);

	}

	/**
	 * Outputs the Social Snap admin header.
	 *
	 * @since 1.0.0
	 */
	public function admin_header() {

		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( 'socialsnap-' . $this->page_slug !== $_GET['page'] ) {
			return;
		}
		?>

		<div id="ss-header">
			<?php echo socialsnap()->icons->get_svg( 'socialsnap-light' ); // phpcs:ignore ?>
		</div><!-- END #socialsnap-header -->

		<?php if ( 'socialsnap-addons' === $_GET['page'] ) { ?>
			<h1 class="heading-title ss-clearfix">
				<?php
				esc_html_e( 'Available Addons', 'socialsnap' );

				// $addons_button = '<a href="' . socialsnap_upgrade_link() . '" class="ss-button ss-small-button ss-button-secondary ss-upgrade-button">' . __( 'Upgrade to PRO', 'socialsnap' ) . '</a>';

				$addons_button = '';
				echo wp_kses( apply_filters( 'socialsnap_header_bar_button', $addons_button, 'socialsnap-addons' ), socialsnap_get_allowed_html_tags( 'post' ) );
				?>
			</h1><!-- END .heading-title -->
		<?php } elseif ( 'socialsnap-statistics' === $_GET['page'] ) { ?>
			<h1 class="heading-title ss-clearfix">
				<?php esc_html_e( 'Statistics', 'socialsnap' ); ?>
			</h1><!-- END .heading-title -->
		<?php } elseif ( 'socialsnap-license' === $_GET['page'] ) { ?>
			<h1 class="heading-title ss-clearfix">
				<?php esc_html_e( 'License Activation', 'socialsnap' ); ?>
			</h1><!-- END .heading-title -->
			<?php
		}
	}
}
