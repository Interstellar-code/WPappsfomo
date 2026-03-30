<?php 
namespace HappyFiles;

$setting_user_roles = get_option( HAPPYFILES_SETTING_USER_ROLES, [] );

if ( empty( $setting_user_roles ) ) {
  $setting_user_roles = [];
}
?>

<div class="wrap">
  <h1 class="wp-heading-inline" style="margin-bottom: 15px"><?php esc_html_e( 'HappyFiles Settings', 'happyfiles' ); ?></h1>

  <form action="options.php" method="post">
    <?php settings_fields( HAPPYFILES_SETTINGS_GROUP ); ?>
    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th><?php esc_html_e( 'Category Editing', 'happyfiles' ); ?></th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span><?php esc_html_e( 'Category Editing', 'happyfiles' ); ?></span></legend>
              
              <?php 
              // $user_roles = get_editable_roles(); // NOTE: Don't use as we need to save user role name, not the label (e.g.: 'shop_manager' instead of "Shop manager")
              
              global $wp_roles;
              $user_roles = $wp_roles->role_names;

              foreach ( $user_roles as $user_role_name => $user_role_label ) {
                if ( strtolower( $user_role_name ) === 'administrator' ) {
                  continue;
                }
                ?>
                
                <label for="<?php echo esc_attr( strtolower( HAPPYFILES_SETTING_USER_ROLES . '_' . $user_role_name ) ); ?>">
                <input name="<?php echo HAPPYFILES_SETTING_USER_ROLES . '[]'; ?>" type="checkbox" id="<?php echo esc_attr( strtolower( HAPPYFILES_SETTING_USER_ROLES . '_' . $user_role_name ) ); ?>" value="<?php echo esc_attr( $user_role_name ); ?>" <?php checked( in_array( $user_role_name, $setting_user_roles ), true, true ); ?>/>
                <?php echo  $user_role_label; ?>
                </label>

                <br>
              <?php } ?>
              <p class="description"><?php esc_html_e( 'Only selected user roles can create, rename, delete, arrange and upload to categories. Administrators always have full control over file categories.', 'happyfiles' ); ?></p>
            </fieldset>
          </td>
        </tr>

        <tr>
          <th><?php esc_html_e( 'Assign multiple categories', 'happyfiles' ); ?></th>
          <td>
            <fieldset>
              <?php 
              $multiple_categories = get_option( HAPPYFILES_SETTING_MULTIPLE_CATEGORIES, false );
              ?>
              <input type="checkbox" name="<?php echo esc_attr( HAPPYFILES_SETTING_MULTIPLE_CATEGORIES ); ?>" id="<?php echo esc_attr( HAPPYFILES_SETTING_MULTIPLE_CATEGORIES ); ?>" value="1" <?php checked( $multiple_categories, true, true); ?>>
            </fieldset>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'happyfiles' ); ?>"></p>
  </form>

</div>