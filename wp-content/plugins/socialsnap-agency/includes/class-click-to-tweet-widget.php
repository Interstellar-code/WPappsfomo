<?php
/**
 * Social Snap: Popular Posts widget.
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2018, Social Snap, LLC
 */
class SocialSnap_CTT_Widget extends WP_Widget {

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
			'title'          => '',
			'tweet'          => '',
			'content'        => '',
			'style'          => 'default',
			'link'           => '',
			'via'            => '',
			'related'        => '',
			'hide_on_mobile' => '',
		);

		// Widget Slug.
		$widget_slug = 'socialsnap-ctt-widget';

		// Widget basics.
		$widget_ops = array(
			'classname'   => $widget_slug,
			'description' => _x( 'Displays Click to Tweet box.', 'Widget', 'socialsnap' ),
		);

		// Widget controls.
		$control_ops = array(
			'id_base' => $widget_slug,
		);

		parent::__construct( $widget_slug, _x( 'Social Snap: Click to Tweet', 'Widget', 'socialsnap' ), $widget_ops, $control_ops );

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

		echo do_shortcode( '[ss_click_to_tweet content="' . $instance['content'] . '" tweet="' . $instance['tweet'] . '" style="' . $instance['style'] . '" link="' . $instance['link'] . '" via="' . $instance['via'] . '"]' );

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

		$new_instance['title']          = wp_strip_all_tags( $new_instance['title'] );
		$new_instance['tweet']          = isset( $new_instance['tweet'] ) ? wp_strip_all_tags( $new_instance['tweet'] ) : '';
		$new_instance['content']        = isset( $new_instance['content'] ) ? wp_strip_all_tags( $new_instance['content'] ) : '';
		$new_instance['style']          = isset( $new_instance['style'] ) ? $new_instance['style'] : 'default';
		$new_instance['link']           = isset( $new_instance['link'] ) ? '1' : false;
		$new_instance['via']            = isset( $new_instance['via'] ) ? '1' : false;
		$new_instance['hide_on_mobile'] = isset( $new_instance['hide_on_mobile'] ) ? '1' : false;

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
			<label for="<?php echo esc_attr( $this->get_field_id( 'tweet' ) ); ?>">
				<?php echo esc_html_x( 'Tweet Content:', 'Widget', 'socialsnap' ); ?>
			</label>

			<textarea id="<?php echo esc_attr( $this->get_field_id( 'tweet' ) ); ?>" rows="4" name="<?php echo esc_attr( $this->get_field_name( 'tweet' ) ); ?>" class="widefat" ><?php echo esc_attr( $instance['tweet'] ); ?></textarea><small><?php esc_html_e( 'Content that will be posted on Twitter. If empty, Quote Content will be used.', 'socialsnap' ); ?></small>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>">
				<?php echo esc_html_x( 'Quote Content:', 'Widget', 'socialsnap' ); ?>
			</label>

			<textarea id="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>" rows="4" name="<?php echo esc_attr( $this->get_field_name( 'content' ) ); ?>" class="widefat" ><?php echo esc_attr( $instance['content'] ); ?></textarea><small><?php esc_html_e( 'Text that will be shown on your website, inside the Click to Tweet box.', 'socialsnap' ); ?></small>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>">
				<?php echo esc_html_x( 'Style:', 'Widget', 'socialsnap' ); ?>
			</label>

			<select id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>" class="widefat">
				<option value="default" <?php selected( $instance['style'], 'default', true ); ?>><?php echo esc_html_x( 'Default', 'Widget', 'socialsnap' ); ?></option>
				<option value="1" <?php selected( $instance['style'], '1', true ); ?>><?php echo esc_html_x( 'Style 1', 'Widget', 'socialsnap' ); ?></option>
				<option value="2" <?php selected( $instance['style'], '2', true ); ?>><?php echo esc_html_x( 'Style 2', 'Widget', 'socialsnap' ); ?></option>
				<option value="3" <?php selected( $instance['style'], '3', true ); ?>><?php echo esc_html_x( 'Style 3', 'Widget', 'socialsnap' ); ?></option>
				<option value="4" <?php selected( $instance['style'], '4', true ); ?>><?php echo esc_html_x( 'Style 4', 'Widget', 'socialsnap' ); ?></option>
				<option value="5" <?php selected( $instance['style'], '5', true ); ?>><?php echo esc_html_x( 'Style 5', 'Widget', 'socialsnap' ); ?></option>
				<option value="6" <?php selected( $instance['style'], '6', true ); ?>><?php echo esc_html_x( 'Style 6', 'Widget', 'socialsnap' ); ?></option>
			</select>
		</p>

		<p>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'link' ) ); ?>" <?php checked( '1', $instance['link'] ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>"><?php echo esc_html_x( 'Append page link to Tweet.', 'Widget', 'socialsnap' ); ?></label>
			<br/>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'via' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'via' ) ); ?>" <?php checked( '1', $instance['via'] ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'via' ) ); ?>"><?php echo esc_html_x( 'Include via @username in Tweet.', 'Widget', 'socialsnap' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=socialsnap-settings#ss_social_identity_twitter-ss' ) ); ?>" target="_blank"><?php esc_html_e( 'Configure here', 'socialsnap' ); ?>.</a></label>
			<br/>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'hide_on_mobile' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'hide_on_mobile' ) ); ?>" <?php checked( '1', $instance['hide_on_mobile'] ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'hide_on_mobile' ) ); ?>"><?php echo esc_html_x( 'Hide on mobile devices.', 'Widget', 'socialsnap' ); ?></label>
		</p>
		<?php
	}

}

/**
 * Register Click to Tweet widget.
 */
function socialsnap_register_ctt_widget() {
	register_widget( 'SocialSnap_CTT_Widget' );
}

add_action( 'widgets_init', 'socialsnap_register_ctt_widget' );
