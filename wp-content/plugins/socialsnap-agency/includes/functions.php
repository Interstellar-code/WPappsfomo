<?php
/**
 * Contains various functions that may be potentially used throughout
 * the Social Snap plugin.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */

/**
 * Get the value of a specific Social Snap setting.
 *
 * @since 1.0.0
 * @return mixed
 */
function socialsnap_settings( $key, $default = false, $options = false, $option_slug = SOCIALSNAP_SETTINGS ) {

	if ( false === $options ) {
		$options = socialsnap()->settings;
	}

	$value = is_array( $options ) && ! empty( $options[ $key ] ) ? $options[ $key ] : $default;

	return 'on' === $value ? true : $value;
}

/**
 * Update of a specific Social Snap setting.
 *
 * @since 1.0.0
 */
function update_socialsnap_settings( $key, $value, $option = SOCIALSNAP_SETTINGS ) {

	$options         = get_option( $option, false );
	$options[ $key ] = $value;
	update_option( $option, $options );
}

/**
 * Enqueue frontend assets
 *
 * @since 1.0.0
 */
function socialsnap_enqueue_assets() {

	// Don't enqueue on AMP pages.
	if ( socialsnap_is_amp_page() ) {
		return;
	}

	// Enqueue frontend styles
	wp_enqueue_style(
		'socialsnap-styles',
		SOCIALSNAP_PLUGIN_URL . 'assets/css/socialsnap.css',
		null,
		SOCIALSNAP_VERSION
	);

	if ( ! is_admin() ) {

		// Enqueue frontend scripts
		wp_register_script(
			'socialsnap-js',
			SOCIALSNAP_PLUGIN_URL . 'assets/js/socialsnap.js',
			array( 'jquery' ),
			SOCIALSNAP_VERSION,
			true
		);

		wp_enqueue_script( 'socialsnap-js' );

		// Localize variables to be used in plugin JavaScript files.
		$strings = array(
			'ajaxurl'         => admin_url( 'admin-ajax.php' ),
			'on_media_width'  => socialsnap_settings( 'ss_ss_on_media_minwidth' ),
			'on_media_height' => socialsnap_settings( 'ss_ss_on_media_minheight' ),
			'nonce'           => wp_create_nonce( 'socialsnap-nonce' ),
			'post_id'         => socialsnap_get_current_post_id(),
		);

		$strings = apply_filters( 'socialsnap_js_localized_strings', $strings );

		wp_localize_script(
			'socialsnap-js',
			'socialsnap_script',
			$strings
		);
	}

}
// add_action( 'wp_enqueue_scripts', 'socialsnap_enqueue_assets' );

/**
 * Build Share URL.
 *
 * @since 1.0.0
 * @param string $network, Social network
 * @param array  $args, Additional arguments to specify the share URL.
 * @return string, share URL of the current page
 */
function socialsnap_get_share_url( $network, $args = array() ) {

	// Default Args
	$defaults = array(
		'post_id'   => '',
		'image'     => '',
		'permalink' => '',
		'title'     => '',
		'location'  => '',
	);

	$args = wp_parse_args( $args, $defaults );

	if ( '' == $args['post_id'] ) {
		$args['post_id'] = socialsnap_get_current_post_id();
	}

	if ( '' === $args['permalink'] ) {
		if ( class_exists( 'WooCommerce' ) && is_checkout() || $args['post_id'] <= 0 ) {
			$args['permalink'] = socialsnap_get_shared_permalink(
				array(
					'permalink' => get_bloginfo( 'url' ),
					'network'   => $network,
				)
			);
		} else {
			$args['permalink'] = socialsnap_get_shared_permalink(
				array(
					'post_id' => $args['post_id'],
					'network' => $network,
				)
			);
		}
	}

	$encoded_permalink = rawurlencode( $args['permalink'] );

	// Title.
	if ( empty( $args['title'] ) ) {
		$args['title'] = socialsnap_get_shared_title(
			array(
				'post_id'  => $args['post_id'],
				'network'  => $network,
				'location' => $args['location'],
			)
		);
	}

	$encoded_title = rawurlencode( wp_strip_all_tags( html_entity_decode( $args['title'], ENT_QUOTES, 'UTF-8' ) ) );

	$url = '#';

	switch ( $network ) {

		case 'twitter':
			$related = apply_filters( 'socialsnap_twitter_related_users', false );
			$via     = apply_filters( 'socialsnap_twitter_via_username', socialsnap_settings( 'ss_twitter_username' ) );

			$custom_twt = $args['post_id'] > 0 ? get_post_meta( $args['post_id'], 'ss_ss_custom_tweet', true ) : false;

			$args['title'] = $custom_twt ? $custom_twt : $args['title'];
			$args['title'] = wp_strip_all_tags( html_entity_decode( $args['title'], ENT_QUOTES, 'UTF-8' ) );

			$url = socialsnap_twitter_share_url(
				$args['title'],
				$args['permalink'],
				$via,
				$related
			);

			break;

		case 'facebook':
			$url = add_query_arg(
				array(
					't' => $encoded_title,
					'u' => $encoded_permalink,
				),
				'https://www.facebook.com/sharer.php'
			);
			break;

		case 'linkedin':
			$url = add_query_arg(
				array(
					'title' => $encoded_title,
					'url'   => $encoded_permalink,
					'mini'  => 'true',
				),
				'https://www.linkedin.com/shareArticle'
			);
			break;

		case 'envelope':
			$url = add_query_arg(
				array(
					'body'    => $encoded_permalink,
					'subject' => $encoded_title,
				),
				'mailto:'
			);
			break;

		case 'mix':
			$url = add_query_arg(
				array(
					'url' => $encoded_permalink,
				),
				'https://mix.com/add'
			);
			break;

		case 'pinterest':
			$pinterest_image = apply_filters( 'socialsnap_share_pinterest_image', $args['image'], $args['post_id'], $args['location'] );

			if ( $pinterest_image ) {

				$url = add_query_arg(
					array(
						'url'         => $encoded_permalink,
						'media'       => $pinterest_image,
						'description' => $encoded_title,
					),
					'https://pinterest.com/pin/create/button/'
				);
			}

			break;

		case 'copy':
			$url = $args['permalink'];
			break;

		default:
			break;
	}

	$args = array(
		'image'     => $args['image'],
		'post_id'   => $args['post_id'],
		'permalink' => $args['permalink'],
		'title'     => $args['title'],
		'location'  => $args['location'],
	);

	$url = apply_filters( 'socialsnap_social_share_url', $url, $network, $args );

	return $url;
}

