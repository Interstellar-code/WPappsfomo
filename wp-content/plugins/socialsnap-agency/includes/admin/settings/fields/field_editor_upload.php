<?php
/**
 * Social Snap upload field.
 *
 * @package   Social Snap
 * @author    Social Snap
 * @since     1.0.0
 * @license   GPL-3.0+
 * @copyright Copyright (c) 2020, Social Snap LLC
 */
class SocialSnap_Field_editor_upload {

	public $field;
	public $name;
	public $id;
	public $default;
	public $value;
	public $description;
	public $extradesc;
	public $allowed_type;
	public $button_caption;
	public $dependency;
	public $multiple;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $value ) {

		$this->field          = $value['type'];
		$this->name           = $value['name'];
		$this->id             = $value['id'];
		$this->default        = isset( $value['default'] ) ? $value['default'] : '';
		$this->value          = isset( $value['value'] ) ? $value['value'] : '';
		$this->description    = isset( $value['desc'] ) ? $value['desc'] : '';
		$this->extradesc      = isset( $value['extradesc'] ) ? $value['extradesc'] : '';
		$this->allowed_type   = isset( $value['allowed_type'] ) ? $value['allowed_type'] : array( 'image' );
		$this->button_caption = isset( $value['button_caption'] ) ? $value['button_caption'] : esc_html__( 'Upload Image', 'socialsnap' );
		$this->dependency     = isset( $value['dependency'] ) ? $value['dependency'] : '';
		$this->multiple       = isset( $value['multiple'] ) ? boolval( $value['multiple'] ) : false;
	}

	/**
	 * HTML Output of the field.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render() {

		$class     = empty( $this->value ) ? ' hidden' : '';
		$id        = is_array( $this->value ) && isset( $this->value['id'] ) ? $this->value['id'] : $this->value;
		$edit      = '#';
		$mime_type = '';

		// We have an upload.
		if ( ! empty( $id ) ) {

			$edit = admin_url( 'post.php?post=' . $id . '&action=edit' );

			// Determine the type of the uploaded file.
			$mime_type = get_post_mime_type( $id );
			$mime_type = explode( '/', $mime_type );
			if ( is_array( $mime_type ) && ! empty( $mime_type ) ) {
				$mime_type = $mime_type[0];
			}
		}

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $this->id ); ?>_wrapper"
			class="ss-field-wrapper ss-field-upload ss-upload-element ss-clearfix"
			<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-left-section">
				<label for="<?php echo esc_attr( $this->id ); ?>">

					<strong><?php echo wp_kses_post( $this->name ); ?></strong>

					<?php if ( ! empty( $this->description ) ) { ?>
						<span class="ss-desc"><?php echo wp_kses_post( $this->description ); ?></span>
					<?php } ?>

				</label>
			</div>

			<div class="ss-right-section">


				<input
					id="<?php echo esc_attr( $this->id ); ?>_img_id"
					type="hidden"
					name="<?php echo esc_attr( $this->id ); ?>"
					value="<?php echo esc_attr( $id ); ?>" />

				<a
					href="#"
					class="ss-upload-button ss-button"
					id="<?php echo esc_attr( $this->id ); ?>_button"
					data-type="<?php echo esc_attr( implode( ',', $this->allowed_type ) ); ?>"
					data-multiple="<?php echo intval( $this->multiple ); ?>">
					<?php echo esc_html( $this->button_caption ); ?>
				</a>

				<div class="wp-clearfix"></div>

				<div
					id="<?php echo esc_attr( $this->id ); ?>-preview"
					class="show-upload-image mime-type-<?php echo esc_attr( $mime_type ); ?>">

					<?php if ( ! empty( $id ) ) { ?>

						<?php
						if ( false !== strpos( $mime_type, 'image' ) ) {
							$src  = wp_get_attachment_image_src( $id, 'medium' );
							$full = wp_get_attachment_image_src( $id, 'full' );
							echo '<img src="' . esc_attr( $src[0] ) . '" alt="' . esc_attr( $this->name ) . '"/>';
						} elseif ( false !== strpos( $mime_type, 'video' ) ) {
							$src = get_attached_file( $id );
							echo '<span class="ss-video-name">' . esc_html( basename( $src ) ) . '</span>';
						}
						?>

					<?php } ?>

					<div class="ss-image-tools">

						<a href="#" class="ss-remove-image<?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $this->id ); ?>_remove"><i class="ss"><?php echo socialsnap()->icons->get_svg( 'close' ); // phpcs:ignore ?></i></a>

						<a href="<?php echo esc_attr( $edit ); ?>" id="<?php echo esc_attr( $this->id ); ?>_edit" target="_blank"><i class="dashicons dashicons-edit"></i></a>

						<span class="ss-image-dimension" id="<?php echo esc_attr( $this->id ); ?>_dimension">
							<?php
							if ( isset( $mime_type ) && false !== strpos( $mime_type, 'image' ) && isset( $full ) && isset( $full[1] ) && isset( $full[2] ) ) {
								echo esc_html( $full[1] ) . 'px x ' . esc_html( $full[2] ) . 'px';
							}
							?>
						</span>

					</div>
				</div>

				<?php if ( ! empty( $this->extradesc ) ) { ?>
					<div class="ss-extra-desc"><?php echo wp_kses_post( $this->extradesc ); ?></div>
				<?php } ?>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}
}
