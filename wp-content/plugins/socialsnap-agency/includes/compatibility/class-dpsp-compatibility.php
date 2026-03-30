<?php
/**
 * Handles compatibility with Social Pug plugin.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.1.8
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */

// Check if Social Pug was installed & settings exist in database.
$socialsnap_dpsp_options = get_option( 'dpsp_settings' );

if ( empty( $socialsnap_dpsp_options ) ) {
	return;
}

class SocialSnap_DPSP_Compatibility {

	/**
	 * Options array.
	 *
	 * @since 1.1.8
	 * @var array
	 */
	private $options = array();

	/**
	 * Primary class constructor.
	 *
	 * @since 1.1.8
	 */
	public function __construct( $options ) {

		$this->dpsp_options       = $options;
		$this->socialsnap_options = array();

		// Add import button to the settings panel.
		add_filter( 'socialsnap_plugin_migration', array( $this, 'add_to_settings' ) );

		// AJAX callback to import settings.
		add_action( 'wp_ajax_socialsnap_dpsp_migrate', array( $this, 'migrate_settings' ) );

		// AJAX callback to import shares.
		add_action( 'wp_ajax_socialsnap_dpsp_migrate_shares', array( $this, 'migrate_shares' ) );

		// Shortcode compatibility.
		if ( ! shortcode_exists( 'socialpug_tweet' ) ) {
			add_shortcode( 'socialpug_tweet', array( $this, 'click_to_tweet_shortcode' ) );
		}
	}

	/**
	 * Add button to the settings panel which imports settings via AJAX.
	 *
	 * @since 1.1.8
	 */
	public function add_to_settings( $fields ) {

		$fields['ss_dpsp_migrate'] = array(
			'id'      => 'ss_dpsp_migrate',
			'name'    => __( 'Social Pug', 'socialsnap' ),
			'desc'    => __( 'Import Social Pug settings', 'socialsnap' ),
			'text'    => __( 'Import Settings', 'socialsnap' ),
			'type'    => 'button',
			'action'  => 'socialsnap_dpsp_migrate',
			'confirm' => esc_html__( 'Are you sure you want to import Social Pug settings? This will overwrite current plugin settings and individual post settings. Please make a full backup of your website before you start the migration process.', 'socialsnap' ),
		);

		$fields['ss_dpsp_migrate_shares'] = array(
			'id'      => 'ss_dpsp_migrate_shares',
			'text'    => __( 'Import Share Counts', 'socialsnap' ),
			'type'    => 'button',
			'action'  => 'socialsnap_dpsp_migrate_shares',
			'confirm' => esc_html__( 'Are you sure you want to import Social Pug share counts? Share counts will be imported only if they are greater than counts stored by Social Snap. Please make a full backup of your website before you start the import process.', 'socialsnap' ),
		);

		return $fields;
	}

