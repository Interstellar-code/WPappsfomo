<?php 
namespace HappyFiles;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Admin {

  public function __construct() {
    add_filter( 'plugin_action_links_' . HAPPYFILES_BASENAME, [$this, 'plugin_action_links'] );
    add_action( 'admin_notices', [$this, 'admin_notices'] );
    add_action( 'admin_enqueue_scripts' , [$this, 'admin_enqueue_scripts'] );
    add_action( 'wp_ajax_happyfiles_dismiss_first_use_notification', [$this, 'happyfiles_dismiss_first_use_notification'] );
    add_action( 'wp_ajax_happyfiles_dismiss_rate_us_notification', [$this, 'happyfiles_dismiss_rate_us_notification'] );
    
    add_action( 'admin_init', [$this, 'admin_init'] );
    add_action( 'admin_menu', [$this, 'admin_menu'] );

    // List view media category filter (HTML and query)
    add_action( 'parse_tax_query', [$this, 'parse_tax_query'] );
    add_action( 'restrict_manage_posts', [$this, 'add_categories_filter_list_view'] );

    add_action( 'add_attachment', [$this, 'add_attachment'] );

    add_filter( 'ajax_query_attachments_args', [$this, 'ajax_query_attachments_args'] );

    // Add categories HTML to media library
    add_action( 'admin_footer-upload.php', [$this, 'render'] );

    // Return categories HTML for media modal
    add_action( 'wp_ajax_happyfiles_get_categories_html', [$this, 'get_categories_html'] );

    add_action( 'wp_ajax_happyfiles_get_category_terms_and_tree', [$this, 'get_category_terms_and_tree'] );
    add_action( 'wp_ajax_happyfiles_create_new_category', [$this, 'create_new_category'] );
    add_action( 'wp_ajax_happyfiles_rename_category', [$this, 'rename_category'] );
    add_action( 'wp_ajax_happyfiles_delete_category', [$this, 'delete_category'] );

    add_action( 'wp_ajax_happyfiles_update_term_parent', [$this, 'update_term_parent'] );
    add_action( 'wp_ajax_happyfiles_update_term_position', [$this, 'update_term_position'] );

    add_action( 'wp_ajax_happyfiles_update_attachment_terms', [$this, 'update_attachment_terms'] );
    add_action( 'wp_ajax_happyfiles_get_attachment_terms', [$this, 'get_attachment_terms'] );

    add_action( 'wp_ajax_happyfiles_get_item_categories', [$this, 'get_item_categories'] );

    add_action( 'wp_ajax_happyfiles_save_sidebar_width', [$this, 'save_sidebar_width'] );

    add_filter( 'pre-upload-ui', [$this, 'upload_ui_media_new'] );
  }

  public function plugin_action_links( $links ) {
    array_unshift( $links, '<a href="' . network_admin_url( 'options-general.php?page=happyfiles_settings' ) . '">' . esc_html__( 'Settings', 'happyfiles' ) . '</a>' );
    
    return $links;
  }

  public function admin_notices() {
    self::notification_first_use();
    self::notification_rate_plugin();
  }
  
  public static function notification_first_use() {
    // Check db option
    if ( get_option( 'happyfiles_hide_first_use_notification', false ) ) {
      return;
    }

    if ( ! Helpers::$can_edit ) {
      return;
    }

    if ( get_current_screen() && get_current_screen()->base === 'upload' ) {
      return;
    }

    $media_terms = get_terms( [
      'taxonomy'   => HAPPYFILES_TAXONOMY,
      'hide_empty' => false,
    ] );

    // Don't show if media categories exist
    if ( $media_terms ) {
      return;
    }

    $classes = [
      'notice',
      'notice-info',
      'is-dismissible',
    ];

    $message = sprintf(
      esc_html__( 'Let\'s create your first media category: %s', 'happyfiles' ),
      '<strong><a href="' . esc_url( admin_url( '/upload.php' ) ) . '">' . esc_html__( 'Go to media library', 'happyfiles' ) . '</a></strong>'
    );

    printf( 
      '<div id="hf-notification-first-use" class="%s"><p>%s</p></div>', 
      trim( implode( ' ', $classes ) ),
      $message
    );
  }

