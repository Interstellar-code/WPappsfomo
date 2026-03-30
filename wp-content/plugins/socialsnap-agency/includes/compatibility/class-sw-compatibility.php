<?php
/**
 * Handles compatibiliy with Social Warfare plugin.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.9
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */

// Check if Social Warfare was installed & settings exist in database.
$sw_options = get_option( 'social_warfare_settings' );

if ( empty( $sw_options ) ) {
	return;
}

class SocialSnap_SW_Compatibility {

	/**
	 * Options array.
	 *
	 * @since 1.0.9
	 * @var array
	 */
	private $options = array();

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.9
	 */
	public function __construct( $options ) {

		$this->options = $options;

		// Add import button to the settings panel.
		add_filter( 'socialsnap_plugin_migration', array( $this, 'add_to_settings' ) );

		// AJAX callback to import settings.
		add_action( 'wp_ajax_socialsnap_sw_migrate', array( $this, 'migrate_settings' ) );

		// AJAX callback to import shares.
		add_action( 'wp_ajax_socialsnap_sw_migrate_shares', array( $this, 'migrate_shares' ) );

		// Shortcode compatibility.
		if ( ! shortcode_exists( 'click_to_tweet' ) ) {
			add_shortcode( 'click_to_tweet', array( $this, 'click_to_tweet_shortcode' ) );
		}

		// Shortcode compatibility.
		if ( ! shortcode_exists( 'clickToTweet' ) ) {
			add_shortcode( 'clickToTweet', array( $this, 'click_to_tweet_shortcode' ) );
		}
	}

	/**
	 * Add button to the settings panel which imports settings via AJAX.
	 *
	 * @since 1.0.9
	 */
	public function add_to_settings( $fields ) {

		$fields['ss_sw_migrate'] = array(
			'id'      => 'ss_sw_migrate',
			'name'    => __( 'Social Warfare', 'socialsnap' ),
			'desc'    => __( 'Import Social Warfare settings', 'socialsnap' ),
			'text'    => __( 'Import Settings', 'socialsnap' ),
			'type'    => 'button',
			'action'  => 'socialsnap_sw_migrate',
			'confirm' => esc_html__( 'Are you sure you want to import Social Warfare settings? This will overwrite current plugin settings and individual post settings. Please make a full backup of your website before you start the migration process.', 'socialsnap' ),
		);

		$fields['ss_sw_migrate_shares'] = array(
			'id'      => 'ss_sw_migrate_shares',
			'text'    => __( 'Import Share Counts', 'socialsnap' ),
			'type'    => 'button',
			'action'  => 'socialsnap_sw_migrate_shares',
			'confirm' => esc_html__( 'Are you sure you want to import Social Warfare share counts? Share counts will be imported only if they are greater than counts stored by Social Snap. Please make a full backup of your website before you start the import process.', 'socialsnap' ),
		);

		return $fields;
	}

	/**
	 * Migrate SWF options & post meta.
	 *
	 * @since 1.0.9
	 */
	public function migrate_settings() {

		check_ajax_referer( 'socialsnap-admin' );

		if ( ! current_user_can( apply_filters( 'socialsnap_manage_cap', 'manage_options' ) ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error. Access denied.', 'socialsnap' ),
				)
			);
		}

		$this->import_settings();

		$meta_key_map = array(
			'swp_og_image'              => 'ss_smt_image',
			'swp_og_title'              => 'ss_smt_title',
			'swp_og_description'        => 'ss_smt_description',
			'swp_pinterest_image'       => 'ss_image_pinterest',
			'swp_custom_tweet'          => 'ss_ss_custom_tweet',
			'swp_recovery_url'          => 'ss_share_recovery_url',
			'swp_pinterest_description' => 'ss_pinterest_description',
			'swp_pin_button_opt_out'    => 'ss_no_pin',
			// 'swp_pin_browser_extension',
			// 'swp_pin_browser_extension_location',
			// 'swp_pin_browser_extension_url',
			// 'swp_post_location',
			// 'swp_float_location',
			// 'swp_twitter_id',
		);

		foreach ( $meta_key_map as $old_key => $new_key ) {
			$this->import_post_meta( $old_key, $new_key );
		}