	/**
	 * Migrate options & post meta.
	 *
	 * @since  1.1.8
	 * @return void
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

		set_time_limit( 300 );

		$this->socialsnap_options = get_option( SOCIALSNAP_SETTINGS );

		$this->import_settings();
		$this->import_floating_sidebar_settings();
		$this->import_inline_content_settings();
		$this->import_sticky_bar_settings();

		update_option( SOCIALSNAP_SETTINGS, $this->socialsnap_options );

		$this->import_post_meta();

		// Send success message.
		wp_send_json_success( array( 'message' => esc_html__( 'Social Pug settings imported successfully.', 'sinatra' ) ) );
	}

	/**
	 * Import settings.
	 * Populates compatible Social Snap settings.
	 *
	 * @since  1.1.8
	 * @return void
	 */
	public function import_settings() {

		$dpsp_settings = get_option( 'dpsp_settings', array() );

		// HTTP share recovery.
		if ( isset( $dpsp_settings['http_and_https_share_counts'] ) && 'yes' === $dpsp_settings['http_and_https_share_counts'] ) {
			$this->socialsnap_options['ss_share_recovery']          = 'on';
			$this->socialsnap_options['ss_share_recovery_protocol'] = 'http';
		}

		// Permalink structure share recovery.
		if ( isset( $dpsp_settings['previous_permalink_share_counts'] ) && 'yes' === $dpsp_settings['previous_permalink_share_counts'] ) {

			$this->socialsnap_options['ss_share_recovery']               = 'on';
			$this->socialsnap_options['ss_share_recovery_format']        = 'unaltered';
			$this->socialsnap_options['ss_share_recovery_custom_format'] = '';

			// Permalink structure.
			if ( isset( $dpsp_settings['previous_permalink_structure'] ) ) {

				switch ( $dpsp_settings['previous_permalink_structure'] ) {
					case 'plain':
						$this->socialsnap_options['ss_share_recovery_format'] = 'plain';
						break;

					case '/%year%/%monthnum%/%day%/%postname%/':
						$this->socialsnap_options['ss_share_recovery_format'] = 'day-name';
						break;

					case '/%year%/%monthnum%/%postname%/':
						$this->socialsnap_options['ss_share_recovery_format'] = 'month-name';
						break;

					case '/archives/%post_id%':
						$this->socialsnap_options['ss_share_recovery_format'] = 'numeric';
						break;

					case '/%postname%/':
						$this->socialsnap_options['ss_share_recovery_format'] = 'post-name';
						break;

					case 'custom':
						$this->socialsnap_options['ss_share_recovery_format'] = 'custom';
						break;

					default:
						$this->socialsnap_options['ss_share_recovery_format'] = 'unaltered';
						break;
				}
			}

			// // Permalink structure custom.
			if ( isset( $dpsp_settings['previous_permalink_structure_custom'] ) ) {
				$this->socialsnap_options['ss_share_recovery_custom_format'] = $dpsp_settings['previous_permalink_structure_custom'];
			}
		}

		// Domain share recovery.
		if ( isset( $dpsp_settings['previous_domain_share_counts'] ) && 'yes' === $dpsp_settings['previous_domain_share_counts'] ) {

			$this->socialsnap_options['ss_share_recovery']                = 'on';
			$this->socialsnap_options['ss_share_recovery_domain']         = 'on';
			$this->socialsnap_options['ss_share_recovery_prev_domain']    = '';
			$this->socialsnap_options['ss_share_recovery_current_domain'] = '';

			if ( isset( $dpsp_settings['previous_base_domain'] ) ) {
				$this->socialsnap_options['ss_share_recovery_prev_domain']    = $dpsp_settings['previous_base_domain'];
				$this->socialsnap_options['ss_share_recovery_current_domain'] = preg_replace( '#^www\.(.+\.)#i', '$1', parse_url( get_site_url(), PHP_URL_HOST ) );
			}
		}

		// UTM tracking.
		if ( isset( $dpsp_settings['utm_tracking'] ) && $dpsp_settings['utm_tracking'] ) {
			$this->socialsnap_options['ss_utm_tracking'] = 'on';

			if ( isset( $dpsp_settings['utm_source'] ) ) {
				$this->socialsnap_options['ss_utm_tracking_source'] = $dpsp_settings['utm_source'];
			}

			if ( isset( $dpsp_settings['utm_medium'] ) ) {
				$this->socialsnap_options['ss_utm_tracking_medium'] = $dpsp_settings['utm_medium'];
			}

			if ( isset( $dpsp_settings['utm_campaign'] ) ) {
				$this->socialsnap_options['ss_utm_tracking_campaign'] = $dpsp_settings['utm_campaign'];
			}
		}

		// Twitter share provider.
		if ( isset( $dpsp_settings['twitter_share_counts'] ) && $dpsp_settings['twitter_share_counts'] ) {
			if ( isset( $dpsp_settings['twitter_share_counts_provider'] ) && 'twitcount' === $dpsp_settings['twitter_share_counts_provider'] ) {
				$this->socialsnap_options['ss_ss_twitter_count_provider'] = 'twitcount';
			}
		}

		// Social meta tags.
		if ( isset( $dpsp_settings['disable_meta_tags'] ) && $dpsp_settings['disable_meta_tags'] ) {
			$this->socialsnap_options['ss_smt_enable'] = false;
		}

		// Twitter username.
		if ( isset( $dpsp_settings['twitter_username'] ) ) {
			$this->socialsnap_options['ss_twitter_username'] = $dpsp_settings['twitter_username'];
		}

		// Facebook username.
		if ( isset( $dpsp_settings['pinterest_username'] ) ) {
			$this->socialsnap_options['ss_pinterest_username'] = $dpsp_settings['pinterest_username'];
		}

		if ( isset( $dpsp_settings['ctt_style'] ) ) {
			switch ( intval( $dpsp_settings['ctt_style'] ) ) {
				case 1:
					$this->socialsnap_options['ss_ctt_preview_style'] = 6;
					break;

				case 2:
					$this->socialsnap_options['ss_ctt_preview_style'] = 4;
					break;

				case 3:
				case 4:
					$this->socialsnap_options['ss_ctt_preview_style'] = 1;
					break;

				case 5:
					$this->socialsnap_options['ss_ctt_preview_style'] = 2;
					break;

				default:
					$this->socialsnap_options['ss_ctt_preview_style'] = 1;
					break;
			}
		}
	}