  public static function notification_rate_plugin() {
    $classes = [
      'notice',
      'notice-info',
      'is-dismissible',
    ];

    $plugin_activation_timestamp = get_option( 'happyfiles_plugin_activation' );
    $seconds_till_activation = time() - $plugin_activation_timestamp;

    // Show rating notification 7 days after plugin activation
    $rate_us_notification_time_passed = ( $seconds_till_activation / 60 / 60 / 24 ) > 7;

    $hide_rate_us_notification = get_option( 'happyfiles_hide_rate_us_notification', false );

    if ( $rate_us_notification_time_passed && ! $hide_rate_us_notification ) {
      $message = sprintf( 
        esc_html__( 'Wow, time flies. You have been using HappyFiles for quite a while now. If you like the plugin, please consider %s to help others discover it too.', 'happyfiles' ), 
        '<a href="https://wordpress.org/support/plugin/happyfiles/reviews/#new-post" target="_blank">' . esc_html__( 'rating it', 'happyfiles' ) . '</a>' 
      );

      printf( 
        '<div id="hf-notification-rate-plugin" class="%s"><p>%s</p></div>', 
        trim( implode( ' ', $classes ) ),
        $message
      );
    }
  }

  public function admin_enqueue_scripts() {
    wp_enqueue_script( 'happyfiles-admin', HAPPYFILES_ASSETS_URL . '/js/admin.js', ['jquery'], filemtime( HAPPYFILES_ASSETS_PATH .'/js/admin.js' ) );
  }

  public function happyfiles_dismiss_first_use_notification() {
    // Return if user is not an admin
    if ( ! current_user_can( 'administrator' ) ) {
      wp_send_json_error( ['error' => esc_html__( 'Not an admin', 'happyfiles' )] );
    }

    update_option( 'happyfiles_hide_first_use_notification', true );

    wp_send_json_success( $_POST );
  }

  public function happyfiles_dismiss_rate_us_notification() {
    // Return if user is not an admin
    if ( ! current_user_can( 'administrator' ) ) {
      wp_send_json_error( ['error' => esc_html__( 'Not an admin', 'happyfiles' )] );
    }

    update_option( 'happyfiles_hide_rate_us_notification', true );

    wp_send_json_success( $_POST );
  }

  public function admin_init() {
    // Register HappyFiles settings
    register_setting( HAPPYFILES_SETTINGS_GROUP, HAPPYFILES_SETTING_USER_ROLES );
    register_setting( HAPPYFILES_SETTINGS_GROUP, HAPPYFILES_SETTING_MULTIPLE_CATEGORIES );
  }

  public function admin_menu() {
    add_options_page( 
      esc_html__( 'HappyFiles Settings', 'happyfiles' ),
      esc_html__( 'HappyFiles', 'happyfiles' ),
      'manage_options', 
      HAPPYFILES_SETTINGS_GROUP, 
      [$this, 'admin_screen_settings']
    );
  }

  public function admin_screen_settings() {
    require_once HAPPYFILES_PATH . 'includes/admin/admin-screen-settings.php';
  }

  /**
   * Exclude term children for media library (View: List)
   * 
   * https://core.trac.wordpress.org/ticket/18703#comment:10
   *
   * @param object $query Already parsed query object.
   * @return void
   */
  public function parse_tax_query( $query ) {
    global $pagenow, $typenow;

    // Return if we are not in media library
    if ( $pagenow != 'upload.php' ) {
      return;
    }

    if ( ! empty( $query->tax_query->queries ) ) {
      foreach ( $query->tax_query->queries as &$tax_query ) {
        $taxonomy = $tax_query['taxonomy'];

        if ( $taxonomy === HAPPYFILES_TAXONOMY ) {

          $term_id = &$query->query_vars[$taxonomy];
          // $term_obj = get_term_by( 'id', $term_id, $taxonomy );
          // $term_slug = $term_obj->slug;

          // Uncategorized
          if ( $term_id == '-1' ) {
            $tax_query['operator'] = 'NOT EXISTS';
          }
          
          else {
            $tax_query['include_children'] = false;
            $tax_query['field'] = 'id';
          }

        }
      }
    }
  }


