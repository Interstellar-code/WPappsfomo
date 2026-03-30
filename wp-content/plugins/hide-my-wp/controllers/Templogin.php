<?php
/**
 * Temporary Login Class
 * Called on Temporary Logins
 *
 * @file The Temporary Logins file
 * @package HMWP/Templogin
 * @since 7.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Controllers_Templogin extends HMWP_Classes_FrontController
{

    public function hookInit() {

        //Don't show the Temporary Login menu for temporary logged users
        if ( $this->model->isValidTempLogin(get_current_user_id()) ) {
            add_filter('hmwp_menu', function ($menu) {
                unset($menu['hmwp_templogin']);

                return $menu;
            });

            add_filter('hmwp_features', function ($features) {
                foreach ($features as &$feature){
                    if($feature['option'] == 'hmwp_templogin'){
                        $feature['show'] = false;
                    }
                }

                return $features;
            });


            add_filter( 'locale', function ($locale ){
                if($hmwp_locale = get_user_meta( get_current_user_id(), 'locale', true )){
                    if($hmwp_locale <> 'en_US'){
                        return $hmwp_locale;
                    }
                }

                return $locale;
            }, 1, 1 );
        }

        //First, check if the user is still active
        $this->checkTempLoginExpired();
    }

    public function hookFrontinit() {

        //First, check if the user is still active
        $this->checkTempLoginExpired();

        if ( HMWP_Classes_Tools::getValue('hmwp_token') <> '') {
            //initialize the redirect
            $redirect_to = admin_url();

            //check if token is set
            $token = sanitize_key( HMWP_Classes_Tools::getValue('hmwp_token') );

            if ( !$user = $this->model->findUserByToken( $token ) ) {

                $redirect_to = home_url(); //redirect to home page

            }else{

                $do_login = true;
                if ( function_exists('is_user_logged_in') && is_user_logged_in() ) {
                    if ( $user->ID !== get_current_user_id() ) {
                        wp_logout();
                    } else {
                        $do_login = false;
                    }
                }

                if ( $do_login ) {
                    update_user_meta( $user->ID, '_hmwp_last_login', $this->model->gtmTimestamp() ); // phpcs:ignore
                    wp_set_current_user( $user->ID, $user->login );
                    wp_set_auth_cookie( $user->ID );

                    // Set login count
                    // If we already have a count, increment by 1
                    if ( $login_count = get_user_meta( $user->ID, '_hmwp_login_count', true ) ) {
                        $login_count ++;
                    } else {
                        $login_count = 1;
                    }

                    update_user_meta( $user->ID, '_hmwp_login_count', $login_count );
                    do_action( 'wp_login', $user->login, $user );

                    if($user->details->redirect_to <> ''){
                        $redirect_to = $user->details->redirect_to;
                    }
                }

            }

            wp_safe_redirect( $redirect_to ); // Redirect to given url after successful login.
            exit();
        }

    }

    /**
     * Check if the temporary login is still active
     *
     * @return void
     */
    public function checkTempLoginExpired(){

        // Restrict unauthorized page access for temporary users
        if ( function_exists('is_user_logged_in') && is_user_logged_in() ) {

            $user_id = get_current_user_id();
            if ( ! empty( $user_id ) && $this->model->isValidTempLogin( $user_id ) ) {


                if ( $this->model->isExpired( $user_id ) ) {

                    wp_logout();
                    wp_safe_redirect( home_url() );
                    exit();

                } else {

                    global $pagenow;
                    $restricted_pages = $this->model->getRestrictedPages();
                    $restricted_actions = $this->model->getRestrictedActions();
                    $page         = HMWP_Classes_Tools::getValue('page');
                    $action         = HMWP_Classes_Tools::getValue('action');

                    if ( $page <> '' && in_array( $page, $restricted_pages ) ||
                        ( ! empty( $pagenow ) && ( in_array( $pagenow, $restricted_pages ) ) ) ||
                        ( ! empty( $pagenow ) && ( 'users.php' === $pagenow && in_array( $action, $restricted_actions )  ) ) ) { //phpcs:ignore
                        wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
                    }

                }
            }
        }
    }

    /**
     * Admin actions
     */
    public function action()
    {
        parent::action();

        //if current user can't manage settings
        if (!HMWP_Classes_Tools::userCan('hmwp_manage_settings') ) {
            return;
        }

        //if current user is temporary user
        if( $this->model->isValidTempLogin(get_current_user_id()) ){
            return;
        }

        switch ( HMWP_Classes_Tools::getValue('action') ) {
            case 'hmwp_temploginsettings':

                HMWP_Classes_Tools::saveOptions('hmwp_templogin', HMWP_Classes_Tools::getValue('hmwp_templogin', 0));
                HMWP_Classes_Tools::saveOptions('hmwp_templogin_role', HMWP_Classes_Tools::getValue('hmwp_templogin_role', 0));
                HMWP_Classes_Tools::saveOptions('hmwp_templogin_redirect', HMWP_Classes_Tools::getValue('hmwp_templogin_redirect', ''));
                HMWP_Classes_Tools::saveOptions('hmwp_templogin_expires', HMWP_Classes_Tools::getValue('hmwp_templogin_expires', 'hour_after_access'));
                HMWP_Classes_Tools::saveOptions('hmwp_templogin_delete_uninstal', HMWP_Classes_Tools::getValue('hmwp_templogin_delete_uninstal', 0));

                break;
            case 'hmwp_templogin_new':
                $data = HMWP_Classes_Tools::getValue('hmwp_details', array());
                HMWP_Classes_Error::clearErrors();

                if ( empty( $data['user_email'] ) ) {
                    HMWP_Classes_Error::setError(esc_html__('Empty email address','hide-my-wp'));
                } elseif ( ! is_email( $data['user_email'] ) ) {
                    HMWP_Classes_Error::setError(esc_html__('Invalid email address','hide-my-wp'));
                } elseif ( ! empty( $data['user_email'] ) && email_exists( $data['user_email'] ) ) {
                    HMWP_Classes_Error::setError(esc_html__('Email address already exists','hide-my-wp'));
                }

                if(!HMWP_Classes_Error::isError()){
                    $user = $this->model->createNewUser( $data );

                    if ( isset( $user['error'] ) && isset( $user['message'] ) && $user['error'] ) {
                        HMWP_Classes_Error::setError($user['message']);
                    } else {
                        HMWP_Classes_Error::setError(esc_html__('User successfully created.','hide-my-wp'),'success');

                        $user_id       = isset( $user['user_id'] ) ? $user['user_id'] : 0;
                        $templogin_url = $this->model->getTempLoginUrl( $user_id );
                        $templogin_url = '<span class="hmwp-clipboard-text"  style="max-width:50%" >'.$templogin_url.'</span> <i id="token_notification" class="fa fa-copy hmwp_clipboard_copy" data-clipboard-text="' . $templogin_url . '"></i>';

                        HMWP_Classes_Error::setError(esc_html__('Temporary Login','hide-my-wp') . ': ' . $templogin_url,'success');
                    }
                }

                break;

            case 'hmwp_templogin_update':
                $data = HMWP_Classes_Tools::getValue('hmwp_details', array());
                $data['user_id'] = HMWP_Classes_Tools::getValue('user_id', 0);
                HMWP_Classes_Error::clearErrors();

                if ( $data['user_id'] == 0 ) {
                    HMWP_Classes_Error::setError(esc_html__('Could not detect the user','hide-my-wp'));
                }

                if(!HMWP_Classes_Error::isError()){
                    //Update the user ... return user_id or array of error
                    $user = $this->model->updateUser( $data );

                    if ( isset( $user['error'] ) && isset( $user['message'] ) && $user['error'] ) {
                        HMWP_Classes_Error::setError($user['message']);
                    } else {

                        $redirect = HMWP_Classes_Tools::getSettingsUrl(HMWP_Classes_Tools::getValue('page'));
                        $redirect = add_query_arg( 'hmwp_message', esc_html__('User successfully updated.','hide-my-wp'), $redirect );


                        wp_redirect($redirect);
                        exit();
                    }

                }

                break;

            case 'hmwp_templogin_block':
                $user_id = HMWP_Classes_Tools::getValue('user_id', 0);
                if ( $this->model->updateLoginStatus( absint( $user_id ), 'disable' ) ) {
                    HMWP_Classes_Error::setError(esc_html__('User successfully disabled.','hide-my-wp'),'success');
                } else {
                    HMWP_Classes_Error::setError(esc_html__('User could not be disabled.','hide-my-wp'));
                }
                break;

            case 'hmwp_templogin_activate':
                $user_id = HMWP_Classes_Tools::getValue('user_id', 0);
                if ( $this->model->updateLoginStatus( absint( $user_id ), 'enable' ) ) {
                    HMWP_Classes_Error::setError(esc_html__('User successfully activated.','hide-my-wp'),'success');
                } else {
                    HMWP_Classes_Error::setError(esc_html__('User could not be activated.','hide-my-wp'));
                }
                break;

            case 'hmwp_templogin_delete':
                $user_id = HMWP_Classes_Tools::getValue('user_id', 0);
                $delete_user = wp_delete_user( $user_id, get_current_user_id() );

                // delete user from Multisite network too!
                if ( HMWP_Classes_Tools::isMultisites() ) {

                    // If it's a super admin, we can't directly delete user from network site.
                    // We need to revoke super admin access first and then delete user
                    if ( is_super_admin( $user_id ) ) {
                        revoke_super_admin( $user_id );
                    }

                    $delete_user = wpmu_delete_user( $user_id );
                }

                if ( ! is_wp_error( $delete_user ) ) {
                    HMWP_Classes_Error::setError(esc_html__('User successfully deleted.','hide-my-wp'),'success');
                } else {
                    HMWP_Classes_Error::setError(esc_html__('User could not be deleted.','hide-my-wp'));
                }
                break;


        }
    }


    public function getTempLogins()
    {
        $data = '<table class="table table-striped" >';
        $users = $this->model->getTempUsers();
        $data .= "<tr>
                    <th>" . esc_html__('User', 'hide-my-wp') . "</th>
                    <th>" . esc_html__('Role', 'hide-my-wp') . "</th>
                    <th>" . esc_html__('Last Access', 'hide-my-wp') . "</th>
                    <th>" . esc_html__('Expires', 'hide-my-wp') . "</th>
                    <th>" . esc_html__('Options', 'hide-my-wp') . "</th>
                 </tr>";
        if (!empty($users)) {
            foreach ($users as $user) {
                $user->details = $this->model->getUserDetails($user);

                $user_details = '<div><span>';
                if ( ( esc_attr( $user->first_name ) ) ) {
                    $user_details .= '<span>' . esc_attr( $user->first_name ) . '</span>';
                }

                if ( ( esc_attr( $user->last_name ) ) ) {
                    $user_details .= '<span> ' . esc_attr( $user->last_name ) . '</span>';
                }

                $user_details .= "  (<span class='user-login'>" . esc_attr( $user->user_login ) . ')</span><br />';

                if ( ( esc_attr( $user->user_email ) ) ) {
                    $user_details .= '<p class="inline-block pt-1 font-medium text-black-50">' . esc_attr( $user->user_email ) . '</p> <br />';
                }

                $user_details .= '</span></div>';

                $form = '<div class="row m-0 p-0">';

                if($user->details->is_active){
                    $form .= '<form method="POST" class="col-3 m-0 p-1">
                                ' . wp_nonce_field('hmwp_templogin_block', 'hmwp_nonce', true, false) . '
                                <input type="hidden" name="action" value="hmwp_templogin_block" />
                                <input type="hidden" name="user_id" value="'.$user->ID.'" />
                                <button type="submit" class="btn btn-link btn-sm m-0 p-0" /><i class="fa fa-unlock" title="'.esc_html__('Lock user', 'hide-my-wp').'"></i></button>
                            </form>';
                }else{
                    $form .= '<form method="POST" class="col-3 m-0 p-1">
                                ' . wp_nonce_field('hmwp_templogin_activate', 'hmwp_nonce', true, false) . '
                                <input type="hidden" name="action" value="hmwp_templogin_activate" />
                                <input type="hidden" name="user_id" value="'.$user->ID.'" />
                                <button type="submit" class="btn btn-link btn-sm m-0 p-0" /><i class="fa fa-lock" title="'.esc_html__('Reactivate user for 1 day', 'hide-my-wp').'"></i></button>
                            </form>';
                }

                $form .= '<div class="col-3 m-0 p-1"><a href="?page=hmwp_templogin&action=hmwp_update&user_id='.$user->ID.'" type="button" class="btn btn-link btn-sm m-0 p-0" /><i class="fa fa-edit" title="'.esc_html__('Edit user', 'hide-my-wp').'"></i></a></div>';
                $form .= '<form method="POST" class="col-3 m-0 p-1">
                                ' . wp_nonce_field('hmwp_templogin_delete', 'hmwp_nonce', true, false) . '
                                <input type="hidden" name="action" value="hmwp_templogin_delete" />
                                <input type="hidden" name="user_id" value="'.$user->ID.'" />
                                <button type="submit" class="btn btn-link btn-sm m-0 p-0" onclick="return confirm(\''.esc_html__('Do you want to delete temporary user?', 'hide-my-wp').'\')" /><i class="fa fa-close text-danger" title="'.esc_html__('Delete user', 'hide-my-wp').'"></i></button>
                            </form>';
                if($user->details->is_active) {
                    $form .= '<div class="col-3 m-0 p-1"><button type="button" id="text-' . $user->ID . '" class="btn btn-link btn-sm m-0 p-0 hmwp_clipboard_copy" data-clipboard-text="' . $user->details->templogin_url . '" /><i class="fa fa-link" title="' . esc_html__('Copy Link', 'hide-my-wp') . '"></i></button></div>';
                }

                if((int)$user->details->expire > 0){
                    $expires = $this->model->timeElapsed( $user->details->expire );
                }else{
                    if(isset($this->model->expires[$user->details->expire])){
                        $expires = $this->model->expires[$user->details->expire]['label'];
                        $expires .= '<br /><span class="text-black-50 small">('.esc_html__('after first access').')</span>';
                    }
                }


                $form .= '</div>';

                $data .= "<tr>
                        <td>$user_details</td>
                        <td>{$user->details->user_role_name}</td>
                        <td>{$user->details->last_login}</td>
                        <td class='hmwp-status-" . strtolower( $user->details->status ) . " pl-4'>{$expires}</td>
                        <td class='p-2'>$form</td>
                     </tr>";
            }
        } else {
            $data .= "<tr>
                                <td colspan='5'>" . esc_html__('No temporary logins.','hide-my-wp') . " 
                                <button type='button' class='btn btn-link btn-sm text-dark inline p-0' style='vertical-align: top' onclick=\"jQuery('#hmwp_templogin_modal_new').modal('show');\">". esc_html__('Create New Temporary Login', 'hide-my-wp') ."</button>
                                </td>
                             </tr>";
        }
        $data .= "</table>";

        return $data;
    }



}