/**
 * Get permalink that's going to be shared on a social network.
 *
 * @since 1.0.7
 * @param $network, Social network
 * @return string, share URL of the current page
 */
function socialsnap_get_shared_permalink( $args = array() ) {

	$defaults = array(
		'post_id'   => '',
		'permalink' => '',
		'network'   => '',
	);

	$args = wp_parse_args( $args, $defaults );

	if ( '' === $args['permalink'] ) {
		$args['permalink'] = socialsnap_get_current_url( $args['post_id'] );
	}

	return apply_filters( 'socialsnap_social_share_permalink', $args['permalink'], $args['network'] );
}

/**
 * Get title/message that's going to be shared on a social network.
 *
 * @since 1.0.7
 * @param $args
 * @return string, share URL of the current page
 */
function socialsnap_get_shared_title( $args = array() ) {

	$defaults = array(
		'post_id'  => '',
		'title'    => '',
		'network'  => '',
		'location' => '',
	);

	$args = wp_parse_args( $args, $defaults );

	if ( empty( $args['title'] ) ) {

		if ( ! empty( $args['post_id'] ) ) {
			$args['title'] = socialsnap_get_current_page_title( $args['post_id'] );
		} else {
			$args['title'] = socialsnap_get_current_page_title();
		}
	}

	return apply_filters( 'socialsnap_social_share_title', $args['title'], $args['network'], $args['post_id'], $args['location'] );
}

/**
 * Get current page title.
 *
 * @since 1.0.0
 * @return string, Title of the current page
 */
function socialsnap_get_current_page_title( $post_id = 0 ) {

	$title = get_bloginfo( 'name' );

	if ( intval( $post_id ) > 0 || is_singular() ) {
		$post_id = $post_id ? $post_id : get_the_ID();
		$title   = get_the_title( $post_id );
	} elseif ( is_archive() ) {
		$title = get_the_archive_title();
	}

	return apply_filters( 'socialsnap_current_page_title', $title );
}

/**
 * Get current page url.
 *
 * @since 1.0.0
 * @return string, current page url
 */
function socialsnap_get_current_url( $post_id = '' ) {

	if ( intval( $post_id ) <= 0 ) {
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			// build the URL in the address bar.
			$permalink  = is_ssl() ? 'https://' : 'http://';
			$permalink .= sanitize_text_field( $_SERVER['HTTP_HOST'] );
			$permalink .= sanitize_text_field( $_SERVER['REQUEST_URI'] );
		} else {
			global $wp;
			$permalink = esc_url( add_query_arg( $wp->query_string, '', esc_url( home_url( '/' ) ) ) );
		}
	} else {
		$permalink = get_permalink( $post_id );
	}

	return apply_filters( 'socialsnap_permalink', $permalink, $post_id );
}

/**
 * Get current page/post ID.
 *
 * @since 1.0.0
 * @return integer, current page ID
 */
