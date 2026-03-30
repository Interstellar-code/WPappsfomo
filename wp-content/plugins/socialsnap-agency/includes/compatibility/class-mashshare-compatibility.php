<?php
/**
 * Handles compatibiliy with MashShare plugin.
 *
 * @package   Social Snap
 * @author    Social Snap
 * @since     1.1.6
 * @license   GPL-3.0+
 * @copyright Copyright (c) 2019, Social Snap LLC
 */

// Check if MashShare was installed & settings exist in database.
$socialsnap_mashsb_options = get_option( 'mashsb_settings' );

if ( empty( $socialsnap_mashsb_options ) ) {
	return;
}

class SocialSnap_MashShare_Compatibility {

	/**
	 * Options array.
	 *
	 * @since 1.1.6
	 * @var array
	 */
	private $options = array();

	/**
	 * Primary class constructor.
	 *
	 * @since 1.1.6
	 */
	public function __construct( $options ) {

		$this->options = $options;

		// Add import button to the settings panel.
		add_filter( 'socialsnap_plugin_migration', array( $this, 'add_to_settings' ) );

		// AJAX callback to import settings.
		add_action( 'wp_ajax_socialsnap_mashshare_migrate', array( $this, 'migrate_settings' ) );

		// AJAX callback to import shares.
		add_action( 'wp_ajax_socialsnap_mashshare_migrate_shares', array( $this, 'migrate_shares' ) );

		// Shortcode compatibility.
		if ( ! shortcode_exists( 'mashshare' ) ) {
			add_shortcode( 'mashshare', array( $this, 'share_buttons_shortcode' ) );
		}
	}

	/**
	 * Add button to the settings panel which imports settings via AJAX.
	 *
	 * @since  1.1.6
	 * @param  array $fields Settings fields array.
	 * @return array         Modifieed settings fields array.
	 */
	public function add_to_settings( $fields ) {

		$fields['ss_mashshare_migrate'] = array(
			'id'      => 'ss_mashshare_migrate',
			'name'    => __( 'MashShare', 'socialsnap' ),
			'desc'    => __( 'Import MashShare settings', 'socialsnap' ),
			'text'    => __( 'Import Settings', 'socialsnap' ),
			'type'    => 'button',
			'action'  => 'socialsnap_mashshare_migrate',
			'confirm' => esc_html__( 'Are you sure you want to import MashShare settings? This will overwrite current plugin settings and individual post settings. Please make a full backup of your website before you start the migration process.', 'socialsnap' ),
		);

		$fields['ss_mashshare_migrate_shares'] = array(
			'id'      => 'ss_mashshare_migrate_shares',
			'text'    => __( 'Import Share Counts', 'socialsnap' ),
			'type'    => 'button',
			'action'  => 'socialsnap_mashshare_migrate_shares',
			'confirm' => esc_html__( 'Are you sure you want to import MashShare share counts? Share counts will be imported only if they are greater than counts stored by Social Snap. Please make a full backup of your website before you start the import process.', 'socialsnap' ),
		);

		return $fields;
	}

	/**
	 * Migrate options & post meta.
	 *
	 * @since  1.1.6
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

		$this->import_settings();

		$meta_key_map = array(
			'mashsb_og_image'              => 'ss_smt_image',
			'mashsb_og_title'              => 'ss_smt_title',
			'mashsb_og_description'        => 'ss_smt_description',
			'mashsb_pinterest_image'       => 'ss_image_pinterest',
			'mashsb_custom_tweet'          => 'ss_ss_custom_tweet',
			'mashsb_pinterest_description' => 'ss_pinterest_description',
		);

		foreach ( $meta_key_map as $old_key => $new_key ) {
			$this->import_post_meta( $old_key, $new_key );
		}

		// Send success message.
		wp_send_json_success( array( 'message' => esc_html__( 'MashShare settings imported successfully.', 'sinatra' ) ) );
	}

	/**
	 * Import settings. Populates Social Snap settings from MashShare settings.
	 *
	 * @since  1.1.6
	 * @return void
	 */
	public function import_settings() {

		set_time_limit( 300 );

		$ss_settings = get_option( SOCIALSNAP_SETTINGS );
		$ms_settings = $this->options;

		// Social Share networks.
		if ( isset( $ms_settings['networks'] ) && ! empty( $ms_settings['networks'] ) ) {

			$ss_networks       = array();
			$ss_share_networks = socialsnap_get_social_share_networks();

			foreach ( $ms_settings['networks'] as $network ) {

				// Skip inactive networks.
				if ( ! isset( $network['status'] ) || ! $network['status'] ) {
					continue;
				}

				if ( 'mail' === $network['id'] ) {
					$network['id'] = 'envelope';
				} elseif ( 'vk' === $network['id'] ) {
					$network['id'] = 'vkontakte';
				}

				if ( array_key_exists( $network['id'], $ss_share_networks ) ) {

					$ss_networks[ $network['id'] ] = array(
						'text'               => $network['name'] ? esc_html( $network['name'] ) : socialsnap_get_network_name( $network['id'] ),
						'desktop_visibility' => 'on',
						'mobile_visibility'  => 'on',
					);

					$ss_networks['order'] = isset( $ss_networks['order'] ) ? $ss_networks['order'] . ',' . $network['id'] : $network['id'];
				}
			}

			if ( ! empty( $ss_networks ) ) {
				$ss_networks['order']                    = isset( $ss_networks['order'] ) ? trim( $ss_networks['order'], ',' ) : '';
				$ss_settings['ss_social_share_networks'] = $ss_networks;
			}
		}

		// Social Meta tags.
		if ( ( isset( $ms_settings['twitter_card'] ) && $ms_settings['twitter_card'] ) || ( isset( $ms_settings['open_graph'] ) && $ms_settings['open_graph'] ) ) {
			$ss_settings['ss_smt_enable'] = true;
		}

		// Display on for Inline buttons.
		if ( isset( $ms_settings['post_types'] ) && ! empty( $ms_settings['post_types'] ) ) {
			$ss_settings['ss_ss_inline_content_post_types'] = array();
			foreach ( $ms_settings['post_types'] as $key => $value ) {
				$ss_settings['ss_ss_inline_content_post_types'][ $key ] = 'on';
			}

			if ( isset( $ms_settings['frontpage'] ) && $ms_settings['frontpage'] ) {
				$ss_settings['ss_ss_inline_content_post_types']['home'] = 'on';
			}
		}

		if ( isset( $ms_settings['mashsharer_position'] ) && ! empty( $ms_settings['mashsharer_position'] ) ) {

			$position = $ms_settings['mashsharer_position'];

			if ( 'before' === $position ) {
				$ss_settings['ss_ss_inline_content_location'] = 'above';
			} elseif ( 'both' === $position ) {
				$ss_settings['ss_ss_inline_content_location'] = 'both';
			} else {
				$ss_settings['ss_ss_inline_content_location'] = 'below';
			}
		}

		update_option( SOCIALSNAP_SETTINGS, $ss_settings );
	}

