<?php
/**
 * Click To Tweet for Social Snap.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Click_To_Tweet {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add settings from the Click to Tweet settings panel.
		add_filter( 'socialsnap_ctt_settings', array( $this, 'get_default_settings' ), 10, 1 );

		// Register a shortcode for Click to Tweet.
		add_shortcode( 'ss_click_to_tweet', array( $this, 'register_shortcodes' ) );

		// Add live preview.
		add_action( 'socialsnap_live_preview', array( $this, 'preview_settings' ) );

		// Add support for block editor.
		add_action( 'plugins_loaded', array( $this, 'block_editor_support' ) );

		// Add record to DB on CTT click.
		add_action( 'wp_ajax_ss_ctt_clicked', array( $this, 'add_to_stats_db' ) );
		add_action( 'wp_ajax_nopriv_ss_ctt_clicked', array( $this, 'add_to_stats_db' ) );
	}

	/**
	 * Register Block for Click to Tweet.
	 *
	 * @since 1.0.0
	 */
	public function block_editor_support() {

		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'socialsnap/click-to-tweet',
			array(
				'render_callback' => array( $this, 'block_editor_click_to_tweet' ),
			)
		);
	}

	/**
	 * Click to Tweet Block block editor support.
	 *
	 * @param array $attributes Block attributes.
	 * @since 1.0.0
	 */
	public function block_editor_click_to_tweet( $attributes ) {

		ob_start();

		$shortcode = '[ss_click_to_tweet';

		if ( isset( $attributes['content'] ) && '' !== $attributes['content'] ) {
			$shortcode .= ' content="' . wp_kses_post( htmlentities( $attributes['content'], ENT_QUOTES ) ) . '"';
		}

		if ( isset( $attributes['tweet'] ) && '' !== $attributes['tweet'] ) {
			$shortcode .= ' tweet="' . wp_kses_post( htmlentities( $attributes['tweet'] ), ENT_QUOTES ) . '"';
		}

		if ( isset( $attributes['style'] ) ) {
			$shortcode .= ' style="' . sanitize_text_field( $attributes['style'] ) . '"';
		}

		if ( isset( $attributes['link'] ) ) {
			$shortcode .= ' link="' . sanitize_text_field( $attributes['link'] ) . '"';
		}

		if ( isset( $attributes['via'] ) ) {
			$shortcode .= ' via="' . sanitize_text_field( $attributes['via'] ) . '"';
		}

		$shortcode .= ']';

		echo do_shortcode( $shortcode );

		return ob_get_clean();
	}

	/**
	 * Register shortcode for click to tweet module.
	 *
	 * @param array $atts Shortcode attributes.
	 * @since 1.0.0
	 */
	public function register_shortcodes( $atts ) {

		// Default Settings.
		$ctt = array(
			'tweet'          => '',
			'content'        => __( 'Enter Tweet Content here...', 'socialsnap' ),
			'style'          => 'default',
			'link'           => '',
			'via'            => '',
			'related'        => '',
			'hide_on_mobile' => '',
		);

		// Apply settings from settings panel.
		$ctt = apply_filters( 'socialsnap_ctt_settings', $ctt );

		$ctt = shortcode_atts( $ctt, $atts );

		if ( '' == $ctt['tweet'] ) {
			$ctt['tweet'] = $ctt['content'];
		}

		if ( '' == $ctt['content'] ) {
			$ctt['content'] = $ctt['tweet'];
		}

		if ( isset( $ctt['hide_on_mobile'] ) && $ctt['hide_on_mobile'] ) {
			$ctt['hide_on_mobile'] = ' ss-hide-on-mobile';
		} else {
			$ctt['hide_on_mobile'] = '';
		}

		if ( '' !== $ctt['link'] && '0' !== $ctt['link'] && 'false' !== $ctt['link'] ) {
			$ctt['link'] = socialsnap_get_shared_permalink( array( 'network' => 'twitter' ) );
			$ctt['link'] = apply_filters( 'socialsnap_complete_shared_permalink', $ctt['link'], 'twitter' );
		}

		if ( '' === $ctt['via'] || '0' === $ctt['via'] || 'false' === $ctt['via'] ) {
			$ctt['via'] = '';
		} else {
			$ctt['via'] = apply_filters( 'socialsnap_sanitize_username', socialsnap_settings( 'ss_twitter_username' ) );
		}

		if ( 'default' === $ctt['style'] || ! $ctt['style'] ) {
			$ctt['style'] = socialsnap_settings( 'ss_ctt_preview_style' );
		}

		// Generate tweet URL.
		$tweet_url = socialsnap_twitter_share_url(
			wp_strip_all_tags( html_entity_decode( $ctt['tweet'], ENT_QUOTES, get_bloginfo( 'charset' ) ) ),
			$ctt['link'],
			$ctt['via'],
			$ctt['related']
		);

		// Enqueue assets.
		socialsnap_enqueue_assets();

		ob_start();
		?>
		<div class="ss-ctt-wrapper ss-ctt-style-<?php echo esc_attr( $ctt['style'] . $ctt['hide_on_mobile'] ); ?>" data-ss-post-id="<?php the_ID(); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'socialsnap-ctt-add-to-db' ) ); ?>">

			<a href="<?php echo esc_url( $tweet_url ); ?>" class="ss-ctt-tweet" data-title="<?php esc_html_e( 'Click to Tweet', 'socialsnap' ); ?>" rel="nofollow noopener" target="_blank"><?php echo wp_kses( html_entity_decode( $ctt['content'] ), socialsnap_get_allowed_html_tags( 'post' ) ); ?></a>

			<a href="<?php echo esc_url( $tweet_url ); ?>" class="ss-ctt-link" data-title="<?php esc_html_e( 'Click to Tweet', 'socialsnap' ); ?>" rel="nofollow noopener" target="_blank">
				<span><?php esc_html_e( 'Click to Tweet', 'socialsnap' ); ?></span>
				<?php echo socialsnap()->icons->get_svg( 'twitter' ); // phpcs:ignore ?>
			</a>

		</div><!-- END .ss-ctt-wrapper -->

		<?php
		return ob_get_clean();
	}

	/**
	 * Get default settings from the Settings Panel.
	 *
	 * @param array $settings Array of settings.
	 * @since 1.0.0
	 */
	public function get_default_settings( $settings ) {

		// Use Twitter username from Social Identity for 'via' parameter.
		$username = socialsnap_settings( 'ss_twitter_username' );
		$via      = socialsnap_settings( 'ss_ctt_include_via' );
		if ( '' !== $username && $via ) {
			$settings['via'] = $username;
		}

		// Include link to this post.
		$settings['link'] = socialsnap_settings( 'ss_ctt_include_link' ) ? true : '';

		// Hide on mobile.
		$settings['hide_on_mobile'] = socialsnap_settings( 'ss_ctt_hide_mobile' );

		// Related.
		$accounts = socialsnap_settings( 'ss_ctt_related' );
		$related  = '';
		for ( $i = 0; $i < 2; $i++ ) {

			if ( isset( $accounts[ $i ] ) ) {

				if ( isset( $accounts[ $i ]['username'] ) && '' !== $accounts[ $i ]['username'] ) {

					$accounts[ $i ]['username'] = str_replace( '@', '', $accounts[ $i ]['username'] );
					$related                   .= $accounts[ $i ]['username'];

					if ( isset( $accounts[ $i ]['desc'] ) && '' !== $accounts[ $i ]['desc'] ) {
						$related .= ':' . $accounts[ $i ]['desc'];
					}

					$related .= ',';
				}
			}
		}
		$related = rtrim( $related, ',' );
		if ( '' != $related ) {
			$settings['related'] = $related;
		}

		return $settings;
	}

	/**
	 * Add a record to Social Snap DB when vistior clicks a Click to Tweet element.
	 *
	 * @since 1.0.0
	 */
	public function add_to_stats_db() {

		// Security check.
		check_ajax_referer( 'socialsnap-nonce' );

		// Data is required.
		if ( ! isset( $_POST['ss_click_data'] ) ) {
			wp_send_json_error();
		}

		// Format data.
		$click_data = str_replace( '\\', '', sanitize_text_field( $_POST['ss_click_data'] ) );
		$click_data = json_decode( $click_data, true );

		$click_data['type']    = 'ctt';
		$click_data['network'] = 'ctt';

		// Add to Stats DB. This function will validate and sanitize data.
		$ctt_count = socialsnap_add_to_stats_db( $click_data );

		wp_send_json_success( array( 'ctt_count' => $ctt_count ) );
	}

	/**
	 * Render settings preview screen.
	 *
	 * @since 1.0.0
	 */
	public function preview_settings() {
		?>

		<?php
		$content = __( 'Social Snap allows you to create beautiful Click to Tweet boxes in several different styles. #awesome', 'socialsnap' );
		$style   = socialsnap_settings( 'ss_ctt_preview_style', 'default' );
		?>

		<div class="ss-preview-screen ss-preview-ctt">

			<div class="ss-ctt-preview-shortcode">
				<?php echo do_shortcode( '[ss_click_to_tweet content="' . $content . '" style="' . $style . '"]' ); ?>
			</div>

		</div>
		<?php
	}
}
new SocialSnap_Click_To_Tweet();
