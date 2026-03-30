<?php
/**
 * Social Snap: Popular Posts widget.
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Popular_Posts_Widget extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Widget defaults.
		$this->defaults = array(
			'title'       => '',
			'post_count'  => 5,
			'post_type'   => 'post',
			'post_age'    => 'all',
			'order'       => 'shares',
			'thumb_shape' => 'square',
			'thumb_size'  => 'thumbnail',
			'show_thumb'  => '1',
			'rankings'    => false,
			'show_count'  => '1',
		);

		// Widget Slug.
		$widget_slug = 'socialsnap-popular-posts-widget';

		// Widget basics.
		$widget_ops = array(
			'classname'   => $widget_slug,
			'description' => _x( 'Displays the most popular posts by share or view count.', 'Widget', 'socialsnap' ),
		);

		// Widget controls.
		$control_ops = array(
			'id_base' => $widget_slug,
		);

		parent::__construct( $widget_slug, _x( 'Social Snap: Popular Posts', 'Widget', 'socialsnap' ), $widget_ops, $control_ops );

	}

	/**
	 * Outputs the HTML for this widget.
	 *
	 * @since 1.0.0
	 * @param array $args An array of standard parameters for widgets in this theme.
	 * @param array $instance An array of settings for this widget instance.
	 */
	public function widget( $args, $instance ) {

		// Merge with defaults.
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		echo wp_kses( $args['before_widget'], socialsnap_get_allowed_html_tags( 'post' ) );

		// Title.
		if ( ! empty( $instance['title'] ) ) {
			echo wp_kses( $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'], socialsnap_get_allowed_html_tags( 'post' ) );
		}

		// Query args.
		$query_args = array(
			'post_type'           => $instance['post_type'],
			'ignore_sticky_posts' => true,
			'post_status'         => 'publish',
			'posts_per_page'      => $instance['post_count'],
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
		);

		// Published time filter.
		if ( 'all' != $instance['post_age'] && '' != $instance['post_age'] ) {
			$query_args['date_query'] = array(
				array(
					'column' => 'post_date',
					'after'  => '1 ' . $instance['post_age'] . ' ago',
				),
			);
		}

		if ( 'share' === $instance['order'] ) {
			$query_args['meta_key'] = 'ss_total_share_count';
		} elseif ( 'view' === $instance['order'] ) {
			$query_args['meta_key'] = 'ss_view_count';
		} else {
			$query_args['meta_key'] = 'ss_ss_click_share_count_heart';
		}

		// Query top posts
		$top_posts = new WP_Query( $query_args );

		$widget_class = array( 'ss-popular-posts-widget', 'ss-clearfix' );

		if ( $instance['rankings'] ) {
			$widget_class[] = 'ss-with-rankings';
		}

		if ( $instance['show_thumb'] ) {
			$widget_class[] = 'ss-' . $instance['thumb_shape'] . '-thumb';
			$widget_class[] = 'ss-' . $instance['thumb_size'] . '-thumb';
		}

		$widget_class = implode( ' ', apply_filters( 'socialsnap_top_performing_widget_class', $widget_class ) );

		$labels = array(
			'like'  => __( 'Likes', 'socialsnap' ),
			'share' => __( 'Shares', 'socialsnap' ),
			'view'  => __( 'Views', 'socialsnap' ),
		);

		$ranking = 1;

		socialsnap_enqueue_assets();

		if ( $top_posts->have_posts() ) : ?>

			<div class="<?php echo esc_attr( $widget_class ); ?>">

			<?php
			while ( $top_posts->have_posts() ) :
				$top_posts->the_post();
				?>

				<div class="ss-popular-post ss-clearfix">

					<?php if ( $instance['show_thumb'] && has_post_thumbnail() ) { ?>
						<a href="<?php the_permalink(); ?>" class="ss-popular-post-image">
							<?php the_post_thumbnail( $instance['thumb_size'] ); ?>
						</a>
					<?php } ?>

					<span class="ss-popular-post-content">

						<a href="<?php the_permalink(); ?>" class="ss-popular-post-title" data-ranking="<?php echo esc_attr( $ranking ); ?>. "><?php the_title(); ?></a>

						<?php if ( $instance['show_count'] ) { ?>

							<span class="ss-popular-post-shares">

								<?php
								printf(
									'%1$s %2$s',
									esc_html( socialsnap_format_number( get_post_meta( get_the_ID(), $query_args['meta_key'], true ) ) ),
									esc_html( $labels[ $instance['order'] ] )
								);
								?>
							</span>

						<?php } ?>

					</span><!-- END .ss-popular-post-content -->
				</div><!-- END .ss-popular-post -->

				<?php $ranking++; ?>

			<?php endwhile; ?>

			</div><!-- END .ss-popular-posts-widget -->

			<?php
			wp_reset_postdata();
		endif;

		echo wp_kses( $args['after_widget'], socialsnap_get_allowed_html_tags( 'post' ) );
	}

	/**
	 * Deals with the settings when they are saved by the admin. Here is
	 * where any validation should be dealt with.
	 *
	 * @since 1.0.0
	 * @param array $new_instance An array of new settings as submitted by the admin.
	 * @param array $old_instance An array of the previous settings.
	 * @return array The validated and (if necessary) amended settings
	 */
	public function update( $new_instance, $old_instance ) {

		$new_instance['title']       = wp_strip_all_tags( $new_instance['title'] );
		$new_instance['post_count']  = isset( $new_instance['post_count'] ) ? absint( $new_instance['post_count'] ) : 5;
		$new_instance['post_type']   = isset( $new_instance['post_type'] ) ? $new_instance['post_type'] : 'post';
		$new_instance['post_age']    = isset( $new_instance['post_age'] ) ? $new_instance['post_age'] : 'all';
		$new_instance['order']       = isset( $new_instance['order'] ) ? $new_instance['order'] : 'shares';
		$new_instance['thumb_shape'] = isset( $new_instance['thumb_shape'] ) ? $new_instance['thumb_shape'] : 'square';
		$new_instance['thumb_size']  = isset( $new_instance['thumb_size'] ) ? $new_instance['thumb_size'] : 'regular';
		$new_instance['show_thumb']  = isset( $new_instance['show_thumb'] ) && $new_instance['show_thumb'] ? '1' : false;
		$new_instance['rankings']    = isset( $new_instance['rankings'] ) && $new_instance['rankings'] ? '1' : false;
		$new_instance['show_count']  = isset( $new_instance['show_count'] ) && $new_instance['show_count'] ? '1' : false;

		$new_instance['post_count'] = 0 !== $new_instance['post_count'] ? $new_instance['post_count'] : 5;

		return $new_instance;
	}

	/**
	 * Displays the form for this widget on the Widgets page of the WP Admin area.
	 *
	 * @since 1.0.0
	 * @param array $instance An array of the current settings for this widget.
	 * @return void
	 */
	public function form( $instance ) {

		// Merge with defaults.
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php echo esc_html_x( 'Title:', 'Widget', 'socialsnap' ); ?>
			</label>
			<input type="text"
					id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
					value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat"/>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'post_count' ) ); ?>">
				<?php echo esc_html_x( 'Number of posts to show:', 'Widget', 'socialsnap' ); ?>
			</label>

			<input id="<?php echo esc_attr( $this->get_field_id( 'post_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'post_count' ) ); ?>" type="number" step="1" min="1" max="99" value="<?php echo esc_attr( $instance['post_count'] ); ?>" size="2" class="widefat"/>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'post_type' ) ); ?>">
				<?php echo esc_html_x( 'Select which post type to display:', 'Widget', 'socialsnap' ); ?>
			</label>

			<?php
			$custom_post_types = socialsnap_get_post_types();
			?>

			<select id="<?php echo esc_attr( $this->get_field_id( 'post_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'post_type' ) ); ?>" class="widefat">
				<option value="post" <?php selected( $instance['post_type'], 'post', true ); ?>><?php echo esc_html_x( 'Posts', 'Widget', 'socialsnap' ); ?></option>
				<option value="page" <?php selected( $instance['post_type'], 'page', true ); ?>><?php echo esc_html_x( 'Pages', 'Widget', 'socialsnap' ); ?></option>

				<?php
				if ( is_array( $custom_post_types ) && ! empty( $custom_post_types ) ) {
					foreach ( $custom_post_types as $id => $name ) {
						echo '<option value="' . esc_attr( $id ) . '"' . selected( $instance['post_type'], $id, false ) . '>' . esc_html( $name ) . '</option>';
					}
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>">
				<?php echo esc_html_x( 'Select how to base post rankings:', 'Widget', 'socialsnap' ); ?>
			</label>

			<select id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" class="widefat">
				<option value="share" <?php selected( $instance['order'], 'share', true ); ?>><?php echo esc_html_x( 'Posts with most shares', 'Widget', 'socialsnap' ); ?></option>
				<option value="view" <?php selected( $instance['order'], 'view', true ); ?>><?php echo esc_html_x( 'Posts with most views', 'Widget', 'socialsnap' ); ?></option>
				<option value="like" <?php selected( $instance['order'], 'like', true ); ?>><?php echo esc_html_x( 'Posts with most likes', 'Widget', 'socialsnap' ); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'post_age' ) ); ?>">
				<?php echo esc_html_x( 'Display posts published:', 'Widget', 'socialsnap' ); ?>
			</label>

			<select id="<?php echo esc_attr( $this->get_field_id( 'post_age' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'post_age' ) ); ?>" class="widefat">
				<option value="day" <?php selected( $instance['post_age'], 'day', true ); ?>><?php echo esc_html_x( 'Past Day', 'Widget', 'socialsnap' ); ?></option>
				<option value="week" <?php selected( $instance['post_age'], 'week', true ); ?>><?php echo esc_html_x( 'Past Week', 'Widget', 'socialsnap' ); ?></option>
				<option value="month" <?php selected( $instance['post_age'], 'month', true ); ?>><?php echo esc_html_x( 'Past Month', 'Widget', 'socialsnap' ); ?></option>
				<option value="year" <?php selected( $instance['post_age'], 'year', true ); ?>><?php echo esc_html_x( 'Past Year', 'Widget', 'socialsnap' ); ?></option>
				<option value="all" <?php selected( $instance['post_age'], 'all', true ); ?>><?php echo esc_html_x( 'Anytime', 'Widget', 'socialsnap' ); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'thumb_shape' ) ); ?>">
				<?php echo esc_html_x( 'Thumbnail shape:', 'Widget', 'socialsnap' ); ?>
			</label>

			<select id="<?php echo esc_attr( $this->get_field_id( 'thumb_shape' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumb_shape' ) ); ?>" class="widefat">
				<option value="square" <?php selected( $instance['thumb_shape'], 'square', true ); ?>><?php echo esc_html_x( 'Square thumbnails', 'Widget', 'socialsnap' ); ?></option>
				<option value="circle" <?php selected( $instance['thumb_shape'], 'circle', true ); ?>><?php echo esc_html_x( 'Circle thumbnails', 'Widget', 'socialsnap' ); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'thumb_size' ) ); ?>">
				<?php echo esc_html_x( 'Thumbnail size:', 'Widget', 'socialsnap' ); ?>
			</label>

			<select id="<?php echo esc_attr( $this->get_field_id( 'thumb_size' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumb_size' ) ); ?>" class="widefat">
				<option value="thumbnail" <?php selected( $instance['thumb_size'], 'thumbnail', true ); ?>><?php echo esc_html_x( 'Default Thumbnails', 'Widget', 'socialsnap' ); ?></option>
				<option value="medium" <?php selected( $instance['thumb_size'], 'medium', true ); ?>><?php echo esc_html_x( 'Medium thumbnails', 'Widget', 'socialsnap' ); ?></option>
				<option value="large" <?php selected( $instance['thumb_size'], 'large', true ); ?>><?php echo esc_html_x( 'Large thumbnails', 'Widget', 'socialsnap' ); ?></option>
			</select>
		</p>

		<p>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'show_thumb' ) ); ?>" <?php checked( '1', $instance['show_thumb'] ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"><?php echo esc_html_x( 'Display thumbnails', 'Widget', 'socialsnap' ); ?></label>
			<br/>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'rankings' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'rankings' ) ); ?>" <?php checked( '1', $instance['rankings'] ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'rankings' ) ); ?>"><?php echo esc_html_x( 'Display ranking numbers', 'Widget', 'socialsnap' ); ?></label>
			<br/>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>" <?php checked( '1', $instance['show_count'] ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"><?php echo esc_html_x( 'Display total share/view count', 'Widget', 'socialsnap' ); ?></label>
		</p>
		<?php
	}

}

/**
 * Register lite widgets.
 */
function socialsnap_register_lite_widgets() {
	register_widget( 'SocialSnap_Popular_Posts_Widget' );
}

add_action( 'widgets_init', 'socialsnap_register_lite_widgets' );