		// Send success message.
		wp_send_json_success( array( 'message' => esc_html__( 'Social Warfare settings imported successfully.', 'sinatra' ) ) );
	}

	/**
	 * Import settings. Populates Social Snap settings from SWF settings.
	 *
	 * @since 1.1.0
	 */
	public function import_settings() {

		set_time_limit( 300 );

		$ss_settings = get_option( SOCIALSNAP_SETTINGS );
		$sw_settings = $this->options;

		// Social Share networks.
		if ( isset( $sw_settings['order_of_icons'] ) && ! empty( $sw_settings['order_of_icons'] ) ) {

			$ss_networks       = array();
			$ss_share_networks = socialsnap_get_social_share_networks();

			foreach ( $sw_settings['order_of_icons'] as $network ) {

				if ( 'email' === $network ) {
					$network = 'envelope';
				}

				if ( array_key_exists( $network, $ss_share_networks ) ) {

					$ss_networks[ $network ] = array(
						'text'               => socialsnap_get_network_name( $network ),
						'desktop_visibility' => 'on',
						'mobile_visibility'  => 'on',
					);

					$ss_networks['order'] = isset( $ss_networks['order'] ) ? $ss_networks['order'] . ',' . $network : $network;
				}
			}

			if ( ! empty( $ss_networks ) ) {
				$ss_networks['order']                    = isset( $ss_networks['order'] ) ? trim( $ss_networks['order'], ',' ) : '';
				$ss_settings['ss_social_share_networks'] = $ss_networks;
			}
		}

		// Social Share inline content style.
		if ( isset( $sw_settings['button_shape'] ) ) {

			$value = $sw_settings['button_shape'];

			if ( 'flat_fresh' === $value ) {
				$ss_settings['ss_ss_inline_content_button_shape'] = 'rounded';
			} elseif ( 'leaf' === $value || 'shift' === $value ) {
				$ss_settings['ss_ss_inline_content_button_shape'] = 'slanted';
			} elseif ( 'pill' === $value ) {
				$ss_settings['ss_ss_inline_content_button_shape'] = 'circle';
			} elseif ( 'three_dee' === $value || 'connected' === $value ) {
				$ss_settings['ss_ss_inline_content_button_shape'] = 'rectangle';
			} else {
				$ss_settings['ss_ss_inline_content_button_shape'] = 'rounded';
			}
		}

		// Social Share inline content size.
		if ( isset( $sw_settings['button_size'] ) ) {

			$value = floatval( $sw_settings['button_size'] );

			if ( $value <= 1 ) {
				$ss_settings['ss_ss_inline_content_button_size'] = 'small';
			} elseif ( $value <= 1.2 ) {
				$ss_settings['ss_ss_inline_content_button_size'] = 'regular';
			} else {
				$ss_settings['ss_ss_inline_content_button_size'] = 'large';
			}
		}

		$ss_settings['ss_ss_inline_content_hover_animation'] = 'ss-reveal-label';
		$ss_settings['ss_ss_inline_content_position']        = 'stretched';
		$ss_settings['ss_ss_inline_content_all_networks']    = false;
		$ss_settings['ss_ss_inline_content_full_content']    = (bool) $sw_settings['full_content'];

		// Social Share sidebar.
		if ( isset( $sw_settings['floating_panel'] ) && $sw_settings['floating_panel'] ) {

			$position = isset( $sw_settings['float_location'] ) ? $sw_settings['float_location'] : 'left';

			if ( 'left' === $position || 'right' === $position ) {

				$ss_settings['ss_ss_sidebar_enabled']      = true;
				$ss_settings['ss_ss_sidebar_position']     = $position;
				$ss_settings['ss_ss_sidebar_all_networks'] = false;

				$size = floatval( $sw_settings['float_size'] );

				if ( $size <= 1 ) {
					$ss_settings['ss_ss_sidebar_button_size'] = 'small';
				} elseif ( $size <= 1.2 ) {
					$ss_settings['ss_ss_sidebar_button_size'] = 'regular';
				} else {
					$ss_settings['ss_ss_sidebar_button_size'] = 'large';
				}

				$ss_settings['ss_ss_sidebar_entrance_animation'] = $sw_settings['transition'];

				$ss_settings['ss_ss_sidebar_button_shape'] = 'circles' === $sw_settings['float_button_shape'] ? 'circle' : 'rounded';
				$ss_settings['ss_ss_sidebar_button_size']  = 'large';

				if ( 'off' !== $sw_settings['float_mobile'] ) {
					$ss_settings['ss_ss_sidebar_hide_on_mobile']        = true;
					$ss_settings['ss_ss_sticky_bar_enabled']            = true;
					$ss_settings['ss_ss_sticky_bar_position']           = $sw_settings['float_mobile'];
					$ss_settings['ss_ss_sticky_bar_style']              = 'as-inline';
					$ss_settings['ss_ss_sticky_bar_visibility']         = 'ss-hide-on-desktop';
					$ss_settings['ss_ss_sticky_bar_entrance_animation'] = $sw_settings['transition'];
				}
			} else {
				$ss_settings['ss_ss_sticky_bar_enabled']  = true;
				$ss_settings['ss_ss_sticky_bar_position'] = $position;
			}
		}

		// Display network share count.
		if ( isset( $sw_settings['network_shares'] ) ) {

			$value = (bool) $sw_settings['network_shares'];

			// Sidebar counts.
			$ss_settings['ss_ss_sidebar_share_count'] = $value;

			// Share hub counts.
			$ss_settings['ss_ss_hub_share_count'] = $value;

			// Sticky bar counts.
			$ss_settings['ss_ss_sticky_bar_share_count'] = $value;

			// Inline content counts.
			if ( $value ) {
				if ( 'none' === $ss_settings['ss_ss_inline_content_button_label'] ) {
					$ss_settings['ss_ss_inline_content_button_label'] = 'count';
				} elseif ( 'label' === $ss_settings['ss_ss_inline_content_button_label'] ) {
					$ss_settings['ss_ss_inline_content_button_label'] = 'both';
				}
			} else {
				if ( 'count' === $ss_settings['ss_ss_inline_content_button_label'] ) {
					$ss_settings['ss_ss_inline_content_button_label'] = 'none';
				} elseif ( 'both' === $ss_settings['ss_ss_inline_content_button_label'] ) {
					$ss_settings['ss_ss_inline_content_button_label'] = 'label';
				}
			}
		}

		// Display total share count.
		if ( isset( $sw_settings['total_shares'] ) ) {

			$value = (bool) $sw_settings['total_shares'];

			$ss_settings['ss_ss_sidebar_total_count']        = $value;
			$ss_settings['ss_ss_inline_content_total_count'] = $value;
			$ss_settings['ss_ss_hub_total_count']            = $value;
			$ss_settings['ss_ss_sticky_bar_total_count']     = $value;

			$ss_settings['ss_ss_inline_content_total_share_placement'] = 'right';
		}

		// Min share count.
		if ( isset( $sw_settings['minimum_shares'] ) && $sw_settings['minimum_shares'] ) {

			$value = intval( $sw_settings['minimum_shares'] );

			$ss_settings['ss_ss_sidebar_min_count']        = $value;
			$ss_settings['ss_ss_inline_content_min_count'] = $value;
			$ss_settings['ss_ss_hub_min_count']            = $value;
			$ss_settings['ss_ss_sticky_bar_min_count']     = $value;
		}

		// Pinit button.
		if ( isset( $sw_settings['pinit_toggle'] ) && $sw_settings['pinit_toggle'] ) {

			$ss_settings['ss_ss_on_media_enabled'] = true;
			$ss_settings['ss_ss_on_media_type']    = 'pin_it';

			// Pinit position.
			if ( isset( $sw_settings['pinit_location_vertical'], $sw_settings['pinit_location_horizontal'] ) ) {

				if ( 'middle' !== $sw_settings['pinit_location_vertical'] && 'center' !== $sw_settings['pinit_location_horizontal'] ) {
					$ss_settings['ss_ss_on_media_position'] = $sw_settings['pinit_location_vertical'] . '-' . $sw_settings['pinit_location_horizontal'];
				} else {
					$ss_settings['ss_ss_on_media_position'] = 'center';
				}
			}

			// Min width & height.
			if ( isset( $sw_settings['pinit_min_width'], $sw_settings['pinit_min_height'] ) ) {
				$ss_settings['ss_ss_on_media_minwidth']  = $sw_settings['pinit_min_width'];
				$ss_settings['ss_ss_on_media_minheight'] = $sw_settings['pinit_min_height'];
			}
		}

		// Pinterest image & description source.
		if ( isset( $sw_settings['pinit_image_source'] ) ) {
			$ss_settings['ss_ss_pinterest_image_src'] = $sw_settings['pinit_image_source'];
		}

		if ( isset( $sw_settings['pinit_image_description'] ) ) {
			$ss_settings['ss_ss_pinterest_description_src'] = $sw_settings['pinit_image_description'];
		}

		// Social Meta tags.
		if ( ( isset( $sw_settings['og_tags'] ) && $sw_settings['og_tags'] ) || ( isset( $sw_settings['twitter_cards'] ) && $sw_settings['twitter_cards'] ) ) {

			$ss_settings['ss_smt_enable'] = true;
		}

		// Twitter ID.
		if ( isset( $sw_settings['twitter_id'] ) ) {
			$ss_settings['ss_twitter_username'] = $sw_settings['twitter_id'];
		}

		// Pinterest ID.
		if ( isset( $sw_settings['pinterest_id'] ) ) {
			$ss_settings['ss_pinterest_username'] = $sw_settings['pinterest_id'];
		}

		// Twitter count source.
		if ( isset( $sw_settings['tweet_count_source'] ) ) {
			if ( 'opensharecount' === $sw_settings['tweet_count_source'] ) {
				$ss_settings['ss_ss_twitter_count_provider'] = 'opensharecounts';
			} elseif ( 'twitcount' === $sw_settings['tweet_count_source'] ) {
				$ss_settings['ss_ss_twitter_count_provider'] = 'twitcount';
			}
		}

		// Click Tracking.
		if ( isset( $sw_settings['click_tracking'] ) ) {
			$ss_settings['ss_click_tracking'] = (bool) $sw_settings['click_tracking'];
		}

		// UTM Tracking.
		if ( isset( $sw_settings['google_analytics'] ) ) {
			$ss_settings['ss_utm_tracking']          = (bool) $sw_settings['google_analytics'];
			$ss_settings['ss_utm_tracking_medium']   = $sw_settings['analytics_medium'];
			$ss_settings['ss_utm_tracking_campaign'] = $sw_settings['analytics_campaign'];
		}

		// Share recovery
		if ( isset( $sw_settings['recover_shares'] ) ) {

			$ss_settings['ss_share_recovery'] = (bool) $sw_settings['recover_shares'];

			// URL Format.
			$formats                                 = array(
				'unchanged'      => 'unaltered',
				'default'        => 'plain',
				'day_and_name'   => 'day-name',
				'month_and_name' => 'month-name',
				'numeric'        => 'numeric',
				'post_name'      => 'post-name',
				'custom'         => 'custom',
			);
			$ss_settings['ss_share_recovery_format'] = $formats[ $sw_settings['recovery_format'] ];

			if ( isset( $sw_settings['recovery_permalink'] ) ) {
				$ss_settings['ss_share_recovery_custom_format'] = $sw_settings['recovery_permalink'];
			}

			// Protocol.
			if ( isset( $sw_settings['recovery_protocol'] ) ) {

				$protocol = $sw_settings['recovery_protocol'];

				if ( 'unchanged' === $protocol ) {
					$protocol = 'unaltered';
				}

				$ss_settings['ss_share_recovery_protocol'] = $protocol;
			}

			// Prefix.
			if ( isset( $sw_settings['recovery_prefix'] ) ) {

				$prefix = $sw_settings['recovery_prefix'];

				if ( 'unchanged' === $prefix ) {
					$prefix = 'unaltered';
				} elseif ( 'nonwww' === $prefix ) {
					$prefix = 'non-www';
				}

				$ss_settings['ss_share_recovery_www'] = $prefix;
			}

			// Domain.
			if ( isset( $sw_settings['former_domain'] ) && $sw_settings['former_domain'] && isset( $sw_settings['current_domain'] ) && $sw_settings['current_domain'] ) {

				$ss_settings['ss_share_recovery_domain']         = true;
				$ss_settings['ss_share_recovery_prev_domain']    = $sw_settings['former_domain'];
				$ss_settings['ss_share_recovery_current_domain'] = $sw_settings['current_domain'];
			} else {
				$ss_settings['ss_share_recovery_domain'] = false;
			}

			// Subdomain.
			if ( isset( $sw_settings['recovery_subdomain'] ) && $sw_settings['recovery_subdomain'] ) {
				$ss_settings['ss_share_recovery_subdomain']     = 'old';
				$ss_settings['ss_share_recovery_subdomain_old'] = $sw_settings['recovery_subdomain'];
			}
		}

		update_option( SOCIALSNAP_SETTINGS, $ss_settings );
	}

	/**
	 * Click to Tweet shortcode.
	 *
	 * @since 1.1.6
	 */
	public function click_to_tweet_shortcode( $atts ) {

		$shortcode = '[ss_click_to_tweet';

		if ( isset( $atts['theme'] ) && '' !== $atts['theme'] ) {
			$shortcode .= ' style="' . str_replace( 'style', '', $atts['theme'] ) . '"';
		}

		if ( isset( $atts['tweet'] ) && '' !== $atts['tweet'] ) {
			$shortcode .= ' tweet="' . $atts['tweet'] . '"';
		}

		if ( isset( $atts['quote'] ) && '' !== $atts['quote'] ) {
			$shortcode .= ' content="' . $atts['quote'] . '"';
		}

		$shortcode .= ']';

		ob_start();

		echo do_shortcode( $shortcode );

		return ob_get_clean();
	}

	/**
	 * Import post meta. Populates post meta from SWF metaboxes.
	 *
	 * @since 1.1.0
	 */
	public function import_post_meta( $old_key, $new_key ) {

		global $wpdb;

		set_time_limit( 300 );

		$query = "
			SELECT postmeta.post_id, postmeta.meta_value
			FROM   $wpdb->postmeta postmeta
			WHERE  postmeta.meta_key = %s
		";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $old_key ) );

		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				update_post_meta( $row->post_id, $new_key, $row->meta_value );
			}
		}
	}

	/**
	 * Migrate SWF shares.
	 *
	 * @since 1.1.3
	 */
	public function migrate_shares() {

		// Security check.
		check_ajax_referer( 'socialsnap-admin' );

		if ( ! current_user_can( apply_filters( 'socialsnap_manage_cap', 'manage_options' ) ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error. Access denied.', 'socialsnap' ),
				)
			);
		}

		// Map SWF meta keys with SS meta keys.
		$meta_key_map = array(
			'_total_shares' => 'ss_total_share_count',
		);

		// SWF registered social networks.
		global $swp_social_networks;

		$swp_networks = array();

		if ( ! empty( $swp_social_networks ) ) {
			$swp_networks = array_keys( $swp_social_networks );
		} else {
			$swp_networks = array(
				'facebook',
				'twitter',
				'linkedin',
				'pinterest',
				'mix',
				'buffer',
				'reddit',
				'flipboard',
				'email',
				'hacker_news',
				'pocket',
				'tumblr',
				'whatsapp',
				'yummly',
			);
		}

		if ( ! empty( $swp_networks ) ) {
			foreach ( $swp_networks as $network ) {

				if ( 'email' === $network ) {
					$network = 'envelope';
				}

				$meta_key_map[ '_' . $network . '_shares' ] = 'ss_ss_share_count_' . $network;
			}
		}

		// Get posts with meta key.
		global $wpdb;

		foreach ( $meta_key_map as $old_key => $new_key ) {

			set_time_limit( 300 );

			$query = "
				SELECT postmeta.post_id, postmeta.meta_value
				FROM   $wpdb->postmeta postmeta
				WHERE  postmeta.meta_key = %s
			";

			$results = $wpdb->get_results( $wpdb->prepare( $query, $old_key ) );

			if ( ! empty( $results ) ) {
				foreach ( $results as $row ) {

					// Update only if swf value is greater than socialsnap value.
					if ( intval( get_post_meta( $row->post_id, $old_key, true ) ) > intval( get_post_meta( $row->post_id, $new_key, true ) ) ) {
						update_post_meta( $row->post_id, $new_key, $row->meta_value );
					}
				}
			}
		}

		// Send success message.
		wp_send_json_success( array( 'message' => esc_html__( 'Social Warfare shares imported successfully.', 'sinatra' ) ) );
	}

}
new SocialSnap_SW_Compatibility( $sw_options );
