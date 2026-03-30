<?php
/**
 * Social Snap restore field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_restore {

	public $field;
	public $name;
	public $id;
	public $description;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $value ) {
		$this->field       = $value['type'];
		$this->description = $value['desc'];
		$this->name        = $value['name'];
		$this->id          = $value['id'];
	}

	/**
	 * HTML Output of the field
	 *
	 * @since 1.0.0
	 */
	public function render() {

		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-restore-element ss-clearfix ss-field-spacing" id="<?php echo esc_attr( $this->id ); ?>">

			<div class="ss-field-title">
				<?php echo wp_kses_post( $this->name ); ?>

				<?php if ( $this->description ) { ?>
					<i class="ss-tooltip ss-question-mark" data-title="<?php echo esc_attr( $this->description ); ?>"><?php echo socialsnap()->icons->get_svg( 'info' ); // phpcs:ignore ?></i>
				<?php } ?>	
			</div>

			<div class="ss-field-element ss-clearfix">
				<a href="#" class="ss-button ss-small-button" id="ss-restore-settings" data-nonce="<?php echo esc_attr( wp_create_nonce( 'socialsnap-settings' ) ); ?>" data-confirm="<?php esc_html_e( 'Are you sure you want to reset all settings?', 'socialsnap' ); ?>"><?php esc_html_e( 'Reset to Default', 'socialsnap' ); ?></a><span class="spinner"></span>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}
}
