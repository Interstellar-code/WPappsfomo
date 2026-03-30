<?php
/**
 * Social Sharing. Class generates sharing buttons.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Social_Share {

	/**
	 * Singleton instance of the class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance;

	/**
	 * Social Networks array.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $networks;

	/**
	 * Display Positions of share bar.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $positions;

	/**
	 * Indicator to check if share buttons are placed on the page.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $is_displayed = false;

	/**
	 * Indicator to check if share counts are displayed.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $counts_displayed = false;

	/**
	 * Indicator to check if share counts are displayed.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $networks_displayed = array();

	/**
	 * Indicator to check if share all is displayed.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $share_all_popup_displayed = false;

	/**
	 * Current post ID.
	 *
	 * @since 1.1.8
	 * @var array
	 */
	protected $post_id = false;

	/**
	 * Main SocialSnap_Social_Share Instance.
	 *
	 * @since 1.1.6
	 * @return SocialSnap_Social_Share
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SocialSnap_Social_Share ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( is_admin() ) {
			add_action( 'init', array( $this, 'init' ), 20 );
			add_action( 'init', array( $this, 'display_positions' ), 20 );
		} else {
			add_action( 'wp', array( $this, 'init' ), 20 );
			add_action( 'wp', array( $this, 'display_positions' ), 20 );
		}

		// Register a shortcode for Social Share.
		add_shortcode( 'ss_social_share', array( $this, 'shortcode_social_share' ) );
		add_shortcode( 'ss_on_media_share', array( $this, 'shortcode_on_media_share' ) );

		// Add support for Block Editor.
		add_action( 'plugins_loaded', array( $this, 'block_editor_support' ) );

		// Refresh share count cache.
		add_action( 'wp_ajax_socialsnap_ss_cache_refresh', array( $this, 'socialsnap_refresh_share_count_cache' ) );

		add_filter( 'socialsnap_complete_shared_permalink', array( $this, 'remove_params' ), 5, 2 );
	}

	/**
	 * Initialize class variables.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Social networks.
		$this->networks = apply_filters(
			'socialsnap_filter_social_share_networks',
			socialsnap_settings( 'ss_social_share_networks' )
		);

		// Share buttons positions.
		$this->positions = socialsnap_get_social_share_positions();

		// Post ID.
		$this->post_id = socialsnap_get_current_post_id();

		// Crate marker that shares have expired and to fetch new.
		if ( ! is_admin() ) {
			add_action( 'wp_footer', array( $this, 'share_count_cache_expired' ), 99 );
		}
	}

	/**
	 * Display Social Sharing buttons on enabled positions in the settings panel.
	 *
	 * @since 1.0.0
	 */
	public function display_positions() {

		// No positions are enabled.
		if ( ! is_array( $this->positions ) || empty( $this->positions ) ) {
			return;
		}

		// Check AMP pages.
		if ( socialsnap_is_amp_page() ) {
			return;
		}

		add_filter( 'socialsnap_display_position_classes', array( $this, 'get_display_position_classes' ), 10, 3 );

		// All networks popup.
		add_action( 'wp_footer', array( $this, 'render_share_all_popup' ), 20 );
		add_action( 'wp_footer', array( $this, 'render_copy_popup' ), 20 );

		foreach ( $this->positions as $position ) {

			// Check if render method exists.
			if ( ! method_exists( $this, 'render_position_' . $position ) ) {
				continue;
			}

			// Hook into Admin Live Preview.
			add_action( 'preview_social_share_' . $position, array( $this, 'render_position_' . $position ) );

			// Move on if this position is not enabled.
			if ( ! socialsnap_settings( 'ss_ss_' . $position . '_enabled' ) ) {
				continue;
			}

			switch ( $position ) {
				case 'sidebar':
					add_action( 'wp_footer', array( $this, 'render_position_sidebar' ) );
					break;

				case 'inline_content':
					if ( ! is_admin() ) {

						if ( is_singular() || socialsnap_settings( 'ss_ss_inline_content_full_content' ) ) {
							add_filter( 'the_content', array( $this, 'filter_content_inline_content_share' ), 100 );
						} else {
							add_filter( 'the_excerpt', array( $this, 'filter_content_inline_content_share' ), 100 );
						}

						add_action( 'woocommerce_share', array( $this, 'inline_content_woocommerce_product' ) );
					}
					break;

				case 'on_media':
					if ( ! is_admin() && socialsnap_settings( 'ss_ss_on_media_enabled' ) && $this->check_display_on( socialsnap_settings( 'ss_ss_on_media_post_types' ) ) ) {
						add_filter( 'the_content', array( $this, 'filter_content_on_media_share' ), 30 );
						add_filter( 'the_content', array( $this, 'filter_content_on_media_share' ), 30 );
						add_filter( 'post_thumbnail_html', array( $this, 'filter_content_on_media_share' ), 30 );
						add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'filter_content_on_media_share' ), 30 );
					}
					break;

				default:
					// code...
					break;
			}
		}
	}

	/**
	 * Determines whether the location should be displayed on this page or not
	 *
	 * @param array $settings Array of pages where enabled.
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_display_on( $settings ) {

		if ( ! is_array( $settings ) || empty( $settings ) ) {
			return false;
		}

		if ( get_post_meta( socialsnap_get_current_post_id(), 'ss_social_share_disable', true ) ) {
			return false;
		}

		foreach ( $settings as $key => $value ) {

			if ( ! $value ) {
				continue;
			}

			switch ( $key ) {
				case 'home':
					if ( is_front_page() ) {
						return true;
					}
					break;

				case 'blog':
					if ( is_home() ) {
						return true;
					}
					break;

				case 'shop':
					if ( function_exists( 'is_shop' ) && is_shop() ) {
						return true;
					}
					break;

				case 'category':
					if ( is_category() ) {
						return true;
					}
					break;

				case 'post_tag':
					if ( is_tag() ) {
						return true;
					}
					break;

				default:
				
					if ( post_type_exists( $key ) && get_post_type( socialsnap_get_current_post_id() ) === $key && is_singular( $key ) && ! socialsnap_is_homepage() ) {
						return true;
					} elseif ( is_tax( $key ) ) {
						return true;
					} elseif ( false !== strpos( $key, 'archive_' ) && post_type_exists( str_replace( 'archive_', '', $key ) ) && is_post_type_archive( str_replace( 'archive_', '', $key ) ) ) {
						// Post type archive.
						return true;
					}
					break;
			}
		}

		return false;
	}

	/**
	 * Determines whether the share popup html should be printed.
	 * Returns true if at least one All Networks button is enabled.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_display_share_popup() {

		$return = false;

		if ( is_array( $this->positions ) && ! empty( $this->positions ) ) {
			foreach ( $this->positions as $position ) {

				if ( in_array( $position, array( 'on_media' ) ) ) {
					continue;
				}

				$position_enabled     = socialsnap_settings( 'ss_ss_' . $position . '_enabled' );
				$all_networks_enabled = socialsnap_settings( 'ss_ss_' . $position . '_all_networks' );
				$display              = $this->check_display_on( socialsnap_settings( 'ss_ss_' . $position . '_post_types' ) );

				$return = $return || ( $position_enabled && $all_networks_enabled && $display );
			}
		}

		return apply_filters( 'socialsnap_display_share_popup', $return );
	}

	/**
	 * Render popup that displays share buttons for all networks
	 *
	 * @since 1.0.0
	 */
	public function render_share_all_popup() {

		// No need for this since no share buttons are displayed.
		if ( ! apply_filters( 'socialsnap_share_all_popup_displayed', $this->share_all_popup_displayed ) ) {
			return;
		}

		$all_networks = apply_filters( 'socialsnap_all_networks_popup_networks', socialsnap_get_social_share_networks() );
		$post_id      = socialsnap_get_current_post_id();
		$post_title   = socialsnap_get_current_page_title();
		$current_link = socialsnap_get_current_url();

		if ( ! is_admin() ) {
			socialsnap_enqueue_assets();
		}

		ob_start();
		?>

		<div id="ss-all-networks-popup" class="ss-popup-overlay" data-nonce="<?php echo esc_attr( wp_create_nonce( 'socialsnap-ss-counts-refresh' ) ); ?>">
			<div class="ss-popup">

				<div class="ss-popup-heading">
					<span><?php esc_html_e( 'Share via', 'socialsnap' ); ?></span>
					<a href="#" class="ss-close-modal" rel="nofollow noopener">
						<?php echo socialsnap()->icons->get_svg( 'close' ); // phpcs:ignore ?>
					</a>
				</div><!-- END .ss-popup-heading -->

				<div class="ss-popup-content">
					<div class="ss-popup-networks ss-clearfix">
						<?php
						foreach ( $all_networks as $id => $name ) {

							$this->add_displayed_networks( $id );

							$permalink = socialsnap_get_shared_permalink(
								array(
									'permalink' => $current_link,
									'network'   => $id,
								)
							);
							$permalink = apply_filters( 'socialsnap_complete_shared_permalink', $permalink, $id );
							$share_url = socialsnap_get_share_url(
								$id,
								array(
									'permalink' => $permalink,
									'title'     => $post_title,
									'location'  => 'share_all_popup',
								)
							);

							$additional_data = $this->get_button_data( $share_url, $permalink, $id, $post_id, 'popup' );
							$icon_class      = apply_filters( 'socialsnap_social_share_button_class', 'ss-' . $id . '-color', $id, $post_id );

							?>

							<div class="ss-popup-network ss-popup-<?php echo esc_attr( $id ); ?>">
								<a href="#" aria-label="<?php echo esc_attr( $name ); ?>" data-ss-ss-link="<?php echo esc_url( $share_url, socialsnap_get_allowed_protocols() ); ?>" data-id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $icon_class ); ?>" <?php echo $additional_data; // phpcs:ignore ?> rel="nofollow noopener"> <!-- phpcs:ignore -->
									<span><?php echo socialsnap()->icons->get_svg( $id ); // phpcs:ignore ?></span>
									<span><?php echo esc_html( $name ); ?></span>
								</a>
							</div>

						<?php } ?>
					</div><!-- END .ss-popup-networks -->

					<?php socialsnap_signature(); ?>

				</div><!-- END .ss-popup-content -->
			</div><!-- END .ss-popup -->
		</div><!-- END #ss-all-networks-popup -->

		<?php
		$output = ob_get_clean();

		echo wp_kses( apply_filters( 'socialsnap_share_all_popup', $output ), socialsnap_get_allowed_html_tags( 'post' ) );
	}

	/**
	 * Render popup that displays the copy link.
	 *
	 * @since 1.1.6
	 */
	public function render_copy_popup() {

		if ( ! in_array( 'copy', $this->networks_displayed, true ) ) {
			return;
		}

		$post_id = socialsnap_get_current_post_id();

		if ( 0 === $post_id ) {
			return;
		}

		$permalink = socialsnap_get_shared_permalink(
			array(
				'permalink' => socialsnap_get_current_url(),
				'network'   => 'copy',
			)
		);
		$permalink = apply_filters( 'socialsnap_complete_shared_permalink', $permalink, 'copy' );

		socialsnap_enqueue_assets();

		ob_start();
		?>

		<div id="ss-copy-popup" class="ss-popup-overlay">
			<div class="ss-popup">

				<div class="ss-popup-heading">
					<span><?php esc_html_e( 'Copy link', 'socialsnap' ); ?></span>
					<a href="#" class="ss-close-modal" rel="nofollow noopener">
						<?php echo socialsnap()->icons->get_svg( 'close' ); // phpcs:ignore ?>
					</a>
				</div><!-- END .ss-popup-heading -->

				<div class="ss-popup-content">

					<div class="ss-copy-action">
						<input type="text" readonly="readonly" value="<?php echo esc_url( $permalink ); ?>" class="ss-copy-action-field" aria-label="<?php esc_html_e( 'Copy', 'socialsnap' ); ?>" />
						<a href="#" class="ss-button" rel="nofollow noopener"><?php esc_html_e( 'Copy', 'socialsnap' ); ?><span class="ss-share-network-tooltip"><?php esc_html_e( 'Copied', 'socialsnap' ); ?></span></a>
						<?php echo socialsnap()->icons->get_svg( 'copy' ); // phpcs:ignore ?>
					</div><!-- END .ss-copy-action -->

					<?php socialsnap_signature(); ?>

				</div><!-- END .ss-popup-content -->
			</div><!-- END .ss-popup -->
		</div><!-- END #ss-copy-popup -->

		<?php
		$output = ob_get_clean();

		echo $output; // phpcs:ignore
	}

	/**
	 * Display Social Sharing buttons on a floating sidebar.
	 *
	 * @since 1.0.0
	 */
	public function render_position_sidebar() {
		
		if ( ! socialsnap_settings( 'ss_ss_sidebar_enabled' ) && ! is_admin() ) {
			return;
		}

		if ( ! is_admin() && ! $this->check_display_on( socialsnap_settings( 'ss_ss_sidebar_post_types' ) ) ) {
			return;
		}

		// No networks selected in the settings panel.
		if ( ! is_array( $this->networks ) || empty( $this->networks ) ) {
			return;
		}

		$class = array();
		$class = apply_filters( 'socialsnap_display_position_classes', $class, 'sidebar' );
		$class = implode( ' ', $class );

		$atts       = apply_filters( 'socialsnap_floating_sidebar_atts', array() );
		$attributes = '';

		if ( ! empty( $atts ) ) {
			foreach ( $atts as $key => $value ) {
				$attributes .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		ob_start();
		?>
		<div id="ss-floating-bar" class="<?php echo esc_attr( $class ); ?>" <?php echo $attributes; // phpcs:ignore ?>>

			<div class="ss-floating-bar__inner">
				<?php
				$params = array(
					'post_id'   => $this->post_id,
					'permalink' => socialsnap_get_current_url(),
				);

				$this->render_share_count( 'sidebar', $params );
				$this->render_social_icons( 'sidebar', $params );
				$this->render_view_count( 'sidebar', $params );
				?>

				<span class="ss-hide-floating-bar">					
					<svg width="5" height="8" viewBox="0 0 5 8" fill="none">
						<path d="M4.25 0.75L0.75 4L4.25 7.25" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</span>
			</div>

		</div><!-- END #ss-floating-bar -->
		<?php
		$output = ob_get_clean();

		$this->add_inline_styles( 'sidebar' );		

		echo wp_kses( apply_filters( 'socialsnap_social_share_sidebar', $output ), socialsnap_get_allowed_html_tags( 'post' ) );
	}

	/**
	 * Display Social Sharing buttons around content.
	 *
	 * @param array $options Inline buttons options.
	 * @since 1.0.0
	 */
	public function render_position_inline_content( $options = array() ) {

		$defaults = array(
			'share_label'      => socialsnap_settings( 'ss_ss_inline_content_share_label' ),
			'show_total_count' => socialsnap_settings( 'ss_ss_inline_content_total_count' ),
			'format_number'    => true,
		);

		$options = array_replace_recursive( $defaults, (array) $options );

		$class = array( 'ss-inline-share-wrapper' );
		$class = apply_filters( 'socialsnap_display_position_classes', $class, 'inline_content', $options );
		$class = implode( ' ', $class );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class ); ?>">

			<?php
			// Echo share label.
			if ( $options['share_label'] ) {
				?>
				<p class="ss-social-share-label">
					<span><?php echo wp_kses( $options['share_label'], socialsnap_get_allowed_html_tags( 'post' ) ); ?></span>
				</p>
			<?php } ?>

			<div class="ss-inline-share-content">

				<?php
				$this->render_share_count(
					'inline_content',
					array(
						'post_id'   => get_the_ID(),
						'permalink' => socialsnap_get_current_url(),
						'options'   => $options,
					)
				);
				?>
				<?php
				$this->render_social_icons(
					'inline_content',
					array(
						'post_id'   => get_the_ID(),
						'permalink' => socialsnap_get_current_url(),
						'options'   => $options,
					)
				);
				?>

			</div><!-- END .ss-inline-share-content -->
		</div><!-- END .ss-inline-share-wrapper -->
		<?php
		$output = ob_get_clean();
		echo wp_kses( apply_filters( 'socialsnap_social_share_inline_content', $output ), socialsnap_get_allowed_html_tags( 'post' ) );
	}

	/**
	 * Filter content and insert inline social sharing buttons
	 *
	 * @param string $content Post content.
	 * @since 1.0.0
	 */
	public function filter_content_inline_content_share( $content = '' ) {

		if ( ! socialsnap_settings( 'ss_ss_inline_content_enabled' ) && ! is_admin() ) {
			return $content;
		}

		if ( post_type_exists( 'product' ) && is_singular( 'product' ) ) {
			return $content;
		}

		if ( ! is_admin() && ! $this->check_display_on( socialsnap_settings( 'ss_ss_inline_content_post_types' ) ) ) {
			return $content;
		}

		ob_start();
		$this->render_position_inline_content();
		$output = ob_get_clean();

		$inline_location = socialsnap_settings( 'ss_ss_inline_content_location' );

		if ( in_array( $inline_location, array( 'above', 'both' ) ) ) {
			$content = $output . $content;
		}

		if ( in_array( $inline_location, array( 'below', 'both' ) ) ) {
			$content = $content . $output;
		}

		return $content;
	}

	/**
	 * Share buttons on product pages.
	 *
	 * @since  1.1.8
	 * @return void
	 */
	public function inline_content_woocommerce_product() {

		if ( ! socialsnap_settings( 'ss_ss_inline_content_enabled' ) && ! is_admin() ) {
			return;
		}

		if ( ! is_admin() && ! $this->check_display_on( socialsnap_settings( 'ss_ss_inline_content_post_types' ) ) ) {
			return;
		}

		$this->render_position_inline_content();
	}

	/**
	 * Filter content and insert on media sharing buttons
	 *
	 * @param string $content Post content.
	 * @param array  $args On Media buttons parameters.
	 * @since 1.0.0
	 */
	public function filter_content_on_media_share( $content, $args = array() ) {

		$prep_content = $content;
		$skip_classes = array();

		if ( defined( 'ZB_T20_URL' ) ) {
			// Twenty20 Image Before-After compatibility.
			$skip_classes[] = 'twenty20';
			$skip_classes[] = 'bafg-twentytwenty-container';
		}

		$skip_classes = apply_filters( 'socialsnap_on_media_skip_classes', $skip_classes );

		// Skip divs with classes. Plugins compatibility.
		$prep_content = socialsnap_strip_tags_by_class( $skip_classes, $prep_content );

		// Get all imgs from prepped content.
		preg_match_all( '/<img [^>]*>/s', $prep_content, $images_array );

		if ( ! is_array( $images_array ) || empty( $images_array ) ) {
			return $content;
		}

		$images_array = array_unique( $images_array[0] );

		foreach ( $images_array as $image ) {

			if ( false !== strpos( $image, 'ss-on-media-img' ) ) {
				continue;
			}

			if ( false !== strpos( $image, 'rev-slide' ) ) {
				// Revolution slide compatibility.
				continue;
			}

			if ( false !== strpos( $image, 'mv-create-image' ) ) {
				// Create by Mediavine compatibility.
				continue;
			}

			preg_match( '@src="([^"]+)"@', $image, $image_src );

			// Check for image src.
			if ( isset( $image_src[1] ) ) {
				$image_src = $image_src[1];
			} elseif ( isset( $image_src[0] ) ) {
				$image_src = $image_src[0];
			}

			preg_match( '@data-src="([^"]+)"@', $image, $image_data_src );

			// Check for image src.
			if ( isset( $image_data_src[1] ) ) {
				$image_src = $image_data_src[1];
			} elseif ( isset( $image_data_src[0] ) ) {
				$image_src = $image_data_src[0];
			}

			if ( ! isset( $image_src ) || empty( $image_src ) ) {
				continue;
			}

			// Get classes from img.
			preg_match( '@class="([^"]+)"@', $image, $image_classes );

			$image_class      = 'ss-on-media-container';
			$image_wrap_class = 'ss-on-media-image-wrap';

			if ( isset( $image_classes[1] ) ) {
				$image_class      .= ' ' . $image_classes[1];
				$image_wrap_class .= ' ' . $image_classes[1];

				$image_processed = str_replace( 'class="', 'class="ss-on-media-img ', $image );
			} else {
				$image_processed = str_replace( '<img', '<img class="ss-on-media-img"', $image );
			}

			ob_start();
			$this->render_position_on_media( $image_src, $args );
			$icons = ob_get_clean();

			if ( ! $icons ) {
				continue;
			}

			$replacement = '<div class="' . $image_class . '"><span class="' . $image_wrap_class . '">' . $image_processed . $icons . '</span></div>';
			$content     = str_replace( $image, $replacement, $content );
		}

		return $content;
	}

	/**
	 * Display Social Sharing buttons on a media element.
	 *
	 * @param string $image_src Image URL.
	 * @param array  $args Array of arguments for on media buttons.
	 * @since 1.0.0
	 */
	public function render_position_on_media( $image_src, $args = array() ) {

		$defaults = array(
			'type'  => socialsnap_settings( 'ss_ss_on_media_type' ),
			'class' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$class = array( 'ss-on-media-wrapper' );
		$class = array_merge( $class, (array) $args['class'] );
		$class = apply_filters( 'socialsnap_display_position_classes', $class, 'on_media' );
		$class = implode( ' ', $class );

		$title         = '';
		$attachment_id = '';

		if ( $image_src ) {
			$attachment_id = (int) socialsnap_get_attachment_id_by_url( $image_src );
			$attachment    = get_post( $attachment_id );

			if ( $attachment && isset( $attachment->post_content ) ) {
				$title = $attachment->post_content;
			}
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class ); ?>">

			<?php
			if ( 'pin_it' === $args['type'] || is_admin() ) {

				$title = apply_filters( 'socialsnap_on_media_pin_it_title', $title, $image_src );

				$share_url = is_admin() ? '#' : socialsnap_get_share_url(
					'pinterest',
					array(
						'image'    => $image_src,
						'location' => 'on_media',
						'title'    => $title,
					)
				);

				if ( $share_url ) {

					// Enqueue assets.
					socialsnap_enqueue_assets();
					?>
					<ul class="ss-social-icons-container ss-on-media-pinit">
						<li>
							<div data-ss-ss-link="<?php echo esc_url( $share_url, socialsnap_get_allowed_protocols() ); ?>" class="ss-pinterest-color ss-pinit-button ss-ss-on-media-button" data-ss-ss-location="on_media" data-ss-ss-network-id="pinterest" data-ss-ss-type="share">
								<span class="ss-on-media-content">
									<?php echo socialsnap()->icons->get_svg( 'pinterest' ); // phpcs:ignore ?><?php echo esc_html( apply_filters( 'socialsnap_pinit_text', __( 'Save', 'socialsnap' ) ) ); ?>
								</span>
							</div>
						</li>
					</ul>
					<?php
				}
			}

			$args['image']   = $image_src;
			$args['post_id'] = $attachment_id;

			if ( 'share_buttons' === $args['type'] || is_admin() ) {
				$this->render_social_icons( 'on_media', $args );
			}
			?>
		</div>
		<?php
		$output = ob_get_clean();
		echo wp_kses( apply_filters( 'socialsnap_social_share_on_media', $output ), socialsnap_get_allowed_html_tags( 'post' ) );
	}

	/**
	 * Display Total share count
	 *
	 * @param string $location Location name.
	 * @param array  $settings Array of arguments.
	 * @since 1.0.0
	 */
	protected function render_share_count( $location = null, $settings = array() ) {

		if ( is_null( $location ) ) {
			return;
		}

		$defaults = array(
			'post_id'   => socialsnap_get_current_post_id(),
			'permalink' => socialsnap_get_current_url(),
			'options'   => array(
				'show_total_count'   => socialsnap_settings( 'ss_ss_' . $location . '_total_count' ),
				'inline_total_style' => socialsnap_settings( 'ss_ss_inline_content_total_share_style' ),
				'format_number'      => true,
			),
		);

		$settings = array_replace_recursive( $defaults, $settings );
		$settings = apply_filters( 'socialsnap_social_share_display_args', $settings, $location );

		if ( ! $settings['post_id'] ) {
			$settings['post_id'] = socialsnap_get_current_post_id();
		}

		if ( 0 === $settings['post_id'] && ! is_admin() ) {
			return;
		}

		if ( is_admin() ) {
			$count = wp_rand( 300, 700 );
		} else {
			$count = socialsnap_get_total_share_count( array( 'post_id' => $settings['post_id'] ) );
		}

		$this->counts_displayed = $this->counts_displayed || $settings['options']['show_total_count'];

		$settings = apply_filters( 'socialsnap_total_share_count_settings', $settings, $location, $count );

		if ( ! $settings['options']['show_total_count'] && ! is_admin() ) {
			return;
		}

		if ( $settings['options']['format_number'] ) {
			$count = socialsnap_format_number( $count );
		}

		if ( 'inline_content' === $location ) {
			?>

			<!-- Total share counter -->
			<div class="ss-inline-counter">

			<?php do_action( 'socialsnap_before_total_share_counter', $location, $settings ); ?>

		<?php } ?>

		<span class="ss-total-counter ss-total-shares ss-share-<?php echo esc_attr( $location ); ?>-total-shares" data-ss-ss-post-id="<?php echo esc_attr( $settings['post_id'] ); ?>">
			<span><?php echo esc_html( $count ); ?></span>
			<span><?php echo wp_kses( apply_filters( 'socialsnap_total_share_label', _n( 'Share', 'Shares', $count, 'socialsnap' ), $count ), socialsnap_get_allowed_html_tags( 'post' ) ); ?></span>
		</span>

		<?php if ( 'inline_content' === $location ) { ?>
			</div>
			<?php
		}

		do_action( 'socialsnap_after_total_share_counter', $location, $settings );
	}

	/**
	 * Display View count
	 *
	 * @param string $location Location name.
	 * @since 1.0.0
	 */
	protected function render_view_count( $location = null ) {
		echo wp_kses( apply_filters( 'socialsnap_view_count', '', $location ), socialsnap_get_allowed_html_tags( 'post' ) );
	}

	/**
	 * Generate list of social sharing icons
	 *
	 * @param string $location Location name.
	 * @param array  $data Location data.
	 * @since 1.0.0
	 */
	protected function render_social_icons( $location, $data = array() ) {

		// Location is required.
		if ( ! isset( $location ) ) {
			return;
		}

		// Set the displayed indicator to true.
		$this->is_displayed = true;

		// Default settings.
		$defaults = array(
			'post_id'   => '',
			'permalink' => '',
			'image'     => '',
			'options'   => array(
				'networks'            => array(),
				'share_text'          => '',
				'share_count'         => socialsnap_settings( 'ss_ss_' . $location . '_share_count' ),
				'tooltip'             => socialsnap_settings( 'ss_ss_' . $location . '_label_tooltip' ),
				'all_networks'        => socialsnap_settings( 'ss_ss_' . $location . '_all_networks' ),
				'inline_button_label' => socialsnap_settings( 'ss_ss_inline_content_button_label' ),
				'hover_animation'     => socialsnap_settings( 'ss_ss_inline_content_hover_animation' ),
			),
		);

		// Filter data with the default alues.
		$data = array_replace_recursive( $defaults, $data );

		// Allow modification of data.
		$data = apply_filters( 'socialsnap_social_share_display_args', $data, $location );

		// Set up the Post ID.
		if ( ! $data['post_id'] ) {
			$data['post_id'] = socialsnap_get_current_post_id( $data['permalink'] );
		}

		if ( ! $data['options']['share_text'] ) {
			$data['title'] = socialsnap_get_shared_title(
				array(
					'post_id'  => $data['post_id'],
					'location' => $location,
				)
			);
		} else {
			$data['title'] = $data['options']['share_text'];
		}

		// No networks specified, take from settings.
		if ( ! is_array( $data['options']['networks'] ) || empty( $data['options']['networks'] ) ) {
			$data['options']['networks'] = $this->networks;
		}

		// No networks selected in the settings panel.
		if ( ! is_array( $data['options']['networks'] ) || empty( $data['options']['networks'] ) ) {

			if ( is_admin() ) {
				echo '<ul class="ss-social-icons-container"></ul>';
			}

			return;
		}

		// Enqueue assets.
		socialsnap_enqueue_assets();

		// Mobile only social networks.
		$mobile_only_networks = socialsnap_get_mobile_only_social_share_networks();
		$container_class      = is_admin() ? ' ' . $location : '';
		?>

		<ul class="ss-social-icons-container<?php echo esc_attr( $container_class ); ?>">

			<?php
			// Loop through the networks.
			foreach ( $data['options']['networks'] as $network => $network_settings ) {

				if ( 'order' === $network ) {
					continue;
				}

				$this->add_displayed_networks( $network );

				if ( ! $data['permalink'] ) {
					$permalink = socialsnap_get_shared_permalink(
						array(
							'post_id' => $data['post_id'],
							'network' => $network,
						)
					);
				} else {
					$permalink = $data['permalink'];
				}

				$permalink = apply_filters( 'socialsnap_complete_shared_permalink', $permalink, $network );

				$network_settings = wp_parse_args(
					$network_settings,
					array(
						'desktop_visibility' => ! in_array( $network, array_keys( $mobile_only_networks ) ),
						'text'               => socialsnap_get_network_name( $network ),
					)
				);

				$hide_class = array();

				if ( ! isset( $network_settings['desktop_visibility'] ) || ! $network_settings['desktop_visibility'] ) {
					$hide_class[] = 'ss-hide-on-desktop';
				}

				if ( ( ! isset( $network_settings['mobile_visibility'] ) || ! $network_settings['mobile_visibility'] ) && ! in_array( $network, array_keys( $mobile_only_networks ) ) ) {
					$hide_class[] = 'ss-hide-on-mobile';
				}

				$hide_class = implode( ' ', $hide_class );
				$share_url  = is_admin() ? '#' : socialsnap_get_share_url(
					$network,
					array(
						'image'     => $data['image'],
						'post_id'   => $data['post_id'],
						'permalink' => $permalink,
						'title'     => $data['title'],
						'location'  => $location,
					)
				);

				$additional_data = $this->get_button_data( $share_url, $permalink, $network, $data['post_id'], $location );

				// Share URL not valid, go to next network.
				if ( ! $share_url ) {
					continue;
				}
				?>
				<li class="<?php echo esc_attr( $hide_class ); ?>">

					<?php if ( 'on_media' === $location ) { ?>

						<?php $icon_class = apply_filters( 'socialsnap_social_share_button_class', 'ss-ss-on-media-button ss-' . esc_attr( $network ) . '-color ss-on-media', $network, $data['post_id'] ); ?>

						<div data-ss-ss-link="<?php echo esc_url( $share_url, socialsnap_get_allowed_protocols() ); ?>" class="<?php echo esc_attr( $icon_class ); ?>" <?php echo $additional_data; // phpcs:ignore ?>>
					<?php } else { ?>

						<?php $icon_class = apply_filters( 'socialsnap_social_share_button_class', 'ss-' . $network . '-color', $network, $data['post_id'] ); ?>

						<a href="#" aria-label="<?php echo esc_attr( $network_settings['text'] ); ?>" data-ss-ss-link="<?php echo esc_url( $share_url, socialsnap_get_allowed_protocols() ); ?>" class="<?php echo esc_attr( $icon_class ); ?>" rel="nofollow noopener" <?php echo $additional_data; // phpcs:ignore ?>> 
					<?php } ?>

						<span class="ss-share-network-content">
							<i class="ss-network-icon"><?php echo socialsnap()->icons->get_svg( $network ); // phpcs:ignore ?></i>

							<?php
							$hover_animation = socialsnap_settings( 'ss_ss_' . $location . '_hover_animation' );

							if ( false !== strpos( $hover_animation, 'ss-hover-animation-2' ) ) {
								?>
								<i class="ss-network-icon ss-slide-icon"><?php echo socialsnap()->icons->get_svg( $network ); // phpcs:ignore ?></i>
								<?php
							}
							?>

							<?php
							$share_count = socialsnap_get_share_count(
								$network,
								array(
									'url'     => $permalink,
									'post_id' => $data['post_id'],
								)
							);
							$share_count = apply_filters( 'socialsnap_filter_social_share_count', $share_count, $location );
							$share_count = is_admin() ? wp_rand( 1, 200 ) : $share_count;

							if ( in_array( $location, array( 'inline_content' ) ) ) {

								if ( socialsnap()->pro && ( is_admin() || 'ss-reveal-label' === $data['options']['hover_animation'] ) ) {
									?>
									<span class="ss-reveal-label-wrap">
									<?php
								}

								$data['options']['share_count'] = in_array( $data['options']['inline_button_label'], array( 'count', 'both' ) );

								if ( in_array( $data['options']['inline_button_label'], array( 'label', 'both' ) ) || is_admin() ) {
									?>
									<span class="ss-network-label"><?php echo esc_html( $network_settings['text'] ); ?></span>
									<?php
								}
							}

							$this->counts_displayed = $this->counts_displayed || $data['options']['share_count'];

							if ( is_admin() || is_numeric( $share_count ) && $data['options']['share_count'] ) {
								?>
								<span class="ss-network-count">
									<?php echo esc_html( socialsnap_format_number( intval( $share_count ) ) ); ?>		
								</span>
								<?php
							}

							if ( socialsnap()->pro && 'ss-reveal-label' === $data['options']['hover_animation'] ) {
								?>
								</span><!-- .ss-reveal-label-wrap -->
							<?php } ?>

						</span>

					<?php if ( 'on_media' === $location ) { ?>
						</div>
					<?php } else { ?>
						</a>
					<?php } ?>

					<?php if ( $data['options']['tooltip'] || is_admin() ) { ?>
						<span class="ss-share-network-tooltip"><?php echo esc_html( $network_settings['text'] ); ?></span>
					<?php } ?>
				</li>
			<?php } ?>

			<?php
			if ( $data['options']['all_networks'] || is_admin() ) {

				$this->share_all_popup_displayed = true;

				$no_label_class = '';

				if ( 'inline_content' == $location && ! socialsnap_settings( 'ss_ss_inline_content_all_networks_label' ) ) {
					$no_label_class = 'ss-without-all-networks-label ';
				}
				?>

				<li>
					<a href="#" class="<?php echo esc_attr( $no_label_class ); ?>ss-share-all ss-shareall-color" rel="nofollow noopener">
						<span class="ss-share-network-content">
							<i class="ss-network-icon"><?php echo socialsnap()->icons->get_svg( 'plus' ); // phpcs:ignore ?></i>

						<?php
						$hover_animation = socialsnap_settings( 'ss_ss_' . $location . '_hover_animation' );

						if ( false !== strpos( $hover_animation, 'ss-hover-animation-2' ) ) {
							?>
								<i class="ss-network-icon ss-slide-icon"><?php echo socialsnap()->icons->get_svg( 'plus' ); // phpcs:ignore ?></i>
								<?php
						}

						if ( 'inline_content' == $location ) {
							?>
								<span class="ss-reveal-label-wrap">
									<?php
									$label = socialsnap_settings( 'ss_ss_inline_content_all_networks_label' );
									if ( $label || is_admin() ) {
										?>
										<span class="ss-network-label"><?php echo wp_kses( socialsnap_settings( 'ss_ss_inline_content_all_networks_label' ), socialsnap_get_allowed_html_tags( 'post' ) ); ?></span>
									<?php } ?>
								</span>
							<?php } ?>
						</span>
					</a>

					<?php if ( $data['options']['tooltip'] || is_admin() ) { ?>
						<span class="ss-share-network-tooltip"><?php esc_html_e( 'More Networks', 'socialsnap' ); ?></span>
					<?php } ?>

				</li>
			<?php } ?>
		</ul>
		<?php
	}

	/**
	 * Create array of classes for specified button location
	 *
	 * @param array  $class Array of display position classes.
	 * @param string $location Location name.
	 * @param array  $settings Display position settings.
	 * @since 1.0.0
	 */
	public function get_display_position_classes( $class = array(), $location = null, $settings = array() ) {

		if ( is_null( $location ) ) {
			return $class;
		}

		// Default Settings.
		$defaults = array(
			'position'            => socialsnap_settings( 'ss_ss_' . $location . '_position' ),
			'button_size'         => socialsnap_settings( 'ss_ss_' . $location . '_button_size' ),
			'button_spacing'      => socialsnap_settings( 'ss_ss_' . $location . '_button_spacing' ),
			'button_shape'        => socialsnap_settings( 'ss_ss_' . $location . '_button_shape' ),
			'hide_on_mobile'      => socialsnap_settings( 'ss_ss_' . $location . '_hide_on_mobile' ),
			'on_media_visibility' => socialsnap_settings( 'ss_ss_on_media_hover' ),
			'inline_button_label' => socialsnap_settings( 'ss_ss_inline_content_button_label' ),
		);

		$settings = array_replace_recursive( $defaults, $settings );

		$location_class = str_replace( '_', '-', $location );

		if ( 'sidebar' !== $location ) {
			$class[] = 'ss-' . $settings['position'] . '-' . $location_class;
		}

		if ( 'sidebar' === $location_class ) {
			if ( strpos( $settings['position'], 'left' ) !== false ) {
				$class[] = 'ss-left-sidebar';
			} elseif ( strpos( $settings['position'], 'right' ) !== false ) {
				$class[] = 'ss-right-sidebar';
			}
		}

		$class[] = 'ss-' . $settings['button_size'] . '-icons';

		if ( $settings['hide_on_mobile'] ) {
			$class[] = 'ss-hide-on-mobile';
		}

		if ( in_array( $location, array( 'sidebar', 'inline_content', 'on_media' ), true ) && $settings['button_spacing'] ) {
			$class[] = 'ss-with-spacing';
		}

		if ( in_array( $location, array( 'sidebar', 'inline_content', 'on_media', 'hub' ), true ) ) {
			$class[] = 'ss-' . $settings['button_shape'] . '-icons';
		}

		if ( 'on_media' === $location && 'always' === $settings['on_media_visibility'] ) {
			$class[] = 'ss-on-media-always-visible';
		}

		if ( 'inline_content' === $location ) {
			if ( 'none' === $settings['inline_button_label'] ) {
				$class[] = 'ss-without-labels';
			}

			if ( 'both' === $settings['inline_button_label'] ) {
				$class[] = 'ss-both-labels';
			}
		}

		return apply_filters( 'socialsnap_social_share_class', $class );
	}

	/**
	 * Mark button to use API for share count.
	 *
	 * @param string $data Additional data.
	 * @param string $url URL to share.
	 * @param string $permalink Permalink.
	 * @param string $network Network name.
	 * @param int    $post_id Post ID.
	 * @param string $location Location name.
	 * @since 1.0.0
	 */
	public function get_button_data( $url, $permalink, $network, $post_id, $location ) {

		$data = array(
			'data-ss-ss-network-id' => $network,
			'data-ss-ss-post-id'    => $post_id,
			'data-ss-ss-location'   => $location,
			'data-ss-ss-permalink'  => $permalink,
		);

		if ( 'heart' === $network ) {
			$data['data-ss-ss-type'] = 'like';
		} else {
			$data['data-ss-ss-type'] = 'share';
		}

		$with_api = socialsnap_get_social_share_networks_with_api();

		if ( in_array( $network, $with_api ) ) {
			$data['data-has-api'] = 'true';
		}

		$data = apply_filters( 'socialsnap_ss_button_data', $data, $url, $permalink, $network, $post_id, $location );

		$return = '';

		if ( ! empty( $data ) ) {
			foreach ( $data as $key => $value ) {
				$return .= ' ' . esc_html( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		return $return;
	}

	/**
	 * Javascript Indicator that share count cache has expired.
	 *
	 * @since 1.0.0
	 */
	public function share_count_cache_expired() {

		// Check AMP pages.
		if ( socialsnap_is_amp_page() ) {
			return;
		}

		// No share buttons here.
		if ( ! apply_filters( 'socialsnap_share_buttons_displayed', $this->is_displayed ) ) {
			return;
		}

		// No share counts here.
		if ( ! apply_filters( 'socialsnap_share_counts_displayed', $this->counts_displayed ) ) {
			return;
		}

		// Skip WooCommerce account page.
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return;
		}

		$url     = socialsnap_get_current_url();
		$post_id = socialsnap_get_current_post_id( $url );
		$expired = socialsnap_share_count_expired( $url, $post_id );

		// Only do for published pages.
		if ( $post_id > 0 && 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		?>
		<!-- Social Snap Share count cache indicator -->
		<script type="text/javascript">

			var SocialSnapURL 				= window.location.href;
			var SocialSnapShareCacheExpired = <?php echo intval( $expired ); ?>;
			var SocialSnapShareNetworks     = <?php echo wp_json_encode( $this->networks_displayed ); ?>;

			if ( -1 !== SocialSnapURL.indexOf('ss_cache_refresh') ) {

				SocialSnapShareCacheExpired = true;

			} else {

				var SocialSnapServerTimestamp 	= <?php echo esc_html( time() ); ?>;
				var SocialSnapBrowserTimestamp 	= Date.now();

				if ( ! SocialSnapBrowserTimestamp ) {
					SocialSnapBrowserTimestamp = new Date().getTime();
				}

				SocialSnapBrowserTimestamp = Math.floor( SocialSnapBrowserTimestamp / 1000 );

				SocialSnapShareCacheExpired = SocialSnapShareCacheExpired && ( SocialSnapBrowserTimestamp - SocialSnapServerTimestamp < 60 );
			}

		</script>
		<!-- Social Snap Share count cache indicator -->
		<?php
	}

	/**
	 * Register shortcode for click to social sharing module.
	 *
	 * @param array $atts Array of shortcode attributes.
	 * @since 1.0.0
	 */
	public function shortcode_social_share( $atts ) {

		$defaults = array(
			'networks'       => '',
			'share_text'     => '',
			'align'          => socialsnap_settings( 'ss_ss_inline_content_position' ),
			'total'          => socialsnap_settings( 'ss_ss_inline_content_total_count' ),
			'shape'          => socialsnap_settings( 'ss_ss_inline_content_button_shape' ),
			'size'           => socialsnap_settings( 'ss_ss_inline_content_button_size' ),
			'labels'         => socialsnap_settings( 'ss_ss_inline_content_button_label' ),
			'spacing'        => socialsnap_settings( 'ss_ss_inline_content_button_spacing' ),
			'hide_on_mobile' => socialsnap_settings( 'ss_ss_inline_content_hide_on_mobile' ),
			'all_networks'   => socialsnap_settings( 'ss_ss_inline_content_all_networks' ),
			'format_number'  => true,
		);

		$defaults = apply_filters( 'socialsnap_social_share_shortcode_atts', $defaults );

		$atts = shortcode_atts(
			$defaults,
			$atts
		);

		$networks_default = $this->networks;
		$networks_mobile  = socialsnap_get_mobile_only_social_share_networks();
		$allowed_networks = array_keys( socialsnap_get_social_share_networks() );

		if ( '' === $atts['networks'] ) {
			$atts['networks'] = $networks_default;
		} else {

			$networks         = explode( ';', strtolower( str_replace( ' ', '', $atts['networks'] ) ) );
			$atts['networks'] = array();

			if ( is_array( $networks ) && ! empty( $networks ) ) {
				foreach ( $networks as $network ) {

					if ( ! in_array( $network, $allowed_networks ) ) {
						continue;
					}

					if ( isset( $networks_default[ $network ] ) ) {
						$atts['networks'][ $network ] = $networks_default[ $network ];
					} else {
						$atts['networks'][ $network ] = array(
							'text'               => socialsnap_get_network_name( $network ),
							'desktop_visibility' => isset( $networks_mobile[ $network ] ) ? false : true,
							'mobile_visibility'  => true,
						);
					}
				}
			}
		}

		$options = array(
			'networks'            => $atts['networks'],
			'share_text'          => $atts['share_text'],
			'button_shape'        => $atts['shape'],
			'button_size'         => $atts['size'],
			'button_spacing'      => $atts['spacing'],
			'position'            => $atts['align'],
			'inline_button_label' => $atts['labels'],
			'hide_on_mobile'      => $atts['hide_on_mobile'],
			'show_total_count'    => $atts['total'],
			'all_networks'        => $atts['all_networks'],
			'share_label'         => false,
			'format_number'       => $atts['format_number'],
		);

		$options = apply_filters( 'socialsnap_social_share_shotcode_options', $options, $atts );

		ob_start();

		$this->render_position_inline_content( $options );

		$output = ob_get_clean();

		$search = array(
			'/\>[^\S ]+/s',     // strip whitespaces after tags, except space.
			'/[^\S ]+\</s',     // strip whitespaces before tags, except space.
			'/(\s)+/s',         // shorten multiple whitespace sequences.
			'/<!--(.|\s)*?-->/', // Remove HTML comments.
		);

		$replace = array(
			'>',
			'<',
			'\\1',
			'',
		);

		$output = preg_replace( $search, $replace, $output );

		return $output;
	}

	/**
	 * On Media buttons shortcode.
	 *
	 * @param array  $atts Array of shortcode attributes.
	 * @param string $content Shortcode content.
	 * @since  1.1.7
	 */
	public function shortcode_on_media_share( $atts, $content = '' ) {
		return $this->filter_content_on_media_share( do_shortcode( $content ), $atts );
	}

	/**
	 * Register Block for Social Share.
	 *
	 * @since 1.0.0
	 */
	public function block_editor_support() {

		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'socialsnap/social-share',
			array(
				'render_callback' => array( $this, 'block_editor_social_share' ),
			)
		);
	}

	/**
	 * Social Share block editor support.
	 *
	 * @param array $attributes Array of block attributes.
	 * @since 1.0.0
	 */
	public function block_editor_social_share( $attributes ) {
		ob_start();

		$defaults = array(
			'networks'       => 'twitter;facebook;linkedin',
			'align'          => 'left',
			'total'          => false,
			'shape'          => 'rounded',
			'size'           => 'small',
			'labels'         => 'label',
			'spacing'        => true,
			'hide_on_mobile' => false,
			'all_networks'   => true,
		);

		$defaults = apply_filters( 'socialsnap_social_share_block_editor_atts', $defaults );

		$attributes['networks'] = isset( $attributes['networks'] ) ? $attributes['networks'] : '';
		$attributes['networks'] = preg_replace( '/ |\t/', '', $attributes['networks'] );
		$attributes['networks'] = preg_replace( '/\n/', ';', $attributes['networks'] );
		$attributes['networks'] = strtolower( $attributes['networks'] );

		$attributes = wp_parse_args( $attributes, $defaults );

		$shortcode = '[ss_social_share';

		foreach ( $attributes as $key => $value ) {
			$shortcode .= ' ' . $key . '="' . $value . '"';
		}
		$shortcode .= ']';

		echo do_shortcode( $shortcode );

		return ob_get_clean();
	}

	/**
	 * Reset flags for share count cache.
	 *
	 * @since 1.0.0
	 */
	public function socialsnap_refresh_share_count_cache() {

		check_ajax_referer( 'socialsnap-admin' );

		if ( ! current_user_can( apply_filters( 'socialsnap_manage_cap', 'manage_options' ) ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error. Access denied.', 'socialsnap' ),
				)
			);
		}

		// Reset homepage flag.
		update_option( 'socialsnap_homepage_share_count_timestamp', false );

		// Reset individual post/page flag.
		global $wpdb;

		set_time_limit( 300 );

		$query = "
			SELECT postmeta.post_id, postmeta.meta_value
			FROM   $wpdb->postmeta postmeta
			WHERE  postmeta.meta_key = %s
		";

		$results = $wpdb->get_results( $wpdb->prepare( $query, 'socialsnap_share_count_timestamp' ) ); // phpcs:ignore

		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				update_post_meta( $row->post_id, 'socialsnap_share_count_timestamp', false );
			}
		}
	}

	/**
	 * Remove incoming parameters.
	 *
	 * @param string $url URL to modify.
	 * @param string $network Network name.
	 * @since 1.1.7
	 */
	public function remove_params( $url, $network ) {

		return remove_query_arg(
			array(
				'utm_source',
				'utm_medium',
				'utm_campaign',
			),
			$url
		);
	}

	/**
	 * Get displayed networks.
	 */
	public function get_displayed_networks() {
		return $this->networks_displayed;
	}

	/**
	 * Add network to displayed networks array.
	 *
	 * @param array $networks Displayed networks.
	 */
	public function add_displayed_networks( $networks = array() ) {
		$this->networks_displayed = array_unique( array_merge( $this->networks_displayed, (array) $networks ) );
	}

	public function add_inline_styles( $position ) {

		$style = array();
		$css   = array();

		if ( 'sidebar' === $position ) {

			$sidebar_position     = socialsnap_settings( 'ss_ss_sidebar_position' );
			$sidebar_with_spacing = socialsnap_settings( 'ss_ss_sidebar_button_spacing' );
			$sidebar_btn_shape    = socialsnap_settings( 'ss_ss_sidebar_button_shape' );

			// Spacing.
			if ( 'circle' === $sidebar_btn_shape ) {
				$spacing = 18;
			} elseif ( $sidebar_with_spacing ) {
				$spacing = 6;
			} else {
				$spacing = 0;
			}

			// Horizontal alignment.
			$style[':root'][] = "--ss-fsidebar-spacing: {$spacing}px";

			if ( strpos( $sidebar_position, 'left' ) !== false ) {
				$style['#ss-floating-bar'][] = 'left: 0;';
			} elseif ( strpos( $sidebar_position, 'right' ) !== false ) {
				$style['#ss-floating-bar'][] = 'right: 0;';
			}
		}

		$style = apply_filters( 'socialsnap_inline_styles', $style, $position );

		if ( is_array( $style ) && ! empty( $style ) ) {
			foreach ( $style as $selector => $styling ) {
				$css[] = $selector . '{' . implode( '; ', $styling ) . '}';
			}
		}

		$css = implode( ' ', $css );

		if ( ! is_admin() ) {
			echo '<style>' . $css . '</style>'; // phpcs:ignore
		}
	}
}

/**
 * The function which returns the one SocialSnap_Social_Share instance.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $socialsnap_social_share = socialsnap_social_share(); ?>
 *
 * @since  1.1.6
 * @return object
 */
function socialsnap_social_share() {
	return SocialSnap_Social_Share::instance();
}

socialsnap_social_share();
