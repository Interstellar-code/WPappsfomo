<?php
/**
 * Welcome page class.
 *
 * This page is shown when the plugin is activated.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Welcome extends SocialSnap_Admin_Page {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Call parent constructor
		parent::__construct();

		$this->page_slug = 'welcome';
		$this->title     = __( 'Welcome to Social Snap!', 'socialsnap' );

		// Actions
		add_action( 'admin_menu', array( $this, 'register_pages' ), 12 );
		add_action( 'admin_init', array( $this, 'redirect' ), 9999 );
		add_action( 'admin_notices', array( $this, 'display_notices' ) );

		// Add AJAX handler for subscribe
		add_action( 'wp_ajax_socialsnap_subscribe', array( $this, 'subscribe' ) );
	}

	/**
	 * Register the pages to be used for the Welcome screen (and tabs).
	 *
	 * These pages will be removed from the Dashboard menu, so they will
	 * not actually show.
	 *
	 * @since 1.0.0
	 */
	public function register_pages() {

		// Getting started - shows after installation
		add_submenu_page(
			'socialsnap-settings',
			__( 'Welcome to Social Snap', 'socialsnap' ),
			__( 'About', 'socialsnap' ),
			apply_filters( 'socialsnap_welcome_cap', 'manage_options' ),
			'about-socialsnap',
			array( $this, 'render' )
		);
	}

	/**
	 * Welcome screen redirect.
	 *
	 * This function checks if a new install or update has just occured. If so,
	 * then we redirect the user to the appropriate page.
	 *
	 * @since 1.0.0
	 */
	public function redirect() {

		// Check if we should consider redirection.
		if ( ! get_site_transient( 'socialsnap_activation_redirect' ) ) {
			return;
		}

		// If we are redirecting, clear the transient so it only happens once.
		delete_site_transient( 'socialsnap_activation_redirect' );

		// Check option to disable welcome redirect.
		if ( get_option( 'socialsnap_activation_redirect', false ) ) {
			return;
		}

		// Only do this for single site installs.
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Initial install.
		wp_safe_redirect( admin_url( 'admin.php?page=about-socialsnap' ) );
		exit;
	}

	/**
	 * Build the output for the plugin welcome page.
	 *
	 * @since 1.0.0
	 */
	public function render() { ?>

		<div class="ss-welcome-wrapper">

			<div class="ss-welcome-logo">
				<?php echo socialsnap()->icons->get_svg( 'socialsnap-dark' ); // phpcs:ignore ?>
			</div>

			<div class="ss-welcome-about ss-welcome-section ss-clearfix">

				<p><?php echo wp_kses_post( 'Welcome to <strong>Social Snap</strong> — Start driving more traffic and increase engagement by leveraging the power of social media.', 'socialsnap' ); ?></p>

				<div class="ss-actions-wrapper ss-clearfix">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=socialsnap-settings' ) ); ?>" class="ss-button ss-button-primary ss-button-large"><?php esc_html_e( 'Customize Social Snap', 'socialsnap' ); ?></a>

					<a href="https://socialsnap.com/docs/?utm_source=WordPress&utm_medium=link&utm_campaign=liteplugin" class="ss-button ss-button-secondary" target="_blank"><?php esc_html_e( 'Read Guide', 'socialsnap' ); ?></a>
				</div><!-- END .ss-actions-wrapper -->

			</div><!-- END .ss-welcome-about -->

			<div class="ss-welcome-features ss-welcome-section">

				<h3>
					<?php socialsnap()->pro ? esc_html_e( 'Social Snap Features', 'socialsnap' ) : esc_html_e( 'Social Snap Lite Features', 'socialsnap' ); ?>
				</h3>

				<p><?php esc_html_e( 'Social Snap is the best social media plugin that gives you everything you need to increase shares and drive more traffic to your website.', 'socialsnap' ); ?></p>

				<div class="ss-row ss-clearfix">
					<div class="ss-col-6">
						<div class="ss-feature-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23.87 23.86"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><polygon class="cls-1" points="21.64 23.36 15.66 17.37 12.76 20.6 6.84 6.7 20.69 12.63 17.33 15.58 23.37 21.63 21.64 23.36"/><path class="cls-1" d="M7.34,12.87a4.6,4.6,0,1,1,5.55-5.43"/><path class="cls-1" d="M8.39,16.29a7.9,7.9,0,1,1,7.9-7.89"/></g></g></svg>
						</div>
						<h5><?php esc_html_e( 'Social Share Buttons', 'socialsnap' ); ?></h5>
						<p><?php esc_html_e( 'Add social sharing buttons without slowing down your website.', 'socialsnap' ); ?></p>
					</div>

					<div class="ss-col-6">
						<div class="ss-feature-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M5.5,8.5a2,2,0,0,1,4,0"/><path class="cls-1" d="M14.5,8.5a2,2,0,0,1,4,0"/><path class="cls-1" d="M18.5,17A2.5,2.5,0,0,0,16,14.5a2.37,2.37,0,0,0-2.5,2.34c0,.72.31,1.66.8,1.66h0l4.2,4.37,4.2-4.12v0a2.42,2.42,0,0,0,.8-1.8,2.5,2.5,0,0,0-5,0Z"/><path class="cls-1" d="M15.31,19.83A8.5,8.5,0,0,1,3.5,12"/><path class="cls-1" d="M20.5,12a8.47,8.47,0,0,1-.38,2.53"/><path class="cls-1" d="M17.54,22.08A11.5,11.5,0,1,1,23.5,12,11.37,11.37,0,0,1,23,15.42"/></g></g></svg>
						</div>
						<h5><?php esc_html_e( 'Share Counters', 'socialsnap' ); ?></h5>
						<p><?php esc_html_e( 'Display social media share counters and encourage users to share.', 'socialsnap' ); ?></p>
					</div>
				</div><!-- END .ss-row -->

				<div class="ss-row ss-clearfix">
					<div class="ss-col-6">
						<div class="ss-feature-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 23.5"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M16.36,9.5a6.06,6.06,0,0,0-6.07-6,5.91,5.91,0,0,0-5.89,6A5,5,0,0,0,7,14.37V20h7V14.37C15,13.28,16.36,11.51,16.36,9.5Z"/><path class="cls-1" d="M13.22,20c0,1-1.34,3-3,3s-3-2-3-3Z"/><line class="cls-1" x1="7.5" y1="15" x2="13.5" y2="15"/><line class="cls-1" x1="7.22" y1="15.5" x2="13.22" y2="16.5"/><line class="cls-1" x1="7.22" y1="17.5" x2="13.22" y2="18.5"/><line class="cls-1" x1="0.5" y1="10" x2="2.5" y2="10"/><line class="cls-1" x1="18.5" y1="10" x2="20.5" y2="10"/><line class="cls-1" x1="6.22" y1="0.5" x2="7.22" y2="2.5"/><line class="cls-1" x1="14.22" y1="0.5" x2="13.22" y2="2.5"/><line class="cls-1" x1="1.22" y1="4.5" x2="3.22" y2="5.5"/><line class="cls-1" x1="19.22" y1="4.5" x2="17.22" y2="5.5"/></g></g></svg>
						</div>
						<h5><?php esc_html_e( 'Floating Share Buttons', 'socialsnap' ); ?></h5>
						<p><?php esc_html_e( 'Stunning share buttons that are accessible throughout the entire page.', 'socialsnap' ); ?></p>
					</div>

					<div class="ss-col-6">
						<div class="ss-feature-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24.01 24"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M14.58,23.21a11.48,11.48,0,1,1,7.94-6.55"/><path class="cls-1" d="M14.51,20.12l-.58.16A8.78,8.78,0,0,1,12,20.5,8.51,8.51,0,0,1,3.5,12"/><path class="cls-1" d="M20.5,12a8.4,8.4,0,0,1-.7,3.37,7.57,7.57,0,0,1-.59,1.13"/><path class="cls-1" d="M5.5,8.5a2,2,0,0,1,4,0"/><path class="cls-1" d="M14.5,8.5a2,2,0,0,1,4,0"/><path class="cls-1" d="M14.78,17.86c1-.71,1-2,2-3.77V12.35c1-1,3.06.15,2,4.15h3.12c.49,0,1.64.47,1.64,1.23,0,.52-.11.95-.33,1a1.33,1.33,0,0,1-.25,1.67A1.52,1.52,0,0,1,22.27,22c.09.62-.44,1.46-1,1.46H15.34c-.43,0-.56-.2-.56-.53Z"/></g></g></svg>
						</div>
						<h5><?php esc_html_e( 'On Media Share Buttons', 'socialsnap' ); ?></h5>
						<p><?php esc_html_e( 'Display share buttons on media and leverage the power of Pinterest.', 'socialsnap' ); ?></p>
					</div>
				</div><!-- END .ss-row -->

				<div class="ss-row ss-clearfix">
					<div class="ss-col-6">
						<div class="ss-feature-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><rect class="cls-1" x="12.5" y="0.5" width="11" height="20" rx="1.5" ry="1.5"/><line class="cls-1" x1="13" y1="2.5" x2="23" y2="2.5"/><line class="cls-1" x1="13" y1="17.5" x2="23" y2="17.5"/><path class="cls-1" d="M12,12.5H1.72C.9,12.5.5,13.17.5,14v8c0,.83.4,1.5,1.22,1.5h17A1.76,1.76,0,0,0,20.5,22V21"/><line class="cls-1" x1="2.5" y1="23" x2="2.5" y2="13"/><line class="cls-1" x1="17.5" y1="23" x2="17.5" y2="21"/></g></g></svg>
						</div>
						<h5><?php esc_html_e( 'Fully Responsive', 'socialsnap' ); ?></h5>
						<p><?php esc_html_e( 'Social Snap is fully responsive and looks great on all devices.', 'socialsnap' ); ?></p>
					</div>

					<div class="ss-col-6">
						<div class="ss-feature-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22.5 22.71"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><polygon class="cls-1" points="10.88 11.62 5.22 9.86 10.06 6.43 9.99 0.5 14.74 4.05 20.36 2.14 18.45 7.76 22 12.52 16.07 12.44 12.64 17.28 10.88 11.62"/><line class="cls-1" x1="8.77" y1="13.94" x2="0.5" y2="22.21"/></g></g></svg>
						</div>
						<h5><?php esc_html_e( 'Gutenberg Block &amp; Shortcode', 'socialsnap' ); ?></h5>
						<p><?php esc_html_e( 'Insert social media buttons into your content in a few simple clicks.', 'socialsnap' ); ?></p>
					</div>
				</div><!-- END .ss-row -->

				<div class="ss-row ss-clearfix">
					<div class="ss-col-6">
						<div class="ss-feature-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 15"><defs><style>.cls-1,.cls-2{fill:none;stroke:#d06a5d;stroke-linejoin:round;}.cls-1{stroke-linecap:round;}</style></defs><title>svg</title><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><rect class="cls-1" x="10.28" y="0.5" width="4" height="4"/><line class="cls-2" x1="3.78" y1="2.5" x2="9.78" y2="2.5"/><line class="cls-2" x1="14.78" y1="2.5" x2="20.78" y2="2.5"/><circle class="cls-1" cx="21.02" cy="2.5" r="1"/><circle class="cls-1" cx="3.02" cy="2.5" r="1"/><path class="cls-1" d="M14,3.17A11.51,11.51,0,0,1,23.5,14.5"/><path class="cls-1" d="M.5,14.5A11.51,11.51,0,0,1,10,3.17"/></g></g></svg>
						</div>
						<h5><?php esc_html_e( 'Scalable Vector Icons (SVG)', 'socialsnap' ); ?></h5>
						<p><?php esc_html_e( 'Scalable vector icons will look crisp on all devices and load faster.', 'socialsnap' ); ?></p>
					</div>

					<div class="ss-col-6">
						<div class="ss-feature-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 17.56 23.19"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><polygon class="cls-1" points="8.84 14.19 0.5 14.19 8.84 0.5 8.84 9.19 17.06 9.19 8.84 22.69 8.84 14.19"/></g></g></svg>
						</div>
						<h5><?php esc_html_e( 'Lightning Fast / Async Load', 'socialsnap' ); ?></h5>
						<p><?php esc_html_e( 'Advanced caching and async loading makes everything run smooth.', 'socialsnap' ); ?></p>
					</div>
				</div><!-- END .ss-row -->

				<div class="ss-row ss-clearfix">
					<div class="ss-col-6">
						<div class="ss-feature-icon">
                            <svg viewBox="0 0 24 24" fill='#d06a5d' stroke="#FFF">
                                <g>
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </g>
                            </svg>
                            

						</div>
						<h5><?php esc_html_e( 'Click to Tweet Boxes', 'socialsnap' ); ?></h5>
						<p><?php esc_html_e( 'An effective tool for increasing your site engagement and getting more shares on X (Twitter). ', 'socialsnap' ); ?></p>
					</div>

					<div class="ss-col-6">
						<div class="ss-feature-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23.04 21.6"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M15.14,7.21V9.12s1,.43,1,1v1a2.06,2.06,0,0,1-1.71,1.82A3.53,3.53,0,0,1,11,16.31H10.9a3.31,3.31,0,0,1-3.08-3.45c-.79,0-1.3-1-1.3-1.82v-1c0-.53,0-1,1-1V7.5c0-1.28.64-2.35,1.91-2.35C11.79,4.33,15.14,2.42,15.14,7.21Z"/><path class="cls-1" d="M14.4,16.31a4.69,4.69,0,0,1,4.83,4.79H2.9a4.68,4.68,0,0,1,4.83-4.79"/><path class="cls-1" d="M2.9,6.73A2.39,2.39,0,0,0,.5,9.12"/><path class="cls-1" d="M20.15,6.73a2.39,2.39,0,0,1,2.39,2.39"/><path class="cls-1" d="M15.14,7.21V9.12s1,.43,1,1v1a2.06,2.06,0,0,1-1.71,1.82A3.53,3.53,0,0,1,11,16.31H10.9a3.31,3.31,0,0,1-3.08-3.45c-.79,0-1.3-1-1.3-1.82v-1c0-.53,0-1,1-1V7.5c0-1.28.64-2.35,1.91-2.35C11.79,4.33,15.14,2.42,15.14,7.21Z"/><path class="cls-1" d="M14.4,16.31a4.69,4.69,0,0,1,4.83,4.79H2.9a4.68,4.68,0,0,1,4.83-4.79"/><path class="cls-1" d="M2.9,6.73A2.39,2.39,0,0,0,.5,9.12"/><path class="cls-1" d="M20.15,6.73a2.39,2.39,0,0,1,2.39,2.39"/><path class="cls-2" d="M6.73,2.42c0,1.06-.86,3.83-1.92,3.83S2.9,3.48,2.9,2.42a1.92,1.92,0,1,1,3.83,0Z"/><path class="cls-2" d="M16.31,2.42c0,1.06.86,3.83,1.92,3.83s1.92-2.77,1.92-3.83a1.92,1.92,0,1,0-3.84,0Z"/></g></g></svg>
						</div>
						<h5><?php esc_html_e( 'Social Follow Buttons', 'socialsnap' ); ?></h5>
						<p><?php esc_html_e( 'Get more followers and subscribers with beautifully designed follow buttons and counters.', 'socialsnap' ); ?></p>
					</div>
				</div><!-- END .ss-row -->

				<?php $button_text = socialsnap()->pro ? __( 'See All Features', 'socialsnap' ) : __( 'See Premium Features', 'socialsnap' ); ?>
				<div class="ss-actions-wrapper ss-clearfix">
					<a href="<?php echo esc_url( socialsnap_upgrade_link() ); ?>" class="ss-button ss-button-secondary" target="_blank"><?php echo esc_html( $button_text ); ?></a>
				</div><!-- END .ss-actions-wrapper -->


			</div><!-- END .ss-welcome-section -->


			<?php if ( ! socialsnap()->pro ) { ?>

			<div class="ss-welcome-upgrade ss-welcome-section ss-clearfix">
				<div class="ss-row ss-clearfix">

					<div class="ss-col-8">
						<h3 class="ss-premium"><?php esc_html_e( 'Go Premium', 'socialsnap' ); ?></h3>

						<div class="ss-row ss-clearfix">

							<ul class="ss-col-6 ss-check-list">
								<li><?php esc_html_e( '30+ Share Providers', 'socialsnap' ); ?></li>
								<li><?php esc_html_e( 'More Share Positions', 'socialsnap' ); ?></li>
								<li><?php esc_html_e( 'Share Counters', 'socialsnap' ); ?></li>
								<li><?php esc_html_e( 'URL Shortening', 'socialsnap' ); ?></li>
								<li><?php esc_html_e( 'Analytics', 'socialsnap' ); ?></li>
							</ul>


							<ul class="ss-col-6 ss-check-list">
								<li><?php esc_html_e( 'Top Posts Widget', 'socialsnap' ); ?></li>
								<li><?php esc_html_e( 'Social Login', 'socialsnap' ); ?></li>
								<li><?php esc_html_e( 'Boost Old Posts', 'socialsnap' ); ?></li>
								<li><?php esc_html_e( 'Social Auto Poster', 'socialsnap' ); ?></li>
								<li><?php esc_html_e( 'Social Meta Tags', 'socialsnap' ); ?></li>
							</ul>


						</div><!-- END .ss-row -->
					</div><!-- END .ss-col-8 -->

					<div class="ss-col-4">

						<h3 class="ss-premium"><?php esc_html_e( 'Starting at', 'socialsnap' ); ?></h3>

						<div class="ss-upgrade-price">
							<span class="ss-amount">39</span>
							<span class="ss-per-year">per year</span>
						</div>

						<a href="<?php echo esc_url( socialsnap_upgrade_link() ); ?>" target="_blank" class="ss-button ss-upgrade-button"><?php echo esc_html( apply_filters( 'socialsnap_upgrade_button_text', __( 'Upgrade Now', 'socialsnap' ) ) ); ?></a>
					</div>

				</div><!-- END .ss-row -->
			</div><!-- END .ss-welcome-upgrade -->

			<?php } ?>


			<?php
			$margin_top = '';
			if ( socialsnap()->pro ) {
				$margin_top = ' style="margin-top: 25px;"';
			}
			?>

			<div class="ss-welcome-subscribe ss-welcome-section">
				<div class="ss-subscribe-content">
					<h2><?php esc_html_e( 'Stay in the loop!', 'socialsnap' ); ?></h2>

					<h4 style="max-width:340px;"><?php esc_html_e( 'Sign up to receive emails for the latest Social Snap updates, features, and news.', 'socialsnap' ); ?></h4>

					<div class="ss-subscribe-form">
						<input type="email" placeholder="Enter your email address" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
						<button type="submit" data-nonce="<?php echo esc_attr( wp_create_nonce( 'socialsnap-subscribe' ) ); ?>" class="ss-subscribe-action"><?php esc_html_e( 'Subscribe', 'socialsnap' ); ?></button>

						<span class="spinner"></span>
					</div>

					<div class="ss-subscribe-response">

					</div><!-- END .ss-subscribe-response -->

				</div>
			</div><!-- END .ss-welcome-subscribe -->

		</div><!-- END .ss-welcome-wrapper -->

		<ul class="ss-welcome-follow">
			<li>
				<a href="https://www.facebook.com/socialsnaphq/" target="_blank"><i class="ss-facebook-color"><?php echo socialsnap()->icons->get_svg( 'facebook' ); // phpcs:ignore ?></i></a>
			</li>

			<li>
				<a href="https://twitter.com/socialsnaphq" target="_blank"><i class="ss-twitter-color"><?php echo socialsnap()->icons->get_svg( 'twitter' ); // phpcs:ignore ?></i></a>
			</li>

			<li>
				<a href="https://socialsnap.com/" target="_blank"><i class="ss-socialsnap-color"><?php echo socialsnap()->icons->get_svg( 'socialsnap-icon' ); // phpcs:ignore?></i></a>
			</li>
		</ul>

		<?php
	}


	/**
	 * Handles subscribe AJAX request.
	 *
	 * @since 1.0.0
	 */
	public function subscribe() {

		check_ajax_referer( 'socialsnap-subscribe', 'security' );

		if ( ! isset( $_POST['email'] ) ) {
			wp_send_json_error();
		}

		// Sanitize email
		$email = sanitize_email( $_POST['email'] );

		$url = add_query_arg(
			array(
				'email' => $email,
			),
			'https://socialsnap.com/wp-json/api/v1/subscribe'
		);

		$args = array(
			'user-agent' => 'SocialSnap/' . SOCIALSNAP_VERSION . '; ' . esc_url( home_url() ),
		);

		// Send request to socialsnap.com
		$request = wp_remote_get( $url, $args );

		if ( is_wp_error( $request ) ) {
			wp_send_json_error();
		}

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			wp_send_json_error();
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( ! isset( $response['success'] ) ) {
			wp_send_json_error();
		}

		if ( false == $response['success'] ) {
			wp_send_json_error(
				array(
					'message' => isset( $response['message'] ) ? esc_html( $response['message'] ) : __( 'Error. Please try again.', 'socialsnap' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => isset( $response['message'] ) ? esc_html( $response['message'] ) : __( 'Success.', 'socialsnap' ),
			)
		);
	}

	/**
	 * Outputs the Social Snap admin notices.
	 *
	 * @since 1.0.5
	 */
	public function display_notices() {
	}
}
new SocialSnap_Welcome();
