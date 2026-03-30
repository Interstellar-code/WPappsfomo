<?php 
namespace HappyFiles;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Setup {

  public function __construct() {
    add_action( 'init', [$this, 'register_taxonomy_media_category'] );
    add_action( 'admin_enqueue_scripts', [$this, 'enqueue_scripts'] );
    register_activation_hook( HAPPYFILES_FILE, [$this, 'plugin_activation'] );
    register_deactivation_hook( HAPPYFILES_FILE, [$this, 'plugin_deactivation'] );

    /** 
     * Load HappyFiles CSS and JS conditionally (only load on frontend for page builder editing)
     * 
     * Bricks:          Check via URL param 'bricksbuilder'
     * Beaver Builder:  Check via URL param 'fl_builder'
     * Brizy:           Check via URL param 'brizy-edit-iframe'
     * Divi Theme:      Check via URL param 'et_fb'
     * Elementor:       Editor already loads in wp-admin
     * Oxygen:          Check via URL param 'ct_builder'
     * Visual Composer: Editor already loads in wp-admin
     */
    if ( 
      isset( $_GET['bricksbuilder'] ) ||
      isset( $_GET['fl_builder'] ) || 
      isset( $_GET['et_fb'] ) || 
      isset( $_GET['brizy-edit-iframe'] ) || 
      isset( $_GET['ct_builder'] )

    ) {
      add_action( 'wp_enqueue_scripts', [$this, 'enqueue_scripts'] );
    }

    // Elementor Support
    add_action( 'elementor/editor/after_enqueue_scripts', [$this, 'enqueue_scripts'] );
  }

  public function enqueue_scripts( $hook ) {
    wp_enqueue_style( 'happyfiles', HAPPYFILES_ASSETS_URL . '/css/hf.min.css', [], filemtime( HAPPYFILES_ASSETS_PATH .'/css/hf.min.css' ) );

    if ( is_rtl() ) {
      wp_enqueue_style( 'happyfiles-rtl', HAPPYFILES_ASSETS_URL . '/css/hf-rtl.min.css', [], filemtime( HAPPYFILES_ASSETS_PATH .'/css/hf-rtl.min.css' ) );
    }

    wp_enqueue_script( 'happyfiles', HAPPYFILES_ASSETS_URL . '/js/hf.min.js', ['jquery', 'media-views'], filemtime( HAPPYFILES_ASSETS_PATH .'/js/hf.min.js' ), true );
      
    wp_localize_script( 'happyfiles', 'happyFiles', [
      'debug'              => false,
      'initialized'        => false,
      'width'              => get_option( HAPPYFILES_DB_OPTION_WIDTH, 300 ),
      'attachmentsBrowser' => '', // Backbone.js view to refresh .attachements-browser
      'open'               => isset( $_GET['happyfiles_category'] ) && ! empty( $_GET['happyfiles_category'] ) ? Helpers::sanitize_data( $_GET['happyfiles_category'] ) : '', // Term ID of currently selected media category
      'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
      'uploadUrl'          => admin_url( 'upload.php' ),
      'filters'            => '',
      'filterId'           => 'happyfiles_category',
      'filterIdUpload'     => 'happyfiles_category_upload',
      'canEdit'            => Helpers::$can_edit,
      
      'taxonomy'           => HAPPYFILES_TAXONOMY,
      'terms'              => Helpers::$category_terms,
      'tree'               => Helpers::get_tree(),

      'l10n'               => [
        'move'                              => esc_html__( 'Move', 'happyfiles' ),
        'file'                              => esc_html__( 'file', 'happyfiles' ),
        'files'                             => esc_html__( 'files', 'happyfiles' ),
        'newCategoryNoName'                 => esc_html__( 'Please enter a category name.', 'happyfiles' ),
        'renameCategoryNoName'              => esc_html__( 'Please enter a category name.', 'happyfiles' ),
        'renameCategoryNoCategorySelected'  => esc_html__( 'Please select a category to rename.', 'happyfiles' ),
        'allCategories'                     => esc_html__( 'All categories', 'happyfiles' ),
        'uncategorized'                     => esc_html__( 'Uncategorized', 'happyfiles' ),
        'deleteCategoryConfirmation'        => esc_html__( 'Do you want to delete this media category? This only deletes the taxonomy term in your database. Your files are safe.', 'happyfiles' ),
        'deleteCategoryNoCategorySelected'  => esc_html__( 'Please select a media category to delete.', 'happyfiles' ),
        'deleteCategoryFinishRenamingFirst' => esc_html__( 'Please finish renaming your media category first.', 'happyfiles' ),
      ],
    ] );
  }

  public function register_taxonomy_media_category() {
    register_taxonomy(
      HAPPYFILES_TAXONOMY,
      ['attachment'],
      [
        'labels' => [
          'name'               => esc_html__( 'Category', 'happyfiles' ),
          'singular_name'      => esc_html__( 'Category', 'happyfiles' ),
          'add_new_item'       => esc_html__( 'Add New Category', 'happyfiles' ),
          'edit_item'          => esc_html__( 'Edit Category', 'happyfiles' ),
          'new_item'           => esc_html__( 'Add New Category', 'happyfiles' ),
          'search_items'       => esc_html__( 'Search Category', 'happyfiles' ),
          'not_found'          => esc_html__( 'Category not found', 'happyfiles' ),
          'not_found_in_trash' => esc_html__( 'Category not found in trash', 'happyfiles' ),
        ],
        'public'             => false,
        'publicly_queryable' => false,
        'hierarchical'       => true,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'show_in_nav_menus'  => false,
        'show_in_quick_edit' => false,
        'show_admin_column'  => false,
        'rewrite'            => false,
        'update_count_callback' => '_update_generic_term_count', // Update term count for attachments
      ]
    );
  }

  public function plugin_activation() {
    // Store plugin activation timestamp for "Rate Us" notification after 7 days
    update_option( 'happyfiles_plugin_activation', time() );
  }

  public function plugin_deactivation() {
    delete_option( 'happyfiles_plugin_activation' );
    delete_option( 'happyfiles_hide_rate_us_notification' );
  }

}