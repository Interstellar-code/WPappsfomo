<?php
/**
 * Social Snap text field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_editor_text {

	public $field;
	public $name;
	public $id;
	public $default;
	public $value;
	public $description;
	public $value_type;
	public $placeholder;
	public $dependency;
	public $countchar;

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
		$this->value_type  = isset( $value['value_type'] ) ? $value['value_type'] : 'text';
		$this->placeholder = isset( $value['placeholder'] ) ? 'placeholder="' . esc_attr( $value['placeholder'] ) . '"' : '';
		$this->dependency  = isset( $value['dependency'] ) ? $value['dependency'] : '';
		$this->countchar   = isset( $value['countchar'] ) ? $value['countchar'] : false;
	}

	/**
	 * HTML output of the field
	 *
	 * @since 1.0.0
	 */
	public function render() {

		$additional_params = '';

		if ( 'number' === $this->value_type ) {
			$additional_params = ' min="0" step="1"';

			$this->value = $this->value ? intval( $this->value ) : '0';
		}

		if ( '' !== $this->default ) {
			$additional_params = ' class="ss-has-default"';
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-field-text ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-left-section">
				<label for="<?php echo esc_attr( $this->id ); ?>"><strong><?php echo wp_kses_post( $this->name ); ?></strong>

					<?php if ( $this->description ) { ?>
					<span class="ss-desc"><?php echo wp_kses_post( $this->description ); ?></span>
					<?php } ?>

				</label>
			</div>

			<div class="ss-right-section">
				<input type="<?php echo esc_attr( $this->value_type ); ?>" <?php echo $this->placeholder; // phpcs:ignore ?> name="<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $this->value ); ?>" <?php echo esc_html( $additional_params ); ?>/>

				<?php if ( $this->countchar ) { ?>
				<div class="ss-count-char" data-count="<?php echo esc_attr( $this->countchar ); ?>"><strong><?php echo esc_html( $this->countchar ); ?></strong> <?php esc_html_e( 'characters remaining' ); ?></div>
				<?php } ?>

			</div>
		</div>

		<?php
		return ob_get_clean();
	}
}
