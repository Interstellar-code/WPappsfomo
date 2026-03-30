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
class SocialSnap_Field_editor_toggle {
	
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 */
	public function render() {

		if ( $this->value || 1 === $this->value || true === $this->value ) {
			$this->value = 'on';
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-field-toggle ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-left-section">
				<label for="<?php echo esc_attr( $this->id ); ?>"><strong><?php echo wp_kses_post( $this->name ); ?></strong>

					<?php if ( $this->description ) { ?>
					<span class="ss-desc"><?php echo wp_kses_post( $this->description ); ?></span>
					<?php } ?>

				</label>
			</div>

			<div class="ss-right-section">

				<span class="ss-small-toggle">
					<input type="checkbox" id="<?php echo esc_attr( $this->id ); ?>" name="<?php echo esc_attr( $this->id ); ?>" <?php checked( 'on', $this->value, true ); ?> />
					<label for="<?php echo esc_attr( $this->id ); ?>"></label>
				</span>
			</div>

		</div>

		<?php
		return ob_get_clean();
	}
}
