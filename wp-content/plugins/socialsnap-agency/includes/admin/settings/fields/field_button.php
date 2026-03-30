<?php
/**
 * Social Snap button field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.9
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_button {
	
	public $field;
	public $description;
	public $name;
	public $id;
	public $action;
	public $href;
	public $text;
	public $confirm;
	public $dependency;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.9
	 */
	public function __construct( $value ) {
		$this->field       = $value['type'];
		$this->description = isset( $value['desc'] ) ? $value['desc'] : '';
		$this->name        = isset( $value['name'] ) ? $value['name'] : '';
		$this->id          = $value['id'];
		$this->action      = isset( $value['action'] ) ? $value['action'] : '';
		$this->href        = isset( $value['href'] ) ? $value['href'] : '#';
		$this->text        = isset( $value['text'] ) ? $value['text'] : __( 'Button', 'socialsnap' );
		$this->confirm     = isset( $value['confirm'] ) ? $value['confirm'] : '';
		$this->dependency  = isset( $value['dependency'] ) ? $value['dependency'] : '';
	}

	/**
	 * HTML Output of the field
	 *
	 * @since 1.0.9
	 */
	public function render() {

		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-button-element ss-clearfix ss-field-spacing" id="<?php echo esc_attr( $this->id ); ?>"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<?php if ( ! empty( $this->name ) || ! empty( $this->description ) ) { ?>
				<div class="ss-field-title">
					<?php echo wp_kses_post( $this->name ); ?>

					<?php if ( $this->description ) { ?>
						<i class="ss-tooltip ss-question-mark" data-title="<?php echo esc_attr( $this->description ); ?>"><?php echo socialsnap()->icons->get_svg( 'info' ); // phpcs:ignore ?></i>
					<?php } ?>
				</div>
			<?php } ?>

			<div class="ss-field-element ss-clearfix">
				<a 
					href="<?php echo esc_url( $this->href ); ?>"
					class="ss-button ss-small-button"
					id="ss-settings-button-<?php echo esc_attr( $this->id ); ?>"
					<?php
					if ( $this->action ) {
						echo ' data-action="' . esc_attr( $this->action ) . '"'; }
					?>
					<?php
					if ( $this->confirm ) {
						echo ' data-confirm="' . esc_attr( $this->confirm ) . '"'; }
					?>
					>
					<?php echo esc_html( $this->text ); ?>	
				</a>
				<span class="spinner"></span>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}
}