function socialsnap_get_current_post_id( $permalink = null ) {

	if ( isset( $permalink ) ) {

		if ( in_array( $permalink, array( home_url(), home_url( '/' ) ) ) ) {

			$posts_page = get_option( 'page_for_posts' );
			$front_page = get_option( 'page_on_front' );

			if ( $front_page ) {
				$post_id = $front_page;
			} elseif ( $posts_page ) {
				$post_id = $posts_page;
			} else {
				$post_id = -1;
			}
		} elseif ( function_exists( 'wc_get_page_id' ) && in_array( $permalink, array( get_post_type_archive_link( 'product' ), get_permalink( wc_get_page_id( 'shop' ) ) ) ) ) {
			$post_id = wc_get_page_id( 'shop' );
		} else {
			$post_id = url_to_postid( $permalink );
		}
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$post_id = wc_get_page_id( 'shop' );
	} elseif ( is_singular() ) {
		$post_id = get_the_ID();
	} elseif ( socialsnap_is_homepage() ) {

		$posts_page = get_option( 'page_for_posts' );
		$front_page = get_option( 'page_on_front' );

		if ( is_home() && $posts_page ) {
			$post_id = $posts_page;
		} elseif ( is_front_page() && $front_page ) {
			$post_id = $front_page;
		} else {
			$post_id = -1;
		}
	} else {
		$post_id = 0;
	}

	return apply_filters( 'socialsnap_current_post_id', $post_id );
}

/**
 * Filter Twitter username.
 *
 * @since 1.0.0
 * @param $username, Twitter username that needs to be filtered.
 * @return string, filtered username without @ and tags.
 */
function socialsnap_filter_twitter_username( $username ) {
	return str_replace( array( '@', ' ' ), '', strip_tags( stripslashes( $username ) ) );
}
add_filter( 'socialsnap_sanitize_username', 'socialsnap_filter_twitter_username', 10, 1 );

/**
 * Twitter Share URL.
 *
 * @since 1.0.0
 * @param
 * @return string, Twitter share URL
 */
function socialsnap_twitter_share_url( $text, $url, $via = false, $related = false ) {

	$url = add_query_arg(
		array(
			'text' => urlencode( html_entity_decode( $text, ENT_COMPAT, 'UTF-8' ) ),
			'url'  => urlencode( $url ),
		),
		'https://twitter.com/intent/tweet'
	);

	if ( $via ) {
		$url = add_query_arg(
			array(
				'via' => apply_filters( 'socialsnap_sanitize_username', $via ),
			),
			$url
		);
	}

	if ( $related ) {
		$url = add_query_arg(
			array(
				'related' => urlencode( $related ),
			),
			$url
		);
	}

	return apply_filters( 'socialsnap_twitter_share_url', $url );
}

/**
 * Array of social share networks.
 *
 * @since 1.0.0
 * @return array, Social networks
 */
function socialsnap_get_social_share_networks() {

	$networks = array(
		'facebook' => 'Facebook',
		'twitter'  => 'X (Twitter)',
		'linkedin' => 'LinkedIn',
		'mix'      => 'Mix',
		'envelope' => 'Email',
		'print'    => __( 'Print', 'socialsnap' ),
		'copy'     => __( 'Copy Link', 'socialsnap' ),
	);

	return apply_filters( 'socialsnap_social_share_networks', $networks );
}

/**
 * Array of social follow networks.
 *
 * @since 1.0.0
 * @return array, Social networks
 */
function socialsnap_get_social_follow_networks() {

	$networks = array(
		'facebook'  => 'Facebook',
		'twitter'   => 'X (Twitter)',
		'pinterest' => 'Pinterest',
		'instagram' => 'Instagram',
		'tumblr'    => 'Tumblr',
		'mix'       => 'Mix',
	);

	return apply_filters( 'socialsnap_social_follow_networks', $networks );
}

/**
 * Array of share social network colors.
 *
 * @since 1.0.0
 * @return array, Social networks
 */
function socialsnap_get_network_color( $network ) {

	$colors = array(
		'facebook'  => '#1877f2',
		'twitter'   => '#000000',
		'ctt'       => '#1da1f2',
		'google'    => '#ea4335',
		'envelope'  => '#323b43',
		'pinterest' => '#bd081c',
		'linkedin'  => '#2867b2',
		'tumblr'    => '#36465d',
		'copy'      => '#323b43',
		'other'     => '#323b43',
		'instagram' => '#c13584',
		'mix'       => '#ff8226',
	);

	$colors = apply_filters( 'socialsnap_network_colors', $colors );

	return isset( $colors[ $network ] ) ? $colors[ $network ] : '#333';
}

/**
 * Get Network name by network ID.
 *
 * @since 1.0.0
 * @return array, Social networks
 */
function socialsnap_get_network_name( $network ) {

	$networks = array();

	$networks = array_merge(
		socialsnap_get_social_share_networks(),
		socialsnap_get_social_follow_networks()
	);

	$networks = array_merge(
		$networks,
		array(
			'google' => 'Google',
			'other'  => __( 'Other', 'socialsnap' ),
			'ctt'    => __( 'Click to Tweet', 'socialsnap' ),
		)
	);

	if ( isset( $networks[ $network ] ) ) {
		return $networks[ $network ];
	}

	return false;
}

/**
 * Array of share social networks for mobile only.
 *
 * @since 1.0.0
 * @return array, Social networks
 */
function socialsnap_get_mobile_only_social_share_networks() {

	$networks = array();

	return apply_filters( 'socialsnap_social_share_networks_mobile_only', $networks );
}