	/**
	 * Import floating sidebar settings.
	 *
	 * @since  1.1.8
	 * @return void
	 */
	private function import_floating_sidebar_settings() {

		$dpsp_settings = get_option( 'dpsp_location_sidebar', array() );

		if ( empty( $dpsp_settings ) ) {
			return;
		}

		if ( isset( $dpsp_settings['active'] ) && $dpsp_settings['active'] ) {
			$this->socialsnap_options['ss_ss_sidebar_enabled'] = 'on';
		} else {
			$this->socialsnap_options['ss_ss_sidebar_enabled'] = false;
		}

		$this->socialsnap_options['ss_ss_sidebar_all_networks'] = false;

		// Floating Sidebar display.
		if ( isset( $dpsp_settings['display'] ) ) {

			// Shape.
			if ( isset( $dpsp_settings['display']['shape'] ) ) {
				if ( 'rectangular' === $dpsp_settings['display']['shape'] ) {
					$this->socialsnap_options['ss_ss_sidebar_button_shape'] = 'rectangle';
				} else {
					$this->socialsnap_options['ss_ss_sidebar_button_shape'] = $dpsp_settings['display']['shape'];
				}
			}

			// Size.
			if ( isset( $dpsp_settings['display']['size'] ) ) {
				if ( 'medium' === $dpsp_settings['display']['size'] ) {
					$this->socialsnap_options['ss_ss_sidebar_button_size'] = 'regular';
				} else {
					$this->socialsnap_options['ss_ss_sidebar_button_size'] = $dpsp_settings['display']['size'];
				}
			} else {
				$this->socialsnap_options['ss_ss_sidebar_button_size'] = 'small';
			}

			// Position.
			if ( isset( $dpsp_settings['display']['position'] ) ) {
				$this->socialsnap_options['ss_ss_sidebar_position'] = $dpsp_settings['display']['position'];
			}

			// Hover Animation.
			if ( isset( $dpsp_settings['display']['icon_animation'] ) && 'yes' === $dpsp_settings['display']['icon_animation'] ) {
				$this->socialsnap_options['ss_ss_sidebar_hover_animation'] = 'ss-hover-animation-2';
			} else {
				$this->socialsnap_options['ss_ss_sidebar_hover_animation'] = 'ss-hover-animation-fade';
			}

			// Label Tooltip.
			if ( isset( $dpsp_settings['display']['show_labels'] ) && 'yes' === $dpsp_settings['display']['show_labels'] ) {
				$this->socialsnap_options['ss_ss_sidebar_label_tooltip'] = 'on';
			} else {
				$this->socialsnap_options['ss_ss_sidebar_label_tooltip'] = false;
			}

			// Button Spacing.
			if ( isset( $dpsp_settings['display']['spacing'] ) && 'yes' === $dpsp_settings['display']['spacing'] ) {
				$this->socialsnap_options['ss_ss_sidebar_button_spacing'] = 'on';
			} else {
				$this->socialsnap_options['ss_ss_sidebar_button_spacing'] = false;
			}

			// Entrance animation.
			if ( isset( $dpsp_settings['display']['intro_animation'] ) ) {
				$dpsp_settings_animation = intval( $dpsp_settings['display']['intro_animation'] );

				if ( 1 === $dpsp_settings_animation ) {
					$dpsp_settings_animation = 'fade';
				} elseif ( 2 === $dpsp_settings_animation ) {
					$dpsp_settings_animation = 'slide';
				} else {
					$dpsp_settings_animation = 'none';
				}

				$this->socialsnap_options['ss_ss_sidebar_entrance_animation'] = $dpsp_settings_animation;
			}

			// Show on mobile.
			if ( isset( $dpsp_settings['display']['show_mobile'] ) && 'yes' === $dpsp_settings['display']['show_mobile'] ) {
				$this->socialsnap_options['ss_ss_sidebar_hide_on_mobile'] = false;
			} else {
				$this->socialsnap_options['ss_ss_sidebar_hide_on_mobile'] = 'on';
			}

			// Share counts.
			if ( isset( $dpsp_settings['display']['show_count'] ) && 'yes' === $dpsp_settings['display']['show_count'] ) {
				$this->socialsnap_options['ss_ss_sidebar_share_count'] = 'on';
			} else {
				$this->socialsnap_options['ss_ss_sidebar_share_count'] = false;
			}

			// Total Share count.
			if ( isset( $dpsp_settings['display']['show_count_total'] ) && 'yes' === $dpsp_settings['display']['show_count_total'] ) {
				$this->socialsnap_options['ss_ss_sidebar_total_count'] = 'on';
			} else {
				$this->socialsnap_options['ss_ss_sidebar_total_count'] = false;
			}

			// Min share count.
			if ( isset( $dpsp_settings['display']['minimum_count'] ) ) {
				$this->socialsnap_options['ss_ss_sidebar_min_count'] = $dpsp_settings['display']['minimum_count'];
			}

			if ( isset( $dpsp_settings['display']['custom_color'] ) && $dpsp_settings['display']['custom_color'] ) {
				$this->socialsnap_options['ss_ss_sidebar_custom_colors']           = 'on';
				$this->socialsnap_options['ss_ss_sidebar_button_background_color'] = $dpsp_settings['display']['custom_color'];
			}

			if ( isset( $dpsp_settings['display']['custom_hover_color'] ) && $dpsp_settings['display']['custom_hover_color'] ) {
				$this->socialsnap_options['ss_ss_sidebar_custom_colors']                 = 'on';
				$this->socialsnap_options['ss_ss_sidebar_button_background_hover_color'] = $dpsp_settings['display']['custom_hover_color'];
			}
		}

		// Post types.
		if ( isset( $dpsp_settings['post_type_display'] ) ) {
			foreach ( $this->socialsnap_options['ss_ss_sidebar_post_types'] as $key => $value ) {
				$this->socialsnap_options['ss_ss_sidebar_post_types'][ $key ] = false;
			}

			foreach ( $dpsp_settings['post_type_display'] as $key => $value ) {
				if ( isset( $this->socialsnap_options['ss_ss_sidebar_post_types'][ $value ] ) ) {
					$this->socialsnap_options['ss_ss_sidebar_post_types'][ $value ] = 'on';
				}
			}
		}
	}

