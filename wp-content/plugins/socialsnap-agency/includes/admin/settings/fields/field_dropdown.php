<?php
/**
 * Social Snap dropdown field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_dropdown {
	
	public $field;
	public $name;
	public $id;
	public $default;
	public $value;
	public $description;
	public $options;
	public $dependency;
	public $pro;
	public $pro_info;
	public $multiselect;
	public $source;

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
		$this->pro         = isset( $value['pro'] ) ? $value['pro'] : false;
		$this->pro_info    = isset( $value['pro_info'] ) ? $value['pro_info'] : '';
		$this->multiselect = isset( $value['multiselect'] ) ? $value['multiselect'] : false;
		$this->source      = isset( $value['source'] ) ? $value['source'] : '';
	}

	/**
	 * HTML output of the field
	 *
	 * @since 1.0.0
	 */
	public function render() {

		$options = $this->get_options();
		$current = '';

		$multiselect = $this->multiselect ? ' data-multiselect="1"' : '';

		ob_start();

		$is_pro       = $this->pro && socialsnap()->pro || ! $this->pro;
		$is_pro_class = $is_pro ? '' : ' ss-pro-feature';
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

					<div class="ss-dropdown-wrapper">

						<?php if ( ! $this->multiselect ) { ?>
							<input type="hidden" name="<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $this->value ); ?>" />
						<?php } ?>

						<a href="#" class="ss-dropdown">

							<span>
							<?php
							if ( ! $this->multiselect ) {
								if ( $this->value && isset( $options[ $this->value ] ) ) {
									echo esc_html( $options[ $this->value ] );
								}
							} else {
								esc_html_e( 'Select Option', 'socialsnap' );
							}
							?>
							</span>

							<i class="dashicons dashicons-arrow-down"></i>

							<?php if ( is_array( $options ) && ! empty( $options ) ) { ?> 
								<ul class="ss-dropdown-values"<?php echo ( $multiselect ); // phpcs:ignore ?>>

									<?php
									foreach ( $options as $key => $value ) {
										$current = '';
										if ( ! $this->multiselect && $key === $this->value ) {
											$current = ' class="ss-current"';
										}

										$style = '';
										if ( $this->multiselect && in_array( $key, (array) $this->value ) ) {
											$style = ' style="display:none;"';
										}
										?>
										<li data-value="<?php echo esc_attr( $key ); ?>"<?php echo esc_html( $current ); ?><?php echo esc_html( $style ); ?>
										><?php echo esc_html( $value ); ?></li>
									<?php } ?>
								</ul>
							<?php } ?>

						</a>

						<?php
						if ( $this->multiselect ) {
							?>
							<div class="ss-dropdown-selected-values">
							<?php
							if ( is_array( $this->value ) && ! empty( $this->value ) ) {
								foreach ( $this->value as $value ) {
									if ( isset( $options[ $value ] ) ) {
										echo '<div class="ss-dropdown-single-value">' . esc_html( $options[ $value ] ) . '<a href="#" class="ss-ss-remove">' . socialsnap()->icons->get_svg( 'close' ) . '</i></a><input type="hidden" name="' . esc_attr( $this->id ) . '[]" value="' . esc_attr( $value ) . '" /></div>'; // phpcs:ignore
									}
								}
							}
							?>
							<input type="hidden" value="" name="<?php echo esc_attr( $this->id ); ?>[]" class="ss-trigger" />
							</div>
							<?php
						}
						?>
					</div><!-- END .ss-dropdown-wrapper -->

				</div>
			<?php } ?>
		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Get Dropdown options
	 *
	 * @since 1.0.0
	 */
	private function get_options() {

		if ( ! $this->source ) {
			return $this->options;
		}

		if ( 'taxonomies' === $this->source ) {

			$args = array(
				'public' => true,
			);

			$taxonomies = get_taxonomies( $args, 'objects' );

			$return = array();

			if ( is_array( $taxonomies ) && ! empty( $taxonomies ) ) {

				foreach ( $taxonomies as $taxonomy ) {
					$terms = get_terms(
						array(
							'taxonomy'   => $taxonomy->name,
							'hide_empty' => false,
						)
					);

					if ( is_array( $terms ) && ! empty( $terms ) ) {

						foreach ( $terms as $term ) {
							$return[ $taxonomy->name . ';' . $term->term_id ] = $taxonomy->labels->singular_name . ' - ' . $term->name;
						}
					}
				}
			}

			return $return;
		}
	}
}