/**
 * Array of share social networks that have share count API support.
 *
 * @since 1.0.0
 * @return array, Social networks
 */
function socialsnap_get_social_share_networks_with_api() {

	$networks = array(
		'facebook',
	);

	return apply_filters( 'socialsnap_social_share_networks_with_api', $networks );
}

/**
 * Array of share social networks that have share count API but don't require authentication.
 *
 * @since 1.0.0
 * @return array, Social networks
 */
function socialsnap_social_follow_networks_automatic() {

	$networks = array(
		'pinterest',
	);

	return apply_filters( 'socialsnap_social_follow_networks_automatic', $networks );
}

/**
 * Array of follow social networks that have share count API support.
 *
 * @since 1.0.0
 * @return array, Social networks
 */
function socialsnap_social_follow_networks_with_api() {

	$networks = array();

	return apply_filters( 'socialsnap_social_follow_networks_with_api', $networks );
}

/**
 * Insert into array before specified key.
 *
 * @since 1.0.0
 * @return array, array with inserted $new value
 */
function socialsnap_array_insert( $array, $pairs, $key, $position = 'after' ) {

	$key_pos = array_search( $key, array_keys( $array ) );

	if ( 'after' == $position ) {
		$key_pos++;
	}

	if ( false !== $key_pos ) {
		$result = array_slice( $array, 0, $key_pos );
		$result = array_merge( $result, $pairs );
		$result = array_merge( $result, array_slice( $array, $key_pos ) );
	} else {
		$result = array_merge( $array, $pairs );
	}

	return $result;
}

/**
 * Filter function to remove premium social networks if Pro was disabled.
 *
 * @since 1.0.0
 * @return array, array of allowed social networks
 */
function socialsnap_verify_social_share_networks( $networks ) {

	$allowed_networks = array_keys( socialsnap_get_social_share_networks() );

	if ( is_array( $networks ) && ! empty( $networks ) ) {

		unset( $networks['order'] );

		foreach ( $networks as $id => $settings ) {
			if ( ! in_array( $id, $allowed_networks, true ) ) {
				unset( $networks[ $id ] );
			}
		}

		$networks['order'] = implode( ';', array_keys( $networks ) );
	}

	return $networks;
}
add_filter( 'socialsnap_filter_social_share_networks', 'socialsnap_verify_social_share_networks', 10, 1 );

/**
 * Filter function to remove premium social networks if Pro was disabled.
 *
 * @since 1.0.0
 * @return array, array of allowed social networks
 */
function socialsnap_verify_social_follow_networks( $networks ) {

	$allowed_networks = array_keys( socialsnap_get_social_follow_networks() );

	if ( is_array( $networks ) && ! empty( $networks ) ) {

		unset( $networks['order'] );

		foreach ( $networks as $id => $settings ) {
			if ( ! in_array( $id, $allowed_networks, true ) ) {
				unset( $networks[ $id ] );
			}
		}

		$networks['order'] = implode( ';', array_keys( $networks ) );
	}

	return $networks;
}
add_filter( 'socialsnap_filter_social_follow_networks', 'socialsnap_verify_social_follow_networks' );

/**
 * Return an array of registered post types slugs and names.
 *
 * @since 1.0.0
 * @return array, array of post types
 */
function socialsnap_get_post_types() {

	$post_types = get_post_types(
		array(
			'public'   => true,
			'_builtin' => false,
		)
	);

	// The array we wish to return
	$return_post_types = array();

	foreach ( $post_types as $post_type ) {
		$post_type_object                = get_post_type_object( $post_type );
		$return_post_types[ $post_type ] = $post_type_object->labels->singular_name;
	}

	return apply_filters( 'socialsnap_get_post_types', $return_post_types );
}

/**
 * Return an array of registered taxonomy slugs and names.
 *
 * @since 1.0.0
 * @return array, array of post types
 */
function socialsnap_get_taxonomies() {

	global $wp_taxonomies;

	$taxonomies = array(
		'category' => esc_html__( 'Categories', 'socialsnap' ),
		'post_tag' => esc_html__( 'Tags', 'socialsnap' ),
	);

	$taxonomies = $taxonomies + wp_filter_object_list(
		$wp_taxonomies,
		array(
			'_builtin' => false,
			'public'   => true,
			'show_ui'  => true,
		),
		'and',
		'label'
	);

	return apply_filters( 'socialsnap_get_taxonomies', $taxonomies );
}

/**
 * Return an array of available social share positions.
 *
 * @since 1.0.0
 * @return array, array of post types
 */
function socialsnap_get_social_share_positions() {

	$positions = array(
		'sidebar',
		'inline_content',
		'on_media',
	);

	return apply_filters( 'socialsnap_social_share_positions', $positions );
}

/**
 * Check if current page is homepage.
 *
 * @since 1.0.0
 * @return boolean
 */
function socialsnap_is_homepage() {
	return is_home() || is_front_page();
}

