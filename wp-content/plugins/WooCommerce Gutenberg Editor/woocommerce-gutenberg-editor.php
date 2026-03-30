<?php
/**
 * Plugin Name: WooCommerce Gutenberg Editor
 * Plugin URI: https://appsfomo.com/
 * Description: Enables the Gutenberg editor for WooCommerce products and shows Product Categories and Tags in the editor.
 * Version: 1.0
 * Author: Sathwik Prabhu
 * Author URI: https://appsfomo.com/
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-gutenberg-editor
 * Domain Path: /languages
 */

function wge_enable_gutenberg_for_products() {
    add_post_type_support( 'product', 'editor' );
}
add_action( 'init', 'wge_enable_gutenberg_for_products' );

function wge_show_taxonomy_in_editor() {
    add_filter( 'woocommerce_taxonomy_args_product_cat', 'wge_show_taxonomy_in_editor_args' );
    add_filter( 'woocommerce_taxonomy_args_product_tag', 'wge_show_taxonomy_in_editor_args' );
}

function wge_show_taxonomy_in_editor_args( $args ) {
    $args['show_in_rest'] = true;
    return $args;
}

add_action( 'init', 'wge_show_taxonomy_in_editor' );
