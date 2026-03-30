<?php
/**
 * Social Snap info box field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_info {

	public $field;
	public $name;
	public $id;
	public $description;
	public $dependency;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $value ) {
		$this->field       = $value['type'];
		$this->name        = $value['name'];
		$this->id          = $value['id'];
		$this->description = isset( $value['desc'] ) ? $value['desc'] : '';
		$this->dependency  = isset( $value['dependency'] ) ? $value['dependency'] : '';
	}

	/**
	 * HTML output of the field
	 *
	 * @since 1.0.0
	 */
	public function render() {
		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-info-field">
				<h4><?php echo wp_kses_post( $this->name ); ?></h4>
				<?php echo wp_kses_post( $this->description ); ?>
				<br/>
				<a href="<?php echo esc_url( socialsnap_upgrade_link() ); ?>" class="ss-button ss-small-button ss-upgrade-button" target="_blank"><?php echo socialsnap()->icons->get_svg( 'socialsnap-icon' ); // phpcs:ignore ?><?php echo esc_html( apply_filters( 'socialsnap_upgrade_button_text', __( 'Upgrade Now', 'socialsnap' ) ) ); ?></a>
			</div><!-- END .ss-info-field -->

		</div>
		<?php
		return ob_get_clean();
	}
}
