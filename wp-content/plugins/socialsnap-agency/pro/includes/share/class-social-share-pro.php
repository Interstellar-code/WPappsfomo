<?php
/**
 * Social Sharing PRO. Extend the Social Share with PRO features.
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Social_Share_PRO extends SocialSnap_Social_Share {

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
	public $networks;

	/**
	 * Display Positions of share bar.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $positions;

	/**
	 * Current post ID.
	 *
	 * @since 1.1.8
	 * @var array
	 */
	protected $post_id = false;

	/**
	 * Main SocialSnap_Social_Share_PRO Instance.
	 *
	 * @since 1.1.6
	 * @return SocialSnap_Social_Share_PRO
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SocialSnap_Social_Share_PRO ) ) {
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

		// Add Positions.
		add_filter( 'socialsnap_social_share_positions', array( $this, 'social_share_positions' ) );

		// Add Networks.
		add_filter( 'socialsnap_social_share_networks', array( $this, 'social_share_networks' ) );

		// API networks.
		add_filter( 'socialsnap_social_share_networks_with_api', array( $this, 'social_share_networks_with_api' ) );

		// Add new request URLs for share counts.
		add_filter( 'socialsnap_social_share_request_url', array( $this, 'social_share_request_url' ), 10, 3 );

		// Get API share counts.
		add_filter( 'socialsnap_social_share_api_response', array( $this, 'social_share_counts_api' ), 10, 3 );

		// Add social share URLs for new networks.
		add_filter( 'socialsnap_social_share_url', array( $this, 'social_share_url' ), 10, 3 );

		// Filter the social sharing title for certain networks if needed.
		add_filter( 'socialsnap_social_share_title', array( $this, 'social_share_title' ), 10, 4 );

		// Pinterest share image.
		add_filter( 'socialsnap_share_pinterest_image', array( $this, 'pinterest_share_image' ), 10, 3 );

		// Pinterest extension support.
		add_filter( 'the_content', array( $this, 'pinterest_extension_support' ), 20 );
		add_filter( 'the_excerpt', array( $this, 'pinterest_extension_support' ), 20 );

		// View Count.
		add_filter( 'socialsnap_view_count', array( $this, 'render_view_count_filter' ), 10, 2 );

		// Min Share count.
		add_filter( 'socialsnap_filter_social_share_count', array( $this, 'minimum_share_count' ), 10, 2 );
		add_filter( 'socialsnap_total_share_count_settings', array( $this, 'minimum_share_count_total_shares' ), 10, 3 );

		// Classes.
		add_filter( 'socialsnap_display_position_classes', array( $this, 'add_display_position_classes' ), 10, 3 );

		// Clicked counts.
		add_filter( 'socialsnap_social_share_counts', array( $this, 'social_share_click_counts' ), 10, 3 );

		// Extend shortcode with new options.
		add_filter( 'socialsnap_social_share_shortcode_atts', array( $this, 'add_shortcode_atts' ), 10, 1 );
		add_filter( 'socialsnap_social_share_block_editor_atts', array( $this, 'add_block_editor_atts' ), 10, 1 );
		add_filter( 'socialsnap_social_share_shotcode_options', array( $this, 'add_shortcode_output_options' ), 10, 2 );

		// Add settings.
		add_filter( 'socialsnap_settings_config', array( $this, 'add_settings_config' ), 5, 1 );

		// Add options to shortcode generator.
		add_filter( 'socialsnap_editor_config', array( $this, 'add_editor_config' ), 5, 1 );

		// Add fields to metaboxes.
		add_filter( 'socialsnap_metaboxes_config', array( $this, 'add_metaboxes_config' ), 10, 1 );

		// Total share style.
		add_action( 'socialsnap_before_total_share_counter', array( $this, 'inline_total_share_style' ), 10, 2 );

		// Add class to network button.
		add_filter( 'socialsnap_social_share_button_class', array( $this, 'like_button_class' ), 10, 3 );

		// Complete the share URL. Combines multiple filters to generate final share URL.
		add_filter( 'socialsnap_complete_shared_permalink', array( $this, 'complete_social_share_permalink' ), 10, 2 );

		// Customize social share permalink.
		add_filter( 'socialsnap_social_share_display_args', array( $this, 'custom_social_share_permalink' ), 10, 2 );

		// Add custom styles.
		add_action( 'wp_footer', array( $this, 'print_custom_style' ) );

		// Add options to media attachments.
		add_filter( 'attachment_fields_to_edit', array( $this, 'edit_attachment_field' ), 11, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_attachment_field' ), 11, 2 );

		// Set displayed indicators.
		add_filter( 'socialsnap_share_buttons_displayed', array( $this, 'share_buttons_displayed' ) );
		add_filter( 'socialsnap_share_counts_displayed', array( $this, 'share_counts_displayed' ) );
		add_filter( 'socialsnap_share_all_popup_displayed', array( $this, 'share_all_popup_displayed' ) );

		add_filter( 'socialsnap_inline_styles', array( $this, 'inline_styles' ), 10, 2 );
	}

	/**
	 * Initialize class variables.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Init networks.
		$this->networks = apply_filters(
			'socialsnap_filter_social_share_networks',
			socialsnap_settings( 'ss_social_share_networks' )
		);

		// Init display positions.
		$this->positions = $this->social_share_positions();

		// Post ID.
		$this->post_id = socialsnap_get_current_post_id();
	}

	/**
	 * Add Social Share positions.
	 *
	 * @since 1.0.0
	 * @return array, array of post types
	 */
	public function social_share_positions( $positions = array() ) {

		$positions[] = 'hub';
		$positions[] = 'sticky_bar';

		return $positions;
	}

	/**
	 * Add Social Share networks.
	 *
	 * @since 1.0.0
	 * @param array $networks Array of share networks.
	 * @return array, array of post types
	 */
	public function social_share_networks( $networks = array() ) {

		// Change button order.
		if ( isset( $networks['envelope'] ) ) {
			unset( $networks['envelope'] );
		}

		if ( isset( $networks['copy'] ) ) {
			unset( $networks['copy'] );
		}

		if ( isset( $networks['print'] ) ) {
			unset( $networks['print'] );
		}

		// Add new networks.
		$networks = array_unique(
			array_merge(
				$networks,
				array(
					'pinterest'   => 'Pinterest',
					'tumblr'      => 'Tumblr',
					'skype'       => 'Skype',
					'buffer'      => 'Buffer',
					'pocket'      => 'Pocket',
					'vkontakte'   => 'VKontakte',
					'parler'      => 'Parler',
					'xing'        => 'Xing',
					'reddit'      => 'Reddit',
					'line'        => 'Line',
					'flipboard'   => 'Flipboard',
					'myspace'     => 'MySpace',
					'delicious'   => 'Delicious',
					'amazon'      => 'Amazon',
					'digg'        => 'Digg',
					'evernote'    => 'Evernote',
					'blogger'     => 'Blogger',
					'livejournal' => 'LiveJournal',
					'baidu'       => 'Baidu',
					'mewe'        => 'MeWe',
					'newsvine'    => 'NewsVine',
					'yummly'      => 'Yummly',
					'yahoo'       => 'Yahoo',
					'whatsapp'    => 'WhatsApp',
					'viber'       => 'Viber',
					'sms'         => 'SMS',
					'telegram'    => 'Telegram',
					'messenger'   => 'Facebook Messenger',
					'heart'       => __( 'Like', 'socialsnap' ),
					'envelope'    => 'Email',
					'print'       => __( 'Print', 'socialsnap' ),
					'copy'        => __( 'Copy Link', 'socialsnap' ),
				)
			)
		);

		return $networks;
	}

	/**
	 * Filter networks that have share count API support.
	 *
	 * @param array $networks Array of share networks with API.
	 * @since 1.0.0
	 */
	public function social_share_networks_with_api( $networks ) {

		$networks = array_merge(
			$networks,
			array(
				'pinterest',
				'tumblr',
				// 'reddit',
				// 'vkontakte',
				'buffer',
			)
		);

		if ( 'click_tracking' !== socialsnap_settings( 'ss_ss_twitter_count_provider' ) ) {
			$networks[] = 'twitter';
		}

		return $networks;
	}

	/**
	 * Add hidden Pinterest image for compatibility with Pinterest Extensions.
	 *
	 * @param string $content Post content.
	 * @since 1.0.0
	 */
	public function pinterest_extension_support( $content ) {

		// Check if pinterest browser extension is.
		if ( ! socialsnap_settings( 'ss_ss_pinterest_browser_extension' ) ) {
			return $content;
		}

		// Check AMP pages.
		if ( socialsnap_is_amp_page() ) {
			return $content;
		}

		if ( socialsnap_get_current_post_id() !== get_the_ID() ) {
			return $content;
		}

		$image     = apply_filters( 'socialsnap_share_pinterest_image', false, get_the_ID(), '' );
		$permalink = socialsnap_get_shared_permalink(
			array(
				'permalink' => get_permalink(),
				'network'   => 'pinterest',
			)
		);
		$title     = apply_filters( 'socialsnap_social_share_title', get_the_title(), 'pinterest', get_the_ID(), '' );

		$image     = apply_filters( 'socialsnap_pinterest_browser_extension_image', $image, get_the_ID() );
		$permalink = apply_filters( 'socialsnap_pinterest_browser_extension_permalink', $permalink, get_the_ID() );
		$title     = apply_filters( 'socialsnap_pinterest_browser_extension_title', $title, get_the_ID() );

		// Print hidden image in the content.
		if ( $image ) {

			$image_id = socialsnap_get_attachment_id_by_url( $image );
			$alt      = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : $title;

			$content .= '<img src="' . esc_url( $image ) . '" class="ss-hidden-pin-image" alt="' . esc_attr( $alt ) . '" data-pin-url="' . esc_attr( $permalink ) . '" data-pin-media="' . esc_url( $image ) . '" data-pin-description="' . esc_attr( $title ) . '" style="display:none"/>';
		}

		return $content;
	}

	/**
	 * Get Pinterest share image URL.
	 *
	 * @since 1.0.0
	 */
	public function pinterest_share_image( $pinterest_image, $post_id, $location ) {

		// If image exists, use that.
		if ( $pinterest_image ) {
			if ( 'on_media' === $location ) {
				if ( 'custom' !== socialsnap_settings( 'ss_ss_pinterest_image_src' ) ) {
					return $pinterest_image;
				}
			} else {
				return $pinterest_image;
			}
		}

		// Check if we have specific image in post meta.
		if ( $post_id > 0 ) {

			// Check if we have specific image in post meta.
			$meta_pinterest_image = get_post_meta( $post_id, 'ss_image_pinterest', true );

			if ( $meta_pinterest_image ) {
				return wp_get_attachment_image_url( $meta_pinterest_image, 'full' );
			}

			// Check for default Social meta tags share image.
			$meta_social_image = get_post_meta( $post_id, 'ss_smt_image', true );

			if ( $meta_social_image ) {
				return wp_get_attachment_image_url( $meta_social_image, 'full' );
			}

			// Try to fetch featured image.
			if ( has_post_thumbnail( $post_id ) ) {
				return wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'full' );
			}
		}

		$default_image = socialsnap_settings( 'ss_smt_default_image' );
		if ( is_array( $default_image ) && isset( $default_image['id'] ) ) {
			return wp_get_attachment_image_url( $default_image['id'], 'full' );
		}

		return false;
	}

	/**
	 * Share title.
	 *
	 * @since 1.0.0
	 */
	public function social_share_title( $title, $network = '', $post_id = null, $location = '' ) {

		if ( 'pinterest' === $network ) {

			if ( 'on_media' !== $location || 'custom' === socialsnap_settings( 'ss_ss_pinterest_description_src' ) ) {
				if ( $post_id ) {
					$description = get_post_meta( $post_id, 'ss_pinterest_description', true );

					if ( $description ) {
						$title = $description;
					}
				}
			}

			$via = socialsnap_settings( 'ss_pinterest_username' );

			if ( $via ) {
				$title .= ' via @' . apply_filters( 'socialsnap_sanitize_username', $via );
			}
		}

		return $title;
	}

	/**
	 * Build share url for PRO networks.
	 *
	 * @param string $url Share URL.
	 * @param string $network Network name.
	 * @param array  $args Args.
	 * @since 1.0.0
	 */
	public function social_share_url( $url, $network, $args = array() ) {

		// Already generated in Lite.
		if ( '#' !== $url && 'pinterest' !== $network ) {
			return $url;
		}

		$defaults = array(
			'image'     => '',
			'post_id'   => '',
			'permalink' => '',
			'title'     => '',
			'location'  => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( '' == $args['post_id'] ) {
			$args['post_id'] = get_the_ID();
		}

		if ( class_exists( 'WooCommerce' ) && is_checkout() || $args['post_id'] <= 0 ) {
			$args['permalink'] = socialsnap_get_shared_permalink(
				array(
					'permalink' => get_bloginfo( 'url' ),
					'network'   => $network,
				)
			);
		} elseif ( '' === $args['permalink'] ) {
			$args['permalink'] = socialsnap_get_shared_permalink(
				array(
					'post_id' => $args['post_id'],
					'network' => $network,
				)
			);
		}

		$encoded_permalink = rawurlencode( $args['permalink'] );

		// Title.
		if ( class_exists( 'WooCommerce' ) && is_checkout() || $args['post_id'] <= 0 ) {
			$args['title'] = socialsnap_get_shared_title(
				array(
					'title'    => get_bloginfo( 'name' ),
					'network'  => $network,
					'location' => $args['location'],
				)
			);
		} elseif ( '' === $args['title'] ) {
			$args['title'] = socialsnap_get_shared_title(
				array(
					'post_id'  => $args['post_id'],
					'network'  => $network,
					'location' => $args['location'],
				)
			);
		} else {
			$args['title'] = apply_filters( 'socialsnap_social_share_title', $args['title'], $network, $args['post_id'], $args['location'] );
		}

		$encoded_title = rawurlencode( wp_strip_all_tags( html_entity_decode( $args['title'], ENT_QUOTES, 'UTF-8' ) ) );

		// Build specific share url for each social network.
		switch ( $network ) {

			case 'digg':
				$url = add_query_arg(
					array(
						'title' => $encoded_title,
						'url'   => $encoded_permalink,
					),
					'https://digg.com/submit'
				);
				break;

			case 'reddit':
				$url = add_query_arg(
					array(
						'title' => $encoded_title,
						'url'   => $encoded_permalink,
					),
					'https://www.reddit.com/submit'
				);
				break;

			case 'vkontakte':
				$url = add_query_arg(
					array(
						'url' => $encoded_permalink,
					),
					'https://vk.com/share.php'
				);

				break;

			case 'myspace':
				$url = add_query_arg(
					array(
						'u' => $encoded_permalink,
					),
					'https://myspace.com/post'
				);

				break;

			case 'delicious':
				$url = add_query_arg(
					array(
						'url'   => $encoded_permalink,
						'title' => $encoded_title,
					),
					'https://del.icio.us/post'
				);

				break;

			case 'amazon':
				$url = add_query_arg(
					array(
						'u' => $encoded_permalink,
						't' => $encoded_title,
					),
					'https://www.amazon.com/gp/wishlist/static-add'
				);
				break;

			case 'skype':
				$url = add_query_arg(
					array(
						'url' => $encoded_permalink,
					),
					'https://web.skype.com/share'
				);
				break;

			case 'buffer':
				$url = add_query_arg(
					array(
						'url'  => $encoded_permalink,
						'text' => $encoded_title,
					),
					'https://buffer.com/add'
				);
				break;

			case 'evernote':
				$url = add_query_arg(
					array(
						'url' => $encoded_permalink,
					),
					'https://www.evernote.com/clip.action'
				);
				break;

			case 'pocket':
				$url = add_query_arg(
					array(
						'url' => $encoded_permalink,
					),
					'https://getpocket.com/save'
				);
				break;

			case 'blogger':
				$url = add_query_arg(
					array(
						'u' => $encoded_permalink,
						'n' => $encoded_title,
					),
					'https://www.blogger.com/blog-this.g'
				);
				break;

			case 'livejournal':
				$url = add_query_arg(
					array(
						'event'   => $encoded_permalink,
						'subject' => $encoded_title,
					),
					'http://www.livejournal.com/update.bml'
				);
				break;

			case 'baidu':
				$url = add_query_arg(
					array(
						'iu' => $encoded_permalink,
						'it' => $encoded_title,
					),
					'http://cang.baidu.com/do/add'
				);
				break;

			case 'newsvine':
				$url = add_query_arg(
					array(
						'u' => $encoded_permalink,
						'h' => $encoded_title,
					),
					'https://www.newsvine.com/_tools/seed&save'
				);
				break;

			case 'yummly':
				$url = add_query_arg(
					array(
						'url'     => $encoded_permalink,
						'title'   => $encoded_title,
						'yumtype' => 'button',
					),
					'https://www.yummly.com/urb/verify'
				);
				break;

			case 'xing':
				$url = add_query_arg(
					array(
						'url' => $encoded_permalink,
						'op'  => 'share',
					),
					'https://www.xing.com/app/user'
				);
				break;

			case 'whatsapp':
				$url = add_query_arg(
					array(
						'text' => $encoded_title . '%20' . $encoded_permalink,
					),
					'https://api.whatsapp.com/send'
				);
				break;

			case 'viber':
				$url = add_query_arg(
					array(
						'text' => $encoded_title . '%20' . $encoded_permalink,
					),
					'viber://forward'
				);
				break;

			case 'yahoo':
				$url = add_query_arg(
					array(
						'body'    => $encoded_permalink,
						'subject' => $encoded_title,
					),
					'https://compose.mail.yahoo.com/'
				);
				break;

			case 'sms':
				$url = add_query_arg(
					array(
						'body' => $encoded_title . '%20' . $encoded_permalink,
					),
					'sms:'
				);

				$url = 'sms:?&body=' . $encoded_title . '%20' . $encoded_permalink;
				break;

			case 'messenger':
				$app_id = socialsnap_settings( 'ss_facebook_appid' );
				$app_id = empty( $app_id ) ? '772401629609127' : $app_id;

				$redirect_uri = home_url( '/' );

				if ( wp_is_mobile() ) {
					$url = add_query_arg(
						array(
							'link'   => $encoded_permalink,
							'app_id' => $app_id,
						),
						'fb-messenger://share/'
					);
				} else {
					$url = add_query_arg(
						array(
							'display'      => 'popup',
							'link'         => $encoded_permalink,
							'app_id'       => $app_id,
							'redirect_uri' => $redirect_uri,
						),
						'https://www.facebook.com/dialog/send'
					);
				}

				break;

			case 'telegram':
				$url = add_query_arg(
					array(
						'url' => $encoded_title . '%20' . $encoded_permalink,
					),
					'https://t.me/share/url'
				);
				break;

			case 'flipboard':
				$url = add_query_arg(
					array(
						'v'     => '2',
						'title' => $encoded_title,
						'url'   => $encoded_permalink,
					),
					'https://share.flipboard.com/bookmarklet/popout'
				);
				break;

			case 'tumblr':
				$url = add_query_arg(
					array(
						'canonicalUrl' => $encoded_permalink,
						'title'        => $encoded_title,
						'posttype'     => 'link',
					),
					'https://www.tumblr.com/widgets/share/tool'
				);
				break;

			case 'pinterest':
				$pinterest_image = apply_filters( 'socialsnap_share_pinterest_image', $args['image'], $args['post_id'], $args['location'] );

				if ( $pinterest_image ) {

					$attachment_id = (int) socialsnap_get_attachment_id_by_url( $pinterest_image );

					if ( get_post_meta( $attachment_id, 'ss_no_pin', true ) ) {
						$url = false;
					} else {

						$attachment = get_post( $attachment_id );

						if ( $attachment && 'attachment' === $attachment->post_type ) {

							$custom_description = '';

							if ( get_post_meta( $attachment_id, 'ss_pinterest_description', true ) ) {
								$custom_description = get_post_meta( $attachment_id, 'ss_pinterest_description', true );
							} elseif ( $attachment->post_content ) {
								$custom_description = $attachment->post_content;
							} elseif ( $attachment->post_excerpt ) {
								$custom_description = $attachment->post_excerpt;
							}

							$custom_description = apply_filters( 'socialsnap_social_share_title', $custom_description, 'pinterest', $args['post_id'], $args['location'] );

							if ( $custom_description ) {
								$encoded_title = rawurlencode( wp_strip_all_tags( html_entity_decode( $custom_description, ENT_QUOTES, 'UTF-8' ) ) );
							}
						}

						$url = add_query_arg(
							array(
								'url'         => $encoded_permalink,
								'media'       => $pinterest_image,
								'description' => $encoded_title,
							),
							'https://pinterest.com/pin/create/button/'
						);
					}
				}
				break;

			case 'parler':
				$url = add_query_arg(
					array(
						// 'url'     => $encoded_permalink,
						// 'message' => $encoded_title,
						'message' => $encoded_title . '%20' . $encoded_permalink,
					),
					'https://parler.com/new-post'
				);
				break;

			case 'mewe':
				$url = add_query_arg(
					array(
						'link' => $encoded_title . '%20' . $encoded_permalink,
					),
					'https://mewe.com/share'
				);
				break;

			case 'line':
				$url = add_query_arg(
					array(
						'text' => $encoded_title . '%20' . $encoded_permalink,
					),
					'https://line.me/R/share'
				);
				break;

			default:
				break;
		}

		return $url;
	}

	/**
	 * Build request share count url for PRO networks.
	 *
	 * @since 1.0.0
	 */
	public function social_share_request_url( $request, $network, $url ) {

		switch ( $network ) {

			case 'pinterest':
				$request = 'http://widgets.pinterest.com/v1/urls/count.json?url=';
				break;

			case 'tumblr':
				$request = 'http://api.tumblr.com/v2/share/stats?url=';
				break;

			case 'reddit':
				$request = 'http://www.reddit.com/api/info.json?url=';
				break;

			case 'vkontakte':
				$request = 'https://vk.com/share.php?act=count&index=1&format=json&url=';
				break;

			case 'buffer':
				$request = 'https://api.bufferapp.com/1/links/shares.json?url=';
				break;

			case 'twitter':
				$provider = socialsnap_settings( 'ss_ss_twitter_count_provider' );

				if ( 'opensharecounts' === $provider ) {
					$request = 'http://opensharecount.com/count.json?url=';
				} elseif ( 'twitcount' === $provider ) {
					$request = 'https://counts.twitcount.com/counts.php?url=';
				}

				break;
		}

		return $request;
	}

	/**
	 * Parse response from network API.
	 *
	 * @since 1.0.0
	 */
	public function social_share_counts_api( $result, $network, $response ) {

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {

			$response = wp_remote_retrieve_body( $response );

			switch ( $network ) {

				case 'pinterest':
					$response = preg_replace( '/^receiveCount\((.*)\)$/', "\\1", $response );
					$response = json_decode( $response );

					if ( isset( $response ) && isset( $response->count ) ) {
						$result = intval( $response->count );
					}

					break;

				case 'tumblr':
					$response = json_decode( $response );

					if ( isset( $response->meta->status ) && 200 == $response->meta->status ) {
						if ( isset( $response->response->note_count ) ) {
							$result = intval( $response->response->note_count );
						} else {
							$result = 0;
						}
					}
					break;

				case 'reddit':
					$response = json_decode( $response );

					if ( isset( $response->data->children ) ) {
						foreach ( $response->data->children as $child ) {
							$result += (int) $child->data->score;
						}
					}

					break;

				case 'vkontakte':
					preg_match( '/VK.Share.count\(1, ([0-9]+)\);/', $response, $matches );

					if ( is_array( $matches ) && isset( $matches[1] ) ) {
						$result = (int) $matches[1];
					}
					break;

				case 'buffer':
					$response = json_decode( $response );

					if ( isset( $response->shares ) ) {
						$result = $response->shares;
					}
					break;

				case 'twitter':
					$response = json_decode( $response );

					if ( isset( $response->count ) ) {
						$result = $response->count;
					}

					break;
			}
		}

		return $result;
	}

	/**
	 * Display share counts based on clicks.
	 *
	 * @since 1.0.0
	 */
	public function social_share_click_counts( $count, $network, $args = array() ) {

		// Click counts not enabled.
		if ( ! socialsnap_settings( 'ss_ss_share_click_tracking' ) ) {
			return $count;
		}

		if ( in_array( $network, socialsnap_get_social_share_networks_with_api() ) ) {
			return $count;
		}

		// Default Args.
		$defaults = array(
			'post_id' => '',
			'url'     => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$click_count = false;

		// Retrieve saved value.
		if ( -1 == $args['post_id'] ) {
			$count = get_option( 'socialsnap_homepage_click_share_count_' . $network );
		} elseif ( is_numeric( $args['post_id'] ) && $args['post_id'] > 0 ) {
			$count = get_post_meta( $args['post_id'], 'ss_ss_click_share_count_' . $network, true );
		}

		return intval( $count );
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

		// Handle positions.
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
				case 'hub':
					add_action( 'wp_footer', array( $this, 'render_position_hub' ) );
					break;

				case 'sticky_bar':
					add_action( 'wp_footer', array( $this, 'render_position_sticky_bar' ) );
					break;

				default:
					// code...
					break;
			}
		}
	}

	/**
	 * Display Share hub position.
	 *
	 * @since 1.0.0
	 */
	public function render_position_hub() {

		if ( ! socialsnap_settings( 'ss_ss_hub_enabled' ) && ! is_admin() ) {
			return;
		}

		// No "Display On" selected.
		if ( ! is_admin() && ! $this->check_display_on( socialsnap_settings( 'ss_ss_hub_post_types' ) ) ) {
			return;
		}

		// No networks selected in the settings panel.
		if ( ! is_array( $this->networks ) || empty( $this->networks ) ) {
			return;
		}

		$class = array();
		$class = apply_filters( 'socialsnap_display_position_classes', $class, 'hub' );
		$class = implode( ' ', $class );

		$params = array(
			'post_id' => $this->post_id,
		);

		ob_start();
		?>

		<div id="ss-share-hub" class="<?php echo esc_attr( $class ); ?>">
			<a href="#" rel="nofollow noopener"><?php echo socialsnap()->icons->get_svg( 'share' ); // phpcs:ignore ?></a>

			<div class="ss-share-hub-total-counter">

				<?php
				$this->render_view_count( 'hub', $params );
				$this->render_share_count( 'hub', $params );
				?>

			</div><!-- END .ss-share-hub-total-counter -->

			<?php $this->render_social_icons( 'hub', $params ); ?>

		</div><!-- END #ss-share-hub -->

		<?php
		$output = ob_get_clean();

		$this->add_inline_styles( 'hub' );

		echo wp_kses( apply_filters( 'socialsnap_social_share_hub', $output ), socialsnap_get_allowed_html_tags( 'post' ) );
	}

	/**
	 * Display Social Sharing buttons in a sticky bar.
	 *
	 * @since 1.0.0
	 */
	public function render_position_sticky_bar() {

		if ( ! socialsnap_settings( 'ss_ss_sticky_bar_enabled' ) && ! is_admin() ) {
			return;
		}

		// No "Display On" selected.
		if ( ! is_admin() && ! $this->check_display_on( socialsnap_settings( 'ss_ss_sticky_bar_post_types' ) ) ) {
			return;
		}

		// No networks selected in the settings panel.
		if ( ! is_admin() && ( ! is_array( $this->networks ) || empty( $this->networks ) ) ) {
			return;
		}

		$class = array( 'ss-clearfix' );

		// Sticky settings.
		$sticky_style       = socialsnap_settings( 'ss_ss_sticky_bar_style' );
		$sticky_position    = socialsnap_settings( 'ss_ss_sticky_bar_position' );
		$sticky_sync_inline = socialsnap_settings( 'ss_ss_sticky_bar_sync_inline' );
		$sticky_btn_size    = socialsnap_settings( 'ss_ss_sticky_bar_button_size' );

		// Add classes from Settings.
		$class[] = 'ss-' . $sticky_position . '-sticky-bar';
		$class[] = 'ss-' . $sticky_style . '-sticky-bar';

		// Sticky style.
		if ( 'as-inline' !== $sticky_style ) {
			$class[]            = 'ss-' . $sticky_btn_size . '-icons';
			$sticky_sync_inline = false;
		} else {
			$class[] = $sticky_sync_inline ? 'ss-sync-inline' : '';
		}

		// Hide on mobile.
		$class[] = socialsnap_settings( 'ss_ss_sticky_bar_visibility' );

		// Entrance animation.
		if ( 'none' != socialsnap_settings( 'ss_ss_sticky_bar_entrance_animation' ) ) {
			$class[] = 'ss-entrance-animation-' . socialsnap_settings( 'ss_ss_sticky_bar_entrance_animation' );
			$class[] = 'ss-animate-entrance';
		}

		// Sticky visibility when scrolling.
		$after_scroll = '';

		if ( socialsnap_settings( 'ss_ss_sticky_bar_show_after' ) != 0 && ! $sticky_sync_inline && ! is_admin() ) {
			$after_scroll = ' data-afterscroll="' . absint( socialsnap_settings( 'ss_ss_sticky_bar_show_after' ) ) . '"';
			$class[]      = 'ss-show-after-scroll';
			$class[]      = 'ss-initially-hidden';
		}

		// Split classes.
		$class = implode( ' ', $class );

		$params = array(
			'post_id' => $this->post_id,
		);

		ob_start();
		?>

		<div id="ss-sticky-bar" class="<?php echo esc_attr( $class ); ?>"<?php echo $after_scroll; // phpcs:ignore ?>>

			<?php

			if ( 'as-inline' === $sticky_style || is_admin() ) {
				$this->render_position_inline_content();
			}

			if ( 'stretched' === $sticky_style || is_admin() ) {
				$this->render_share_count( 'sticky_bar', $params );
				$this->render_social_icons( 'sticky_bar', $params );
				$this->render_view_count( 'sticky_bar', $params );
			}

			?>

		</div><!-- END #sticky-bar -->

		<?php

		$output = ob_get_clean();

		$this->add_inline_styles( 'sticky_bar' );

		echo wp_kses( apply_filters( 'socialsnap_social_share_sticky_bar', $output ), socialsnap_get_allowed_html_tags( 'post' ) );
	}

	/**
	 * Display View count
	 *
	 * @since 1.0.0
	 */
	public function render_view_count_filter( $output = '', $location = null ) {

		if ( is_null( $location ) ) {
			return;
		}

		if ( ( ! socialsnap_settings( 'ss_ss_' . $location . '_view_count' ) || ! is_singular() ) && ! is_admin() ) {
			return;
		}

		$data = array(
			'post_id'   => socialsnap_get_current_post_id(),
			'permalink' => socialsnap_get_current_url(),
		);

		ob_start();
		?>
		<span class="ss-total-counter ss-share-<?php echo esc_attr( $location ); ?>-views">
			<span>
				<?php
				if ( is_admin() ) {
					$view_count = wp_rand( 50, 100 );
				} else {
					$view_count = socialsnap_format_number( get_post_meta( $data['post_id'], 'ss_view_count', true ) );
				}

				echo esc_html( $view_count );

				?>
				</span>
			<span><?php echo esc_html( _n( 'view', 'views', $view_count, 'socialsnap' ) ); ?></span>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Inline Content Total Share style.
	 *
	 * @since 1.0.0
	 */
	public function inline_total_share_style( $location, $settings ) {

		if ( 'inline_content' === $location ) {
			if ( isset( $settings['options']['inline_total_style'] ) && in_array( $settings['options']['inline_total_style'], array( 'icon', 'both' ) ) || is_admin() ) {
				echo socialsnap()->icons->get_svg( 'share' ); // phpcs:ignore
			}
		}
	}

	/**
	 * Add shortcode parameter
	 *
	 * @param array $atts Shortcode attributes.
	 * @since 1.0.0
	 */
	public function add_shortcode_atts( $atts ) {

		$atts['inline_total_style']    = socialsnap_settings( 'ss_ss_inline_content_total_share_style' );
		$atts['hover_animation']       = socialsnap_settings( 'ss_ss_inline_content_hover_animation' );
		$atts['total_share_placement'] = socialsnap_settings( 'ss_ss_inline_content_total_share_placement' );
		$atts['share_target']          = '';

		return $atts;
	}

	/**
	 * Add block editor default parameter.
	 *
	 * @param array $atts Block attributes.
	 * @since 1.0.0
	 */
	public function add_block_editor_atts( $atts ) {

		$atts['inline_total_style']    = 'none';
		$atts['hover_animation']       = 'ss-hover-animation-fade';
		$atts['total_share_placement'] = 'left';
		$atts['share_target']          = '';

		return $atts;
	}

	/**
	 * Add shortcode output options
	 *
	 * @since 1.0.0
	 */
	public function add_shortcode_output_options( $options, $atts ) {

		if ( isset( $atts['inline_total_style'] ) ) {
			$options['inline_total_style'] = $atts['inline_total_style'];
		}

		if ( isset( $atts['hover_animation'] ) ) {
			$options['hover_animation'] = $atts['hover_animation'];
		}

		if ( isset( $atts['total_share_placement'] ) ) {
			$options['total_share_placement'] = $atts['total_share_placement'];
		}

		if ( isset( $atts['share_target'] ) ) {
			$options['share_target'] = $atts['share_target'];
		}

		return $options;
	}

	/**
	 * Get final share link of the current page. Apply all possible filters.
	 *
	 * @since 1.0.0
	 */
	public function complete_social_share_permalink( $url, $network ) {

		$url = apply_filters( 'socialsnap_add_utm_parameters', $url, $network );

		if ( in_array( $network, array( 'reddit' ) ) ) {
			return $url;
		}

		$url = apply_filters( 'socialsnap_shorten_link', $url, $network );

		return $url;
	}

	/**
	 * Allow sharing custom URL.
	 *
	 * @since 1.0.0`
	 */
	public function custom_social_share_permalink( $data, $location ) {

		if ( isset( $data['options'] ) && isset( $data['options']['share_target'] ) && $data['options']['share_target'] ) {

			$data['post_id']   = socialsnap_get_current_post_id( $data['options']['share_target'] );
			$data['permalink'] = $data['options']['share_target'];

			return $data;
		}

		if ( ! isset( $data['post_id'] ) || ! $data['post_id'] ) {
			$data['post_id'] = socialsnap_get_current_post_id();
		}

		$custom_target = get_post_meta( $data['post_id'], 'ss_ss_button_target', true );

		if ( $custom_target && '' !== $custom_target ) {
			$data['post_id']   = socialsnap_get_current_post_id( $custom_target );
			$data['permalink'] = socialsnap_get_current_url( $data['post_id'] );
		}

		return $data;
	}

	/**
	 * Print custom styles for share buttons in <head> tag.
	 *
	 * @since 1.0.0
	 */
	public function print_custom_style() {

		if ( socialsnap_is_amp_page() ) {
			return;
		}

		$style = array();
		$css   = array();

		// Positions.
		$positions = socialsnap_get_social_share_positions();

		// No positions are enabled.
		if ( ! is_array( $positions ) || empty( $positions ) ) {
			return;
		}

		// Custom styles for each position.
		foreach ( $positions as $position ) {

			if ( ! socialsnap_settings( 'ss_ss_' . $position . '_enabled' ) ) {
				continue;
			}

			if ( ! socialsnap_settings( 'ss_ss_' . $position . '_custom_colors' ) ) {
				continue;
			}

			$background_color       = socialsnap_settings( 'ss_ss_' . $position . '_button_background_color' );
			$icon_color             = socialsnap_settings( 'ss_ss_' . $position . '_button_icon_color' );
			$background_hover_color = socialsnap_settings( 'ss_ss_' . $position . '_button_background_hover_color' );
			$icon_hover_color       = socialsnap_settings( 'ss_ss_' . $position . '_button_icon_hover_color' );
			$hover_animation        = socialsnap_settings( 'ss_ss_' . $position . '_hover_animation' );

			switch ( $position ) {
				case 'inline_content':
					// Button color & Button Hover Background color.
					if ( strpos( $hover_animation, 'ss-hover-animation-1' ) !== false ) {
						$style['.ss-inline-share-wrapper .ss-social-icons-container > li > a'][]                                  = 'background-color: ' . $background_color;
						$style['.ss-inline-share-wrapper.ss-hover-animation-1 .ss-social-icons-container > li > a:hover:after'][] = 'background-color: ' . $background_hover_color;
					} else {
						$style['.ss-inline-share-wrapper .ss-social-icons-container > li > a'][]       = 'background-color: ' . $background_color;
						$style['.ss-inline-share-wrapper .ss-social-icons-container > li > a:hover'][] = 'background-color: ' . $background_hover_color;
					}

					// Icon Color.
					$style['.ss-inline-share-wrapper .ss-social-icons-container > li > a, .ss-inline-share-wrapper .ss-social-icons-container > li > a.ss-share-all'][]             = 'color: ' . $icon_color . ' !important';
					$style['.ss-inline-share-wrapper .ss-social-icons-container > li > a:hover, .ss-inline-share-wrapper .ss-social-icons-container > li > a.ss-share-all:hover'][] = 'color: ' . $icon_hover_color . ' !important';

					break;

				case 'on_media':
					// Button color & Button Hover Background color.
					$style['.ss-on-media-wrapper .ss-social-icons-container > li > .ss-ss-on-media-button'][]       = 'background-color: ' . $background_color;
					$style['.ss-on-media-wrapper .ss-social-icons-container > li > .ss-ss-on-media-button:hover'][] = 'background-color: ' . $background_hover_color;

					// Icon Color.
					$style['.ss-on-media-wrapper .ss-social-icons-container > li > .ss-ss-on-media-button, .ss-on-media-wrapper .ss-social-icons-container > li > .ss-ss-on-media-button.ss-share-all'][] = 'color: ' . $icon_color;
					$style['.ss-on-media-wrapper .ss-social-icons-container > li > .ss-ss-on-media-button:hover'][] = 'color: ' . $icon_hover_color . ' !important';

					break;

				default:
					break;
			}
		}

		$style = apply_filters( 'socialsnap_social_share_buttons_style', $style );

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

	/**
	 * Remove the share count if it's less than specified.
	 *
	 * @since 1.0.0
	 */
	public function minimum_share_count( $count, $location ) {

		// Get min share count from options.
		$min_share_count = intval( socialsnap_settings( 'ss_ss_' . $location . '_min_count' ) );

		if ( $min_share_count <= $count ) {
			return $count;
		}
	}

	/**
	 * Remove the total share count if it's less than specified.
	 *
	 * @since 1.0.0
	 */
	public function minimum_share_count_total_shares( $settings, $location, $count ) {

		// Get min share count from options
		$min_share_count = intval( socialsnap_settings( 'ss_ss_' . $location . '_min_count' ) );

		if ( $min_share_count > $count ) {
			$settings['options']['show_total_count'] = false;
		}

		return $settings;
	}

	/**
	 * Create array of classes for specified button location
	 *
	 * @since 1.0.0
	 */
	public function add_display_position_classes( $class = array(), $location = null, $settings = array() ) {

		if ( is_null( $location ) ) {
			return $class;
		}

		// Default Settings.
		$defaults = array(
			'entrance_animation'    => socialsnap_settings( 'ss_ss_' . $location . '_entrance_animation' ),
			'hover_animation'       => socialsnap_settings( 'ss_ss_' . $location . '_hover_animation' ),
			'light_counter'         => socialsnap_settings( 'ss_ss_' . $location . '_light_counter' ),
			'inline_total_style'    => socialsnap_settings( 'ss_ss_inline_content_total_share_style' ),
			'total_share_placement' => socialsnap_settings( 'ss_ss_inline_content_total_share_placement' ),
		);

		$settings = array_replace_recursive( $defaults, $settings );

		// Entrance animation.
		if ( in_array( $location, array( 'sidebar', 'hub', 'sticky_bar' ) ) ) {
			if ( 'none' != $settings['entrance_animation'] ) {
				$class[] = 'ss-entrance-animation-' . $settings['entrance_animation'];
				$class[] = 'ss-animate-entrance';
			}
		}

		// Hover animation.
		$class[] = $settings['hover_animation'];

		// Light Counter style.
		if ( $settings['light_counter'] ) {
			$class[] = 'ss-light-count';
		}

		if ( 'inline_content' === $location ) {
			if ( 'separator' === $settings['inline_total_style'] || 'both' === $settings['inline_total_style'] ) {
				$class[] = 'ss-with-counter-border';
			}

			$class[] = 'ss-inline-total-counter-' . $settings['total_share_placement'];
		}

		return $class;
	}

	/**
	 * Mark like button as already liked.
	 *
	 * @since 1.0.0
	 */
	public function like_button_class( $class, $network, $post_id ) {

		if ( socialsnap_settings( 'ss_remove_user_data' ) ) {
			return $class;
		}

		if ( 'heart' == $network ) {

			$stats = array(
				'post_id'    => $post_id,
				'network'    => $network,
				'type'       => 'like',
				'ip_address' => socialsnap_get_ip(),
			);

			$results = socialsnap()->stats->get_stats( $stats );

			if ( $results ) {
				$class .= ' ss-already-liked';
			}
		}

		return $class;
	}

	/**
	 * Add inline styles for share buttons.
	 *
	 * @param array  $styles
	 * @param string $position
	 * @return array
	 */
	public function inline_styles( $style, $position = '' ) {

		if ( 'sidebar' === $position ) {

			$sidebar_position = socialsnap_settings( 'ss_ss_sidebar_position' );
			$sidebar_offset   = (int) socialsnap_settings( 'ss_ss_sidebar_position_offset' );

			// Vertical alignment.
			if ( strpos( $sidebar_position, 'top' ) !== false ) {
				$style[':root'][] = '--ss-fsidebar-justify: flex-start';
			} elseif ( strpos( $sidebar_position, 'bottom' ) !== false ) {
				$style[':root'][] = '--ss-fsidebar-justify: flex-end';
			}

			// Offset.
			$style[':root'][] = "--ss-fsidebar-y-offset: {$sidebar_offset}px";

			if ( socialsnap_settings( 'ss_ss_sidebar_custom_colors' ) ) {

				$background_color       = socialsnap_settings( 'ss_ss_sidebar_button_background_color' );
				$icon_color             = socialsnap_settings( 'ss_ss_sidebar_button_icon_color' );
				$background_hover_color = socialsnap_settings( 'ss_ss_sidebar_button_background_hover_color' );
				$icon_hover_color       = socialsnap_settings( 'ss_ss_sidebar_button_icon_hover_color' );
				$hover_animation        = socialsnap_settings( 'ss_ss_sidebar_hover_animation' );

				// Button color & Button Hover Background color.
				if ( strpos( $hover_animation, 'ss-hover-animation-1' ) !== false ) {
					$style['#ss-floating-bar.ss-hover-animation-1 .ss-social-icons-container > li > a:hover:after'][] = 'background-color: ' . $background_hover_color . ' !important';
				} else {
					$style['#ss-floating-bar .ss-social-icons-container > li > a:hover:after'][] = 'background-color: ' . $background_hover_color . ' !important';
				}

				$style['#ss-floating-bar .ss-social-icons-container > li > a'][] = 'background-color: ' . $background_color;

				// Icon Color.
				$style['#ss-floating-bar .ss-social-icons-container > li > a, #ss-floating-bar .ss-social-icons-container > li > a.ss-share-all'][] = 'color: ' . $icon_color . ' !important';
				$style['#ss-floating-bar .ss-social-icons-container > li > a:hover'][] = 'color: ' . $icon_hover_color . ' !important';

			}
		}

		if ( 'sticky_bar' === $position ) {

			if ( socialsnap_settings( 'ss_ss_sticky_bar_custom_colors' ) ) {

				if ( 'stretched' === socialsnap_settings( 'ss_ss_sticky_bar_style' ) ) {

					$background_color       = socialsnap_settings( 'ss_ss_sticky_bar_button_background_color' );
					$icon_color             = socialsnap_settings( 'ss_ss_sticky_bar_button_icon_color' );
					$background_hover_color = socialsnap_settings( 'ss_ss_sticky_bar_button_background_hover_color' );
					$icon_hover_color       = socialsnap_settings( 'ss_ss_sticky_bar_button_icon_hover_color' );

					// Button color & Button Hover Background color.
					$style['#ss-sticky-bar .ss-social-icons-container > li > a'][]             = 'background-color: ' . $background_color;
					$style['#ss-sticky-bar .ss-social-icons-container > li > a:hover'][]       = 'background-color: ' . $background_hover_color;
					$style['#ss-sticky-bar .ss-social-icons-container > li > a:hover:after'][] = 'background: none';

					// Icon Color.
					$style['#ss-sticky-bar .ss-social-icons-container > li > a, #ss-sticky-bar .ss-social-icons-container > li > a.ss-share-all'][]             = 'color: ' . $icon_color . ' !important';
					$style['#ss-sticky-bar .ss-social-icons-container > li > a:hover, #ss-sticky-bar .ss-social-icons-container > li > a.ss-share-all:hover'][] = 'color: ' . $icon_hover_color . ' !important';
				}

				if ( 'as-inline' === socialsnap_settings( 'ss_ss_sticky_bar_style' ) ) {
					$icon_hover_color = socialsnap_settings( 'ss_ss_inline_content_button_icon_hover_color' );

					$style['#ss-sticky-bar .ss-social-icons-container > li > a.ss-share-all'][] = 'color: ' . $icon_hover_color . ' !important';
				}
			}
		}

		if ( 'hub' === $position ) {
			// Hub Button colors.
			$style['#ss-share-hub > a::after'][] = 'background-color:' . socialsnap_settings( 'ss_ss_hub_color' ) . ' !important';
			$style['#ss-share-hub > a'][]        = 'color:' . socialsnap_settings( 'ss_ss_hub_icon_color' ) . ' !important';

			if ( socialsnap_settings( 'ss_ss_hub_custom_colors' ) ) {

				$background_color       = socialsnap_settings( 'ss_ss_hub_button_background_color' );
				$icon_color             = socialsnap_settings( 'ss_ss_hub_button_icon_color' );
				$background_hover_color = socialsnap_settings( 'ss_ss_hub_button_background_hover_color' );
				$icon_hover_color       = socialsnap_settings( 'ss_ss_hub_button_icon_hover_color' );
				$hover_animation        = socialsnap_settings( 'ss_ss_hub_hover_animation' );

				// Button Background color & Button Hover Background color.
				if ( strpos( $hover_animation, 'ss-hover-animation-1' ) !== false ) {
					$style['#ss-share-hub .ss-social-icons-container > li > a'][]                                  = 'background-color: ' . $background_color;
					$style['#ss-share-hub.ss-hover-animation-1 .ss-social-icons-container > li > a:hover:after'][] = 'background-color: ' . $background_hover_color;
				} else {
					$style['#ss-share-hub .ss-social-icons-container > li > a'][]       = 'background-color: ' . $background_color;
					$style['#ss-share-hub .ss-social-icons-container > li > a:after'][] = 'background-color: ' . $background_hover_color;
				}

				// Icon Color
				$style['#ss-share-hub .ss-social-icons-container > li > a, #ss-share-hub .ss-social-icons-container > li > a.ss-share-all'][] = 'color: ' . $icon_color . ' !important;';
				$style['#ss-share-hub .ss-social-icons-container > li > a:hover'][] = 'color: ' . $icon_hover_color . ' !important';
			}
		}

		return $style;
	}

	/**
	 * Set displayed buttons indicator.
	 *
	 * @param  boolean $displayed Share buttons displayed.
	 * @return boolean            Share buttons displayed.
	 */
	public function share_buttons_displayed( $displayed ) {

		return $displayed || $this->is_displayed;
	}

	/**
	 * Set displayed counts indicator.
	 *
	 * @param  boolean $displayed Share counts displayed.
	 * @return boolean            Share counts displayed.
	 */
	public function share_counts_displayed( $displayed ) {

		return $displayed || $this->counts_displayed;
	}

	/**
	 * Set share all displayed counts indicator.
	 *
	 * @param  boolean $displayed Share all popup displayed.
	 * @return boolean            Share all popup displayed.
	 */
	public function share_all_popup_displayed( $displayed ) {

		return $displayed || $this->share_all_popup_displayed;
	}

	/**
	 * Add the custom field when editing media item.
	 *
	 * @since  1.1.3
	 * @param  array  $form_fields The other fields present in the media editor.
	 * @param  object $post The WP Attachment object.
	 * @return array $form_fields The filtered form fields, now including our box.
	 */
	public function edit_attachment_field( $form_fields, $post ) {

		// Pinterest description.
		$form_fields['ss_pinterest_description'] = array(
			'label' => esc_html__( '[Social Snap] Pin Description', 'socialsnap' ),
			'input' => 'textarea',
			'value' => get_post_meta( $post->ID, 'ss_pinterest_description', true ),
		);

		// Disable pin button on this image.
		$form_fields['ss_no_pin'] = array(
			'label' => esc_html__( '[Social Snap] Hide Pin Button', 'socialsnap' ),
			'input' => 'html',
			'html'  => '<input type="checkbox" id="attachments-' . $post->ID . '-ss_no_pin" name="attachments[' . $post->ID . '][ss_no_pin]" ' . ( (bool) get_post_meta( $post->ID, 'ss_no_pin', true ) ? 'checked' : '' ) . '/>',
		);

		return $form_fields;
	}


	/**
	 * Save the custom field when editing media item.
	 *
	 * @since  1.1.3
	 * @param  object $post The WP Attachment object.
	 * @param  array  $attachment $key => $value data about $post.
	 * @return array $post The updated post object.
	 */
	public function save_attachment_field( $post, $attachment ) {

		update_post_meta( $post['ID'], 'ss_pinterest_description', sanitize_text_field( $attachment['ss_pinterest_description'] ) );
		update_post_meta( $post['ID'], 'ss_no_pin', (bool) isset( $attachment['ss_no_pin'] ) );

		return $post;
	}

	/**
	 * Register Pro settings fields.
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function add_settings_config( $settings ) {

		// Insert into settings
		$settings['ss_social_sharing']['fields']['ss_social_share_networks_display']['fields'] = socialsnap_array_insert(
			$settings['ss_social_sharing']['fields']['ss_social_share_networks_display']['fields'],
			array(
				'ss_ss_twitter_provider_note' => array(
					'id'   => 'ss_ss_twitter_provider_note',
					'name' => esc_html__( 'Note:', 'socialsnap' ),
					/* translators: %1$s open anchor tag, %2$s close anchor tag */
					'desc' => sprintf( __( 'Follow our %1$sstep-by-step tutorial%2$s on how to set up Twitter Share Provider.', 'socialsnap' ), '<a href="https://socialsnap.com/help/features/how-to-setup-twitter-share-provider/">', '</a>' ),
					'type' => 'note',
				),
			),
			'ss_ss_twitter_count_provider',
			'after'
		);

		// Floating Sidebar Custom Colors
		$settings['ss_social_sharing']['fields']['ss_social_share_floating_sidebar']['fields']['ss_ss_sidebar_button_background_color'] = array(
			'id'         => 'ss_ss_sidebar_button_background_color',
			'name'       => esc_html__( 'Background Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#4f89e1',
			'dependency' => array(
				'element' => 'ss_ss_sidebar_custom_colors',
				'value'   => 'true',
			),
		);

		$settings['ss_social_sharing']['fields']['ss_social_share_floating_sidebar']['fields']['ss_ss_sidebar_button_icon_color'] = array(
			'id'         => 'ss_ss_sidebar_button_icon_color',
			'name'       => esc_html__( 'Icon Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#ffffff',
			'dependency' => array(
				'element' => 'ss_ss_sidebar_custom_colors',
				'value'   => 'true',
			),
		);

		$settings['ss_social_sharing']['fields']['ss_social_share_floating_sidebar']['fields']['ss_ss_sidebar_button_background_hover_color'] = array(
			'id'         => 'ss_ss_sidebar_button_background_hover_color',
			'name'       => esc_html__( 'Background Hover Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#3263ad',
			'dependency' => array(
				'element' => 'ss_ss_sidebar_custom_colors',
				'value'   => 'true',
			),
		);

		$settings['ss_social_sharing']['fields']['ss_social_share_floating_sidebar']['fields']['ss_ss_sidebar_button_icon_hover_color'] = array(
			'id'         => 'ss_ss_sidebar_button_icon_hover_color',
			'name'       => esc_html__( 'Icon Hover Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#ffffff',
			'dependency' => array(
				'element' => 'ss_ss_sidebar_custom_colors',
				'value'   => 'true',
			),
		);

		// Inline Content Custom Colors
		$settings['ss_social_sharing']['fields']['ss_social_share_inline_content']['fields']['ss_ss_inline_content_button_background_color'] = array(
			'id'         => 'ss_ss_inline_content_button_background_color',
			'name'       => esc_html__( 'Background Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#4f89e1',
			'dependency' => array(
				'element' => 'ss_ss_inline_content_custom_colors',
				'value'   => 'true',
			),
		);

		$settings['ss_social_sharing']['fields']['ss_social_share_inline_content']['fields']['ss_ss_inline_content_button_icon_color'] = array(
			'id'         => 'ss_ss_inline_content_button_icon_color',
			'name'       => esc_html__( 'Icon Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#ffffff',
			'dependency' => array(
				'element' => 'ss_ss_inline_content_custom_colors',
				'value'   => 'true',
			),
		);

		$settings['ss_social_sharing']['fields']['ss_social_share_inline_content']['fields']['ss_ss_inline_content_button_background_hover_color'] = array(
			'id'         => 'ss_ss_inline_content_button_background_hover_color',
			'name'       => esc_html__( 'Background Hover Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#3263ad',
			'dependency' => array(
				'element' => 'ss_ss_inline_content_custom_colors',
				'value'   => 'true',
			),
		);

		$settings['ss_social_sharing']['fields']['ss_social_share_inline_content']['fields']['ss_ss_inline_content_button_icon_hover_color'] = array(
			'id'         => 'ss_ss_inline_content_button_icon_hover_color',
			'name'       => esc_html__( 'Icon Hover Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#ffffff',
			'dependency' => array(
				'element' => 'ss_ss_inline_content_custom_colors',
				'value'   => 'true',
			),
		);

		// On Media Custom Colors
		$settings['ss_social_sharing']['fields']['ss_social_share_on_media']['fields']['ss_ss_on_media_button_background_color'] = array(
			'id'         => 'ss_ss_on_media_button_background_color',
			'name'       => esc_html__( 'Background Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#4f89e1',
			'dependency' => array(
				'element' => 'ss_ss_on_media_custom_colors',
				'value'   => 'true',
			),
		);

		$settings['ss_social_sharing']['fields']['ss_social_share_on_media']['fields']['ss_ss_on_media_button_icon_color'] = array(
			'id'         => 'ss_ss_on_media_button_icon_color',
			'name'       => esc_html__( 'Icon Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#ffffff',
			'dependency' => array(
				'element' => 'ss_ss_on_media_custom_colors',
				'value'   => 'true',
			),
		);

		$settings['ss_social_sharing']['fields']['ss_social_share_on_media']['fields']['ss_ss_on_media_button_background_hover_color'] = array(
			'id'         => 'ss_ss_on_media_button_background_hover_color',
			'name'       => esc_html__( 'Background Hover Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#3263ad',
			'dependency' => array(
				'element' => 'ss_ss_on_media_custom_colors',
				'value'   => 'true',
			),
		);

		$settings['ss_social_sharing']['fields']['ss_social_share_on_media']['fields']['ss_ss_on_media_button_icon_hover_color'] = array(
			'id'         => 'ss_ss_on_media_button_icon_hover_color',
			'name'       => esc_html__( 'Icon Hover Color', 'socialsnap' ),
			'type'       => 'color',
			'default'    => '#ffffff',
			'dependency' => array(
				'element' => 'ss_ss_on_media_custom_colors',
				'value'   => 'true',
			),
		);

		// Hub settings
		$settings['ss_social_sharing']['fields']['ss_social_share_share_hub'] = array(
			'id'          => 'ss_social_share_share_hub',
			'name'        => 'Share Hub',
			'parent_name' => __( 'Social Share', 'socialsnap' ),
			'type'        => 'subgroup',
			'fields'      => array(
				'ss_ss_hub_enabled'                       => array(
					'id'      => 'ss_ss_hub_enabled',
					'name'    => esc_html__( 'Enable Hub', 'socialsnap' ),
					'type'    => 'toggle',
					'default' => false,
				),
				'ss_ss_hub_position'                      => array(
					'id'         => 'ss_ss_hub_position',
					'name'       => esc_html__( 'Position', 'socialsnap' ),
					'type'       => 'radio',
					'options'    => array(
						'left'  => __( 'Left', 'socialsnap' ),
						'right' => __( 'Right', 'socialsnap' ),
					),
					'default'    => 'right',
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_button_shape'                  => array(
					'id'         => 'ss_ss_hub_button_shape',
					'name'       => esc_html__( 'Button Shape', 'socialsnap' ),
					'type'       => 'radio',
					'options'    => array(
						'rounded'   => __( 'Rounded', 'socialsnap' ),
						'circle'    => __( 'Circle', 'socialsnap' ),
						'rectangle' => __( 'Rectangle', 'socialsnap' ),
					),
					'default'    => 'circle',
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_button_size'                   => array(
					'id'         => 'ss_ss_hub_button_size',
					'name'       => esc_html__( 'Button Size', 'socialsnap' ),
					'type'       => 'radio',
					'options'    => array(
						'large'   => __( 'Large', 'socialsnap' ),
						'regular' => __( 'Regular', 'socialsnap' ),
						'small'   => __( 'Small', 'socialsnap' ),
					),
					'default'    => 'regular',
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_post_types'                    => array(
					'id'         => 'ss_ss_hub_post_types',
					'name'       => esc_html__( 'Display on', 'socialsnap' ),
					'type'       => 'checkbox_group',
					'source'     => array( 'post_type', 'taxonomies' ),
					'options'    => array(
						'home' => array(
							'title' => __( 'Home', 'socialsnap' ),
						),
						'blog' => array(
							'title' => __( 'Posts Page', 'socialsnap' ),
						),
						'post' => array(
							'title' => __( 'Post', 'socialsnap' ),
						),
						'page' => array(
							'title' => __( 'Page', 'socialsnap' ),
						),
					),
					'default'    => array(
						'home'    => 'on',
						'archive' => 'off',
						'page'    => 'off',
						'post'    => 'on',
					),
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),

				'ss_ss_hub_all_networks'                  => array(
					'id'         => 'ss_ss_hub_all_networks',
					'name'       => esc_html__( 'All Networks Buttons', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Enable button that allows users to choose from all available networks.', 'socialsnap' ),
					'default'    => true,
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_label_tooltip'                 => array(
					'id'         => 'ss_ss_hub_label_tooltip',
					'name'       => esc_html__( 'Network Label Tooltips', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Show network labels when hovering social buttons.', 'socialsnap' ),
					'default'    => true,
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_view_count'                    => array(
					'id'         => 'ss_ss_hub_view_count',
					'name'       => esc_html__( 'View Count', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Display unique view count of the current post/page.', 'socialsnap' ),
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_total_count'                   => array(
					'id'         => 'ss_ss_hub_total_count',
					'name'       => esc_html__( 'Total Share Count', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Display total share count from all social networks.', 'socialsnap' ),
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_share_count'                   => array(
					'id'         => 'ss_ss_hub_share_count',
					'name'       => esc_html__( 'Share Counts', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Display share counts on share buttons. Not available on Archive pages', 'socialsnap' ),
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_min_count'                     => array(
					'id'         => 'ss_ss_hub_min_count',
					'name'       => esc_html__( 'Min Share Count', 'socialsnap' ),
					'type'       => 'text',
					'value_type' => 'number',
					'desc'       => __( 'Hide share counts if lower than this value.', 'socialsnap' ),
					'default'    => '0',
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_entrance_animation'            => array(
					'id'         => 'ss_ss_hub_entrance_animation',
					'name'       => esc_html__( 'Entrance Animation', 'socialsnap' ),
					'type'       => 'dropdown',
					'options'    => array(
						'none'   => __( 'None', 'socialsnap' ),
						'fade'   => __( 'Fade In', 'socialsnap' ),
						'slide'  => __( 'Slide In', 'socialsnap' ),
						'bounce' => __( 'Bounce In', 'socialsnap' ),
						'flip'   => __( 'Flip In', 'socialsnap' ),
					),
					'default'    => 'fade',
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_hover_animation'               => array(
					'id'         => 'ss_ss_hub_hover_animation',
					'name'       => esc_html__( 'Button Hover Animation', 'socialsnap' ),
					'type'       => 'dropdown',
					'options'    => array(
						'ss-hover-animation-fade' => __( 'Fade', 'socialsnap' ),
						'ss-hover-animation-1'    => __( 'Slide Background', 'socialsnap' ),
						'ss-hover-animation-2'    => __( 'Slide Icon', 'socialsnap' ),
						'ss-hover-animation-1 ss-hover-animation-2' => __( 'Slide Icon & Background', 'socialsnap' ),
					),
					'default'    => 'ss-hover-animation-fade',
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_hide_on_mobile'                => array(
					'id'         => 'ss_ss_hub_hide_on_mobile',
					'name'       => esc_html__( 'Hide on Mobile', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Do not show Share Hub on mobile devices.', 'socialsnap' ),
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_light_counter'                 => array(
					'id'         => 'ss_ss_hub_light_counter',
					'name'       => esc_html__( 'Total Share Count: Light Text', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Set text color of total counters to light. Use this option if your website has a dark background.', 'socialsnap' ),
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_color'                         => array(
					'id'         => 'ss_ss_hub_color',
					'name'       => esc_html__( 'Hub Color', 'socialsnap' ),
					'type'       => 'color',
					'default'    => '#4f89e1',
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_icon_color'                    => array(
					'id'         => 'ss_ss_hub_icon_color',
					'name'       => esc_html__( 'Hub Icon Color', 'socialsnap' ),
					'type'       => 'color',
					'default'    => '#fff',
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_custom_colors'                 => array(
					'id'         => 'ss_ss_hub_custom_colors',
					'name'       => esc_html__( 'Custom Colors', 'socialsnap' ),
					'type'       => 'toggle',
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_hub_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_button_background_color'       => array(
					'id'         => 'ss_ss_hub_button_background_color',
					'name'       => esc_html__( 'Icon Background Color', 'socialsnap' ),
					'type'       => 'color',
					'default'    => '#4f89e1',
					'dependency' => array(
						'element' => 'ss_ss_hub_custom_colors',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_button_icon_color'             => array(
					'id'         => 'ss_ss_hub_button_icon_color',
					'name'       => esc_html__( 'Icon Color', 'socialsnap' ),
					'type'       => 'color',
					'default'    => '#ffffff',
					'dependency' => array(
						'element' => 'ss_ss_hub_custom_colors',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_button_background_hover_color' => array(
					'id'         => 'ss_ss_hub_button_background_hover_color',
					'name'       => esc_html__( 'Icon Background Hover Color', 'socialsnap' ),
					'type'       => 'color',
					'default'    => '#3263ad',
					'dependency' => array(
						'element' => 'ss_ss_hub_custom_colors',
						'value'   => 'true',
					),
				),
				'ss_ss_hub_button_icon_hover_color'       => array(
					'id'         => 'ss_ss_hub_button_icon_hover_color',
					'name'       => esc_html__( 'Icon Hover Color', 'socialsnap' ),
					'type'       => 'color',
					'default'    => '#ffffff',
					'dependency' => array(
						'element' => 'ss_ss_hub_custom_colors',
						'value'   => 'true',
					),
				),
			),
		);

		// Sticky Bar settings
		$settings['ss_social_sharing']['fields']['ss_social_share_sticky_bar'] = array(
			'id'          => 'ss_social_share_sticky_bar',
			'name'        => esc_html__( 'Sticky Bar', 'socialsnap' ),
			'parent_name' => __( 'Social Share', 'socialsnap' ),
			'type'        => 'subgroup',
			'fields'      => array(
				'ss_ss_sticky_bar_enabled'                 => array(
					'id'      => 'ss_ss_sticky_bar_enabled',
					'name'    => esc_html__( 'Enable Sticky Bar', 'socialsnap' ),
					'type'    => 'toggle',
					'default' => false,
				),

				'ss_ss_sticky_bar_position'                => array(
					'id'         => 'ss_ss_sticky_bar_position',
					'name'       => esc_html__( 'Position', 'socialsnap' ),
					'type'       => 'radio',
					'options'    => array(
						'top'    => __( 'Top', 'socialsnap' ),
						'bottom' => __( 'Bottom', 'socialsnap' ),
					),
					'default'    => 'bottom',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_enabled',
						'value'   => 'true',
					),
				),

				'ss_ss_sticky_bar_show_after'              => array(
					'id'         => 'ss_ss_sticky_bar_show_after',
					'name'       => esc_html__( 'Show After Scrolling (in px)', 'socialsnap' ),
					'type'       => 'text',
					'value_type' => 'number',
					'desc'       => __( 'Show sticky bar after scrolling. Leave 0 to show always.', 'socialsnap' ),
					'default'    => '0',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_enabled',
						'value'   => 'true',
					),
				),

				'ss_ss_sticky_bar_post_types'              => array(
					'id'         => 'ss_ss_sticky_bar_post_types',
					'name'       => esc_html__( 'Display on', 'socialsnap' ),
					'type'       => 'checkbox_group',
					'source'     => array( 'post_type', 'taxonomies' ),
					'options'    => array(
						'home' => array(
							'title' => __( 'Home', 'socialsnap' ),
						),
						'blog' => array(
							'title' => __( 'Posts Page', 'socialsnap' ),
						),
						'post' => array(
							'title' => __( 'Post', 'socialsnap' ),
						),
						'page' => array(
							'title' => __( 'Page', 'socialsnap' ),
						),
					),
					'default'    => array(
						'home'    => 'off',
						'archive' => 'off',
						'post'    => 'on',
					),
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_enabled',
						'value'   => 'true',
					),
				),

				'ss_ss_sticky_bar_entrance_animation'      => array(
					'id'         => 'ss_ss_sticky_bar_entrance_animation',
					'name'       => esc_html__( 'Entrance Animation', 'socialsnap' ),
					'type'       => 'dropdown',
					'options'    => array(
						'none'   => __( 'None', 'socialsnap' ),
						'fade'   => __( 'Fade In', 'socialsnap' ),
						'slide'  => __( 'Slide In', 'socialsnap' ),
						'bounce' => __( 'Bounce In', 'socialsnap' ),
						'flip'   => __( 'Flip In', 'socialsnap' ),
					),
					'default'    => 'fade',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_enabled',
						'value'   => 'true',
					),
				),

				'ss_ss_sticky_bar_style'                   => array(
					'id'         => 'ss_ss_sticky_bar_style',
					'name'       => esc_html__( 'Style', 'socialsnap' ),
					'type'       => 'dropdown',
					'options'    => array(
						'stretched' => __( 'Stretched', 'socialsnap' ),
						'as-inline' => __( 'Same as Inline Buttons', 'socialsnap' ),
					),
					'default'    => 'stretched',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_enabled',
						'value'   => 'true',
					),
				),

				'ss_ss_sticky_bar_button_size'             => array(
					'id'         => 'ss_ss_sticky_bar_button_size',
					'name'       => esc_html__( 'Button Size', 'socialsnap' ),
					'type'       => 'radio',
					'options'    => array(
						'large'   => __( 'Large', 'socialsnap' ),
						'regular' => __( 'Regular', 'socialsnap' ),
						'small'   => __( 'Small', 'socialsnap' ),
					),
					'default'    => 'regular',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_style',
						'value'   => 'stretched',
					),
				),
				'ss_ss_sticky_bar_all_networks'            => array(
					'id'         => 'ss_ss_sticky_bar_all_networks',
					'name'       => esc_html__( 'All Networks Buttons', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Enable button that allows users to choose from all available networks.', 'socialsnap' ),
					'default'    => true,
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_style',
						'value'   => 'stretched',
					),
				),
				'ss_ss_sticky_bar_view_count'              => array(
					'id'         => 'ss_ss_sticky_bar_view_count',
					'name'       => esc_html__( 'View Count', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Display view count of the current post/page.', 'socialsnap' ),
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_style',
						'value'   => 'stretched',
					),
				),
				'ss_ss_sticky_bar_share_count'             => array(
					'id'         => 'ss_ss_sticky_bar_share_count',
					'name'       => esc_html__( 'Share Counts', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Display share counts on share buttons. Not available on Archive pages.', 'socialsnap' ),
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_style',
						'value'   => 'stretched',
					),
				),
				'ss_ss_sticky_bar_total_count'             => array(
					'id'         => 'ss_ss_sticky_bar_total_count',
					'name'       => esc_html__( 'Total Share Count', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Display total share count from all social networks.', 'socialsnap' ),
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_style',
						'value'   => 'stretched',
					),
				),
				'ss_ss_sticky_bar_min_count'               => array(
					'id'         => 'ss_ss_sticky_bar_min_count',
					'name'       => esc_html__( 'Min Share Count', 'socialsnap' ),
					'type'       => 'text',
					'value_type' => 'number',
					'desc'       => __( 'Hide share counts if lower than this value.', 'socialsnap' ),
					'default'    => '0',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_style',
						'value'   => 'stretched',
					),
				),
				'ss_ss_sticky_bar_visibility'              => array(
					'id'         => 'ss_ss_sticky_bar_visibility',
					'name'       => esc_html__( 'Visibility', 'socialsnap' ),
					'type'       => 'dropdown',
					'options'    => array(
						'ss-hide-on-desktop' => __( 'Visible on Mobile Only', 'socialsnap' ),
						'ss-hide-on-mobile'  => __( 'Visible on Desktop Only', 'socialsnap' ),
						'ss-always-visible'  => __( 'Always Visible', 'socialsnap' ),
					),
					'default'    => 'ss-always-visible',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_enabled',
						'value'   => 'true',
					),
				),
				'ss_ss_sticky_bar_custom_colors'           => array(
					'id'         => 'ss_ss_sticky_bar_custom_colors',
					'name'       => esc_html__( 'Custom Colors', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'Customize share buttons colors.', 'socialsnap' ),
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_style',
						'value'   => 'stretched',
					),
				),
				'ss_ss_sticky_bar_button_background_color' => array(
					'id'         => 'ss_ss_sticky_bar_button_background_color',
					'name'       => esc_html__( 'Background Color', 'socialsnap' ),
					'type'       => 'color',
					'desc'       => __( 'Choose share button background color.', 'socialsnap' ),
					'default'    => '#4f89e1',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_custom_colors',
						'value'   => 'true',
					),
				),
				'ss_ss_sticky_bar_button_icon_color'       => array(
					'id'         => 'ss_ss_sticky_bar_button_icon_color',
					'name'       => esc_html__( 'Icon Color', 'socialsnap' ),
					'type'       => 'color',
					'desc'       => __( 'Choose share button icon color.', 'socialsnap' ),
					'default'    => '#ffffff',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_custom_colors',
						'value'   => 'true',
					),
				),
				'ss_ss_sticky_bar_button_background_hover_color' => array(
					'id'         => 'ss_ss_sticky_bar_button_background_hover_color',
					'name'       => esc_html__( 'Background Hover Color', 'socialsnap' ),
					'type'       => 'color',
					'desc'       => __( 'Choose share button hover background color.', 'socialsnap' ),
					'default'    => '#3263ad',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_custom_colors',
						'value'   => 'true',
					),
				),
				'ss_ss_sticky_bar_button_icon_hover_color' => array(
					'id'         => 'ss_ss_sticky_bar_button_icon_hover_color',
					'name'       => esc_html__( 'Icon Hover Color', 'socialsnap' ),
					'type'       => 'color',
					'desc'       => __( 'Choose share button icon hover color.', 'socialsnap' ),
					'default'    => '#ffffff',
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_custom_colors',
						'value'   => 'true',
					),
				),
				'ss_ss_sticky_bar_sync_inline'             => array(
					'id'         => 'ss_ss_sticky_bar_sync_inline',
					'name'       => esc_html__( 'Sync with Inline Buttons?', 'socialsnap' ),
					'type'       => 'toggle',
					'desc'       => __( 'If enabled, the bottom bar will be aligned to the inline buttons in your content. It will also be hidden if inline buttons are visible.', 'socialsnap' ),
					'default'    => false,
					'dependency' => array(
						'element' => 'ss_ss_sticky_bar_style',
						'value'   => 'as-inline',
					),
				),
			),
		);

		$settings['ss_social_identity']['fields']['ss_social_identity_pinterest'] = array(
			'id'          => 'ss_social_identity_pinterest',
			'name'        => __( 'Pinterest', 'socialsnap' ),
			'parent_name' => __( 'Social Identity', 'socialsnap' ),
			'desc'        => __( 'Enter your Pinterest info here.', 'socialsnap' ),
			'fields'      => array(
				'ss_pinterest_username' => array(
					'id'          => 'ss_pinterest_username',
					'name'        => esc_html__( 'Pinterest Username', 'socialsnap' ),
					'desc'        => __( 'Enter your Pinterest @username. This adds “via @username” to share description when someone pins one of your posts/pages.', 'socialsnap' ),
					'type'        => 'text',
					'placeholder' => '@username',
				),
			),
		);

		$settings['ss_social_identity']['fields']['ss_social_identity_facebook'] = array(
			'id'          => 'ss_social_identity_facebook',
			'name'        => __( 'Facebook', 'socialsnap' ),
			'parent_name' => __( 'Social Identity', 'socialsnap' ),
			'desc'        => __( 'Enter your Facebook info here.', 'socialsnap' ),
			'fields'      => array(
				'ss_facebook_appid'        => array(
					'id'          => 'ss_facebook_appid',
					'name'        => esc_html__( 'Facebook App ID', 'socialsnap' ),
					'desc'        => __( 'This is required for Facebook Messenger share URL to work on desktop devices.', 'socialsnap' ),
					'type'        => 'text',
					'placeholder' => '',
				),
				'ss_facebook_redirect_uri' => array(
					'id'   => 'ss_facebook_redirect_uri',
					'name' => esc_html__( 'Note:', 'socialsnap' ),
					/* translators: %s is home url */
					'desc' => sprintf( __( 'Add the following link to your OAuth redirect URIs in your Facebook app\'s settings in the Products > Facebook Login config: %s', 'socialsnap' ), '<code>' . home_url( '/' ) ) . '</code>',
					'type' => 'note',
				),
			),
		);

		return $settings;
	}

	/**
	 * Register Pro editor (shortcode generator) fields.
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function add_editor_config( $settings ) {

		// Total Share Style.
		$ss_ss_total_count_style = array(
			'id'      => 'ss_ss_total_count_style',
			'name'    => esc_html__( 'Total Shares Style', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'pro'     => true,
			'desc'    => __( 'Choose total shares style.', 'socialsnap' ),
			'options' => array(
				'none'      => __( 'Default', 'socialsnap' ),
				'icon'      => __( 'With Icon', 'socialsnap' ),
				'separator' => __( 'With Separator', 'socialsnap' ),
				'both'      => __( 'With Icon + Separator', 'socialsnap' ),
			),
			'default' => socialsnap_settings( 'ss_ss_inline_content_total_share_style' ),
		);

		// Append to settings.
		$settings['ss-social-share-editor']['options']['ss_ss_total_count_style'] = $ss_ss_total_count_style;

		// Total Share Placement.
		$ss_ss_total_count_placement = array(
			'id'      => 'ss_ss_total_count_placement',
			'name'    => esc_html__( 'Total Shares Placement', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'pro'     => true,
			'desc'    => __( 'Choose between left and right placement for Total Shares counter.', 'socialsnap' ),
			'options' => array(
				'left'  => __( 'Left', 'socialsnap' ),
				'right' => __( 'Right', 'socialsnap' ),
			),
			'default' => socialsnap_settings( 'ss_ss_inline_content_total_share_placement' ),
		);

		// Append to settings.
		$settings['ss-social-share-editor']['options']['ss_ss_total_count_placement'] = $ss_ss_total_count_placement;

		// Button Hover Animation.
		$ss_ss_hover_animation = array(
			'ss_ss_hover_animation' => array(
				'id'      => 'ss_ss_hover_animation',
				'name'    => esc_html__( 'Button Hover Animation', 'socialsnap' ),
				'type'    => 'editor_dropdown',
				'pro'     => true,
				'options' => array(
					'ss-hover-animation-fade' => __( 'Fade', 'socialsnap' ),
					'ss-hover-animation-1'    => __( 'Slide Background', 'socialsnap' ),
					'ss-reveal-label'         => __( 'Reveal Label', 'socialsnap' ),
				),
				'default' => socialsnap_settings( 'ss_ss_inline_content_hover_animation' ),
			),
		);

		// Insert into settings.
		$settings['ss-social-share-editor']['options'] = socialsnap_array_insert(
			$settings['ss-social-share-editor']['options'],
			$ss_ss_hover_animation,
			'ss_ss_button_labels',
			'after'
		);

		// Share button target custom.
		$ss_ss_button_target = array(
			'ss_ss_button_target_editor' => array(
				'id'          => 'ss_ss_button_target_editor',
				'name'        => esc_html__( 'Custom Share Target URL', 'socialsnap' ),
				'type'        => 'editor_text',
				'default'     => '',
				'placeholder' => 'http://',
				'desc'        => __( 'Enter URL of the page to be shared. This must be a valid page URL on this site. Leave empty to use current page.', 'socialsnap' ),
			),
		);

		$settings['ss-social-share-editor']['options'] += $ss_ss_button_target;

		return $settings;
	}

	/**
	 * Register Social Share metabox fields.
	 *
	 * @since 1.0.0
	 * @param array $metaboxes
	 * @return array
	 */
	public function add_metaboxes_config( $metaboxes ) {

		if ( ! isset( $metaboxes['ss-socialsnap-main'] ) ) {
			return $metaboxes;
		}

		$ss_metabox = array(
			'ss_ss_custom_tweet' => array(
				'id'          => 'ss_ss_custom_tweet',
				'name'        => esc_html__( 'Custom Tweet', 'socialsnap' ),
				'type'        => 'editor_textarea',
				'placeholder' => '',
				'desc'        => __( 'Tweet to be shared when user clicks the Twitter share button. Leave empty to auto-generate the content based on your settings.', 'socialsnap' ),
				'countchar'   => 280,
				'ctt'         => true,
			),
		);

		$metaboxes['ss-socialsnap-main']['options']['ss-ss-metabox']['options'] += $ss_metabox;

		$ss_metabox = array(
			'ss_image_pinterest' => array(
				'id'        => 'ss_image_pinterest',
				'name'      => esc_html__( 'Pinterest Image', 'socialsnap' ),
				'type'      => 'editor_upload',
				'desc'      => __( 'Upload an image which will be displayed when this post is shared on Pinterest.', 'socialsnap' ),
				'extradesc' => __( 'Recommended image dimension is 800px by 1200px.', 'socialsnap' ),
			),
		);

		$metaboxes['ss-socialsnap-main']['options']['ss-ss-metabox']['options'] += $ss_metabox;

		$ss_metabox = array(
			'ss_pinterest_description' => array(
				'id'        => 'ss_pinterest_description',
				'name'      => esc_html__( 'Pinterest Description', 'socialsnap' ),
				'type'      => 'editor_textarea',
				'desc'      => esc_html__( 'Content to be shared when user clicks the Pinterest share button. Leave empty to use the title of the post.', 'socialsnap' ),
				'countchar' => 500,
			),
		);

		$metaboxes['ss-socialsnap-main']['options']['ss-ss-metabox']['options'] += $ss_metabox;

		$ss_metabox = array(
			'ss_ss_button_target' => array(
				'id'          => 'ss_ss_button_target',
				'name'        => esc_html__( 'Custom Share Target URL', 'socialsnap' ),
				'type'        => 'editor_text',
				'placeholder' => 'http://',
				'desc'        => __( 'Enter URL of the page to be shared. This must be a valid page URL on this site. Leave empty to use current page.', 'socialsnap' ),
			),
		);

		$metaboxes['ss-socialsnap-main']['options']['ss-ss-metabox']['options'] += $ss_metabox;

		return $metaboxes;
	}
}

/**
 * The function which returns the one SocialSnap_Social_Share_PRO instance.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $socialsnap_pro_social_share = socialsnap_pro_social_share(); ?>
 *
 * @since  1.1.6
 * @return object
 */
function socialsnap_pro_social_share() {
	return SocialSnap_Social_Share_PRO::instance();
}

socialsnap_pro_social_share();
