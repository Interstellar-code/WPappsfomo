<?php
/**
 * Adds meta tags for Twitter Cards, Open Graph and Schema.org
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Meta_Tags {

	/**
	 * Singleton instance of the class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance;

	/**
	 * Main SocialSnap_Meta_Tags Instance.
	 *
	 * @since 1.0.0
	 * @return SocialSnap_Meta_Tags
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SocialSnap_Meta_Tags ) ) {
			self::$instance = new SocialSnap_Meta_Tags();
		}
		return self::$instance;
	}

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add fields to settings panel
		add_filter( 'socialsnap_settings_config', array( $this, 'add_settings_config' ), 6, 1 );

		// Exit if Social Meta Tags are not enabled
		if ( ! socialsnap_settings( 'ss_smt_enable' ) ) {
			return;
		}

		// Insert meta tags
		add_action( 'wp_head', array( $this, 'insert_meta_tags' ), 1 );
		add_action( 'socialsnap_meta_tags_output', array( $this, 'open_graph_meta_tags' ) );
		add_action( 'socialsnap_meta_tags_output', array( $this, 'twitter_meta_tags' ) );
		add_filter( 'language_attributes', array( $this, 'opengraph_namespace' ), 15 );

		// Add fields to metaboxes
		add_filter( 'socialsnap_metaboxes_config', array( $this, 'add_metaboxes_config' ), 5, 1 );

		// Disable Jetpack's Open Graph & Twitter tags
		add_filter( 'jetpack_enable_opengraph', '__return_false', 99 );
		add_filter( 'jetpack_enable_open_graph', '__return_false', 99 );
		add_filter( 'jetpack_disable_twitter_cards', '__return_true', 99 );
	}

	/**
	 * Generate and insert meta tags to <head>
	 *
	 * @since 1.0.0
	 */
	public function insert_meta_tags() {
		echo PHP_EOL . '<!-- Powered by Social Snap v' . esc_attr( socialsnap()->version ) . ' - https://socialsnap.com/ -->';
		do_action( 'socialsnap_meta_tags_output' );
		echo PHP_EOL . '<!-- Powered by Social Snap v' . esc_attr( socialsnap()->version ) . ' - https://socialsnap.com/ -->' . PHP_EOL . PHP_EOL;
	}


	/**
	 * Output Twitter Cards tags.
	 * Integrates well with Yoast SEO plugin.
	 *
	 * The following tags are added:
	 *
	 * twitter:card
	 * twitter:site
	 * twitter:title
	 * twitter:description
	 * twitter:creator
	 * twitter:image:src
	 *
	 * @param String $output
	 * @return String - Twitter Cards tags.
	 * @since 1.0.0
	 */
	public function twitter_meta_tags() {

		$post_id = socialsnap_get_current_post_id();

		if ( $post_id <= 0 ) {
			return;
		}

		$is_product = function_exists( 'is_product' ) && is_product();
		$twt_card   = socialsnap_settings( 'ss_smt_twitter_card_type' );
		$twt_card   = in_array( $twt_card, array( 'summary', 'summary_large_image' ) ) ? $twt_card : 'summary';

		$title       = get_post_meta( $post_id, 'ss_smt_title', true );
		$description = get_post_meta( $post_id, 'ss_smt_description', true );
		$image       = get_post_meta( $post_id, 'ss_smt_image', true );
		$video       = get_post_meta( $post_id, 'ss_smt_video', true );
		$twt_card    = $video ? 'player' : $twt_card;

		// Yoast settings
		$yoast_settings = array(
			'twt_title'       => '',
			'twt_description' => '',
			'twt_image'       => '',
			'seo_title'       => '',
			'seo_description' => '',
		);

		if ( defined( 'WPSEO_VERSION' ) ) {
			$yoast_settings['twt_title']       = get_post_meta( $post_id, '_yoast_wpseo_twitter-title', true );
			$yoast_settings['twt_description'] = get_post_meta( $post_id, '_yoast_wpseo_twitter-description', true );
			$yoast_settings['twt_image']       = get_post_meta( $post_id, '_yoast_wpseo_twitter-image', true );
			$yoast_settings['seo_title']       = get_post_meta( $post_id, '_yoast_wpseo_title', true );
			$yoast_settings['seo_description'] = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );

			remove_action( 'wpseo_head', array( 'WPSEO_Twitter', 'get_instance' ), 40 );
		}

		// Twitter Title
		if ( '' !== $title ) {
			$twt_title = $title;
		} elseif ( '' !== $yoast_settings['twt_title'] ) {
			$twt_title = wpseo_replace_vars( $yoast_settings['twt_title'], get_post( $post_id ) );
		} elseif ( '' !== $yoast_settings['seo_title'] ) {
			$twt_title = wpseo_replace_vars( $yoast_settings['seo_title'], get_post( $post_id ) );
		} elseif ( $post_id <= 0 ) {
			$twt_title = get_bloginfo( 'name' );
		} else {
			$twt_title = wp_strip_all_tags( get_the_title( $post_id ) );
		}

		$twt_title = apply_filters( 'socialsnap_twitter_card_title', $twt_title, $post_id );

		// Twitter Description
		if ( '' !== $description ) {
			$twt_description = $description;
		} elseif ( '' !== $yoast_settings['twt_description'] ) {
			$twt_description = $yoast_settings['twt_description'];
		} elseif ( '' !== $yoast_settings['seo_description'] ) {
			$twt_description = $yoast_settings['seo_description'];
		} elseif ( $post_id <= 0 ) {
			$twt_description = get_bloginfo( 'description' );
		} else {
			$twt_description = html_entity_decode( socialsnap_smart_quotes( htmlspecialchars_decode( socialsnap_get_excerpt( $post_id ) ) ) );

			if ( '' == $twt_description ) {
				$twt_description = get_bloginfo( 'description' );
			}
		}

		$twt_description = apply_filters( 'socialsnap_twitter_card_description', $twt_description, $post_id );

		// Twitter Image
		if ( $image ) {
			$twt_image = wp_get_attachment_image_src( $image, 'full' );
		} elseif ( '' !== $yoast_settings['twt_image'] ) {
			$twt_image = array( $yoast_settings['twt_image'] );
		} else {
			$twt_image = $this->get_share_image();
		}

		$twt_image = apply_filters( 'socialsnap_twitter_card_image', $twt_image, $post_id );

		// Twitter Publisher
		$twt_publisher = socialsnap_settings( 'ss_smt_twitter_publisher' );
		if ( '' !== $twt_publisher && isset( $twt_publisher[0] ) && '@' !== $twt_publisher[0] ) {
			$twt_publisher = '@' . $twt_publisher;
		}

		// Twitter Author/Creator
		$twt_creator = get_the_author_meta( 'ss_twitter_author', get_post_field( 'post_author', $post_id ) );
		if ( '' !== $twt_creator && '@' !== $twt_creator[0] ) {
			$twt_creator = '@' . $twt_creator;
		}

		$output  = PHP_EOL . '<meta name="twitter:card" content="' . $twt_card . '">';
		$output .= PHP_EOL . '<meta name="twitter:title" content="' . $twt_title . '">';
		$output .= PHP_EOL . '<meta name="twitter:description" content="' . $twt_description . '">';

		if ( is_array( $twt_image ) && isset( $twt_image[0] ) ) {
			$output .= PHP_EOL . '<meta name="twitter:image:src" content="' . $twt_image[0] . '">';
		}

		if ( $video ) {

			$metadata = wp_get_attachment_metadata( $video );
			$url      = wp_get_attachment_url( $video );

			$output .= PHP_EOL . '<meta name="twitter:player" content="' . esc_url( $url ) . '">';

			if ( isset( $metadata['width'] ) ) {
				$output .= PHP_EOL . '<meta name="twitter:player:width" content="' . esc_attr( $metadata['width'] ) . '">';
			}

			if ( isset( $metadata['height'] ) ) {
				$output .= PHP_EOL . '<meta name="twitter:player:height" content="' . esc_attr( $metadata['height'] ) . '">';
			}
		}

		if ( $twt_publisher ) {
			$output .= PHP_EOL . '<meta name="twitter:site" content="' . esc_attr( $twt_publisher ) . '">';
		}

		if ( $twt_creator ) {
			$output .= PHP_EOL . '<meta name="twitter:creator" content="' . esc_attr( $twt_creator ) . '">';
		}

		echo $output; // phpcs:ignore
	}

	/**
	 * Output Open Graph tags.
	 * Integrates well with Yoast SEO plugin.
	 *
	 * The following tags are added:
	 *
	 * og:type
	 * og:title
	 * og:description
	 * og:image
	 * og:image:width
	 * og:image:height
	 * og:url
	 * og:site_name
	 * og:updated_time
	 * og:price:amount
	 * og:price:currency
	 * fb:app_id
	 * article:author
	 * article:publisher
	 * article:published_time
	 * article:modified_time
	 *
	 * @param String $output
	 * @return String - Open Graph tags.
	 * @since 1.0.0
	 */
	public function open_graph_meta_tags() {

		if ( false === is_singular() && ( function_exists( 'is_shop' ) && false === is_shop() ) ) {
			return;
		}

		global $wp;

		$post_id    = socialsnap_get_current_post_id();
		$is_product = function_exists( 'is_product' ) && is_product();

		$title       = get_post_meta( $post_id, 'ss_smt_title', true );
		$description = get_post_meta( $post_id, 'ss_smt_description', true );
		$image       = get_post_meta( $post_id, 'ss_smt_image', true );
		$video       = get_post_meta( $post_id, 'ss_smt_video', true );
		$type        = $video ? 'video.other' : 'article';

		if ( isset( $wp->did_permalink ) && $wp->did_permalink ) {
			$url = home_url( $wp->request );
		} elseif ( ! empty( $wp->query_string ) ) {
			$url = home_url( '?' . $wp->query_string );
		} else {
			$url = home_url();
		}

		$site_name      = get_bloginfo( 'name' );
		$published_time = get_post_time( 'c' );
		$modified_time  = get_post_modified_time( 'c' );
		$fb_author      = get_the_author_meta( 'ss_facebook_author', get_post_field( 'post_author', $post_id ) );
		$fb_publisher   = socialsnap_settings( 'ss_smt_facebook_profile_url' );
		$fb_app_id      = socialsnap_settings( 'ss_facebook_app_id' );

		// Yoast settings
		$yoast_settings = array(
			'og_title'        => '',
			'og_description'  => '',
			'og_image'        => '',
			'seo_title'       => '',
			'seo_description' => '',
			'fb_app_id'       => '',
			'fb_site'         => '',
			'fb_author_site'  => '',
		);

		if ( defined( 'WPSEO_VERSION' ) ) {
			global $wpseo_og;

			$wpseo_social = get_option( 'wpseo_social' );

			if ( $wpseo_og && $wpseo_og instanceof WPSEO_OpenGraph ) {
				$yoast_settings['og_title']       = $wpseo_og->og_title( false );
				$yoast_settings['og_description'] = $wpseo_og->description( false );
			} else {
				$yoast_settings['og_title']       = get_post_meta( $post_id, '_yoast_wpseo_opengraph-title', true );
				$yoast_settings['og_description'] = get_post_meta( $post_id, '_yoast_wpseo_opengraph-description', true );
			}

			$yoast_settings['og_image']        = get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true );
			$yoast_settings['seo_title']       = get_post_meta( $post_id, '_yoast_wpseo_title', true );
			$yoast_settings['seo_description'] = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
			$yoast_settings['fb_app_id']       = isset( $wpseo_social['fbadminapp'] ) ? $wpseo_social['fbadminapp'] : '';
			$yoast_settings['fb_site']         = isset( $wpseo_social['facebook_site'] ) ? $wpseo_social['facebook_site'] : '';
			$yoast_settings['fb_author_site']  = get_the_author_meta( 'facebook', get_post_field( 'post_author', $post_id ) );

			// Remove Yoast meta tags to avoid duplicate tags
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'type' ), 5 );
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'og_title' ), 10 );
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'description' ), 11 );
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'image' ), 30 );
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'url' ), 12 );
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'site_name' ), 13 );
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'publish_date' ), 19 );
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'site_owner' ), 20 );
			remove_action( 'wpseo_opengraph', array( $wpseo_og, 'article_author_facebook' ), 15 );

			if ( $fb_publisher ) {
				remove_action( 'wpseo_opengraph', array( $wpseo_og, 'website_facebook' ), 14 );
			}

			add_filter( 'wpseo_json_ld_output', '__return_false' );
		}

		// OG Title
		if ( '' !== $title ) { // phpcs:ignore
		} elseif ( '' !== $yoast_settings['og_title'] ) {
			$title = $yoast_settings['og_title'];
		} elseif ( '' !== $yoast_settings['seo_title'] ) {
			$title = $yoast_settings['seo_title'];
		} elseif ( $post_id <= 0 ) {
			$title = get_bloginfo( 'name' );
		} else {
			$title = wp_strip_all_tags( get_the_title( $post_id ) );
		}

		$title = apply_filters( 'socialsnap_og_title', $title, $post_id );

		// OG Description
		if ( '' !== $description ) { // phpcs:ignore
		} elseif ( '' !== $yoast_settings['og_description'] ) {
			$description = $yoast_settings['og_description'];
		} elseif ( '' !== $yoast_settings['seo_description'] ) {
			$description = $yoast_settings['seo_description'];
		} elseif ( $post_id <= 0 ) {
			$description = get_bloginfo( 'description' );
		} else {
			$description = html_entity_decode( socialsnap_smart_quotes( htmlspecialchars_decode( socialsnap_get_excerpt( $post_id ) ) ) );

			if ( '' == $description ) {
				$description = get_bloginfo( 'description' );
			}
		}

		$description = apply_filters( 'socialsnap_og_description', $description, $post_id );

		// OG Image
		if ( $image ) {
			$image = wp_get_attachment_image_src( $image, 'full' );
		} elseif ( '' !== $yoast_settings['og_image'] ) {
			$image = array( $yoast_settings['og_image'] );
		} else {
			$image = $this->get_share_image();
		}

		$image = apply_filters( 'socialsnap_og_image', $image, $post_id );

		// Author Facebook URL
		if ( ! $fb_author && '' !== $yoast_settings['fb_author_site'] ) {
			$fb_author = $yoast_settings['fb_author_site'];
		}

		// Facebook Publisher URL
		if ( ! $fb_publisher && '' !== $yoast_settings['fb_site'] ) {
			$fb_publisher = $yoast_settings['fb_site'];
		}

		// Facebook App ID
		if ( '' != $fb_app_id ) { // phpcs:ignore
		} elseif ( '' !== $yoast_settings['fb_app_id'] ) {
			$fb_app_id = $yoast_settings['fb_app_id'];
		} else {
			$fb_app_id = '1923155921341058';
		}

		$output  = PHP_EOL . '<meta property="og:type" content="' . esc_attr( $type ) . '">';
		$output .= PHP_EOL . '<meta property="og:title" content="' . esc_attr( $title ) . '">';
		$output .= PHP_EOL . '<meta property="og:description" content="' . esc_attr( $description ) . '">';
		$output .= PHP_EOL . '<meta property="og:url" content="' . esc_url( user_trailingslashit( $url ) ) . '">';
		$output .= PHP_EOL . '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">';
		$output .= PHP_EOL . '<meta property="og:updated_time" content="' . esc_attr( $modified_time ) . '">';

		if ( $fb_publisher ) {
			$output .= PHP_EOL . '<meta property="article:publisher" content="' . esc_attr( $fb_publisher ) . '">';
		}

		if ( $fb_author ) {
			$output .= PHP_EOL . '<meta property="article:author" content="' . esc_attr( $fb_author ) . '">';
		}

		if ( is_array( $image ) && isset( $image[0] ) ) {
			$output .= PHP_EOL . '<meta property="og:image" content="' . esc_url( $image[0] ) . '">';

			if ( isset( $image[1] ) ) {
				$output .= PHP_EOL . '<meta property="og:image:width" content="' . esc_attr( $image[1] ) . '">';
			}

			if ( isset( $image[2] ) ) {
				$output .= PHP_EOL . '<meta property="og:image:height" content="' . esc_attr( $image[2] ) . '">';
			}
		}

		if ( $video ) {

			$metadata = wp_get_attachment_metadata( $video );
			$url      = wp_get_attachment_url( $video );

			$output .= PHP_EOL . '<meta property="og:video:url" content="' . esc_url( $url ) . '">';

			if ( isset( $metadata['width'] ) ) {
				$output .= PHP_EOL . '<meta property="og:video:width" content="' . esc_attr( $metadata['width'] ) . '">';
			}

			if ( isset( $metadata['height'] ) ) {
				$output .= PHP_EOL . '<meta property="og:video:height" content="' . esc_attr( $metadata['height'] ) . '">';
			}

			if ( isset( $metadata['mime_type'] ) ) {
				$output .= PHP_EOL . '<meta property="og:video:type" content="' . esc_attr( $metadata['mime_type'] ) . '">';
			}
		}

		if ( 'video.other' !== $type ) {
			$output .= PHP_EOL . '<meta property="article:published_time" content="' . esc_attr( $published_time ) . '">';
			$output .= PHP_EOL . '<meta property="article:modified_time" content="' . esc_attr( $modified_time ) . '">';
		}

		if ( $fb_app_id ) {
			$output .= PHP_EOL . '<meta property="fb:app_id" content="' . esc_attr( $fb_app_id ) . '">';
		}

		echo $output; // phpcs:ignore
	}

	/**
	 * Filter for the namespace, adding the OpenGraph namespace.
	 *
	 * @link https://developers.facebook.com/docs/web/tutorials/scrumptious/open-graph-object/
	 *
	 * @param string $input The input namespace string.
	 *
	 * @return string
	 */
	public function opengraph_namespace( $input ) {
		$namespaces = array(
			'og: http://ogp.me/ns#',
			'fb: http://ogp.me/ns/fb#',
		);

		/**
		 * Allow for adding additional namespaces to the <html> prefix attributes.
		 *
		 * @param array $namespaces Currently registered namespaces which are to be
		 *                          added to the prefix attribute.
		 *                          Namespaces are strings and have the following syntax:
		 *                          ns: http://url.to.namespace/definition
		 */
		$namespaces       = apply_filters( 'socialsnap_html_namespaces', $namespaces );
		$namespace_string = implode( ' ', array_unique( $namespaces ) );

		if ( strpos( $input, ' prefix=' ) !== false ) {
			$regex   = '`prefix=([\'"])(.+?)\1`';
			$replace = 'prefix="$2 ' . $namespace_string . '"';
			$input   = preg_replace( $regex, $replace, $input );
		} else {
			$input .= ' prefix="' . $namespace_string . '"';
		}

		return $input;
	}

	/**
	 * Get Image which will be visible on share
	 *
	 * @since 1.0.0
	 */
	private function get_share_image() {

		if ( is_singular() || ( function_exists( 'is_shop' ) && is_shop() ) ) {

			$post_id = socialsnap_get_current_post_id();

			// Attachment page, must use attachment image
			if ( is_attachment() ) {
				return wp_get_attachment_image_src( $post_id, 'full' );
			}

			// Try to fetch featured image
			if ( has_post_thumbnail() ) {
				return wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
			}
		}

		// Return default image from settings panel
		$default_image = socialsnap_settings( 'ss_smt_default_image' );
		if ( is_array( $default_image ) && isset( $default_image['id'] ) ) {
			return wp_get_attachment_image_src( $default_image['id'], 'full' );
		}
	}

	/**
	 * Register Social Meta settings fields.
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function add_settings_config( $settings ) {

		if ( ! isset( $settings['ss_meta_tags'] ) ) {
			return $settings;
		}

		$settings['ss_meta_tags']['fields'] = array(
			'ss_smt_enable'               => array(
				'id'      => 'ss_smt_enable',
				'name'    => esc_html__( 'Enable Social Meta', 'socialsnap' ),
				'type'    => 'toggle',
				'default' => true,
			),
			'ss_smt_default_image'        => array(
				'id'         => 'ss_smt_default_image',
				'name'       => esc_html__( 'Default Share Image', 'socialsnap' ),
				'desc'       => __( 'Upload an image that represents your website when shared on social networks. This field is used only if Featured Image or post specific image is not set. Recommended size is 1,200px by 628px.', 'socialsnap' ),
				'type'       => 'upload',
				'default'    => '',
				'dependency' => array(
					'element' => 'ss_smt_enable',
					'value'   => 'true',
				),
			),
			'ss_smt_twitter_card_type'    => array(
				'id'         => 'ss_smt_twitter_card_type',
				'name'       => esc_html__( 'Twitter Card Layout', 'socialsnap' ),
				'desc'       => __( 'The Summary Card is designed to give the reader a preview of the content before clicking through to your website.', 'socialsnap' ),
				'type'       => 'dropdown',
				'options'    => array(
					'summary'             => __( 'Summary', 'socialsnap' ),
					'summary_large_image' => __( 'Summary with Large Image', 'socialsnap' ),
				),
				'default'    => 'summary',
				'dependency' => array(
					'element' => 'ss_smt_enable',
					'value'   => 'true',
				),
			),
			'ss_smt_twitter_publisher'    => array(
				'id'          => 'ss_smt_twitter_publisher',
				'name'        => esc_html__( 'Website Twitter Username', 'socialsnap' ),
				'desc'        => __( 'The Twitter @username the card should be attributed to.', 'socialsnap' ),
				'type'        => 'text',
				'placeholder' => '@username',
				'dependency'  => array(
					'element' => 'ss_smt_enable',
					'value'   => 'true',
				),
			),
			'ss_smt_facebook_profile_url' => array(
				'id'          => 'ss_smt_facebook_profile_url',
				'name'        => esc_html__( 'Website Facebook Profile URL', 'socialsnap' ),
				'desc'        => __( 'Enter your Facebook Profile/Page link.', 'socialsnap' ),
				'type'        => 'text',
				'placeholder' => 'http://www.facebook.com/profile',
				'dependency'  => array(
					'element' => 'ss_smt_enable',
					'value'   => 'true',
				),
			),
			'ss_ie_info'                  => array(
				'id'         => 'ss_ie_info',
				'name'       => esc_html__( 'Note:', 'socialsnap' ),
				/* translators: %s is link to profile. */
				'desc'       => sprintf( __( 'Post authors can specify their Twitter and Facebook info in %s settings.', 'socialsnap' ), '<a href="' . esc_url( get_edit_user_link() ) . '#ss-user-info">' . esc_html__( 'Profile', 'socialsnap' ) . '</a>' ),
				'type'       => 'note',
				'dependency' => array(
					'element' => 'ss_smt_enable',
					'value'   => 'true',
				),
			),
		);

		return $settings;
	}


	/**
	 * Register Social Meta metabox fields.
	 *
	 * @since 1.0.0
	 * @param array $metaboxes
	 * @return array
	 */
	public function add_metaboxes_config( $metaboxes ) {

		if ( ! isset( $metaboxes['ss-socialsnap-main'] ) ) {
			return $metaboxes;
		}

		$smt_metabox = array(
			'ss_smt_title'       => array(
				'id'        => 'ss_smt_title',
				'name'      => esc_html__( 'Social Media Title', 'socialsnap' ),
				'type'      => 'editor_text',
				'desc'      => __( 'A title which will be displayed when this post is shared on networks that use Open Graph, such as Facebook, LinkedIn and others.', 'socialsnap' ),
				'countchar' => 60,
			),

			'ss_smt_description' => array(
				'id'        => 'ss_smt_description',
				'name'      => esc_html__( 'Social Media Description', 'socialsnap' ),
				'type'      => 'editor_textarea',
				'desc'      => __( 'A description which will be displayed when this post is shared on networks that use Open Graph, such as Facebook, LinkedIn and others.', 'socialsnap' ),
				'countchar' => 160,
			),

			'ss_smt_image'       => array(
				'id'        => 'ss_smt_image',
				'name'      => esc_html__( 'Social Media Image', 'socialsnap' ),
				'type'      => 'editor_upload',
				'desc'      => __( 'Upload an image which will be displayed when this post is shared on networks that use Open Graph, such as Facebook, LinkedIn and others.', 'socialsnap' ),
				'extradesc' => __( 'Recommended image dimension is 1,200px by 628px.', 'socialsnap' ),
			),

			'ss_smt_video'       => array(
				'id'             => 'ss_smt_video',
				'name'           => esc_html__( 'Social Media Video', 'socialsnap' ),
				'type'           => 'editor_upload',
				'desc'           => esc_html__( 'Upload a video which will be displayed when this post is shared on networks. Note that your video is not guaranteed to play in-line based on a variety of factors.', 'socialsnap' ),
				'extradesc'      => esc_html__( 'Recommended video extension is mp4.', 'socialsnap' ),
				'allowed_type'   => array( 'video' ),
				'button_caption' => esc_html__( 'Upload Video', 'socialsnap' ),
			),
		);

		$metaboxes['ss-socialsnap-main']['options']['ss-smt-metabox'] = array(
			'title'   => esc_html__( 'Social Meta', 'socialsnap' ),
			'id'      => 'ss-smt-metabox',
			'icon'    => 'api',
			'options' => $smt_metabox,
		);

		return $metaboxes;
	}
}

SocialSnap_Meta_Tags::instance();
