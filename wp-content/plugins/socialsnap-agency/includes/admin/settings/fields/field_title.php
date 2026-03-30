<?php
/**
 * Social Snap title field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_title {

	public $field;
	public $name;
	public $id;
	public $dependency;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $value ) {
		$this->field      = $value['type'];
		$this->name       = $value['name'];
		$this->id         = $value['id'];
		$this->dependency = isset( $value['dependency'] ) ? $value['dependency'] : '';
	}

	/**
	 * HTML output of the field
	 *
	 * @since 1.0.0
	 */
	public function render() {
		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-settings-title"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>
			<?php echo wp_kses_post( $this->name ); ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
