<?php
/**
 * Social Snap import field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_import {

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
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-import-element ss-clearfix ss-field-spacing" id="<?php echo esc_attr( $this->id ); ?>">

			<div class="ss-field-title">
				<?php echo wp_kses_post( $this->name ); ?>

				<?php if ( $this->description ) { ?>
					<i class="ss-tooltip ss-question-mark" data-title="<?php echo esc_attr( $this->description ); ?>"><?php echo socialsnap()->icons->get_svg( 'info' ); // phpcs:ignore ?></i>
				<?php } ?>	
			</div>

			<div class="ss-field-element ss-clearfix">
				<input type="file" id="ss-upload-import-file" multiple="false" style="display: none;" name="ss-upload-import-file" />
				<a href="#" class="ss-button ss-small-button" id="ss-import-settings" data-nonce="<?php echo esc_attr( wp_create_nonce( 'socialsnap-settings' ) ); ?>">
					<?php esc_html_e( 'Import Settings', 'socialsnap' ); ?>	
				</a>
				<span class="ss-file-name"></span>
				<span class="spinner"></span>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}
}
