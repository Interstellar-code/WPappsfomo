<?php
/**
 * Statistics page class.
 *
 * This page displays history of user interaction with the Social Snap elements.
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Statistics_Pro {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Allow disabling of statistics page.
		if ( ! apply_filters( 'socialsnap_statistics_page', true ) ) {
			add_action( 'admin_menu', array( $this, 'remove_from_admin_menu' ), 999 );
			return;
		}

		// Render Statistics pae
		add_action( 'socialsnap_statistics_output', array( $this, 'render' ) );

		// Ajax action to update chart
		add_action( 'wp_ajax_socialsnap_statistics_chart', array( $this, 'generate_chart_data' ) );

		// Ajax action to generate top performing
		add_action( 'wp_ajax_socialsnap_top_performing', array( $this, 'generate_top_performing' ) );

		// Enqueue additional assets required for this page
		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
	}

	/**
	 * Add scripts & styling for statistics page.
	 *
	 * @since 1.0.0
	 */
	public function load_assets() {

		// Enqueue on this page only.
		if ( ! isset( $_GET['page'] ) || 'socialsnap-statistics' !== $_GET['page'] ) {
			return;
		}

		// Enqueue chart.js on settings page
		wp_enqueue_script(
			'socialsnap-chart-js',
			SOCIALSNAP_PLUGIN_URL . 'pro/assets/js/chart.js',
			null,
			SOCIALSNAP_VERSION,
			true
		);

		// Enqueue chart.js on settings page
		wp_enqueue_script(
			'socialsnap-statistics-js',
			SOCIALSNAP_PLUGIN_URL . 'pro/assets/js/admin-statistics.js',
			null,
			SOCIALSNAP_VERSION,
			true
		);
	}

	/**
	 * Return statistics array containing required data.
	 *
	 * @since 1.0.0
	 */
	public function get_general_data() {

		// Data array
		$statistics         = array();
		$share_networks_api = socialsnap_get_social_share_networks_with_api();
		$share_networks     = array_keys( socialsnap_get_social_share_networks() );
		$share_networks     = array_diff( $share_networks, $share_networks_api );

		// All time share count
		$statistics['share_total']  = array_sum( socialsnap_get_meta_values( 'ss_total_share_count' ) );
		$statistics['share_total'] += intval( get_option( 'socialsnap_homepage_share_count_total' ) );

		// Share Click count from past week
		$statistics['shares_past_week'] = 0;

		// Click Tracking
		if ( is_array( $share_networks ) && ! empty( $share_networks ) ) {
			foreach ( $share_networks as $network ) {
				$statistics['shares_past_week'] += socialsnap()->stats->get_stats(
					array(
						'network'   => $network,
						'type'      => 'share',
						'date_from' => gmdate( 'Y-m-d', strtotime( '-6 days' ) ) . ' 00:00:00',
						'date_to'   => gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . ' 00:00:00',
					),
					true
				);
			}
		}

		// API Networks
		if ( is_array( $share_networks_api ) && ! empty( $share_networks_api ) ) {
			foreach ( $share_networks_api as $network ) {
				$statistics['shares_past_week'] += socialsnap()->stats->get_stats(
					array(
						'network'   => $network,
						'type'      => 'share_api',
						'date_from' => gmdate( 'Y-m-d', strtotime( '-6 days' ) ) . ' 00:00:00',
						'date_to'   => gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . ' 00:00:00',
					),
					true
				);
			}
		}

		// Calculate percentage increase from week before
		$shares_week_before = 0;

		// Click Tracking
		if ( is_array( $share_networks ) && ! empty( $share_networks ) ) {
			foreach ( $share_networks as $network ) {
				$shares_week_before += socialsnap()->stats->get_stats(
					array(
						'network'   => $network,
						'type'      => 'share',
						'date_from' => gmdate( 'Y-m-d', strtotime( '-13 days' ) ) . ' 00:00:00',
						'date_to'   => gmdate( 'Y-m-d', strtotime( '-6 days' ) ) . ' 00:00:00',
					),
					true
				);
			}
		}

		// API Networks
		if ( is_array( $share_networks_api ) && ! empty( $share_networks_api ) ) {
			foreach ( $share_networks_api as $network ) {
				$shares_week_before += socialsnap()->stats->get_stats(
					array(
						'network'   => $network,
						'type'      => 'share_api',
						'date_from' => gmdate( 'Y-m-d', strtotime( '-13 days' ) ) . ' 00:00:00',
						'date_to'   => gmdate( 'Y-m-d', strtotime( '-6 days' ) ) . ' 00:00:00',
					),
					true
				);
			}
		}

		$statistics['shares_increase'] = ( $statistics['shares_past_week'] - $shares_week_before ) * 100 / ( 0 == $shares_week_before ? 1 : $shares_week_before );
		$statistics['shares_increase'] = number_format( $statistics['shares_increase'], 2, '.', ',' );

		// All time click to tweet count
		$statistics['ctt_total'] = socialsnap()->stats->get_stats(
			array(
				'type' => 'ctt',
			),
			true
		);

		// CTT count from past week
		$statistics['ctt_past_week'] = socialsnap()->stats->get_stats(
			array(
				'type'      => 'ctt',
				'date_from' => gmdate( 'Y-m-d', strtotime( '-6 days' ) ) . ' 00:00:00',
				'date_to'   => gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . ' 00:00:00',
			),
			true
		);

		// Calculate percentage increase from week before
		$ctt_week_before = socialsnap()->stats->get_stats(
			array(
				'type'      => 'ctt',
				'date_from' => gmdate( 'Y-m-d', strtotime( '-13 days' ) ),
				'date_to'   => gmdate( 'Y-m-d', strtotime( '-6 days' ) ),
			),
			true
		);

		$statistics['ctt_increase'] = ( $statistics['ctt_past_week'] - $ctt_week_before ) * 100 / ( 0 == $ctt_week_before ? 1 : $ctt_week_before );
		$statistics['ctt_increase'] = number_format( $statistics['ctt_increase'], 2, '.', ',' );

		// All time likes count
		$statistics['like_total'] = socialsnap()->stats->get_stats(
			array(
				'type' => 'like',
			),
			true
		);

		// Click to likes count from past week
		$statistics['like_past_week'] = socialsnap()->stats->get_stats(
			array(
				'type'      => 'like',
				'date_from' => gmdate( 'Y-m-d', strtotime( '-6 days' ) ) . ' 00:00:00',
				'date_to'   => gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . ' 00:00:00',
			),
			true
		);

		// Calculate percentage increase from week before
		$like_week_before = socialsnap()->stats->get_stats(
			array(
				'type'      => 'like',
				'date_from' => gmdate( 'Y-m-d', strtotime( '-13 days' ) ),
				'date_to'   => gmdate( 'Y-m-d', strtotime( '-6 days' ) ),
			),
			true
		);

		$statistics['like_increase'] = ( $statistics['like_past_week'] - $like_week_before ) * 100 / ( 0 == $like_week_before ? 1 : $like_week_before );
		$statistics['like_increase'] = number_format( $statistics['like_increase'], 2, '.', ',' );

		return apply_filters( 'socialsnap_statistics', $statistics );
	}

	/**
	 * Generate data for the Statistics Chart
	 *
	 * @since 1.0.0
	 */
	public function generate_chart_data() {

		check_ajax_referer( 'socialsnap_chart_data', 'security' );

		if ( ! isset( $_POST['ss_stats_data'] ) ) {
			wp_send_json_error();
		}

		$filters = str_replace( '\\', '', sanitize_text_field( $_POST['ss_stats_data'] ) );		
		$filters = json_decode( $filters, true );

		if ( ! is_array( $filters ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid chart data', 'socialsnap' ) ) );
		}

		$chart = $this->get_chart( $filters );

		wp_send_json_success( $chart );
	}

	/**
	 * Get complete chart config.
	 *
	 * @since 1.0.0
	 */
	private function get_chart( $filters ) {

		$data    = $this->get_chart_data( $filters );
		$type    = $this->get_chart_type( $filters );
		$options = $this->get_chart_options( $filters );

		return array(
			'data'    => wp_json_encode( $data ),
			'type'    => $type,
			'options' => wp_json_encode( $options ),
		);
	}

	/**
	 * Get chart options to format the Chart.
	 *
	 * @since 1.0.0
	 */
	private function get_chart_options( $filters ) {

		// Default Chart options
		$options = array(
			'responsive'           => true,
			'maintainAspectRation' => true,
			'scaleGridLineColor'   => '#ebebeb',
			'scales'               => array(
				'yAxes' => array(
					array(
						'ticks'     => array(
							'min'      => 0,
							'stepSize' => 1,
						),
						'gridLines' => array(
							'drawBorder' => false,
						),
					),
				),
				'xAxes' => array(
					array(
						'ticks' => array(
							'min'      => 0,
							'stepSize' => 1,
						),
					),
				),
			),
			'legend'               => array(
				'display' => true,
			),
		);

		if ( 'past-month' == $filters['time'] && 'individual-networks' == $filters['display'] ) {
			$options['scales']['yAxes'][0]['stacked'] = true;
			$options['scales']['xAxes'][0]['stacked'] = true;
		}

		if ( 'total' == $filters['time'] ) {
			$options['legend']['display'] = false;
		}

		return $options;
	}

	/**
	 * Get top networks to display in the chart.
	 *
	 * @since 1.0.0
	 */
	private function get_top_networks( $filters ) {

		$stats_args = array(
			'type'     => $filters['type'],
			'location' => 'all' !== $filters['location'] ? $filters['location'] : '',
		);

		$count = 4;

		switch ( $filters['time'] ) {

			// Past Week
			case 'past-week':
				$stats_args['date_from'] = gmdate( 'Y-m-d', strtotime( '-6 days' ) ) . ' 00:00:00';
				$stats_args['date_to']   = gmdate( 'Y-m-d', strtotime( '+1 days' ) ) . ' 00:00:00';
				break;

			// Past Month
			case 'past-month':
				$stats_args['date_from'] = gmdate( 'Y-m-d', strtotime( '-29 days' ) ) . ' 00:00:00';
				$stats_args['date_to']   = gmdate( 'Y-m-d', strtotime( '+1 days' ) ) . ' 00:00:00';
				break;

			// Past Year
			case 'past-year':
				$stats_args['date_from'] = gmdate( 'Y-m', strtotime( '-11 months' ) ) . '-01 00:00:00';
				$stats_args['date_to']   = gmdate( 'Y-m', strtotime( '+1 months' ) ) . '-01 00:00:00';
				break;

			// All Time
			case 'past-years':
				$stats_args['date_from'] = gmdate( 'Y', strtotime( '-4 years' ) ) . '-01-01 00:00:00';
				$stats_args['date_to']   = gmdate( 'Y', strtotime( '+1 years' ) ) . '-01-01 00:00:00';
				break;

			// All Time
			case 'total':
				$count = null;
				break;
		}

		$networks = socialsnap()->stats->get_top_networks( $stats_args, $count );

		return $networks;
	}

	/**
	 * Get chart data based on filters
	 *
	 * @since 1.0.0
	 */
	private function get_chart_data( $filters ) {

		$networks       = $this->get_top_networks( $filters );
		$network_counts = array();

		$stats_args = array(
			'type'     => $filters['type'],
			'location' => 'all' !== $filters['location'] ? $filters['location'] : '',
		);

		switch ( $filters['time'] ) {

			// Past Week
			case 'past-week':
				$count = 6;
				$time  = array(
					'format'   => 'Y-m-d',
					'interval' => ' days',
					'suffix'   => ' 00:00:00',
				);
				break;

			// Past Month
			case 'past-month':
				$count = 29;
				$time  = array(
					'format'   => 'Y-m-d',
					'interval' => ' days',
					'suffix'   => ' 00:00:00',
				);

				break;

			// Past Year
			case 'past-year':
				$count = 11;
				$time  = array(
					'format'   => 'Y-m',
					'interval' => ' months',
					'suffix'   => '-01 00:00:00',
				);

				break;

			// All Time
			case 'past-years':
				$count = 4;
				$time  = array(
					'format'   => 'Y',
					'interval' => ' years',
					'suffix'   => '-01-01 00:00:00',
				);

				break;

			// All Time
			case 'total':
				$count = -1;

				break;
		}

		for ( $i = $count; $i >= 0; $i-- ) {

			$stats_args['network']   = '';
			$stats_args['date_from'] = gmdate( $time['format'], strtotime( ( -$i ) . $time['interval'] ) ) . $time['suffix'];
			$stats_args['date_to']   = gmdate( $time['format'], strtotime( -( $i - 1 ) . $time['interval'] ) ) . $time['suffix'];

			$total_count = socialsnap()->stats->get_stats( $stats_args, true );
			$other_count = 0;

			if ( 'individual-networks' == $filters['display'] ) {

				foreach ( $networks as $network => $count ) {

					$stats_args['network'] = $network;

					if ( 'other' !== $network ) {

						if ( ! isset( $network_counts[ $network ] ) ) {
							$network_counts[ $network ] = array();
						}

						$_network_count               = socialsnap()->stats->get_stats( $stats_args, true );
						$network_counts[ $network ][] = $_network_count;
						$other_count                 -= $_network_count;
					}
				}

				if ( ! in_array( $filters['type'], array( 'ctt', 'like' ) ) ) {
					$network_counts['other'][] = $total_count + $other_count;
				}
			} elseif ( 'total' == $filters['display'] ) {

				$network_counts[] = socialsnap()->stats->get_stats( $stats_args, true );
			}
		}

		$stats_datasets = array();

		if ( 'total' == $filters['time'] ) {

			$network_colors = array();
			$network_labels = array();

			foreach ( $networks as $network => $count ) {

				if ( in_array( $filters['type'], array( 'ctt', 'like' ) ) && 'other' === $network ) {
					continue;
				}

				$network_counts[] = $count;
				$network_colors[] = socialsnap_get_network_color( $network );
				$network_labels[] = socialsnap_get_network_name( $network );
			}

			$stats_datasets[] = array(
				/* translators: %1$s is statistics type */
				'label'                => sprintf( __( 'All time %1$s counts', 'socialsnap' ), strtolower( $filters['type'] ) ),
				'data'                 => $network_counts,
				'backgroundColor'      => $network_colors,
				'hoverBackgroundColor' => $network_colors,
				'borderColor'          => $network_colors,
				'fill'                 => false,
			);

		} else {

			if ( 'individual-networks' === $filters['display'] ) {

				foreach ( $networks as $network => $count ) {

					if ( in_array( $filters['type'], array( 'ctt', 'like' ) ) && 'other' === $network ) {
						continue;
					}

					if ( ! isset( $network_counts[ $network ] ) ) {
						$network_counts[ $network ] = array();
					}

					$stats_datasets[] = array(
						'label'                => socialsnap_get_network_name( $network ),
						'data'                 => $network_counts[ $network ],
						'backgroundColor'      => socialsnap_get_network_color( $network ),
						'hoverBackgroundColor' => socialsnap_get_network_color( $network ),
						'borderColor'          => socialsnap_get_network_color( $network ),
						'fill'                 => false,
					);
				}
			} else {

				$stats_datasets[] = array(
					/* translators: %1$s is statistics type */
					'label'                => sprintf( __( 'Total %1$s', 'socialsnap' ), $this->get_stat_type_name( $filters['type'] ) ),
					'data'                 => $network_counts,
					'backgroundColor'      => '#4f89e1',
					'hoverBackgroundColor' => '#4f89e1',
					'borderColor'          => '#4f89e1',
					'fill'                 => false,
				);
			}
		}

		$data = array(
			'labels'   => $this->get_chart_labels( $filters ),
			'datasets' => $stats_datasets,
		);

		return $data;
	}

	/**
	 * Get chart type based on filters
	 *
	 * @since 1.0.0
	 */
	private function get_chart_type( $filters ) {

		if ( 'individual-networks' == $filters['display'] || 'total' == $filters['time'] ) {
			$type = 'bar';
		} else {
			$type = 'line';
		}

		if ( 'like' == $filters['type'] ) {
			$type = 'line';
		}

		return $type;
	}

	/**
	 * Generate chart labels based on filters
	 *
	 * @since 1.0.0
	 */
	private function get_chart_labels( $filters ) {

		$labels = array();

		switch ( $filters['time'] ) {

			// Past Week
			case 'past-week':
				// Iterate through Past 7 days
				for ( $i = 6; $i >= 0; $i-- ) {
					$labels[] = gmdate( 'D', strtotime( '-' . $i . ' days' ) );
				}
				break;

			// Past Month
			case 'past-month':
				// Iterate through Past 30 days
				for ( $i = 29; $i >= 0; $i-- ) {
					$labels[] = gmdate( 'j M', strtotime( '-' . $i . ' days' ) );
				}
				break;

			// Past Year
			case 'past-year':
				// Iterate through Past 12 months
				for ( $i = 11; $i >= 0; $i-- ) {
					$labels[] = gmdate( 'M', strtotime( '-' . $i . ' months' ) );
				}
				break;

			case 'past-years':
				// Iterate through Past 5 years
				for ( $i = 4; $i >= 0; $i-- ) {
					$labels[] = gmdate( 'Y', strtotime( '-' . $i . ' years' ) );
				}
				break;

			// Total
			case 'total':
				$networks = $this->get_top_networks( $filters );

				foreach ( $networks as $network => $count ) {
					if ( $count > 0 ) {
						$labels[] = socialsnap_get_network_name( $network );
					}
				}

				break;
		}

		return $labels;
	}

	/**
	 * Get full name of stat type
	 *
	 * @since 1.0.0
	 */
	private function get_stat_type_name( $slug ) {

		$types = array(
			'share_api' => __( 'Shares', 'socialsnap' ),
			'share'     => __( 'Shares', 'socialsnap' ),
			'ctt'       => __( 'Click to Tweet', 'socialsnap' ),
			'like'      => __( 'Likes', 'socialsnap' ),
			'view'      => __( 'Views', 'socialsnap' ),
		);

		$types = apply_filters( 'socialsnap_statistics_types', $types );

		if ( isset( $types[ $slug ] ) ) {
			return $types[ $slug ];
		}

		return $slug;
	}

	/**
	 * Generate top performing posts HTML based on top performing filter (most viewed, liked or shared)
	 *
	 * @since 1.0.0
	 */
	public function generate_top_performing() {

		check_ajax_referer( 'socialsnap_top_performing_posts', 'security' );

		if ( ! isset( $_POST['ss_top_data'] ) ) {
			wp_send_json_error();
		}

		$top_data = str_replace( '\\', '', sanitize_text_field( $_POST['ss_top_data'] ) );		
		$top_data = json_decode( $top_data, true );

		// Query args
		$query_args = array(
			'post_type'           => $top_data['post_type'],
			'ignore_sticky_posts' => true,
			'post_status'         => 'publish',
			'posts_per_page'      => 5,
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
		);

		if ( 'share' === $top_data['filter'] || 'share_api' === $top_data['filter'] ) {
			$query_args['meta_key'] = 'ss_total_share_count';
		} elseif ( 'view' === $top_data['filter'] ) {
			$query_args['meta_key'] = 'ss_view_count';
		} else {
			$query_args['meta_key'] = 'ss_ss_click_share_count_heart';
		}

		$top_posts = new WP_Query( $query_args );

		$index = 1;

		ob_start();

		if ( $top_posts->have_posts() ) : ?>
			<ul>
			<?php
			while ( $top_posts->have_posts() ) :
				$top_posts->the_post();
				?>

				<li data-number="<?php echo esc_attr( $index ); ?>">
					<a href="<?php the_permalink(); ?>" target="_blank">

						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail();
						} else {
							echo socialsnap()->icons->get_svg( 'socialsnap-circle' ); // phpcs:ignore
						}
						?>

						<div class="ss-top-performing-content">

							<span class="ss-top-performing-title">
								<?php the_title(); ?>										
							</span>

							<span class="ss-top-performing-shares">

								<?php
								echo wp_kses_post(
									sprintf(
										'%1$s %2$s',
										socialsnap_format_number( get_post_meta( get_the_ID(), $query_args['meta_key'], true ) ),
										$this->get_stat_type_name( $top_data['filter'] )
									)
								);
								?>

							</span>

						</div>
					</a>
				</li>
				<?php $index++; ?>
			<?php endwhile; ?>
			</ul>
			<?php
			wp_reset_postdata();

		else :

			// Get post type object name.
			$ss_post_type = get_post_type_object( $top_data['post_type'] );

			if ( null === $ss_post_type ) {
				$ss_post_type = 'posts';
			} else {
				$ss_post_type = strtolower( $ss_post_type->labels->name );
			}

			echo '<p class="ss-no-posts">No ' . esc_html( $ss_post_type ) . ' found.</p>';

		endif;

		$output = ob_get_clean();

		wp_send_json_success( array( 'html' => $output ) );
	}

	/**
	 * Build the output for the plugin statistics page.
	 *
	 * @since 1.0.0
	 */
	public function render() {

		$statistics = $this->get_general_data();
		?>

		<div class="ss-row ss-stats-wrapper ss-clearfix">

			<div class="ss-col-4 ss-stats">

				<div>
					<h5><?php esc_html_e( 'Total Shares', 'socialsnap' ); ?><i class="ss-question-mark ss-tooltip" data-title="<?php esc_html_e( 'Total number of shares.', 'socialsnap' ); ?>"><?php echo socialsnap()->icons->get_svg( 'info-dark' ); // phpcs:ignore ?></i></h5>
					<div class="ss-share-count"><?php echo esc_html( socialsnap_format_number( $statistics['share_total'] ) ); ?></div>
					<div class="ss-past-week">
						<?php
						/* translators: %1$s is count. */
						echo wp_kses_post( sprintf( __( 'Past 7 days: %1$s shares', 'socialsnap' ), '<strong>' . $statistics['shares_past_week'] . '</strong>' ) );

						if ( $statistics['shares_increase'] < 0 ) {
							echo wp_kses_post( sprintf( '<span class="ss-negative"><span>%1$s&#37;</span> %2$s</span>', $statistics['shares_increase'], __( 'than week before', 'socialsnap' ) ) );
						} else {
							echo wp_kses_post( sprintf( '<span class="ss-positive"><span>+%1$s&#37;</span> %2$s</span>', $statistics['shares_increase'], __( 'than week before', 'socialsnap' ) ) );
						}
						?>
					</div>
				</div>

			</div><!-- END .ss-stats -->


			<div class="ss-col-4 ss-stats">

				<div>
					<h5><?php esc_html_e( 'Total Click to Tweets', 'socialsnap' ); ?><i class="ss-question-mark ss-tooltip" data-title="<?php _e( 'Total number of times viewers clicked the Click to Tweet element.', 'socialsnap' ); ?>"><?php echo socialsnap()->icons->get_svg( 'info-dark' ); // phpcs:ignore ?></i></h5>
					<div class="ss-share-count"><?php echo esc_html( $statistics['ctt_total'] ); ?></div>
					<div class="ss-past-week">
						<?php
						/* translators: %1$s is count */
						echo wp_kses_post( sprintf( __( 'Past 7 days: %1$s click to tweets', 'socialsnap' ), '<strong>' . $statistics['ctt_past_week'] . '</strong>' ) );

						if ( $statistics['ctt_increase'] < 0 ) {
							echo wp_kses_post( sprintf( '<span class="ss-negative"><span>%1$s&#37;</span> %2$s</span>', $statistics['ctt_increase'], __( 'than week before', 'socialsnap' ) ) );
						} else {
							echo wp_kses_post( sprintf( '<span class="ss-positive"><span>+%1$s&#37;</span> %2$s</span>', $statistics['ctt_increase'], __( 'than week before', 'socialsnap' ) ) );
						}
						?>
					</div>
				</div>

			</div><!-- END .ss-stats -->


			<div class="ss-col-4 ss-stats">

				<div>
					<h5><?php esc_html_e( 'Total Likes', 'socialsnap' ); ?><i class="ss-question-mark ss-tooltip" data-title="<?php esc_attr_e( 'Total number of inbuilt likes.', 'socialsnap' ); ?>"><?php echo socialsnap()->icons->get_svg( 'info-dark' ); // phpcs:ignore ?></i></h5>
					<div class="ss-share-count"><?php echo esc_html( $statistics['like_total'] ); ?></div>
					<div class="ss-past-week">
						<?php
						/* translators: %1$s is count */
						echo wp_kses_post( sprintf( __( 'Past 7 days: %1$s likes', 'socialsnap' ), '<strong>' . $statistics['like_past_week'] . '</strong>' ) );

						if ( $statistics['like_increase'] < 0 ) {
							echo wp_kses_post( sprintf( '<span class="ss-negative"><span>%1$s&#37;</span> %2$s</span>', $statistics['like_increase'], __( 'than week before', 'socialsnap' ) ) );
						} else {
							echo wp_kses_post( sprintf( '<span class="ss-positive"><span>+%1$s&#37;</span> %2$s</span>', $statistics['like_increase'], __( 'than week before', 'socialsnap' ) ) );
						}
						?>
					</div>
				</div>

			</div><!-- END .ss-stats -->


		</div><!-- END .ss-stats-wrapper -->


		<div class="ss-row ss-clearfix">
			<div class="ss-col-8">

					<!-- Chart -->
					<h3>
						<?php esc_html_e( 'Chart', 'socialsnap' ); ?>
						<i class="ss-question-mark ss-tooltip" data-title="<?php esc_attr_e( 'Visual representation of your stats.', 'socialsnap' ); ?>"><?php echo socialsnap()->icons->get_svg( 'info-dark' ); // phpcs:ignore ?></i>

						<div class="ss-analytics-selects ss-clearfix">

							<a href="#" class="ss-dropdown-filter" id="ss-locations-filter">
								<span><?php esc_html_e( 'All locations', 'socialsnap' ); ?></span><i class="dashicons dashicons-arrow-down"></i>
								<ul>
									<li data-value="all" class="ss-current"><?php esc_html_e( 'All locations', 'socialsnap' ); ?></li>
									<li data-value="sidebar"><?php esc_html_e( 'Floating Sidebar', 'socialsnap' ); ?></li>
									<li data-value="inline_content"><?php esc_html_e( 'Inline Content', 'socialsnap' ); ?></li>
									<li data-value="hub"><?php esc_html_e( 'Share Hub', 'socialsnap' ); ?></li>
									<li data-value="on_media"><?php esc_html_e( 'On Media', 'socialsnap' ); ?></li>
									<li data-value="sticky_bar"><?php esc_html_e( 'Sticky Bar', 'socialsnap' ); ?></li>
									<li data-value="popup"><?php esc_html_e( 'Network Popup', 'socialsnap' ); ?></li>
								</ul>
							</a>

							<a href="#" class="ss-dropdown-filter" id="ss-total-filter">
								<span><?php esc_html_e( 'Individual Networks', 'socialsnap' ); ?></span><i class="dashicons dashicons-arrow-down"></i>
								<ul>
									<li data-value="total"><?php esc_html_e( 'All Networks', 'socialsnap' ); ?></li>
									<li data-value="individual-networks" class="ss-current"><?php esc_html_e( 'Individual networks', 'socialsnap' ); ?></li>
								</ul>
							</a>

							<a href="#" class="ss-dropdown-filter" id="ss-time-filter">
								<span><?php esc_html_e( 'Past 7 Days', 'socialsnap' ); ?></span><i class="dashicons dashicons-arrow-down"></i>
								<ul>
									<li data-value="past-week" class="ss-current"><?php esc_html_e( 'Past 7 Days', 'socialsnap' ); ?></li>
									<li data-value="past-month"><?php esc_html_e( 'Past 30 Days', 'socialsnap' ); ?></li>
									<li data-value="past-year"><?php esc_html_e( 'Past 12 Months', 'socialsnap' ); ?></li>
									<li data-value="past-years"><?php esc_html_e( 'Past Years', 'socialsnap' ); ?></li>
									<li data-value="total"><?php esc_html_e( 'Total', 'socialsnap' ); ?></li>
								</ul>
							</a>

							<a href="#" class="ss-dropdown-filter" id="ss-type-filter">
								<span><?php esc_html_e( 'Shares API', 'socialsnap' ); ?></span><i class="dashicons dashicons-arrow-down"></i>
								<ul>
									<li data-value="share_api" class="ss-current"><?php esc_html_e( 'Shares API', 'socialsnap' ); ?></li>
									<li data-value="share"><?php esc_html_e( 'Shares Clicks', 'socialsnap' ); ?></li>
									<li data-value="ctt"><?php esc_html_e( 'Click to Tweet', 'socialsnap' ); ?></li>
									<li data-value="like"><?php esc_html_e( 'Likes', 'socialsnap' ); ?></li>
								</ul>
							</a>

						</div><!-- END .ss-analytics-selects -->

					</h3>
					<div id="ss-analytics-stats-wrap" data-nonce="<?php echo esc_attr( wp_create_nonce( 'socialsnap_chart_data' ) ); ?>">

						<div class="line-chart">
							<div class="aspect-ratio">
								<canvas id="ss-chart"></canvas>
							</div>
						</div><!-- END .line-chart -->

					</div><!-- END #ss-analytics-stats-wrap -->

			</div>

			<div class="ss-col-4">
				<h3>
					<?php esc_html_e( 'Top Performing', 'socialsnap' ); ?> <i class="ss-question-mark ss-tooltip" data-title="<?php esc_attr_e( 'Top performing posts on your website.', 'socialsnap' ); ?>"><?php echo socialsnap()->icons->get_svg( 'info-dark' ); // phpcs:ignore ?></i>

					<div class="ss-analytics-selects ss-clearfix">

						<a href="#" class="ss-dropdown-filter" id="ss-top-post-type-filter">
							<span><?php esc_html_e( 'Posts', 'socialsnap' ); ?></span><i class="dashicons dashicons-arrow-down"></i>
							<ul>
								<li data-value="post" class="ss-current"><?php esc_html_e( 'Posts', 'socialsnap' ); ?></li>
								<li data-value="page"><?php esc_html_e( 'Pages', 'socialsnap' ); ?></li>

								<?php
								$custom_post_types = socialsnap_get_post_types();

								if ( is_array( $custom_post_types ) && ! empty( $custom_post_types ) ) {
									foreach ( $custom_post_types as $id => $name ) {
										?>
										<li data-value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></li>
										<?php
									}
								}
								?>
							</ul>
						</a>

						<a href="#" class="ss-dropdown-filter" id="ss-top-performing-filter">
							<span><?php esc_html_e( 'Shares', 'socialsnap' ); ?></span><i class="dashicons dashicons-arrow-down"></i>
							<ul>
								<li data-value="share" class="ss-current"><?php esc_html_e( 'Shares', 'socialsnap' ); ?></li>
								<li data-value="like"><?php esc_html_e( 'Likes', 'socialsnap' ); ?></li>
								<li data-value="view"><?php esc_html_e( 'Views', 'socialsnap' ); ?></li>
							</ul>
						</a>
					</div>

				</h3>

				<div id="ss-stats-top-performing" class="ss-top-performing ss-clearfix" data-nonce="<?php echo esc_attr( wp_create_nonce( 'socialsnap_top_performing_posts' ) ); ?>"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Remove Statistics page from admin menu if disabled.
	 *
	 * @since  1.1.6
	 * @return void
	 */
	public function remove_from_admin_menu() {
		remove_submenu_page( 'socialsnap-settings', 'socialsnap-statistics' );
	}
}
new SocialSnap_Statistics_Pro();
