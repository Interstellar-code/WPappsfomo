<?php
/**
 * Cloud Connect
 * Called for the Token Activation
 *
 * @package HMWP/Connect
 * @file The Cloud Connect file
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Controllers_Connect extends HMWP_Classes_FrontController
{

    /**
     * Called when an action is triggered
     *
     * @throws Exception
     */
    public function action()
    {
        parent::action();

        //Check user permission.
        if (!HMWP_Classes_Tools::userCan('hmwp_manage_settings') ) {
            return;
        }

        //Connect to API with the Token.
        if (HMWP_Classes_Tools::getValue('action') == 'hmwp_connect') {
            $token = HMWP_Classes_Tools::getValue('hmwp_token', '');

            $redirect_to = HMWP_Classes_Tools::getSettingsUrl();
            if ($token <> '') {
                if (preg_match('/^[a-z0-9\-]{32}$/i', $token)) {
                    HMWP_Classes_Tools::checkAccountApi($token, $redirect_to);
                } else {
                    HMWP_Classes_Error::setError(esc_html__('ERROR! Please make sure you use a valid token to activate the plugin', 'hide-my-wp') . " <br /> ");
                }
            } else {
                HMWP_Classes_Error::setError(esc_html__('ERROR! Please make sure you use the right token to activate the plugin', 'hide-my-wp') . " <br /> ");
            }
        }
    }

}
