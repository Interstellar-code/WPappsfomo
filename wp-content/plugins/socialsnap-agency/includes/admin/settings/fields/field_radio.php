<?php
/**
 * Social Snap radio field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_radio {

	public $field;
	public $name;
	public $id;
	public $default;
	public $value;
	public $description;
	public $options;
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
		$this->default     = isset( $value['default'] ) ? $value['default'] : '';
		$this->value       = isset( $value['value'] ) ? $value['value'] : '';
		$this->description = isset( $value['desc'] ) ? $value['desc'] : '';
		$this->options     = isset( $value['options'] ) ? $value['options'] : '';
		$this->dependency  = isset( $value['dependency'] ) ? $value['dependency'] : '';
	}

	/**
	 * HTML output of the field
	 *
	 * @since 1.0.0
	 */
	public function render() {

		ob_start(); ?>

		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-field-spacing ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-field-title">

				<?php echo wp_kses_post( $this->name ); ?>

				<?php if ( $this->description ) { ?>
					<i class="ss-tooltip ss-question-mark" data-title="<?php echo esc_attr( $this->description ); ?>"><?php echo socialsnap()->icons->get_svg( 'info' ); // phpcs:ignore  ?></i>
				<?php } ?>	
			</div>

			<div class="ss-field-element ss-clearfix">
				<div class="ss-checkbox-group">
					<?php
					if ( is_array( $this->options ) && ! empty( $this->options ) ) {
						foreach ( $this->options as $key => $value ) {
							?>
						<div class="ss-radio-button">
							<input id="<?php echo esc_attr( $this->id ); ?>-<?php echo esc_attr( $key ); ?>-radio-button" name="<?php echo esc_attr( $this->id ); ?>" type="radio" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $this->value ); ?>>
							<label for="<?php echo esc_attr( $this->id ); ?>-<?php echo esc_attr( $key ); ?>-radio-button"><?php echo esc_html( $value ); ?></label>
						</div>
							<?php
						}
					}
					?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