/**
 * Get post excert for specific post.
 *
 * @since 1.0.0
 * @return boolean
 */
function socialsnap_get_excerpt( $post_id = '' ) {

	if ( '' === $post_id ) {
		$post_id = socialsnap_get_current_post_id();
	}

	$excerpt        = '';
	$excerpt_length = apply_filters( 'excerpt_length', 100 );
	$excerpt_more   = apply_filters( 'excerpt_more', ' ', '...' );
	$the_post       = get_post( $post_id );

	// User defined excerpt
	if ( has_excerpt( $post_id ) ) {
		$excerpt = $the_post->post_excerpt;
	} else {
		$excerpt = $the_post->post_content;
	}

	// Remove script and style tags
	$excerpt = preg_replace( '/(<script[^>]*>.+?<\/script>|<style[^>]*>.+?<\/style>)/s', '', $excerpt );
	$excerpt = strip_tags( strip_shortcodes( $excerpt ) );
	$excerpt = preg_replace( '/\[[^\]]+\]/', '', $excerpt );
	$excerpt = str_replace( ']]>', ']]&gt;', $excerpt );
	$excerpt = strip_tags( $excerpt );

	$words = preg_split( "/[\n\r\t ]+/", $excerpt, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY );

	if ( is_array( $words ) && count( $words ) > $excerpt_length ) {
		array_pop( $words );
		$excerpt = implode( ' ', $words );
	}

	$excerpt = preg_replace( "/\r|\n/", '', $excerpt );

	return $excerpt;
}

/**
 * Convert curly quotes to straight quotes.
 *
 * @since  1.0.0
 * @param  string $content A string of text to be filtered
 * @return string $content The modified string of text
 */
function socialsnap_smart_quotes( $content ) {
	$content = str_replace( '"', '\'', $content );
	$content = str_replace( '&#8220;', '\'', $content );
	$content = str_replace( '&#8221;', '\'', $content );
	$content = str_replace( '&#8216;', '\'', $content );
	$content = str_replace( '&#8217;', '\'', $content );
	return $content;
}

/**
 * Return array of Post Type IDs.
 *
 * @since  1.0.0
 * @param  array $args custom args for get_post_types function
 * @return array $post_types, array of all registered public post types IDs.
 */
function socialsnap_get_post_types_ids( $args = array() ) {

	if ( empty( $array ) ) {
		$post_types = array_merge(
			get_post_types(
				array(
					'public'   => true,
					'_builtin' => false,
				)
			),
			get_post_types(
				array(
					'public'   => true,
					'_builtin' => true,
				)
			)
		);
	} else {
		$post_types = get_post_types( $args );
	}

	return apply_filters( 'socialsnap_post_types_ids', $post_types );
}

/**
 * Get user IP.
 *
 * @since 1.0.0
 * @return string
 */
function socialsnap_get_ip() {

	$ip = '127.0.0.1';

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
	}
	// Fix potential CSV returned from $_SERVER variables
	$ip_array = array_map( 'trim', explode( ',', $ip ) );
	return $ip_array[0];
}

/**
 * Format number to display rounded thouhsands.
 *
 * @since 1.0.0
 * @return string
 */
function socialsnap_format_number( $number ) {

	if ( ! isset( $number ) ) {
		return 0;
	}

	$number = intval( $number );

	if ( $number < 1000 ) {
		$number = number_format( $number, 0, '.', ',' );
	} elseif ( $number < 1000000 ) {
		$number /= 1000;
		$number  = number_format( $number, 1, '.', ',' ) . 'K';
	} else {
		$number /= 1000000;
		$number  = number_format( $number, 1, '.', ',' ) . 'M';
	}

	$number = str_replace( '.0', '', $number );

	return $number;
}

/**
 * Add a row to Social Snap Statistics DB if no similar rows exist.
 *
 * @since 1.0.0
 * @return string
 */
function socialsnap_add_to_stats_db( $data ) {

	$network = isset( $data['network'] ) ? sanitize_text_field( $data['network'] ) : '';
	$post_id = isset( $data['post_id'] ) ? intval( sanitize_text_field( $data['post_id'] ) ) : '';
	$type    = isset( $data['type'] ) ? sanitize_text_field( $data['type'] ) : '';
	$user_ip = isset( $data['ip_address'] ) ? $data['ip_address'] : socialsnap_get_ip();
	$count   = isset( $data['count'] ) ? intval( $data['count'] ) : 0;

	$stats = array(
		'post_id' => $post_id,
		'network' => $network,
		'type'    => $type,
	);

	// User IP is not required fro API Counts, but count parameter should be checked.
	if ( false === strpos( $stats['type'], 'api' ) ) {
		$stats['ip_address'] = $user_ip;
	} else {
		$stats['count'] = $count;
	}

	// Click to tweet requires URL to make a unique statistic row.
	if ( 'ctt' == $stats['type'] || false !== strpos( $stats['type'], 'api' ) ) {
		$stats['url'] = isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : '';
	}

	// Remove the IP Address if GDPR compliancy is enabled.
	if ( socialsnap_settings( 'ss_remove_user_data' ) ) {

		// Remove the IP Address
		$stats['ip_address'] = '';

	} else {

		// Only check if we have a record if we have IP set.
		$results = socialsnap()->stats->get_stats( $stats );

		// If this row already exists, do nothing.
		if ( $results ) {
			return null;
		}
	}

	$stats['location']  = isset( $data['location'] ) ? sanitize_text_field( $data['location'] ) : '';
	$stats['url']       = isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : '';
	$stats['post_type'] = get_post_type( $post_id );
	$stats['count']     = $count;

	// Add row to table
	socialsnap()->stats->add( $stats );

	if ( false !== strpos( $type, 'api' ) ) {
		return;
	}

	$stats = array(
		'post_id' => $post_id,
		'network' => $network,
		'type'    => $type,
	);

	return socialsnap()->stats->get_stats( $stats, true );
}

