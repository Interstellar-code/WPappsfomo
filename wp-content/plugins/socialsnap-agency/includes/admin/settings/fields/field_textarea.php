<?php
/**
 * Social Snap textarea field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_textarea {

	public $field;
	public $name;
	public $id;
	public $default;
	public $value;
	public $rows;
	public $description;
	public $code;
	public $placeholder;
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
		$this->rows        = isset( $value['rows'] ) ? $value['rows'] : 6;
		$this->description = isset( $value['desc'] ) ? $value['desc'] : '';
		$this->code        = isset( $value['code'] ) ? ' code-style' : '';
		$this->placeholder = isset( $value['placeholder'] ) ? 'placeholder="' . esc_attr( $value['placeholder'] ) . '"' : '';
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
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-field-spacing ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-field-title">

				<?php echo wp_kses_post( $this->name ); ?>

				<?php if ( $this->description ) { ?>
					<i class="ss-tooltip ss-question-mark" data-title="<?php echo esc_attr( $this->description ); ?>"><?php echo socialsnap()->icons->get_svg( 'info' ); // phpcs:ignore ?></i>
				<?php } ?>	
			</div>

			<div class="ss-field-element ss-clearfix">
				<textarea <?php echo $this->placeholder; // phpcs:ignore ?> name="<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>" rows="<?php echo esc_attr( $this->rows ); ?>"><?php echo wp_kses( $this->value, socialsnap_get_allowed_html_tags( 'post' ) ); ?></textarea>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