	/**
	 * Import inline content settings.
	 *
	 * @since  1.1.8
	 * @return void
	 */
	private function import_inline_content_settings() {

		$dpsp_settings = get_option( 'dpsp_location_content', array() );

		if ( empty( $dpsp_settings ) ) {
			return;
		}

		if ( isset( $dpsp_settings['active'] ) && $dpsp_settings['active'] ) {
			$this->socialsnap_options['ss_ss_inline_content_enabled'] = 'on';
		} else {
			$this->socialsnap_options['ss_ss_inline_content_enabled'] = false;
		}

		$this->socialsnap_options['ss_ss_inline_content_all_networks']      = false;
		$this->socialsnap_options['ss_ss_inline_content_position']          = 'stretched';
		$this->socialsnap_options['ss_ss_inline_content_button_label']      = 'none';
		$this->socialsnap_options['ss_ss_inline_content_total_share_style'] = 'icon';

		if ( ! empty( $dpsp_settings['display'] ) ) {

			// Shape.
			if ( isset( $dpsp_settings['display']['shape'] ) ) {
				if ( 'rectangular' === $dpsp_settings['display']['shape'] ) {
					$this->socialsnap_options['ss_ss_inline_content_button_shape'] = 'rectangle';
				} else {
					$this->socialsnap_options['ss_ss_inline_content_button_shape'] = $dpsp_settings['display']['shape'];
				}
			}

			// Size.
			if ( isset( $dpsp_settings['display']['size'] ) ) {
				if ( 'medium' === $dpsp_settings['display']['size'] ) {
					$this->socialsnap_options['ss_ss_inline_content_button_size'] = 'regular';
				} else {
					$this->socialsnap_options['ss_ss_inline_content_button_size'] = $dpsp_settings['display']['size'];
				}
			} else {
				$this->socialsnap_options['ss_ss_inline_content_button_size'] = 'small';
			}

			// Position.
			if ( isset( $dpsp_settings['display']['position'] ) ) {
				$dpsp_settings_position = $dpsp_settings['display']['position'];

				if ( 'top' === $dpsp_settings_position ) {
					$dpsp_settings_position = 'above';
				} elseif ( 'both' === $dpsp_settings_position ) {
					$dpsp_settings_position = 'both';
				} else {
					$dpsp_settings_position = 'below';
				}

				$this->socialsnap_options['ss_ss_inline_content_location'] = $dpsp_settings_position;
			}

			// Share label.
			if ( isset( $dpsp_settings['display']['message'] ) ) {
				$this->socialsnap_options['ss_ss_inline_content_share_label'] = $dpsp_settings['display']['message'];
			}

			// Button label.
			if ( isset( $dpsp_settings['display']['show_labels'] ) && 'yes' === $dpsp_settings['display']['show_labels'] ) {

				if ( isset( $dpsp_settings['display']['show_count'] ) && 'yes' === $dpsp_settings['display']['show_count'] ) {
					$this->socialsnap_options['ss_ss_inline_content_button_label'] = 'both';
				} else {
					$this->socialsnap_options['ss_ss_inline_content_button_label'] = 'label';
				}
			}

			// Button Spacing.
			if ( isset( $dpsp_settings['display']['spacing'] ) && 'yes' === $dpsp_settings['display']['spacing'] ) {
				$this->socialsnap_options['ss_ss_inline_content_button_spacing'] = 'on';
			} else {
				$this->socialsnap_options['ss_ss_inline_content_button_spacing'] = false;
			}

			// Show on mobile.
			if ( isset( $dpsp_settings['display']['show_mobile'] ) && 'yes' === $dpsp_settings['display']['show_mobile'] ) {
				$this->socialsnap_options['ss_ss_inline_content_hide_on_mobile'] = false;
			} else {
				$this->socialsnap_options['ss_ss_inline_content_hide_on_mobile'] = 'on';
			}

			// Share counts.
			if ( isset( $dpsp_settings['display']['show_count'] ) && 'yes' === $dpsp_settings['display']['show_count'] ) {
				if ( isset( $dpsp_settings['display']['show_labels'] ) && 'yes' === $dpsp_settings['display']['show_labels'] ) {
					$this->socialsnap_options['ss_ss_inline_content_button_label'] = 'both';
				} else {
					$this->socialsnap_options['ss_ss_inline_content_button_label'] = 'count';
				}
			}

			// Total Share count.
			if ( isset( $dpsp_settings['display']['show_count_total'] ) && 'yes' === $dpsp_settings['display']['show_count_total'] ) {
				$this->socialsnap_options['ss_ss_inline_content_total_count'] = 'on';
			} else {
				$this->socialsnap_options['ss_ss_inline_content_total_count'] = false;
			}

			// Total Share count.
			if ( isset( $dpsp_settings['display']['total_count_position'] ) ) {
				if ( 'before' === $dpsp_settings['display']['total_count_position'] ) {
					$this->socialsnap_options['ss_ss_inline_content_total_share_placement'] = 'left';
				} else {
					$this->socialsnap_options['ss_ss_inline_content_total_share_placement'] = 'right';
				}
			}

			// Custom colors.
			if ( isset( $dpsp_settings['display']['custom_color'] ) && $dpsp_settings['display']['custom_color'] ) {
				$this->socialsnap_options['ss_ss_inline_content_custom_colors']           = 'on';
				$this->socialsnap_options['ss_ss_inline_content_button_background_color'] = $dpsp_settings['display']['custom_color'];
			}

			if ( isset( $dpsp_settings['display']['custom_hover_color'] ) && $dpsp_settings['display']['custom_hover_color'] ) {
				$this->socialsnap_options['ss_ss_inline_content_custom_colors']                 = 'on';
				$this->socialsnap_options['ss_ss_inline_content_button_background_hover_color'] = $dpsp_settings['display']['custom_hover_color'];
			}

			// Columns.
			if ( isset( $dpsp_settings['display']['column_count'] ) && 'auto' === $dpsp_settings['display']['column_count'] ) {
				$this->socialsnap_options['ss_ss_inline_content_position'] = 'left';
			}

			// Min share count.
			if ( isset( $dpsp_settings['display']['minimum_count'] ) ) {
				$this->socialsnap_options['ss_ss_inline_content_min_count'] = $dpsp_settings['display']['minimum_count'];
			}
		}

		// Post types.
		if ( isset( $dpsp_settings['post_type_display'] ) ) {

			foreach ( $this->socialsnap_options['ss_ss_inline_content_post_types'] as $key => $value ) {
				$this->socialsnap_options['ss_ss_inline_content_post_types'][ $key ] = false;
			}

			foreach ( $dpsp_settings['post_type_display'] as $key => $value ) {
				if ( isset( $this->socialsnap_options['ss_ss_inline_content_post_types'][ $value ] ) ) {
					$this->socialsnap_options['ss_ss_inline_content_post_types'][ $value ] = 'on';
				}
			}
		}
	}