/**
 * Check if we're on Block Editor.
 *
 * @since 1.0.0
 * @return string
 */
function socialsnap_is_block_editor( $post_id = null ) {
	global $pagenow;

	// Check if we're on WP 5.0+.
	if ( function_exists( 'use_block_editor_for_post_type' ) ) {

		// Return if post type is not compatible with the block editor.
		if ( ! use_block_editor_for_post_type( get_post_type( $post_id ) ) ) {
			return false;
		}

		// Check if Classic Editor plugin is installed.
		if ( class_exists( 'Classic_Editor' ) ) {

			if ( 'post-new.php' === $pagenow ) {
				return 'block' === get_option( 'classic-editor-replace' );
			}

			// Check post editor.
			if ( $post_id ) {

				if ( isset( $_GET['classic-editor__forget'] ) ) {
					return ! isset( $_GET['classic-editor'] );
				} else {
					return 'block-editor' === get_post_meta( $post_id, 'classic-editor-remember', true );
				}
			}
		}
	} else {

		// Check if Gutenberg plugin is installed & activated.
		return ( defined( 'GUTENBERG_VERSION' ) || defined( 'GUTENBERG_DEVELOPMENT_MODE' ) ) && in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) && ! isset( $_REQUEST['classic-editor'] );
	}

	return true;
}

/**
 * Check if we're on Block Editor (Gutenberg). Compatibility function.
 *
 * @since 1.0.0
 * @return string
 */
function socialsnap_is_gutenberg( $post_id = null ) {
	return socialsnap_is_block_editor( $post_id );
}

/**
 * Upgrade button on settings panel.
 *
 * @since 1.0.0
 */
function socialsnap_settings_upgrade_button( $title = '' ) {

	$class = 'ss-settings-upgrade-feature';
	$atts  = '';

	if ( ! empty( $title ) ) {
		echo '<i class="ss-tooltip ss-question-mark" data-title="' . esc_attr( $title ) . '">' . socialsnap()->icons->get_svg( 'info' ) . '</i>'; // phpcs:ignore
	}

	?>

	<a href="<?php echo esc_url( socialsnap_upgrade_link() ); ?>" class="<?php echo esc_attr( $class ); ?>" target="_blank"<?php echo esc_html( $atts ); ?>>
		<?php echo esc_html( apply_filters( 'socialsnap_upgrade_button_text', __( 'Upgrade', 'socialsnap' ) ) ); ?>	
	</a>

	<?php
}

/**
 * Upgrade URL.
 *
 * @since 1.0.0
 */
function socialsnap_upgrade_link() {

	$link = 'https://socialsnap.com/?utm_source=WordPress&utm_medium=link&utm_campaign=liteplugin';

	return apply_filters( 'socialsnap_upgrade_link', $link );
}

/**
 * Check if feature is available.
 *
 * @since 1.0.0
 */
function socialsnap_settings_require_upgrade( $settings ) {

	$return = isset( $settings['pro'] ) && $settings['pro'] && ! socialsnap()->pro;

	return $return;
}

/**
 * "Powered by Social Snap" signature
 *
 * @since 1.0.0
 */
function socialsnap_signature() {

	if ( socialsnap_settings( 'ss_remove_signature' ) ) {
		return;
	}

	$url = add_query_arg(
		array(
			'utm_source'   => 'WordPress',
			'utm_medium'   => 'link',
			'utm_campaign' => 'inthewild',
		),
		socialsnap_upgrade_link()
	);

	$output = '<div class="ss-powered-by">Powered by <a href="' . esc_attr( $url ) . '" target="_blank" rel="nofollow noopener">' . socialsnap()->icons->get_svg( 'socialsnap-icon' ) . 'Social Snap</a></div><!-- END .ss-powered-by -->'; // phpcs:ignore

	echo wp_kses( apply_filters( 'socialsnap_signature', $output ), socialsnap_get_allowed_html_tags( 'post' ) );
}

/**
 * Deactivated Main Social Snap plugin.
 *
 * @since 1.0.0
 */
