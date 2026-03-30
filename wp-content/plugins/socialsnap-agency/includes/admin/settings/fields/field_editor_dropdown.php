<?php
/**
 * Social Snap dropdown editor field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_editor_dropdown {

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

		$options = $this->options;
		$current = '';

		ob_start();
		?>

		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-field-dropdown ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-left-section">
				<label for="<?php echo esc_attr( $this->id ); ?>"><strong><?php echo wp_kses_post( $this->name ); ?></strong>

					<?php if ( $this->description ) { ?>
					<span class="ss-desc"><?php echo wp_kses( $this->description, socialsnap_get_allowed_html_tags( 'post' ) ); ?></span>
					<?php } ?>

				</label>
			</div>

			<div class="ss-right-section">

				<?php if ( is_array( $this->options ) && ! empty( $this->options ) ) { ?> 
					<select name="<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>">

						<?php
						foreach ( $this->options as $key => $value ) {
							echo '<option value="' . esc_attr( $key ) . '"' . selected( $key, $this->value, false ) . '>' . esc_html( $value ) . '</option>';
						}
						?>

					</select>
				<?php } ?>
			</div>

		</div>

		<?php
		return ob_get_clean();
	}
}