	/**
	 * Import sticky bar settings
	 *
	 * @since  1.1.8
	 * @return void
	 */
	private function import_sticky_bar_settings() {

		$dpsp_settings = get_option( 'dpsp_location_sticky_bar', array() );

		if ( empty( $dpsp_settings ) ) {
			return;
		}

		if ( isset( $dpsp_settings['active'] ) && $dpsp_settings['active'] ) {
			$this->socialsnap_options['ss_ss_sticky_bar_enabled'] = 'on';
		} else {
			$this->socialsnap_options['ss_ss_sticky_bar_enabled'] = false;
		}

		$this->socialsnap_options['ss_ss_sticky_bar_all_networks'] = false;
		$this->socialsnap_options['ss_ss_sticky_bar_style']        = 'stretched';
		$this->socialsnap_options['ss_ss_sticky_bar_view_count']   = false;

		if ( ! empty( $dpsp_settings['display'] ) ) {

			// Shape.
			if ( isset( $dpsp_settings['display']['shape'] ) ) {
				if ( 'rectangular' === $dpsp_settings['display']['shape'] ) {
					$this->socialsnap_options['ss_ss_inline_content_button_shape'] = 'rectangle';
				} else {
					$this->socialsnap_options['ss_ss_inline_content_button_shape'] = $dpsp_settings['display']['shape'];
				}
			}

			// Visibility.
			if ( isset( $dpsp_settings['display']['show_on_device'] ) ) {

				if ( 'mobile' === $dpsp_settings['display']['show_on_device'] ) {
					$dpsp_settings['display']['show_on_device'] = 'ss-hide-on-desktop';
				} elseif ( 'desktop' === $dpsp_settings['display']['show_on_device'] ) {
					$dpsp_settings['display']['show_on_device'] = 'ss-hide-on-mobile';
				} else {
					$dpsp_settings['display']['show_on_device'] = 'ss-always-visible';
				}

				$this->socialsnap_options['ss_ss_sticky_bar_visibility'] = $dpsp_settings['display']['show_on_device'];
			}

			// Position.
			if ( isset( $dpsp_settings['display']['position_mobile'] ) ) {
				$this->socialsnap_options['ss_ss_sticky_bar_position'] = $dpsp_settings['display']['position_mobile'];
			} elseif ( isset( $dpsp_settings['display']['position_desktop'] ) ) {
				$this->socialsnap_options['ss_ss_sticky_bar_position'] = $dpsp_settings['display']['position_desktop'];
			}

			// Entrance animation.
			if ( isset( $dpsp_settings['display']['intro_animation'] ) ) {

				$dpsp_animation = intval( $dpsp_settings['display']['intro_animation'] );

				if ( 1 === $dpsp_animation ) {
					$dpsp_animation = 'fade';
				} elseif ( 2 === $dpsp_animation ) {
					$dpsp_animation = 'slide';
				} else {
					$dpsp_animation = 'none';
				}

				$this->socialsnap_options['ss_ss_sticky_bar_entrance_animation'] = $dpsp_animation;
			}

			// Show after scroll.
			if ( isset( $dpsp_settings['display']['show_after_scrolling'] ) && 'yes' === $dpsp_settings['display']['show_after_scrolling'] && isset( $dpsp_settings['display']['scroll_distance'] ) ) {
				$this->socialsnap_options['ss_ss_sticky_bar_show_after'] = 10 * intval( $dpsp_settings['display']['scroll_distance'] );
			}

			// Share counts.
			if ( isset( $dpsp_settings['display']['show_count'] ) && 'yes' === $dpsp_settings['display']['show_count'] ) {
				$this->socialsnap_options['ss_ss_sticky_bar_share_count'] = 'on';
			} else {
				$this->socialsnap_options['ss_ss_sticky_bar_share_count'] = false;
			}

			// Total Share count.
			if ( isset( $dpsp_settings['display']['show_count_total'] ) && 'yes' === $dpsp_settings['display']['show_count_total'] ) {
				$this->socialsnap_options['ss_ss_sticky_bar_total_count'] = 'on';
			} else {
				$this->socialsnap_options['ss_ss_sticky_bar_total_count'] = false;
			}

			// Min share count.
			if ( isset( $dpsp_settings['display']['minimum_count'] ) ) {
				$this->socialsnap_options['ss_ss_sticky_bar_min_count'] = $dpsp_settings['display']['minimum_count'];
			}

			// Custom colors.
			if ( isset( $dpsp_settings['display']['custom_color'] ) && $dpsp_settings['display']['custom_color'] ) {
				$this->socialsnap_options['ss_ss_sticky_bar_custom_colors']           = 'on';
				$this->socialsnap_options['ss_ss_sticky_bar_button_background_color'] = $dpsp_settings['display']['custom_color'];
			}

			if ( isset( $dpsp_settings['display']['custom_hover_color'] ) && $dpsp_settings['display']['custom_hover_color'] ) {
				$this->socialsnap_options['ss_ss_sticky_bar_custom_colors']                 = 'on';
				$this->socialsnap_options['ss_ss_sticky_bar_button_background_hover_color'] = $dpsp_settings['display']['custom_hover_color'];
			}
		}

		// Post types.
		if ( isset( $dpsp_settings['post_type_display'] ) ) {

			foreach ( $this->socialsnap_options['ss_ss_sticky_bar_post_types'] as $key => $value ) {
				$this->socialsnap_options['ss_ss_sticky_bar_post_types'][ $key ] = false;
			}

			foreach ( $dpsp_settings['post_type_display'] as $key => $value ) {
				if ( isset( $this->socialsnap_options['ss_ss_sticky_bar_post_types'][ $value ] ) ) {
					$this->socialsnap_options['ss_ss_sticky_bar_post_types'][ $value ] = 'on';
				}
			}
		}
	}

