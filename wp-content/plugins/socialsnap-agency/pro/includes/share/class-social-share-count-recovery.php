<?php
/**
 * Share Count recovery. Get share counts from alternative links.
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Share_Count_Recovery {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add fields to settings panel.
		add_filter( 'socialsnap_settings_config', array( $this, 'add_settings_config' ), 6, 1 );

		// Check if share recovery is enabled.
		if ( ! socialsnap_settings( 'ss_share_recovery' ) ) {
			return;
		}

		// Add fields to metaboxes.
		add_filter( 'socialsnap_metaboxes_config', array( $this, 'add_metaboxes_config' ), 5, 1 );

		add_filter( 'socialsnap_alternative_urls', array( $this, 'generate_alternative_link' ), 2, 2 );
	}

	/**
	 * Function based on get_permalink() function in wp-includes/link-template.php
	 *
	 * @since 1.0.0
	 */
	public function generate_alternative_link( $urls = array(), $post = 0 ) {

		global $wp_rewrite;

		$rewritecode = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			'%postname%',
			'%post_id%',
			'%category%',
			'%author%',
			'%pagename%',
		);

		$url = home_url( '/' );

		// Did not work for recovery URL on homepage (static page).
		if ( $post && -1 != $post ) {

			if ( is_object( $post ) && isset( $post->filter ) && 'sample' == $post->filter ) {
				$sample = true;
			} else {
				$post   = get_post( $post );
				$sample = false;
			}

			// Check if there is recovery url in post meta.
			$custom_url = get_post_meta( $post->ID, 'ss_share_recovery_url', true );
			if ( $custom_url ) {
				$urls[] = $custom_url;
			}

			$socialsnap_structure = socialsnap_settings( 'ss_share_recovery_format' );

			if ( ! $socialsnap_structure || 'unaltered' === $socialsnap_structure ) {
				$url = get_permalink( $post );
			} else {

				$permalink = get_option( 'permalink_structure' );

				switch ( $socialsnap_structure ) {

					case 'plain':
						$permalink = '';
						break;
					case 'day-name':
						$permalink = '/%year%/%monthnum%/%day%/%postname%/';
						break;
					case 'month-name':
						$permalink = '/%year%/%monthnum%/%postname%/';
						break;
					case 'numeric':
						$permalink = '/archives/%post_id%/';
						break;
					case 'post-name':
						$permalink = '/%postname%/';
						break;
					case 'custom':
						$permalink = socialsnap_settings( 'ss_share_recovery_custom_format' );
						break;
				}

				$permalink = apply_filters( 'pre_post_link', $permalink, $post, false );

				if ( '' != $permalink && ! in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft', 'future' ) ) ) {

					$unixtime = strtotime( $post->post_date );
					$category = '';

					if ( strpos( $permalink, '%category%' ) !== false ) {

						$cats = get_the_category( $post->ID );

						if ( $cats ) {
							$cats = wp_list_sort(
								$cats,
								array(
									'term_id' => 'ASC',
								)
							);

							$category_object = apply_filters( 'post_link_category', $cats[0], $cats, $post );
							$category_object = get_term( $category_object, 'category' );
							$category        = $category_object->slug;

							$parent = $category_object->parent;
							if ( $parent ) {
								$category = get_category_parents( $parent, false, '/', true ) . $category;
							}
						}

						if ( empty( $category ) ) {
							$default_category = get_term( get_option( 'default_category' ), 'category' );
							if ( $default_category && ! is_wp_error( $default_category ) ) {
								$category = $default_category->slug;
							}
						}
					}

					$author = '';
					if ( strpos( $permalink, '%author%' ) !== false ) {
						$authordata = get_userdata( $post->post_author );
						$author     = $authordata->user_nicename;
					}

					$date           = explode( ' ', gmdate( 'Y m d H i s', $unixtime ) );
					$rewritereplace =
					array(
						$date[0],
						$date[1],
						$date[2],
						$date[3],
						$date[4],
						$date[5],
						$post->post_name,
						$post->ID,
						$category,
						$author,
						$post->post_name,
					);
					$permalink      = home_url( str_replace( $rewritecode, $rewritereplace, $permalink ) );
				} else {
					$permalink = home_url( '?p=' . $post->ID );
				}

				$url = apply_filters( 'post_link', $permalink, $post, false );
			}
		}

		$ss_protocol    = socialsnap_settings( 'ss_share_recovery_protocol' );
		$ss_www         = socialsnap_settings( 'ss_share_recovery_www' );
		$ss_subdmn      = socialsnap_settings( 'ss_share_recovery_subdomain' );
		$ss_prev_subdmn = socialsnap_settings( 'ss_share_recovery_subdomain_old' );
		$ss_curr_subdmn = socialsnap_settings( 'ss_share_recovery_subdomain_new' );
		$ss_dmn         = socialsnap_settings( 'ss_share_recovery_domain' );
		$ss_prev_dmn    = socialsnap_settings( 'ss_share_recovery_prev_domain' );
		$ss_curr_dmn    = socialsnap_settings( 'ss_share_recovery_current_domain' );

		// Replace current domain with previous domain.
		if ( isset( $ss_dmn ) && 'on' == $ss_dmn && isset( $ss_prev_dmn ) && '' !== $ss_prev_dmn && isset( $ss_curr_dmn ) && '' !== $ss_curr_dmn ) {
			$url = str_replace( $ss_curr_dmn, $ss_prev_dmn, $url );
		}

		// Replace HTTP protocol.
		if ( 'http' == $ss_protocol && false !== strpos( $url, 'https' ) ) {
			$url = str_replace( 'https', 'http', $url );
		} elseif ( 'https' == $ss_protocol && false === strpos( $url, 'https' ) && false !== strpos( $url, 'http' ) ) {
			$url = str_replace( 'http', 'https', $url );
		}

		// Filter subdomains.
		switch ( $ss_subdmn ) {
			case 'new':
				if ( '' != $ss_curr_subdmn ) {
					$url = str_replace( '://' . $ss_curr_subdmn . '.', '://', $url );
				}
				break;

			case 'old':
				if ( '' != $ss_prev_subdmn ) {
					$url = str_replace( '://', '://' . $ss_prev_subdmn . '.', $url );
				}
				break;

			case 'both':
				if ( '' != $ss_curr_subdmn && '' != $ss_prev_subdmn ) {
					$url = str_replace( '://' . $ss_curr_subdmn . '.', '://' . $ss_prev_subdmn . '.', $url );
				}
				break;
		}

		// Filter WWW prefix.
		switch ( $ss_www ) {
			case 'www':
				if ( false === strpos( $url, '://www' ) ) {
					$url = str_replace( 'http://', 'http://www.', $url );
					$url = str_replace( 'https://', 'https://www.', $url );
				}
				break;

			case 'non-www':
				if ( false !== strpos( $url, '://www' ) ) {
					$url = str_replace( 'http://www.', 'http://', $url );
					$url = str_replace( 'https://www.', 'https://', $url );
				}
				break;
		}

		$urls[] = $url;

		return array_unique( $urls );
	}

	/**
	 * Register Social Meta settings fields.
	 *
	 * @since 1.0.0
	 * @param array $settings Array of settings.
	 * @return array
	 */
	public function add_settings_config( $settings ) {

		if ( ! isset( $settings['ss_advanced_settings'] ) ) {
			return $settings;
		}

		$share_recovery = array(
			'ss_share_recovery'                => array(
				'id'      => 'ss_share_recovery',
				'name'    => esc_html__( 'Enable Share Count Recovery', 'socialsnap' ),
				'type'    => 'toggle',
				'default' => false,
			),
			'ss_share_recovery_domain'         => array(
				'id'         => 'ss_share_recovery_domain',
				'name'       => esc_html__( 'Domain Migration', 'socialsnap' ),
				'desc'       => __( 'Enable if you changed your base domain (for example, moved from “oldsite.org” to “newsite.com”)', 'socialsnap' ),
				'type'       => 'toggle',
				'default'    => false,
				'dependency' => array(
					'element' => 'ss_share_recovery',
					'value'   => 'true',
				),
			),
			'ss_share_recovery_prev_domain'    => array(
				'id'          => 'ss_share_recovery_prev_domain',
				'name'        => esc_html__( 'Old Domain', 'socialsnap' ),
				'desc'        => __( 'Your old domain. Enter clean domain, without http protocol, www prefix and/or subdomain.', 'socialsnap' ),
				'type'        => 'text',
				'placeholder' => 'oldsite.org',
				'default'     => '',
				'dependency'  => array(
					'element' => 'ss_share_recovery_domain',
					'value'   => 'true',
				),
			),
			'ss_share_recovery_current_domain' => array(
				'id'          => 'ss_share_recovery_current_domain',
				'name'        => esc_html__( 'New Domain', 'socialsnap' ),
				'desc'        => __( 'Your new domain. Enter clean domain, without http protocol, www prefix and/or subdomain.', 'socialsnap' ),
				'type'        => 'text',
				'placeholder' => 'newsite.com',
				'default'     => '',
				'dependency'  => array(
					'element' => 'ss_share_recovery_domain',
					'value'   => 'true',
				),
			),
			'ss_share_recovery_protocol'       => array(
				'id'         => 'ss_share_recovery_protocol',
				'name'       => esc_html__( 'Connection Protocol', 'socialsnap' ),
				'desc'       => __( 'Specify your previous connection protocol.', 'socialsnap' ),
				'type'       => 'radio',
				'options'    => array(
					'unaltered' => __( 'Unaltered', 'socialsnap' ),
					'http'      => __( 'http://', 'socialsnap' ),
					'https'     => __( 'https://', 'socialsnap' ),
				),
				'default'    => 'unaltered',
				'dependency' => array(
					'element' => 'ss_share_recovery',
					'value'   => 'true',
				),
			),
			'ss_share_recovery_www'            => array(
				'id'         => 'ss_share_recovery_www',
				'name'       => esc_html__( 'WWW Prefix', 'socialsnap' ),
				'desc'       => __( 'Specify if you previously used “www” in your URL.', 'socialsnap' ),
				'type'       => 'radio',
				'options'    => array(
					'unaltered' => __( 'Unaltered', 'socialsnap' ),
					'www'       => __( 'With “www” prefix', 'socialsnap' ),
					'non-www'   => __( 'Without “www” prefix', 'socialsnap' ),
				),
				'default'    => 'unaltered',
				'dependency' => array(
					'element' => 'ss_share_recovery',
					'value'   => 'true',
				),
			),
			'ss_share_recovery_subdomain'      => array(
				'id'         => 'ss_share_recovery_subdomain',
				'name'       => esc_html__( 'Subdomain Migration', 'socialsnap' ),
				'type'       => 'dropdown',
				'options'    => array(
					'unaltered' => __( 'Unaltered', 'socialsnap' ),
					'new'       => __( 'I now use a subdomain', 'socialsnap' ),
					'old'       => __( 'I used a subdomain, now I do not', 'socialsnap' ),
					'both'      => __( 'I changed my subdomain', 'socialsnap' ),
				),
				'default'    => 'unaltered',
				'dependency' => array(
					'element' => 'ss_share_recovery',
					'value'   => 'true',
				),
			),
			'ss_share_recovery_subdomain_old'  => array(
				'id'          => 'ss_share_recovery_subdomain_old',
				'name'        => esc_html__( 'Previous Subdomain', 'socialsnap' ),
				'desc'        => __( 'If you used a subdomain such as blog.mysite.com.', 'socialsnap' ),
				'type'        => 'text',
				'placeholder' => 'blog',
				'default'     => '',
				'dependency'  => array(
					'element' => 'ss_share_recovery_subdomain',
					'value'   => array( 'old', 'both' ),
				),
			),
			'ss_share_recovery_subdomain_new'  => array(
				'id'          => 'ss_share_recovery_subdomain_new',
				'name'        => esc_html__( 'New Subdomain', 'socialsnap' ),
				'desc'        => __( 'If are now using a subdomain such as blog.mysite.com.', 'socialsnap' ),
				'type'        => 'text',
				'placeholder' => 'blog',
				'default'     => '',
				'dependency'  => array(
					'element' => 'ss_share_recovery_subdomain',
					'value'   => array( 'new', 'both' ),
				),
			),
			'ss_share_recovery_format'         => array(
				'id'         => 'ss_share_recovery_format',
				'name'       => esc_html__( 'URL Structure', 'socialsnap' ),
				'type'       => 'dropdown',
				'desc'       => __( 'Specify the URL/permalink structure that you migrated away from.', 'socialsnap' ),
				'options'    => array(
					'unaltered'  => __( 'Unaltered', 'socialsnap' ),
					'plain'      => __( 'Plain', 'socialsnap' ),
					'day-name'   => __( 'Day and name', 'socialsnap' ),
					'month-name' => __( 'Month and name', 'socialsnap' ),
					'numeric'    => __( 'Numeric', 'socialsnap' ),
					'post-name'  => __( 'Post Name', 'socialsnap' ),
					'custom'     => __( 'Custom', 'socialsnap' ),
				),
				'default'    => 'unaltered',
				'dependency' => array(
					'element' => 'ss_share_recovery',
					'value'   => 'true',
				),
			),
			'ss_share_recovery_custom_format'  => array(
				'id'          => 'ss_share_recovery_custom_format',
				'name'        => esc_html__( 'Custom URL Structure', 'socialsnap' ),
				'desc'        => __( 'If custom URL structure was used, add it here.', 'socialsnap' ),
				'type'        => 'text',
				'placeholder' => '/%category%/%postname%/',
				'default'     => '',
				'dependency'  => array(
					'element' => 'ss_share_recovery_format',
					'value'   => 'custom',
				),
			),
		);

		$settings['ss_advanced_settings']['fields']['ss_share_count_recovery']['fields'] = $share_recovery;

		return $settings;
	}


	/**
	 * Register Share Recovery metabox fields.
	 *
	 * @since 1.0.0
	 * @param array $metaboxes Array of metabox settings.
	 * @return array
	 */
	public function add_metaboxes_config( $metaboxes ) {

		if ( ! isset( $metaboxes['ss-socialsnap-main'] ) ) {
			return $metaboxes;
		}

		$sr_metabox = array(
			'id'   => 'ss_share_recovery_url',
			'name' => esc_html__( 'Share Recovery URL', 'socialsnap' ),
			'type' => 'editor_text',
			'desc' => __( 'Add specific URL for this post to use in the Share Count Recovery process. If left empty, options from Social Snap Settings will be used.', 'socialsnap' ),
		);

		$metaboxes['ss-socialsnap-main']['options']['ss-ss-metabox']['options']['ss_share_recovery_url'] = $sr_metabox;

		return $metaboxes;
	}

}
new SocialSnap_Share_Count_Recovery();
