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
class SocialSnap_Field_text {

	public $field;
	public $name;
	public $id;
	public $default;
	public $value;
	public $description;
	public $readonly;
	public $value_type;
	public $placeholder;
	public $dependency;
	public $pro;
	public $pro_info;
	public $min;

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
		$this->readonly    = isset( $value['readonly'] ) ? ' readonly' : '';
		$this->value_type  = isset( $value['value_type'] ) ? $value['value_type'] : 'text';
		$this->placeholder = isset( $value['placeholder'] ) ? 'placeholder="' . esc_attr( $value['placeholder'] ) . '"' : '';
		$this->dependency  = isset( $value['dependency'] ) ? $value['dependency'] : '';
		$this->pro         = isset( $value['pro'] ) ? $value['pro'] : false;
		$this->pro_info    = isset( $value['pro_info'] ) ? $value['pro_info'] : '';
		$this->min         = isset( $value['min'] ) ? intval( $value['min'] ) : 0;
	}

	/**
	 * HTML output of the field
	 *
	 * @since 1.0.0
	 */
	public function render() {

		$additional_params = '';

		if ( 'number' === $this->value_type ) {
			$additional_params = ' min="' . $this->min . '" step="1"';

			$this->value = $this->value ? $this->value : '0';
			$this->value = $this->value > $this->min ? $this->value : $this->min;
		}

		$is_pro       = $this->pro && socialsnap()->pro || ! $this->pro;
		$is_pro_class = $is_pro ? '' : ' ss-pro-feature';
		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-field-spacing ss-clearfix<?php echo esc_attr( $is_pro_class ); ?>"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-field-title">
				<span><?php echo wp_kses_post( $this->name ); ?></span>

				<?php if ( $this->description ) { ?>
					<i class="ss-tooltip ss-question-mark" data-title="<?php echo esc_attr( $this->description ); ?>"><?php echo socialsnap()->icons->get_svg( 'info' ); // phpcs:ignore ?></i>
				<?php } ?>	

				<?php
				if ( ! $is_pro ) {
					socialsnap_settings_upgrade_button( $this->pro_info );
				}
				?>
			</div>

			<?php if ( $is_pro ) { ?>

				<div class="ss-field-element ss-clearfix">
					<input type="<?php echo esc_attr( $this->value_type ); ?>" <?php echo $this->placeholder; // phpcs:ignore ?> name="<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $this->value ); ?>" <?php echo esc_html( $additional_params ); ?><?php echo esc_html( $this->readonly ); ?> />
				</div>
			<?php } ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
