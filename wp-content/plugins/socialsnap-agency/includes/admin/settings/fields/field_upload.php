<?php
/**
 * Social Snap toggle field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_upload {

	public $field;
	public $name;
	public $id;
	public $default;
	public $value;
	public $description;
	public $dependency;

	/**
	 * Primary class constructor.
	 *
	 * @since 4.0
	 */
	public function __construct( $value ) {
		$this->field       = $value['type'];
		$this->name        = $value['name'];
		$this->id          = $value['id'];
		$this->default     = isset( $value['default'] ) ? $value['default'] : '';
		$this->value       = isset( $value['value'] ) ? $value['value'] : '';
		$this->description = isset( $value['desc'] ) ? $value['desc'] : '';
		$this->dependency  = isset( $value['dependency'] ) ? $value['dependency'] : '';
	}

	/**
	 * HTML Output of the field
	 *
	 * @since 4.0
	 */
	public function render() {

		$class = ! is_array( $this->value ) ? ' hidden' : '';

		if ( ! is_array( $this->value ) ) {
			$this->value = array(
				'url' => '',
				'id'  => '',
			);
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-field-spacing ss-upload-element ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-field-title">

				<?php echo wp_kses_post( $this->name ); ?>

				<?php if ( $this->description ) { ?>
					<i class="ss-tooltip ss-question-mark" data-title="<?php echo esc_attr( $this->description ); ?>"><?php echo socialsnap()->icons->get_svg( 'info' ); // phpcs:ignore ?></i>
				<?php } ?>	
			</div>

			<div class="ss-field-element ss-clearfix">

				<div class="ss-upload-wrapper">
					<input class="ss-upload-url widefat" readonly="readonly" type="text" id="<?php echo esc_attr( $this->id ); ?>" name="<?php echo esc_attr( $this->id ); ?>[url]" size="50"  value="<?php echo esc_attr( $this->value['url'] ); ?>" />

					<input id="<?php echo esc_attr( $this->id ); ?>_img_id" type="hidden" name="<?php echo esc_attr( $this->id ); ?>[id]" value="<?php echo esc_attr( $this->value['id'] ); ?>">

					<a class="ss-upload-button ss-button ss-small-button" id="<?php echo esc_attr( $this->id ); ?>_button" href="#" data-title="<?php esc_html_e( 'Choose or upload a file', 'socialsnap' ); ?>" data-button="<?php esc_html_e( 'Use this image', 'socialsnap' ); ?>">
						<?php esc_html_e( 'Upload', 'socialsnap' ); ?>	
					</a>
				</div>

				<div id="<?php echo esc_attr( $this->id ); ?>-preview" class="show-upload-image" alt="<?php echo esc_attr( $this->name ); ?>">

					<?php
					if ( $this->value['url'] ) {
						?>
						<img src="<?php echo esc_url( $this->value['url'] ); ?>"/>
					<?php } ?>

					<a href="#" class="ss-remove-image<?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $this->id ); ?>_remove">
						<?php echo socialsnap()->icons->get_svg( 'close' ); // phpcs:ignore ?>
					</a>

				</div>
			</div>

		</div>

		<?php
		return ob_get_clean();
	}
}
