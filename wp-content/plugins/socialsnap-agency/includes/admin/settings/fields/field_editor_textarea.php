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
class SocialSnap_Field_editor_textarea {

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
	public $countchar;
	public $ctt;

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
		$this->countchar   = isset( $value['countchar'] ) ? $value['countchar'] : false;
		$this->ctt         = isset( $value['ctt'] ) ? $value['ctt'] : false;
	}

	/**
	 * HTML output of the field
	 *
	 * @since 1.0.0
	 */
	public function render() {

		$additional_params = '' !== $this->default ? ' class="ss-has-default"' : '';

		if ( $this->ctt && $this->countchar ) {

			$username = strlen( apply_filters( 'socialsnap_sanitize_username', socialsnap_settings( 'ss_twitter_username' ) ) );

			if ( $username > 0 ) {
				$this->countchar -= 6 + $username;
			}

			if ( socialsnap_settings( 'ss_ctt_include_link' ) ) {
				$args             = array(
					'permalink' => get_permalink(),
					'network'   => 'twitter',
				);
				$this->countchar -= 1 + strlen( socialsnap_get_shared_permalink( $args ) );
			}
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-field-textarea ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-left-section">
				<label for="<?php echo esc_attr( $this->id ); ?>"><strong><?php echo wp_kses_post( $this->name ); ?></strong>

					<?php if ( $this->description ) { ?>
					<span class="ss-desc"><?php echo wp_kses_post( $this->description ); ?></span>
					<?php } ?>

				</label>
			</div>

			<div class="ss-right-section">
				<textarea <?php echo $this->placeholder; // phpcs:ignore ?> name="<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>" rows="<?php echo esc_attr( $this->rows ); ?>"<?php echo esc_html( $additional_params ); ?>><?php echo wp_kses_post( $this->value ); ?></textarea>

				<?php if ( $this->countchar ) { ?>
				<div class="ss-count-char" data-count="<?php echo esc_attr( $this->countchar ); ?>"><strong><?php echo esc_attr( $this->countchar ); ?></strong> <?php esc_html_e( 'characters remaining' ); ?></div>
				<?php } ?>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
