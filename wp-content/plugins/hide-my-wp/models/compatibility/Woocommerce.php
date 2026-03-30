<?php
/**
 * Woocommerce Class
 *
 * @file The Woocommerce Model file
 * @package HMWP/WoocommerceModel
 * @since 6.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_Woocommerce
{

    public function __construct()
    {
        add_action('admin_url', array($this, 'admin_url'), PHP_INT_MAX, 3);

        if(HMWP_Classes_Tools::getValue('noredicts')) {
            add_filter('woocommerce_is_rest_api_request', '__return_false');
        }
    }

    /**
     * Fix the admin url if wrong redirect
     *
     * @param mixed $url
     * @param mixed $path
     * @param mixed $blog_id
     */
    public function admin_url( $url, $path, $blog_id )
    {
        if (HMWP_Classes_Tools::getDefault('hmwp_admin_url') <> HMWP_Classes_Tools::getOption('hmwp_admin_url')) {

            if (strpos( $url, '/wp-admin/' . HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/' ) !== false ) {
                $url = str_replace('/' . HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/', '/', $url);
            }

        }

        return $url;

    }

}
