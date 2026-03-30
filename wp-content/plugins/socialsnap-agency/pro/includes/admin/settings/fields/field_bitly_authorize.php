<?php
/**
 * SocialSnap authorize bitly field.
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Field_bitly_authorize {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $value ) {
		$this->field      = $value['type'];
		$this->id         = $value['id'];
		$this->dependency = isset( $value['dependency'] ) ? $value['dependency'] : '';
	}

	/**
	 * HTML output of the field
	 *
	 * @since 1.0.0
	 */
	public function render() {

		$user_data    = (array) get_option( 'socialsnap_bitly_user_data' );
		$access_token = isset( $user_data['access_token'] ) ? $user_data['access_token'] : false;

		$authorize_url = add_query_arg(
			array(
				'network'    => 'bitly',
				'client_url' => rawurlencode( add_query_arg( array( 'page' => 'socialsnap-settings#ss_link_shortening-ss' ), admin_url( 'admin.php' ) ) ),
			),
			'https://socialsnap.com/wp-json/api/v1/authorize'
		);

		ob_start(); ?>
		<div class="ss-field-wrapper ss-field-spacing ss-clearfix"<?php SocialSnap_Fields::dependency_builder( $this->dependency ); ?>>

			<?php if ( $access_token ) { ?>

				<?php
				$disconnect_url = add_query_arg(
					array(
						'ss_network_authorized' => 'bitly',
						'disconnect'            => true,
						'page'                  => 'socialsnap-settings#ss_link_shortening-ss',
					),
					admin_url( 'admin.php' )
				);
				?>

				<span class="ss-bitly-authorized"><i class="dashicons dashicons-yes"></i><?php esc_html_e( 'Bitly connected.', 'socialsnap' ); ?></span>
				<a href="<?php echo esc_url( $disconnect_url ); ?>" data-network="bitly" class="ss-disconnect-authenticated-user"><?php esc_html_e( 'Disconnect?', 'socialsnap' ); ?></a>
			<?php } else { ?>
				<p class="ss-bitly-desc"><?php esc_html_e( 'Please authorize Social Snap to connect with your Bitly account.', 'socialsnap' ); ?></p>
				<a href="<?php echo esc_url( $authorize_url ); ?>" class="ss-button ss-small-button ss-authorize-bitly-button"><?php esc_html_e( 'Authorize', 'socialsnap' ); ?></a>
			<?php } ?>

		</div>
		<?php
		return ob_get_clean();
	}
}