	/**
	 * Share buttons shortcode.
	 *
	 * @since  1.1.6
	 * @return string Shortcode output.
	 */
	public function share_buttons_shortcode( $atts ) {

		$shortcode = '[ss_social_share';

		if ( isset( $atts['networks'] ) && '' !== $atts['networks'] ) {
			$shortcode .= ' networks="' . $atts['networks'] . '"';
		}

		if ( isset( $atts['size'] ) && '' !== $atts['size'] ) {
			$atts['size'] = 'medium' === $atts['size'] ? 'regular' : $atts['size'];
			$shortcode   .= ' size="' . $atts['size'] . '"';
		} else {
			$shortcode .= ' size="regular"';
		}

		if ( isset( $atts['url'] ) && '' !== $atts['url'] ) {
			$shortcode .= ' share_target="' . $atts['url'] . '"';
		}

		if ( isset( $atts['align'] ) && '' !== $atts['align'] ) {
			$shortcode .= ' total_share_placement="' . $atts['align'] . '"';
		}

		if ( isset( $atts['shares'] ) ) {
			$shares     = 'true' === $atts['shares'] ? true : false;
			$shortcode .= ' total="' . intval( $atts['shares'] ) . '"';
		} else {
			$shortcode .= ' total="1"';
		}

		if ( isset( $atts['icons'] ) && true === boolval( $atts['icons'] ) ) {
			$shortcode .= ' labels="none" align="left"';
		} else {
			$shortcode .= ' labels="label" align="stretched"';
		}

		$shortcode .= ']';

		ob_start();

		echo do_shortcode( $shortcode );

		return ob_get_clean();
	}

	/**
	 * Import post meta. Populates post meta from MashShare metaboxes.
	 *
	 * @since  1.1.0
	 * @param  string $old_key Meta key from previous plugin.
	 * @param  string $new_key Meta key in current plugin.
	 * @return void
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
	 * Migrate MashShare shares.
	 *
	 * @since  1.1.6
	 * @return void
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

		// Map MashShare meta keys with SS meta keys.
		$meta_key_map = array(
			'mashsb_shares'     => 'ss_total_share_count',
			'mashsb_jsonshares' => '',
		);

		$ss_share_networks = socialsnap_get_social_share_networks();

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

					// Mashshare stores all share counts in one json encoded array under 'mashsb_jsonshares' meta key.
					if ( 'mashsb_jsonshares' === $old_key ) {

						$mashsb_share_counts = json_decode( $row->meta_value, true );

						if ( ! empty( $mashsb_share_counts ) ) {

							foreach ( $mashsb_share_counts as $network => $count ) {

								if ( 'facebook_total' === $network ) {
									$network = 'facebook';
								} elseif ( 'vk' === $network ) {
									$network = 'vkontakte';
								}

								if ( array_key_exists( $network, $ss_share_networks ) ) {
									if ( intval( $count ) > intval( get_post_meta( $row->post_id, 'ss_ss_share_count_' . $network, true ) ) ) {
										update_post_meta( $row->post_id, 'ss_ss_share_count_' . $network, intval( $count ) );
									}
								}
							}
						}
					} else {
						if ( intval( get_post_meta( $row->post_id, $old_key, true ) ) > intval( get_post_meta( $row->post_id, $new_key, true ) ) ) {
							update_post_meta( $row->post_id, $new_key, $row->meta_value );
						}
					}
				}
			}
		}

		// Send success message.
		wp_send_json_success( array( 'message' => esc_html__( 'MashShare share counts imported successfully.', 'sinatra' ) ) );
	}

}

new SocialSnap_MashShare_Compatibility( $socialsnap_mashsb_options );