  /**
   * Add category filter select dropdown HTML to media library list view
   * 
   * https://stackoverflow.com/a/48656819/2009539
   *
   * @return string
   */
  public function add_categories_filter_list_view() {
    if ( get_current_screen() && get_current_screen()->base !== 'upload' ) {
      return;
    }

    global $wp_query;

    $tax_slug = HAPPYFILES_TAXONOMY;
    $tax_obj = get_taxonomy( $tax_slug );

    wp_dropdown_categories( [
      'show_option_all'  => esc_html__( 'All Categories', 'happyfiles' ),
      'show_option_none' => esc_html__( 'Uncategorized', 'happyfiles' ),
      'taxonomy'         => $tax_slug,
      'name'             => $tax_obj->name,
      'selected'         => isset( $wp_query->query[$tax_slug] ) ? $wp_query->query[$tax_slug] : null,
      'hierarchical'     => false,
      'hide_empty'       => false,
    ] );
  }

  /**
   * Get media category HTML to add to media modal
   */
  public static function get_categories_html() {
    ob_start();
    self::render();
    $html = ob_get_clean();

    wp_send_json_success( ['html' => $html] );
  }

  /**
   * Update term position (SortableJS.onEnd)
   *
   * @return void
   */
  public function update_term_position() {
    if ( ! Helpers::$can_edit ) {
      exit;
    }

    $term_ids = isset( $_POST['termIds'] ) && is_array( $_POST['termIds'] ) ? Helpers::sanitize_data( $_POST['termIds'] ) : [];

    foreach ( $term_ids as $index => $term_id ) {
      update_term_meta( intval( $term_id ), HAPPYFILES_POSITION, $index );
    }

    wp_send_json_success( [
      'term_ids' => $term_ids,
    ] );
  }

  /**
   * Update term parent (SortableJS.onEnd)
   *
   * @return array
   */
  public function update_term_parent() {
    if ( ! Helpers::$can_edit ) {
      exit;
    }
    
    $term_id = isset( $_POST['termId'] ) && ! empty( $_POST['termId'] ) ? intval( Helpers::sanitize_data( $_POST['termId'] ) ) : false;
    $parent_id = isset( $_POST['parentId'] ) && ! empty( $_POST['parentId'] ) ? intval( Helpers::sanitize_data( $_POST['parentId'] ) ) : 0;

    $update_response = wp_update_term( $term_id, HAPPYFILES_TAXONOMY, ['parent' => $parent_id] );
    
    if ( is_wp_error( $update_response ) ) {
      wp_send_json_error( ['error' => $update_response->get_error_message() ] );
    }

    wp_send_json_success( $update_response );
  }

  /**
   * Set term for uploaded attachment
   * 
   * @see admin.js:BeforeUpload
   *
   * @param integer $post_id
   * @return void
   */
  public function add_attachment( $post_id ) {
    if ( ! Helpers::$can_edit ) {
      return;
    }
    
    $attachment_term_id = isset( $_REQUEST['hfTermId'] ) ? intval( Helpers::sanitize_data( $_REQUEST['hfTermId'] ) ) : 0;

    if ( $attachment_term_id > 0 ) {
      wp_set_object_terms( $post_id, $attachment_term_id, HAPPYFILES_TAXONOMY, false );
    }
  }
  
  /**
   * Filter out 'uncategorized' attachment (View: Grid)
   * 
   * Value of '-1' for uncategorized media. Positive integer for custom media terms.
   *
   * @return array
   */
  public function ajax_query_attachments_args( $query = [] ) {

    // Media category term
    if ( isset( $query[HAPPYFILES_TAXONOMY] ) && is_numeric( $query[HAPPYFILES_TAXONOMY] ) ) {

      if ( isset( $query['tax_query'] ) && is_array( $query['tax_query'] ) ) {
        $query['tax_query']['relation'] = 'AND';
      } else {
        $query['tax_query'] = ['relation' => 'AND'];
      }

      $terms = $query[HAPPYFILES_TAXONOMY];

      // Uncategorized (value: -1)
      if ( $terms == -1 ) {
        $query['tax_query'][] = [
          'taxonomy' => HAPPYFILES_TAXONOMY,
          'operator' => 'NOT EXISTS',
        ];
      }

      // Media term ID
      else {
        $query['tax_query'][] = [
          'taxonomy'         => HAPPYFILES_TAXONOMY,
          'terms'            => $terms,
          'field'            => 'id',
          'include_children' => false,
        ];
      }

      unset( $query[HAPPYFILES_TAXONOMY] );
    }

    return $query;
  }