	/**
	 * Import post meta. Populates post meta from DPSP metaboxes.
	 *
	 * @since  1.1.8
	 * @return void
	 */
	public function import_post_meta() {

		$meta_key_map = array(
			'dpsp_pinterest_hidden_images' => 'ss_image_pinterest_multiple',
			'pin_description'              => 'ss_pinterest_description',
			'pin_nopin'                    => 'ss_no_pin',
			'dpsp_share_options'           => array(
				'custom_title'                 => 'ss_smt_title',
				'custom_description'           => 'ss_smt_description',
				'custom_image'                 => 'ss_smt_image',
				'custom_image_pinterest'       => 'ss_image_pinterest',
				'custom_description_pinterest' => 'ss_pinterest_description',
				'custom_tweet'                 => 'ss_ss_custom_tweet',
			),
		);

		foreach ( $meta_key_map as $old_key => $new_key ) {
			$this->import_post_meta_entry( $old_key, $new_key );
		}
	}

	/**
	 * Import post meta. Populates post meta from MashShare metaboxes.
	 *
	 * @since  1.1.8
	 * @param  string $old_key Meta key from previous plugin.
	 * @param  string $new_key Meta key in current plugin.
	 * @return void
	 */
	public function import_post_meta_entry( $old_key, $new_key ) {

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

				$meta_value = $row->meta_value;

				if ( is_array( $new_key ) ) {

					$meta_value = unserialize( $meta_value );

					foreach ( $new_key as $key => $ss_key ) {

						if ( ! isset( $meta_value[ $key ] ) ) {
							continue;
						}

						if ( is_array( $meta_value[ $key ] ) ) {
							update_post_meta( $row->post_id, $ss_key, $meta_value[ $key ]['id'] );
						} else {
							update_post_meta( $row->post_id, $ss_key, $meta_value[ $key ] );
						}
					}
				} else {
					update_post_meta( $row->post_id, $new_key, $meta_value );
				}
			}
		}
	}

	/**
	 * Migrate shares.
	 *
	 * @since 1.1.8
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

		// Social Pug registered social networks.
		$networks = array(
			'facebook',
			'pinterest',
			'linkedin',
			'reddit',
			'vkontakte',
			'buffer',
			'tumblr',
			'yummly',
		);

		// Map meta keys with SS meta keys.
		$meta_key_map = array(
			'dpsp_networks_shares_total' => 'ss_total_share_count',
			'dpsp_networks_shares'       => array(),
		);

		if ( ! empty( $networks ) ) {
			foreach ( $networks as $network ) {
				$meta_key_map['dpsp_networks_shares'][ $network ] = 'ss_ss_share_count_' . $network;
			}
		}

		foreach ( $meta_key_map as $old_key => $new_key ) {
			$this->import_post_meta_entry( $old_key, $new_key );
		}

		// Send success message.
		wp_send_json_success( array( 'message' => esc_html__( 'Social Pug shares imported successfully.', 'sinatra' ) ) );
	}

	/**
	 * Click to Tweet shortcode.
	 *
	 * @since 1.1.8
	 */
	public function click_to_tweet_shortcode( $atts ) {

		$shortcode = '[ss_click_to_tweet';

		if ( isset( $atts['style'] ) && '' !== $atts['style'] ) {
			$shortcode .= ' style="' . $atts['style'] . '"';
		}

		if ( isset( $atts['tweet'] ) && '' !== $atts['tweet'] ) {
			$shortcode .= ' tweet="' . $atts['tweet'] . '"';
		}

		if ( isset( $atts['display_tweet'] ) && '' !== $atts['display_tweet'] ) {
			$shortcode .= ' content="' . $atts['display_tweet'] . '"';
		} else {
			$shortcode .= ' content="' . $atts['tweet'] . '"';
		}

		if ( isset( $atts['remove_username'] ) && 'yes' == $atts['remove_username'] ) {
			$shortcode .= ' via="false"';
		} else {
			$shortcode .= ' via="true"';
		}

		if ( isset( $atts['remove_url'] ) && 'yes' == $atts['remove_url'] ) {
			$shortcode .= ' link="false"';
		} else {
			$shortcode .= ' link="true"';
		}

		$shortcode .= ']';

		ob_start();

		echo do_shortcode( $shortcode );

		return ob_get_clean();
	}

}

new SocialSnap_DPSP_Compatibility( $socialsnap_dpsp_options );
