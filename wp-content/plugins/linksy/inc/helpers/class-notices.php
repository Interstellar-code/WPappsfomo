<?php

namespace Linksy\Inc\Helpers;

/**
 * Hadndle incoming Notices.
 *
 * @since      1.0.1
 * @package    Linksy
 * @subpackage Linksy\admin
 * @author     Gbenga Medunoye <laxusgooee@gmail.com>
 */

/**
 * Notices class.
 */
class Notices {
    private $message;
    private $status; // success | error | info

    function __construct( $message, $status = 'info' ) {
        $this->message = $message;
        $this->status = $status;

        add_action( 'admin_notices', array( $this, 'render' ) );
    }

    function render() {
        $class = 'notice notice-'.$this->status;
        $message = __( $this->message, 'linksy' );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $this->message ) );
    }
}