  /**
   * Get category terms and tree
   * 
   * To refresh categories on every modal open.
   *
   * @return array
   */
  public function get_category_terms_and_tree() {
    wp_send_json_success( [
      'terms'    => Helpers::get_category_terms(),
      'tree'     => Helpers::get_tree(),
    ] );
  }

  /**
   * Create new category
   *
   * @return array
   */
  public function create_new_category() {
    if ( ! Helpers::$can_edit ) {
      exit;
    }

    // Check if category is provided
    $new_category_name = isset( $_POST['newCategoryName']) && ! empty( $_POST['newCategoryName'] ) ? Helpers::sanitize_data( $_POST['newCategoryName'] ) : false;
    $parent_id = isset( $_POST['parentId']) && ! empty( $_POST['parentId'] ) ? intval( Helpers::sanitize_data( $_POST['parentId'] ) ) : 0;

    if ( ! $new_category_name ) {
      wp_send_json_error( ['error' => esc_html__( 'Please enter a category name.', 'happyfiles' )] );
    }

    $new_term = wp_insert_term( 
      esc_attr( trim( $new_category_name ) ), 
      HAPPYFILES_TAXONOMY,
      ['parent' => $parent_id]
    );

    if ( is_wp_error( $new_term ) ) {
      wp_send_json_error( ['error' => $new_term->get_error_message() ] );
    }

    // Update category terms
    $terms = Helpers::get_category_terms();

    wp_send_json_success( [
      'new_term' => $new_term,
      'terms'    => $terms,
      'tree'     => Helpers::get_tree(),
    ] );
  }

  /**
   * Rename category: Name and slug
   *
   * @return array
   */
  public function rename_category() {
    if ( ! Helpers::$can_edit ) {
      exit;
    }

    $new_category_name = isset( $_POST['newCategoryName'] ) ? esc_attr( Helpers::sanitize_data( $_POST['newCategoryName'] ) ) : false;
    $term_id = isset( $_POST['termId'] ) ? intval( Helpers::sanitize_data( $_POST['termId'] ) ) : false;

    if ( ! $new_category_name || ! $term_id ) {
      wp_send_json_error( ['error' => esc_html__( 'No category name or term ID provided.', 'happyfiles' )] );
    }

    $slug = sanitize_title( $new_category_name );

    $rename_response = wp_update_term( $term_id, HAPPYFILES_TAXONOMY, [
      'name' => $new_category_name,
      'slug' => $slug,
    ] );

    if ( is_wp_error( $rename_response ) ) {
      wp_send_json_error( ['error' => $rename_response->get_error_message() ] );
    }

    wp_send_json_success( [
      'term_id' => $term_id,
      'name'    => $new_category_name,
      'slug'    => $slug,
    ] );
  }

  /**
   * Delete category
   *
   * @return array
   */
  public function delete_category() {
    if ( ! Helpers::$can_edit ) {
      exit;
    }
    
    $term_id = isset( $_POST['termId'] ) ? intval( Helpers::sanitize_data( $_POST['termId'] ) ) : false;

    if ( ! $term_id ) {
      wp_send_json_error( ['error' => esc_html__( 'No category name or term ID provided.', 'happyfiles' )] );
    }

    $delete_response = wp_delete_term( $term_id, HAPPYFILES_TAXONOMY );

    if ( is_wp_error( $delete_response ) ) {
      wp_send_json_error( ['error' => $delete_response->get_error_message() ] );
    }

    // Get updated terms (with count)
    $terms = Helpers::get_category_terms();

    wp_send_json_success( [
      'term_id' => $term_id,
      'terms'   => $terms,
      'tree'    => Helpers::get_tree(),
    ] );
  }

