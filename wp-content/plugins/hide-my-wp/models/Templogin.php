<?php
/**
 * Temp Login Model
 *
 * @file  The Temp Login file
 * @package HMWP/Temp Login
 * @since 7.0.0
 */
defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Templogin
{

    public $expires;

    public function __construct()
    {
        $this->expires = array(
            'hour'  => array( 'label' => esc_html__( 'One Hour', 'hide-my-wp' ), 'timestamp' => HOUR_IN_SECONDS ),
            '3_hours' => array( 'label' => esc_html__( 'Three Hours', 'hide-my-wp' ), 'timestamp' => HOUR_IN_SECONDS * 3 ),
            'day' => array( 'label' => esc_html__( 'One Day', 'hide-my-wp' ), 'timestamp' => DAY_IN_SECONDS ),
            '3_days' => array( 'label' => esc_html__( 'Three Days', 'hide-my-wp' ), 'timestamp' => DAY_IN_SECONDS * 3 ),
            'week' => array( 'label' => esc_html__( 'One Week', 'hide-my-wp' ), 'timestamp' => WEEK_IN_SECONDS ),
            'month' => array( 'label' => esc_html__( 'One Month', 'hide-my-wp' ), 'timestamp' => MONTH_IN_SECONDS ),
            'halfyear' => array( 'label' => esc_html__( 'Six Months', 'hide-my-wp' ), 'timestamp' => (6* MONTH_IN_SECONDS) ),
            'year' => array( 'label' => esc_html__( 'One Year', 'hide-my-wp' ), 'timestamp' => YEAR_IN_SECONDS ),
        );
    }

    /**
     * Get valid temporary user based on token
     *
     * @param string $token
     *
     * @return array|bool
     * @since 7.0
     *
     */
    public function findUserByToken( $token = '') {

        if ( empty( $token ) ) {
            return false;
        }

        $args = array(
            'fields'     => 'all',
            'meta_key'   => '_hmwp_expire',
            'order'      => 'DESC',
            'orderby'    => 'meta_value',
            'meta_query' => array(
                0 => array(
                    'key'     => '_hmwp_token',
                    'value'   => sanitize_text_field( $token ),
                    'compare' => '=',
                ),
            ),
        );

        $users = new WP_User_Query( $args );

        $users = $users->get_results();
        if ( empty( $users ) ) {
            return false;
        }

        foreach ( $users as $user ) {
            if(!$expire = get_user_meta( $user->ID, '_hmwp_expire', true )){
                return false;
            }

            if ( is_numeric($expire) && $expire <= $this->gtmTimestamp()) {
                return false;
            } elseif ( $expire <= $this->gtmTimestamp() ) {
                $timestamp      = ! empty( $this->expires[ $expire ] ) ? $this->expires[ $expire ]['timestamp'] : 0;
                update_user_meta( $user->ID, '_hmwp_expire', $this->gtmTimestamp() + $timestamp );
            }

            $user->details = $this->getUserDetails($user);
            return $user;
        }

        return false;

    }


    /**
     * Get user temp login details
     *
     * @param $user
     * @return mixed
     *
     * @since 7.0
     */
    public function getUserDetails($user){
        $details = array();

        $details['redirect_to']  = get_user_meta( $user->ID, '_hmwp_redirect_to', true );
        $details['expire']  = get_user_meta( $user->ID, '_hmwp_expire', true );
        $details['locale']  = get_user_meta( $user->ID, 'locale', true );
        $details['templogin_url'] = $this->getTempLoginUrl( $user->ID );

        $details['last_login_time'] = get_user_meta( $user->ID, '_hmwp_last_login', true );
        $details['last_login'] = esc_html__( 'Not yet logged in', 'hide-my-wp' );
        if ( ! empty( $details['last_login_time'] ) ) {
            $details['last_login'] = $this->timeElapsed( $details['last_login_time'], true );
        }

        $details['status'] = 'Active';
        if ( $this->isExpired( $user->ID ) ) {
            $details['status'] = 'Expired';
        }
        $details['is_active'] = ( 'active' === strtolower( $details['status'] ) ) ? true : false;


        $details['user_role'] = '';
        $details['user_role_name'] = '';
        if ( HMWP_Classes_Tools::isMultisites() && is_super_admin( $user->ID ) ) {
            $details['user_role'] = 'super_admin';
            $details['user_role_name'] = esc_html__( 'Super Admin', 'hide-my-wp' );
        } else {
            global $wpdb;
            $capabilities = $user->{$wpdb->prefix . 'capabilities'};
            $wp_roles     = new WP_Roles();
            foreach ( $wp_roles->role_names as $role => $name ) {
                if ( array_key_exists( $role, $capabilities ) ) {
                    $details['user_role'] = $role;
                    $details['user_role_name'] = $name;
                }
            }
        }

        return json_decode(json_encode($details));
    }

    /**
     * Create a Temporary user
     *
     * @return array
     *
     * @since 7.0
     */
    public function createNewUser( $data ) {

        $result = array(
            'error' => true
        );

        $expire = ! empty( $data['expire'] ) ? $data['expire'] : 'day';
        $password    = HMWP_Classes_Tools::generateRandomString();
        $username    = $this->createUsername( $data );
        $first_name  = isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
        $last_name   = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';
        $email       = isset( $data['user_email'] ) ? sanitize_email( $data['user_email'] ) : '';
        $role        = ! empty( $data['user_role'] ) ? $data['user_role'] : 'subscriber';
        $redirect_to = ! empty( $data['redirect_to'] ) ? sanitize_text_field( $data['redirect_to'] ) : '';
        $user_args   = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'user_login' => $username,
            'user_pass'  => $password,
            'user_email' => $email,
            'role'       => $role,
        );

        $user_id = wp_insert_user( $user_args );

        if ( is_wp_error( $user_id ) ) {
            $code = $user_id->get_error_code();

            $result['errcode'] = $code;
            $result['message'] = $user_id->get_error_message( $code );

        } else {

            if ( is_multisite() && ! empty( $data['super_admin'] ) && 'on' === $data['super_admin'] ) {

                // Grant super admin access to this temporary users
                grant_super_admin( $user_id );

                // Now, add this user to all sites
                $sites = get_sites( array( 'deleted' => '0' ) );

                if ( ! empty( $sites ) && count( $sites ) > 0 ) {
                    foreach ( $sites as $site ) {
                        // If user is not already member of blog? Add into this blog
                        if ( ! is_user_member_of_blog( $user_id, $site->blog_id ) ) {
                            add_user_to_blog( $site->blog_id, $user_id, 'administrator' );
                        }
                    }
                }
            }

            update_user_meta( $user_id, '_hmwp_user', true );
            update_user_meta( $user_id, '_hmwp_created', $this->gtmTimestamp() );
            update_user_meta( $user_id, '_hmwp_expire', $expire );
            update_user_meta( $user_id, '_hmwp_token', $this->generateToken( $user_id ) );
            update_user_meta( $user_id, '_hmwp_redirect_to', $redirect_to );

            //set locale
            $locale = ! empty( $data['locale'] ) ? $data['locale'] : 'en_US';
            update_user_meta( $user_id, 'locale', $locale );

            $result['error']   = false;
            $result['user_id'] = $user_id;
        }

        return $result;

    }

    /**
     * Create a ranadom username for the temporary user
     *
     * @param array $data
     *
     * @return string
     * @since 7.0
     */
    public function createUsername( $data ) {

        $first_name = isset( $data['user_first_name'] ) ? $data['user_first_name'] : '';
        $last_name  = isset( $data['user_last_name'] ) ? $data['user_last_name'] : '';
        $email      = isset( $data['user_email'] ) ? $data['user_email'] : '';

        $name = '';
        if ( ! empty( $first_name ) || ! empty( $last_name ) ) {
            $name = str_replace( array( '.', '+' ), '', strtolower( trim( $first_name . $last_name ) ) );
        } else {
            if ( ! empty( $email ) ) {
                $explode = explode( '@', $email );
                $name    = str_replace( array( '.', '+' ), '', $explode[0] );
            }
        }

        if ( username_exists( $name ) ) {
            $name = $name . substr( uniqid( '', true ), - 6 );
        }

        $username = sanitize_user( $name, true );

        /**
         * We are generating WordPress username from First Name & Last Name fields.
         * When First Name or Last Name comes with non latin words, generated username
         * is non latin and sanitize_user function discrad it and user is not being
         * generated.
         *
         * To avoid this, if this situation occurs, we are generating random username
         * for this user.
         */
        if ( empty( $username ) ) {
            $username = HMWP_Classes_Tools::generateRandomString();
        }

        return sanitize_user( $username, true );
    }

    /**
     * Update user
     *
     * @param array $data
     *
     * @return array|int|WP_Error
     * @since 7.0
     */
    public function updateUser( $data ) {

        $expire = ! empty( $data['expire'] ) ? $data['expire'] : 'day';
        $first_name  = isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
        $last_name   = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';
        $redirect_to = isset( $data['redirect_to'] ) ? sanitize_text_field( $data['redirect_to'] ) : '';
        $role        = ! empty( $data['user_role'] ) ? $data['user_role'] : 'subscriber';
        $user_args   = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'role'       => $role,
            'ID'         => $data['user_id']
        );

        $user_id = wp_update_user( $user_args );

        if ( is_wp_error( $user_id ) ) {
            $code = $user_id->get_error_code();

            return array(
                'error'   => true,
                'errcode' => $code,
                'message' => $user_id->get_error_message( $code ),
            );
        }


        if ( is_multisite() && ! empty( $data['super_admin'] ) && 'on' === $data['super_admin'] ) {
            grant_super_admin( $user_id );
        }

        update_user_meta( $user_id, '_hmwp_updated', $this->gtmTimestamp() );
        update_user_meta( $user_id, '_hmwp_expire', $expire );
        update_user_meta( $user_id, '_hmwp_redirect_to', $redirect_to );

        //set locale
        $locale = ! empty( $data['locale'] ) ? $data['locale'] : 'en_US';
        update_user_meta( $user_id, 'locale', $locale );

        return $user_id;

    }

    /**
     * Get the expiration time based on string
     *
     * @param string $expire
     * @param string $date
     *
     * @return false|float|int
     * @since 7.0
     *
     */
    public function getUserExpireTime( $expire = 'day', $date = '' ) {

        $expire = in_array( $expire, array_keys( $this->expires ) ) ? $expire : 'day';

        $current_timestamp = $this->gtmTimestamp();
        $timestamp         = $this->expires[ $expire ]['timestamp'];

        return $current_timestamp + floatval( $timestamp );

    }

    /**
     * Get current GMT date time
     *
     * @return false|int
     * @since 7.0
     *
     */
    public function gtmTimestamp() {
        return strtotime( gmdate( 'Y-m-d H:i:s', time() ) );
    }

    /**
     * Get Temporary Logins
     *
     * @param string $role
     *
     * @return array|bool
     * @since 7.0
     *
     */
    public function getTempUsers( $role = '' ) {

        $args = array(
            'fields'     => 'all',
            'meta_key'   => '_hmwp_expire',
            'order'      => 'DESC',
            'orderby'    => 'meta_value',
            'meta_query' => array(
                0 => array(
                    'key'   => '_hmwp_user',
                    'value' => 1,
                ),
            ),
        );

        if ( ! empty( $role ) ) {
            $args['role'] = $role;
        }

        $users = new WP_User_Query( $args );

        $users_data = $users->get_results();

        return $users_data;

    }

    /**
     * Get the redable time elapsed string
     *
     * @param int $time
     * @param bool $ago
     *
     * @return string
     * @since 7.0
     *
     */
    public function timeElapsed( $time, $ago = false ) {

        if ( is_numeric( $time ) ) {

            if ( $ago ) {
                $etime = $this->gtmTimestamp() - $time;
            } else {
                $etime = $time - $this->gtmTimestamp();
            }

            if ( $etime < 1 ) {
                return esc_html__( 'Expired', 'hide-my-wp' );
            }

            $a = array(
                // 365 * 24 * 60 * 60 => 'year',
                // 30 * 24 * 60 * 60 => 'month',
                24 * 60 * 60 => 'day',
                60 * 60      => 'hour',
                60           => 'minute',
                1            => 'second',
            );

            $a_plural = array(
                'year'   => 'years',
                'month'  => 'months',
                'day'    => 'days',
                'hour'   => 'hours',
                'minute' => 'minutes',
                'second' => 'seconds',
            );

            foreach ( $a as $secs => $str ) {
                $d = $etime / $secs;

                if ( $d >= 1 ) {
                    $r = round( $d );

                    $time_string = ( $r > 1 ) ? $a_plural[ $str ] : $str;

                    if ( $ago ) {
                        return sprintf( esc_html__('%d %s ago', 'hide-my-wp'), $r, $time_string );
                    } else {
                        return sprintf( esc_html__('%d %s remaining', 'hide-my-wp'), $r, $time_string );
                    }
                }
            }

            return __( 'Expired', 'hide-my-wp' );
        } else {

            return ! empty( $expiry_options[ $time ] ) ? $this->expires[ $time ]['label'] : '';
        }

    }

    /**
     * Check if temporary login expired
     *
     * @param int $user_id
     *
     * @return bool
     *
     * @since 7.0
     */
    public function isExpired( $user_id = 0 ) {

        if ( empty( $user_id ) ) {
            $user_id = get_current_user_id();
        }

        if ( empty( $user_id ) ) {
            return false;
        }

        $expire = get_user_meta( $user_id, '_hmwp_expire', true );

        return ! empty( $expire ) && is_numeric( $expire ) && $this->gtmTimestamp() >= floatval( $expire ) ? true : false;

    }

    /**
     * Get temporary login url
     *
     * @param $user_id
     *
     * @return string
     * @since 7.0
     *
     */
    public function getTempLoginUrl( $user_id ) {

        if ( empty( $user_id ) ) {
            return '';
        }

        $is_valid_temporary_login = $this->isValidTempLogin( $user_id );
        if ( ! $is_valid_temporary_login ) {
            return '';
        }

        $token = get_user_meta( $user_id, '_hmwp_token', true );
        if ( empty( $token ) ) {
            return '';
        }

        $login_url = add_query_arg( 'hmwp_token', $token, trailingslashit( home_url() ) );

        // Make it compatible with iThemes Security plugin with Custom URL Login enabled
        $login_url = apply_filters( 'itsec_notify_admin_page_url', $login_url );

        return apply_filters( 'hmwp_templogin_link', $login_url, $user_id );

    }

    /**
     * Checks whether user is valid temporary user
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function isValidTempLogin( $user_id = 0 ) {

        if ( empty( $user_id ) ) {
            return false;
        }

        $check = get_user_meta( $user_id, '_hmwp_user', true );

        return ! empty( $check ) ? true : false;

    }

    /**
     * Generate Temporary Login Token
     *
     * @param $user_id
     *
     * @return false|string
     *
     * @since 7.0
     */
    public function generateToken( $user_id ) {
        $byte_length = 64;

        if ( function_exists( 'random_bytes' ) ) {
            try {
                return bin2hex( random_bytes( $byte_length ) ); // phpcs:ignore
            } catch ( \Exception $e ) {
            }
        }

        if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
            $crypto_strong = false;

            $bytes = openssl_random_pseudo_bytes( $byte_length, $crypto_strong );
            if ( true === $crypto_strong ) {
                return bin2hex( $bytes );
            }
        }

        // Fallback
        $str  = $user_id . microtime() . uniqid( '', true );
        $salt = substr( md5( $str ), 0, 32 );

        return hash( "sha256", $str . $salt );
    }

    /**
     * Get all pages which needs to be blocked for temporary users
     *
     * @return array
     * @since 7.0
     *
     */
    public function getRestrictedPages() {
        $pages = array( 'user-new.php', 'user-edit.php', 'profile.php' );
        $pages = apply_filters( 'hmwp_templogin_restricted_pages', $pages );

        return $pages;

    }

    /**
     * Get all pages which needs to be blocked for temporary users
     *
     * @return array
     * @since 7.0
     *
     */
    public function getRestrictedActions() {
        $actions = array( 'deleteuser', 'delete' );
        $actions = apply_filters( 'hmwp_templogin_restricted_actions', $actions );

        return $actions;

    }

    /**
     * Update the temporary login status
     *
     * @param int $user_id
     * @param string $action
     *
     * @return bool
     * @since 7.0
     *
     */
    public function updateLoginStatus( $user_id = 0, $action = '' ) {

        if ( empty( $user_id ) || empty( $action ) ) {
            return false;
        }

        if ( !$this->isValidTempLogin( $user_id )) {
            return false;
        }

        $manage_login = false;
        if ( 'disable' === $action ) {
            $manage_login = update_user_meta( $user_id, '_hmwp_expire', $this->gtmTimestamp());
        } elseif ( 'enable' === $action ) {
            $manage_login = update_user_meta( $user_id, '_hmwp_expire', 'day' );
        }

        if ( $manage_login ) {
            return true;
        }

        return false;

    }

    /**
     * Delete all temporary logins
     *
     * @since 7.0
     */
    public function deleteTempLogins() {

        $users = $this->getTempUsers();

        if ( count( $users ) > 0 ) {
            foreach ( $users as $user ) {
                if ( $user instanceof WP_User ) {
                    $user_id = $user->ID;

                    wp_delete_user( $user_id ); // Delete User

                    // delete user from Multisite network too!
                    if ( is_multisite() ) {

                        // If it's a super admin, we can't directly delete user from network site.
                        // We need to revoke super admin access first and then delete user
                        if ( is_super_admin( $user_id ) ) {
                            revoke_super_admin( $user_id );
                        }

                        wpmu_delete_user( $user_id );
                    }
                }
            }
        }

    }

}