function socialsnap_deactivated_plugin() {

	// Hook for add-ons
	do_action( 'socialsnap_deactivated' );
}
add_action( 'deactivated_plugin', 'socialsnap_deactivated_plugin' );

/**
 * Get array of values for $key meta key.
 *
 * @param string $key
 * @since 1.0.0
 */
function socialsnap_get_meta_values( $key = '' ) {

	if ( empty( $key ) ) {
		return;
	}

	global $wpdb;

	$sql = "
		SELECT p.ID, pm.meta_value FROM {$wpdb->postmeta} pm
		LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = '%s' 
		AND p.post_status = '%s' 
	";

	$results = $wpdb->get_results( $wpdb->prepare( $sql, $key, 'publish' ) ); // phpcs:ignore
	$return  = array();

	if ( is_array( $results ) && ! empty( $results ) ) {
		foreach ( $results as $result ) {
			$return[ $result->ID ] = $result->meta_value;
		}
	}

	return $return;
}

/**
 * Queries the remote URL via wp_remote_post and returns a json decoded response.
 *
 * @since 1.0.0
 * @param string $action        The name of the $_POST action var.
 * @param array  $body           The content to retrieve from the remote URL.
 * @param array  $headers        The headers to send to the remote URL.
 * @param string $return_format The format for returning content from the remote URL.
 * @return string|bool          Json decoded response on success, false on failure.
 */
function socialsnap_perform_remote_request( $action, $body = array(), $headers = array(), $return_format = 'json', $remote_url = SOCIALSNAP_API ) {

	// Build the body of the request.
	$body = wp_parse_args(
		$body,
		array(
			'tgm-updater-action'     => $action,
			'tgm-updater-key'        => isset( $body['tgm-updater-key'] ) ? $body['tgm-updater-key'] : '',
			'tgm-updater-wp-version' => get_bloginfo( 'version' ),
			'tgm-updater-ss-version' => SOCIALSNAP_VERSION,
			'tgm-updater-referer'    => site_url(),
		)
	);
	$body = http_build_query( $body, '', '&' );

	// Build the headers of the request.
	$headers = wp_parse_args(
		$headers,
		array(
			'Content-Type'   => 'application/x-www-form-urlencoded',
			'Content-Length' => strlen( $body ),
		)
	);

	// Setup variable for wp_remote_post.
	$post = array(
		'headers' => $headers,
		'body'    => $body,
	);

	// Perform the query and retrieve the response.
	$response      = wp_safe_remote_post( $remote_url, $post );
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );

	// Bail out early if there are any errors.
	if ( 200 !== $response_code ) {
		return new WP_Error( $response_code, json_decode( $response_body ) );
	} elseif ( is_wp_error( $response_body ) ) {
		return $response_body;
	}

	// Return the json decoded content.
	return json_decode( $response_body );
}

/**
 * Print admin notice.
 *
 * @since 1.0.5
 * @param array $args Notice configuration array.
 */
if ( ! function_exists( 'socialsnap_print_notice' ) ) {
	function socialsnap_print_notice( $args ) {

		$defaults = array(
			'type'           => 'success',
			'message'        => '',
			'is_dismissible' => true,
			'message_id'     => '',
			'class'          => '',
			'expires'        => 0,
			'display_on'     => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Get dismissed info
		if ( get_site_transient( 'socialsnap_notice_' . $args['message_id'] ) ) {
			return;
		}

		// Check if we're on the right page
		if ( '' !== $args['display_on'] ) {

			$base = get_current_screen()->base;

			if ( false === strpos( $base, $args['display_on'] ) ) {
				return;
			}
		}

		$class          = $args['class'] ? ' ' . $args['class'] : '';
		$is_dismissible = $args['is_dismissible'] ? ' is-dismissible' : '';
		?>

		<div id="<?php echo esc_attr( $args['message_id'] ); ?>" class="notice socialsnap-notice notice-<?php echo esc_attr( $args['type'] ); ?><?php echo esc_html( $is_dismissible ); ?><?php echo esc_attr( $class ); ?>">
			<?php printf( wp_kses( $args['message'], socialsnap_get_allowed_html_tags( 'post' ) ) ); ?>
		</div>

		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {

				var msgid = "<?php echo esc_html( $args['message_id'] ); ?>";

				$( '#' + msgid ).on( 'click', '.notice-dismiss, .socialsnap-notice-dismiss-button', function ( event ) {

					var expires = "<?php echo esc_html( $args['expires'] ); ?>";
					var nonce = "<?php echo esc_html( wp_create_nonce( 'socialsnap_dismiss_notice' ) ); ?>";

					$.post( ajaxurl, {
						action: 		'socialsnap_dismiss_notice',
						msgid: 			msgid,
						expires: 		expires,
						_ajax_nonce: 	nonce,
					} );

				} );

				$( '#' + msgid ).on( 'click', '.socialsnap-notice-dismiss-button', function ( event ) {
					$(this).closest( '.socialsnap-notice' ).css( 'opacity', 0 ).slideUp(150);
				} );

			} );
		</script>
		<?php
	}
}