  /**
   * When dropping item(s) on term folder
   *
   * @return array
   */
  public function update_attachment_terms() {
    if ( ! Helpers::$can_edit ) {
      exit;
    }

    // Check: Term ID provided
    $term_id = isset( $_POST['termId'] ) ? intval( Helpers::sanitize_data( $_POST['termId'] ) ) : false;
    $open_id = isset( $_POST['openId'] ) ? intval( Helpers::sanitize_data( $_POST['openId'] ) ) : false;
    $multiple_categories = get_option( HAPPYFILES_SETTING_MULTIPLE_CATEGORIES, false );

    if ( $term_id === false ) {
      wp_send_json_error( ['error' => esc_html__( 'Error: No term ID passed.', 'happyfiles' ) ] );
    }

    // Check: Item IDs provided
    $item_ids = isset( $_POST['itemId'] ) ? array_map( 'intval', explode( ',', Helpers::sanitize_data( $_POST['itemId'] ) ) ) : false;

    if ( ! count( $item_ids ) ) {
      wp_send_json_error( ['error' => esc_html__( 'Error: No item ID(s) passed.', 'happyfiles' ) ] );
    }

    foreach ( $item_ids as $item_id ) {
      $term_ids = wp_get_object_terms( $item_id, HAPPYFILES_TAXONOMY, ['fields' => 'ids'] );

      // Multiple categories enabled
      if ( $multiple_categories ) {
        
        if ( $term_id < 1 ) {
          // Remove from open term
          $term_index = array_search( $open_id, $term_ids );

          if ( is_int( $term_index ) ) {
            unset( $term_ids[$term_index] );
          } 
        }
        else {
          $term_ids[] = $term_id;
        }
      } 
      
      // One category per item
      else {
        // Dropped on 'All Files' or 'Uncategorized'
        if ( $term_id < 1 ) {
          $term_ids = [];
        } else {
          $term_ids = [$term_id];
        }
      }

      // Delete terms
      if ( ! count( $term_ids ) ) {
        wp_delete_object_term_relationships( $item_id, HAPPYFILES_TAXONOMY );
      }

      $term_reponse = wp_set_object_terms( $item_id, $term_ids, HAPPYFILES_TAXONOMY, false );

      if ( is_wp_error( $term_reponse ) ) {
        wp_send_json_error( ['error' => $term_reponse->get_error_message() ] );
      }
    }

    // Get fresh category terms with updated count
    $terms = Helpers::get_category_terms();

    $term_ids = [];

    // Update term count now
    foreach ( $terms as $term ) {
      $term_ids[] = $term->term_id;
      wp_update_term_count_now( [$term->term_id], HAPPYFILES_TAXONOMY );
    }

    wp_send_json_success( ['terms' => $terms] );
  }

  public function get_attachment_terms() {
    $terms = Helpers::get_category_terms();

    wp_send_json_success( ['terms' => $terms] );
  }

  public function get_item_categories() {
    $terms = wp_get_object_terms(
      intval( $_POST['itemId'] ),
      HAPPYFILES_TAXONOMY,
      ['orderby' => 'parent']
    );

    wp_send_json_success( $terms );
  }

  public function save_sidebar_width() {
    $sidebar_width = isset( $_POST['width'] ) && intval( Helpers::sanitize_data( $_POST['width'] ) ) >= 210 && intval( Helpers::sanitize_data( $_POST['width'] ) ) <= 600 ? intval( Helpers::sanitize_data( $_POST['width'] ) ) : false;

    if ( ! $sidebar_width  ) {
      wp_send_json_error( ['error' => esc_html__( 'Invalid sidebar width.', 'happyfiles' )] );
    }

    update_option( HAPPYFILES_DB_OPTION_WIDTH, $sidebar_width );

    wp_send_json_success( ['message' => esc_html__( 'Sidebar width updated.', 'happyfiles') ] );
  }

