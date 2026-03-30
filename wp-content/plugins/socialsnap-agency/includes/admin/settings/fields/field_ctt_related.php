<?php
/**
 * Social Snap Click To tweet related accounts.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_ctt_related {

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

		if ( ! is_array( $this->value ) ) {
			$this->value = array();
		}

		if ( ! isset( $this->value[0] ) ) {
			$this->value[0] = array(
				'username' => '',
				'desc'     => '',
			);
		}

		if ( ! isset( $this->value[1] ) ) {
			$this->value[1] = array(
				'username' => '',
				'desc'     => '',
			);
		}

		ob_start();
		?>
		<div class="ss-field-wrapper ss-field-spacing ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-field-title">
				<?php echo wp_kses_post( $this->name ); ?>

				<?php if ( $this->description ) { ?>
					<i class="ss-tooltip ss-question-mark" data-title="<?php echo esc_attr( $this->description ); ?>"><?php echo socialsnap()->icons->get_svg( 'info' ); // phpcs:ignore ?></i>
				<?php } ?>	
			</div>

			<div class="ss-field-element ss-twitter-related-fields ss-clearfix">

				<input type="text" placeholder="@username_1" name="<?php echo esc_attr( $this->id ); ?>[0][username]" id="<?php echo esc_attr( $this->id ); ?>_username_0" value="<?php echo esc_attr( $this->value[0]['username'] ); ?>"/>

				<textarea placeholder="Optional. Shortly describe how @username_1 is related to your Twitter account." name="<?php echo esc_attr( $this->id ); ?>[0][desc]" id="<?php echo esc_attr( $this->id ); ?>_desc_0" rows="2"><?php echo wp_kses( $this->value[0]['desc'], socialsnap_get_allowed_html_tags( 'post' ) ); ?></textarea>

				<input type="text" placeholder="@username_2" name="<?php echo esc_attr( $this->id ); ?>[1][username]" id="<?php echo esc_attr( $this->id ); ?>_username_1" value="<?php echo esc_attr( $this->value[1]['username'] ); ?>"/>

				<textarea placeholder="Optional. Shortly describe how @username_2 is related to your Twitter account." name="<?php echo esc_attr( $this->id ); ?>[1][desc]" id="<?php echo esc_attr( $this->id ); ?>_desc_1" rows="2"><?php echo wp_kses( $this->value[1]['desc'], socialsnap_get_allowed_html_tags( 'post' ) ); ?></textarea>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
