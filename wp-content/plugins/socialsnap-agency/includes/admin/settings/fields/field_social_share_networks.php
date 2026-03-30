<?php
/**
 * Social Snap social share networks field.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_social_share_networks {

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

		if ( ! wp_script_is( 'jquery-ui-sortable', 'enqueued' ) ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
		}

		add_action( 'socialsnap_live_preview', array( $this, 'add_network_popup' ) );
	}

	/**
	 * Popup to select a network
	 *
	 * @since 1.0.0
	 */
	public function add_network_popup() {

		$all_networks = socialsnap_get_social_share_networks();
		$mobile_only  = socialsnap_get_mobile_only_social_share_networks();
		?>

		<div id="ss-ss-networks-popup" class="ss-add-networks-popup ss-hidden">
			<h4>
				<?php esc_html_e( 'Add Networks', 'socialsnap' ); ?>
				<a href="#" id="ss-close-share-networks-modal" class="ss-close-modal"><?php echo socialsnap()->icons->get_svg( 'close' ); // phpcs:ignore ?></a>
			</h4>

			<div class="ss-popup-content">
				<div class="ss-popup-networks ss-clearfix">

					<?php foreach ( $all_networks as $network => $name ) { ?>
						<div class="ss-popup-network">
							<a href="#" data-id="<?php echo esc_attr( $network ); ?>" data-name="<?php echo esc_attr( $name ); ?>" data-mobile-only="<?php echo esc_attr( in_array( $network, array_keys( $mobile_only ), true ) ); ?>" class="ss-<?php echo esc_attr( $network ); ?>-color"><i class="ss"><?php echo socialsnap()->icons->get_svg( $network ); // phpcs:ignore ?></i><?php echo wp_kses_post( $name ); ?><span><i class="ss"><?php echo socialsnap()->icons->get_svg( 'plus' ); // phpcs:ignore ?><?php echo socialsnap()->icons->get_svg( 'close' ); // phpcs:ignore ?></i></span></a>
						</div>
					<?php } ?>

				</div><!-- END .ss-popup-networks -->

				<?php if ( ! socialsnap()->pro ) { ?>
					<div class="ss-pro-notice">
						<?php
						esc_html_e( 'Unlock 30+ share networks, share counters for all networks, URL shortening, custom share images and descriptions and more awesome features!', 'socialsnap' );
						?>

						<a href="<?php echo esc_url( socialsnap_upgrade_link() ); ?>" target="_blank"><?php echo esc_html( apply_filters( 'socialsnap_upgrade_button_text', __( 'Upgrade Now', 'socialsnap' ) ) ); ?></a>
					</div><!-- END .ss-pro-notice -->
				<?php } ?>

			</div><!-- END .ss-popup-content -->

		</div><!-- END #ss-ss-networks-popup -->
		<?php
	}

	/**
	 * HTML output of the field
	 *
	 * @since 1.0.0
	 */
	public function render() {

		$mobile_only     = socialsnap_get_mobile_only_social_share_networks();
		$values          = apply_filters( 'socialsnap_filter_social_share_networks', $this->value );
		$values['order'] = isset( $values['order'] ) ? $values['order'] : '';
		$order           = explode( ';', trim( $values['order'] ) );

		ob_start();
		?>

		<div id="<?php echo esc_attr( $this->id ); ?>_wrapper" class="ss-field-wrapper ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<div class="ss-field-title">
				<?php echo esc_html( $this->name ); ?>

				<?php if ( $this->description ) { ?>
					<i class="ss-tooltip ss-question-mark" data-title="<?php echo esc_attr( $this->description ); ?>"><?php echo socialsnap()->icons->get_svg( 'info' ); // phpcs:ignore ?></i>
				<?php } ?>	
			</div>

			<div class="ss-field-element ss-share-networks ss-clearfix" id="<?php echo esc_attr( $this->id ); ?>">

			<?php
			if ( is_array( $order ) && count( $order ) == 1 && ! $order[0] ) {
				$order = array();
			}

			if ( is_array( $order ) && ! empty( $order ) ) {
				foreach ( $order as $network ) {

					if ( ! isset( $values[ $network ]['desktop_visibility'] ) ) {
						$values[ $network ]['desktop_visibility'] = false;
					}

					if ( ! isset( $values[ $network ]['mobile_visibility'] ) ) {
						$values[ $network ]['mobile_visibility'] = false;
					}

					if ( ! isset( $values[ $network ]['text'] ) ) {
						$values[ $network ]['text'] = ucfirst( $network );
					}
					?>
					<div class="ss-ss-network" data-id="<?php echo esc_attr( $network ); ?>">
						<i class="ss ss-<?php echo esc_attr( $network ); ?>-color"><?php echo socialsnap()->icons->get_svg( $network ); // phpcs:ignore ?></i>
						<input type="text" class="ss-ss-name" name="<?php echo esc_attr( $this->id ); ?>[<?php echo esc_attr( $network ); ?>][text]" value="<?php echo esc_attr( $values[ $network ]['text'] ); ?>" placeholder="<?php esc_html_e( 'Enter network label', 'socialsnap' ); ?>" />

						<div class="ss-ss-actions">
							<a href="#" class="ss-ss-edit ss-tooltip" data-title="<?php esc_html_e( 'Change label', 'socialsnap' ); ?>"><i class="ss"><?php echo socialsnap()->icons->get_svg( 'edit' ); // phpcs:ignore ?></i></a>

							<div class="ss-ss-mobile-visibility ss-tooltip" data-title="<?php esc_html_e( 'Device visibility', 'socialsnap' ); ?>"><i class="ss"><?php echo socialsnap()->icons->get_svg( 'eye' ); // phpcs:ignore ?></i>
								<ul class="ss-ss-visibility-dropdown">

									<?php
									if ( ! in_array( $network, array_keys( $mobile_only ), true ) ) {
										?>
										<li><?php esc_html_e( 'Desktop', 'socialsnap' ); ?>
											<span class="ss-small-toggle">
												<input type="checkbox" id="ss-ss-visibility-desktop-<?php echo esc_attr( $network ); ?>" name="<?php echo esc_attr( $this->id ); ?>[<?php echo esc_attr( $network ); ?>][desktop_visibility]" <?php checked( $values[ $network ]['desktop_visibility'], 'on' ); ?>>
												<label for="ss-ss-visibility-desktop-<?php echo esc_attr( $network ); ?>"></label>
											</span>
										</li>

										<li><?php esc_html_e( 'Mobile', 'socialsnap' ); ?>
											<span class="ss-small-toggle">
												<input type="checkbox" id="ss-ss-visibility-mobile-<?php echo esc_attr( $network ); ?>" name="<?php echo esc_attr( $this->id ); ?>[<?php echo esc_attr( $network ); ?>][mobile_visibility]" <?php checked( $values[ $network ]['mobile_visibility'], 'on' ); ?>>
												<label for="ss-ss-visibility-mobile-<?php echo esc_attr( $network ); ?>"></label>
											</span>
										</li>
										<?php
									} else {
										esc_html_e( 'Mobile only', 'socialsnap' );
									}
									?>

								</ul>
							</div><!-- END .ss-ss-mobile-visibility -->

							<a href="#" class="ss-ss-remove ss-tooltip" data-title="<?php esc_html_e( 'Remove', 'socialsnap' ); ?>"><i class="ss"><?php echo socialsnap()->icons->get_svg( 'close' ); // phpcs:ignore ?></i></a>
						</div>
					</div><!-- END .ss-ss-network -->
					<?php
				}
			}
			?>

			<input type="hidden" name="<?php echo esc_attr( $this->id ); ?>[order]" value="<?php echo esc_attr( $values['order'] ); ?>" class="ss-social-share-order"/>
			</div>

			<a href="#" class="ss-button ss-secondary ss-small-button" id="ss-add-share-networks"><?php echo socialsnap()->icons->get_svg( 'plus' ); // phpcs:ignore ?><?php esc_html_e( 'Add Networks', 'socialsnap' ); ?></a>

			<div class="ss-note">
				<em><?php esc_html_e( 'These networks apply to all locations.', 'socialsnap' ); ?></em>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}
}
