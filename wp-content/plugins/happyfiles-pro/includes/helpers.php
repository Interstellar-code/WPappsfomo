<?php 
namespace HappyFiles;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Helpers {

  public static $can_edit = false;

  public static $category_terms = [];

  public function __construct() {
    add_action( 'init', [$this, 'can_edit_media_categories'] );

    // Call after register_taxonomy
    add_action( 'init', [$this, 'get_category_terms'], 11, 2 );
  }

  /**
   * Sanitize all $_GET, $_POST, $_REQUEST, $_FILE input data before processing
   * 
   * @param $data (array|string)
   * @since  0.1
   * @return mixed
   */
  public static function sanitize_data( $data ) {
   // Sanitize string
    if ( is_string( $data ) ) {
      $data = sanitize_text_field( $data );
    } 
    
    // Sanitize each element individually
    else if ( is_array( $data ) ) {
      foreach ( $data as $key => &$value ) {
        if ( is_array( $value ) ) {
          $value = sanitize_data( $value );
        } else {
          $value = sanitize_text_field( $value );
        }
      }
    }
 
    return $data;
 }

  public static function get_category_terms() {

    // Add 'all' and 'uncategorized' terms to attachment terms (easier to update DOM with all list items)
    $default_terms = [];
    
    $term_all = new \stdClass();
    $term_all->term_id = 'all';
    $term_all->parent  = 0;
    $term_all->slug    = 'all';
    $term_all->name    = esc_html__( 'All Categories', 'happyfiles' );
    $term_all->count   = self::count_all_attachments();
    $term_all->value   = ''; // Empty value to show all attachments

    $default_terms[] = $term_all;

    $term_uncategorized = new \stdClass();
    $term_uncategorized->term_id = -1;
    $term_uncategorized->parent  = 0;
    $term_uncategorized->slug    = 'uncategorized';
    $term_uncategorized->name    = esc_html__( 'Uncategorized', 'happyfiles' );
    $term_uncategorized->count   = self::count_uncategorized_attachments();
    $term_uncategorized->value   = -1; // = Identifier for uncategorized attachment in 'ajax_query_attachments_args' filter

    $default_terms[] = $term_uncategorized;

    // Sort by category position: Terms with position
    $attachments_with_terms = get_terms( [
      'taxonomy'   => HAPPYFILES_TAXONOMY,
      'hide_empty' => false,

      'meta_query' => [
        'terms_exists' => [
          'key' => HAPPYFILES_POSITION,
          'compare' => 'EXISTS'
        ],
      ],

      // 'orderby'    => 'terms_exists',
      'orderby'    => 'meta_value_num',
      'order'      => 'ASC',
      
    ] );

    self::$category_terms = array_merge( $default_terms, $attachments_with_terms );

    // Sort by category position: Terms without position
    $attachments_without_terms = get_terms( [
      'taxonomy'   => HAPPYFILES_TAXONOMY,
      'hide_empty' => false,

      'meta_query' => [
        'terms_not_exists' => [
          'key' => HAPPYFILES_POSITION,
          'compare' => 'NOT EXISTS'
        ],
      ],

      'orderby'    => 'terms_not_exists',
      'order'      => 'ASC',
    ] );

    self::$category_terms = array_merge( self::$category_terms, $attachments_without_terms );

    return self::$category_terms;
  }

  /**
   * Get terms hierarchical
   * 
   * Term array with nested 'children' array
   *
   * @param [type] $terms
   * @param integer $parent_id
   * 
   * @return array
   */
  public static function get_terms_hierarchical( $terms, $parent_id = 0 ) {
    $hierarchical_terms = [];
    
    foreach ( $terms as $term ) {
      if ( $term->parent === $parent_id ) {
        $term->children = self::get_terms_hierarchical( $terms, $term->term_id );  
        $hierarchical_terms[] = $term;
      }
    }

    return $hierarchical_terms;
  }

  public static function get_tree() {
    return Helpers::get_terms_hierarchical( Helpers::$category_terms );
  }

  public static function count_all_attachments() {
    $attachments = get_posts( [
      'post_type'      => 'attachment',
      'posts_per_page' => -1,
      'fields'         => 'ids',
    ] );

    return count( $attachments );
  }

  public static function count_uncategorized_attachments() {
    $attachments = get_posts( [
      'post_type'      => 'attachment',
      'posts_per_page' => -1,
      'fields'         => 'ids',
      'tax_query'      => [
        [
          'taxonomy' => HAPPYFILES_TAXONOMY,
          'operator' => 'NOT EXISTS',
        ],
      ],
    ] );

    return count( $attachments );
  }

  /**
   * Check currently logged in user against HappyFiles option setting to determine if user can edit media categories
   */
  public static function can_edit_media_categories() {
    // Admin can always edit
    if ( current_user_can( 'administrator' ) ) {
      self::$can_edit = true;

      return;
    }

    $setting_user_roles = get_option( HAPPYFILES_SETTING_USER_ROLES, [] );

    if ( empty( $setting_user_roles ) ) {
      $setting_user_roles = [];
    }

    $current_user_roles = wp_get_current_user()->roles;
    
    $user_can_edit = array_intersect( array_map( 'strtolower', $setting_user_roles ), array_map( 'strtolower', $current_user_roles ) );

    self::$can_edit = count( $user_can_edit ) ? true : false;
  }

}