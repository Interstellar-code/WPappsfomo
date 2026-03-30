<?php
/**
 * Adds custom fields to User Profile Page
 *
 * @package    SocialSnap
 * @author     SocialSnap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_User_Profile {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'show_user_profile', array( $this, 'custom_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'custom_user_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'update_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'update_user_profile_fields' ) );
	}

	/**
	 * Add new fields to User Profile page
	 *
	 * @param  object $user The user object
	 * @since  1.0.0
	 */
	public function custom_user_profile_fields( $user ) { ?>

		<h3 id="ss-user-info"><?php esc_html_e( 'Social Snap User Info', 'socialsnap' ); ?>:</h3>

		<table class="form-table">
			<tr>
				<th><label for="ss_twitter_author"><?php esc_html_e( 'Twitter Username', 'socialsnap' ); ?></label></th>
				<td>
					<input type="text" name="ss_twitter_author" id="ss_twitter_author" value="<?php echo esc_attr( get_the_author_meta( 'ss_twitter_author', $user->ID ) ); ?>" placeholder="@username" class="regular-text" />
					<br /><span class="description"><?php esc_html_e( 'Please enter your Twitter username.', 'socialsnap' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="ss_facebook_author"><?php esc_html_e( 'Facebook Profile URL', 'socialsnap' ); ?></label></th>
				<td>
					<input type="text" name="ss_facebook_author" id="ss_facebook_author" value="<?php echo esc_attr( get_the_author_meta( 'ss_facebook_author', $user->ID ) ); ?>" placeholder="https://facebook.com/username" class="regular-text" />
					<br /><span class="description"><?php esc_html_e( 'Please enter the URL of your Facebok profile.', 'socialsnap' ); ?></span>
				</td>
			</tr>

		</table>
		<?php
	}

	/**
	 * Save custom user profile fields to user meta
	 *
	 * @param  object $user The user object
	 * @since  1.0.0
	 */
	public function update_user_profile_fields( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		update_user_meta( $user_id, 'ss_twitter_author', sanitize_text_field( $_POST['ss_twitter_author'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		update_user_meta( $user_id, 'ss_facebook_author', sanitize_text_field( $_POST['ss_facebook_author'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

}
new SocialSnap_User_Profile();