/**
 * Dismiss admin notice.
 *
 * @since 1.0.5
 */
if ( ! function_exists( 'socialsnap_dismiss_notice' ) ) {
	function socialsnap_dismiss_notice() {

		check_ajax_referer( 'socialsnap_dismiss_notice' );

		if ( ! isset( $_POST['msgid'] ) ) {
			die;
		}

		$message_id = sanitize_text_field( $_POST['msgid'] );
		$expires    = isset( $_POST['expires'] ) ? intval( sanitize_text_field( $_POST['expires'] ) ) : 0;

		$message              = (array) get_site_transient( 'socialsnap_notice_' . $message_id );
		$message['time']      = time();
		$message['dismissed'] = true;

		set_site_transient( 'socialsnap_notice_' . $message_id, $message, $expires );
		die;
	}
}
add_action( 'wp_ajax_socialsnap_dismiss_notice', 'socialsnap_dismiss_notice' );

/**
 * Array of allowed HTML Tags.
 *
 * @since 1.0.5
 * @return array, allowed HTML tags.
 */
if ( ! function_exists( 'socialsnap_get_allowed_html_tags' ) ) {
	function socialsnap_get_allowed_html_tags( $type = 'basic' ) {

		$tags = array();

		switch ( $type ) {

			case 'basic':
				$tags = array(
					'strong' => array(),
					'em'     => array(),
					'b'      => array(),
					'br'     => array(),
					'p'      => array(),
					'i'      => array(),
					'a'      => array(
						'href'   => array(),
						'rel'    => array(),
						'target' => array(),
						'class'  => array(),
					),
				);
				break;

			case 'post':
				$tags = wp_kses_allowed_html( 'post' );

				$tags['a']['data-*'] = true;

				$tags = array_merge(
					$tags,
					array(
						'svg'     => array(
							'class'       => true,
							'xmlns'       => true,
							'width'       => true,
							'height'      => true,
							'viewbox'     => true,
							'aria-hidden' => true,
							'role'        => true,
							'focusable'   => true,
						),
						'path'    => array(
							'fill'      => true,
							'fill-rule' => true,
							'd'         => true,
							'transform' => true,
						),
						'polygon' => array(
							'fill'      => true,
							'fill-rule' => true,
							'points'    => true,
							'transform' => true,
							'focusable' => true,
						),
					)
				);

				break;

			default:
				$tags = array(
					'strong' => array(),
					'em'     => array(),
					'b'      => array(),
					'br'     => array(),
					'p'      => array(),
					'i'      => array(),
					'a'      => array(
						'href'   => array(),
						'rel'    => array(),
						'target' => array(),
						'class'  => array(),
					),
				);
				break;
		}

		return apply_filters( 'socialsnap_get_allowed_html_tags', $tags, $type );
	}
}

/**
 * Check if we're on AMP page and if buttons should appear.
 *
 * @since 1.1.2
 * @return boolean
 */
function socialsnap_is_amp_page() {

	$is_amp = false;

	// Check the AMP plugin.
	if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
		$is_amp = true;
	}

	// Check the AMP for WP plugin.
	if ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() ) {
		$is_amp = true;
	}

	return apply_filters( 'socialsnap_is_amp_page', $is_amp );
}

/**
 * Get attachment id from URL.
 *
 * @since 1.1.3
 * @return int $post_id
 */
function socialsnap_get_attachment_id_by_url( $url ) {

	$cache_key = 'socialsnap_attachment_id_' . md5( $url );

	$post_id = get_site_transient( $cache_key, 'socialsnap_attachment_id_by_url' );

	if ( false === $post_id ) {

		$post_id = attachment_url_to_postid( $url );

		if ( ! $post_id ) {

			$dir  = wp_upload_dir();
			$path = $url;

			if ( 0 === strpos( $path, $dir['baseurl'] . '/' ) ) {
				$path = substr( $path, strlen( $dir['baseurl'] . '/' ) );
			}

			if ( preg_match( '/^(.*)(\-\d*x\d*)(\.\w{1,})/i', $path, $matches ) ) {
				$url     = $dir['baseurl'] . '/' . $matches[1] . $matches[3];
				$post_id = attachment_url_to_postid( $url );
			}
		}

		set_site_transient( $cache_key, intval( $post_id ), 'socialsnap_attachment_id_by_url', 12 * HOUR_IN_SECONDS );
	}

	return (int) $post_id;
}

/**
 * Remove div with classname from text.
 *
 * @param  [type] $selectors [description]
 * @param  [type] $text      [description]
 * @return [type]            [description]
 */
function socialsnap_strip_tags_by_class( $selectors, $text ) {

	$selectors = (array) $selectors;

	if ( empty( $selectors ) ) {
		return $text;
	}

	$selectors = implode( '|', $selectors );

	$regex = '#<(\w+)\s[^>]*(class)\s*=\s*[\'"]\s*(' . $selectors . ')[\s*\'"][^>]*>.*</\\1>#isU';

	return ( preg_replace( $regex, '', $text ) );
}