  public static function render() {
    ?>
    <div id="hf-media-wrapper" class="wrap">
      <div id="hf-media-inner">
        <div class="title-wrapper">
          <div class="title"><?php esc_html_e( 'Categories', 'happyfiles' ); ?></div>
        </div>

        <?php 
        $user_can_edit = Helpers::$can_edit;

        // Show toolbar only if user has full edit right right (set in happyfiles_user_roles)
        if ( $user_can_edit ) {
        ?>

        <div class="toolbar-wrapper">
          <div id="hf-new-category-toggle" class="create button">
            <i class="dashicons dashicons-plus-alt"></i>
            <span><?php esc_html_e( 'Create', 'happyfiles' ); ?></span>
          </div>

          <div id="hf-rename-category" class="rename button">
            <i class="dashicons dashicons-edit"></i>
            <span><?php esc_html_e( 'Rename', 'happyfiles' ); ?></span>
          </div>

          <div id="hf-delete-category" class="delete button">
            <i class="dashicons dashicons-trash"></i>
            <span><?php esc_html_e( 'Delete', 'happyfiles' ); ?></span>
          </div>
        </div>
        <?php 
        }
        else {
          echo '<div class="toolbar-wrapper info">' .  esc_html__( 'You can view, but not edit media categories.', 'happyfiles' ) . '</div>';
        }
        ?>
        
        <?php 
        $categories = Helpers::get_category_terms();

        if ( is_array( $categories ) && count( $categories ) <= 2 ) { ?>
        <div id="hf-no-category-notification-wrapper">
          <?php esc_html_e( 'Click the "Create" button above to add your first media category. Then start to drag and drop files into your category.', 'happyfiles' ); ?>
        </div>
        <?php } ?>

        <div id="hf-upgrade-notification-wrapper">
          <h3 class="title"><?php esc_html_e( 'Category Limit Reached!', 'happyfiles' ); ?></h3>
          <p><?php esc_html_e( 'This free version of HappyFiles allows you to create and manage up to 10 media categories. Get HappyFiles Pro for unlimited media categories and premium support.', 'happyfiles' ); ?></p>
          <a href="https://happyfiles.io/#download?utm_source=plugin&utm_medium=wp-admin" target="_blank"><?php esc_html_e( 'Get HappyFiles Pro', 'happyfiles' ); ?></a>
        </div>

        <?php if ( $user_can_edit ) { ?>
        <div id="hf-new-category-wrapper">
          <input type="text" name="hf-new-category-input" id="hf-new-category-input" autocomplete="off" placeholder="<?php esc_attr_e( 'New category name', 'happyfiles' ); ?>" spellcheck="false">
          <button class="button button-small" id="hf-new-category-cancel"><?php esc_html_e( 'Cancel', 'happyfiles' ); ?></button>
          <button class="button button-small button-primary" id="hf-new-category-create"><?php esc_html_e( 'Create', 'happyfiles' ); ?></button>
        </div>
        <?php } ?>

        <!-- NOTE: Populated via admin.js -->
        <ul id="hf-media-category-wrapper"></ul>

        <div id="hf-term-action-wrapper">
          <i id="hf-confirm" class="dashicons dashicons-yes"></i>
          <i id="hf-cancel" class="dashicons dashicons-no"></i>
        </div>
      </div>

      <?php if ( get_current_screen() && get_current_screen()->base === 'upload' ) { ?>
      <div id="hf-resizable">
        <i id="hf-toggle" class="dashicons dashicons-arrow-left"></i>
      </div>
      <?php } ?>

      <?php if ( $user_can_edit ) { ?>
      <ul id="hf-context-menu-wrapper">
        <li class="create"><?php esc_html_e( 'Create Category', 'happyfiles' ); ?></li>
        <li class="rename"><?php esc_html_e( 'Rename Category', 'happyfiles' ); ?></li>
        <li class="delete"><?php esc_html_e( 'Delete Category', 'happyfiles' ); ?></li>
      </ul>
      <?php } ?>

      <ul id="hf-context-menu-categories-wrapper"></ul>
    </div>
    <?php 
  }

  /**
   * Add term dropdown to media-new.php in WP admin
   */
  public function upload_ui_media_new() {
    if ( ! Helpers::$can_edit ) {
      return;
    }

    if ( is_admin() && get_current_screen() && get_current_screen()->base === 'media' ) {
      $tax_slug = HAPPYFILES_TAXONOMY;
      $tax_obj = get_taxonomy( $tax_slug );

      echo '<div id="hf-category-upload-wrapper">';
      echo '<p>' . esc_html__( 'Optional: Assign a category to your uploaded file(s):', 'happyfiles' ) . '</p>';

      echo '<p>';

      wp_dropdown_categories( [
        'id'               => 'hf-category-upload', // Has to be different from AJAX generated 'hf_category' filter
        // 'show_option_all'  => esc_html__( 'All Categories', 'happyfiles' ),
        'show_option_none' => esc_html__( 'Uncategorized', 'happyfiles' ),
        'taxonomy'         => $tax_slug,
        'name'             => $tax_obj->name,
        'hierarchical'     => true,
        'hide_empty'       => false,
      ] );

      echo '</p>';
      echo '</div>';
    }
  }
}