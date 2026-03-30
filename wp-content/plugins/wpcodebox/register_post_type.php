<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register Custom Post Type
function wpcb_custom_post_type() {

    $args = array(
        'taxonomies'            => array(),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => false,
        'show_in_menu'          => false,
        'menu_position'         => 5,
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'page',
        'query_var' => false

    );
    register_post_type( \Wpcb\Config::FOLDER_POST_TYPE, $args );

    $args = array(
        'taxonomies'            => array(),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => false,
        'show_in_menu'          => false,
        'menu_position'         => 5,
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'page',
        'query_var' => false

    );
    register_post_type(\Wpcb\Config::SNIPPET_POST_TYPE , $args );

}
add_action( 'init', 'wpcb_custom_post_type', 0 );


