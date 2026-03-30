<?php

namespace Linksy\Inc\Admin\Partials\Post;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Request;
use Linksy\Inc\Admin\Partials\Base;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Linksy\Post as PostHelper;

/**
 * Post class.
 */
class Post extends Base {

    use Utils;
    use AjaxActions;

    public $postID;

    public function admin_init() {
        $this->page = $this->plugin_name;
        $this->postID = Request::get('post', null);
        
        $this->action( Ajax::prefix('post_add'), 'linksy_post_add');
        $this->action( Ajax::prefix('post_ignore'), 'linksy_post_ignore');

        $this->action( Ajax::prefix('phrase_add'), 'linksy_phrase_add');
        $this->action( Ajax::prefix('phrase_ignore'), 'linksy_phrase_ignore');

        $this->action( Ajax::prefix('post_add_meta'), 'linksy_post_add_meta');

        $this->action( Ajax::prefix('post_get_summary'), 'linksy_post_get_summary');
        $this->action( Ajax::prefix('post_get_phrases'), 'linksy_post_get_phrases');
        $this->action( Ajax::prefix('post_get_content'), 'linksy_post_get_content');
        $this->action( Ajax::prefix('post_get_suggestions'), 'linksy_post_get_suggestions');
        $this->action( Ajax::prefix('post_apply_suggestions'), 'linksy_post_apply_suggestions');
        
        // $this->action( 'admin_post_linksy_post_apply_suggestions', 'linksy_post_apply_suggestions');

        $this->action( 'add_meta_boxes', 'add_phrase_container' );
    }

    public function is_current_page() {
        global $pagenow;
    	// global  $post;
        // (get_post_type($post) == 'post')

        if (($pagenow == 'post.php') && isset($_GET['post'])) {
            return true;
        }
        
        return  false;
    }

    public function register_admin_page(){
        if($this->is_current_page()){
            $this->enqueue_files();
            $this->action('admin_footer', 'display_page');
        }
    }

	public function enqueue_scripts(){
        parent::enqueue_scripts();

        $post = new PostHelper($this->postID);

        $scripts = array(
            [
                $this->page.'-post-keywords',
                LINKSY_PLUGIN_ADMIN_URL."/assets/js/keywords.js",
                array( ),
            ],
            // general
            
            // editors
            [
                $this->page.'-post-editor-block',
                plugin_dir_url( __FILE__ )."_build/editors/block.js",
                array( ),
            ],
            [
                $this->page.'-post-editor-classic',
                plugin_dir_url( __FILE__ )."_build/editors/classic.js",
                array( ),
            ],
            [
                $this->page.'-post-editor-html',
                plugin_dir_url( __FILE__ )."_build/editors/html.js",
                array( ),
            ],
            // phrase container and components
            [
                $this->page.'-post-phrase-home-card',
                plugin_dir_url( __FILE__ )."_build/js/phrase-container/home/card.js",
                array( ),
            ],
            [
                $this->page.'-post-phrase-home-quick-apply',
                plugin_dir_url( __FILE__ )."_build/js/phrase-container/home/quick-apply.js",
                array( ),
            ],
            [
                $this->page.'-post-phrase-home',
                plugin_dir_url( __FILE__ )."_build/js/phrase-container/home/index.js",
                array( $this->page.'-post-phrase-home-card', $this->page.'-post-phrase-home-quick-apply' ),
            ],
            [
                $this->page.'-post-phrase-filters',
                plugin_dir_url( __FILE__ )."_build/js/phrase-container/filters.js",
                array(  ),
            ],
            [
                $this->page.'-post-phrase-info',
                plugin_dir_url( __FILE__ )."_build/js/phrase-container/info.js",
                array(  ),
            ],
            [
                $this->page.'-post-phrase-settings',
                plugin_dir_url( __FILE__ )."_build/js/phrase-container/settings.js",
                array(  ),
            ],
            [
                $this->page.'-post-phrase-container',
                plugin_dir_url( __FILE__ )."_build/js/phrase-container/index.js",
                array(
                    $this->page.'-post-phrase-home',
                    $this->page.'-post-phrase-filters',
                    $this->page.'-post-phrase-info',
                    $this->page.'-post-phrase-settings',
                ),
            ],
            // scripts
            [
                $this->page.'-post-highlight',
                plugin_dir_url( __FILE__ )."_build/js/highlight.js",
                array( ),
            ],
            [
                $this->page.'-post',
                plugin_dir_url( __FILE__ )."_build/js/app.js",
                array(
                    $this->page.'-post-keywords',
                    $this->page.'-post-highlight',
                    $this->page.'-post-phrase-container',
                    $this->plugin_name.'-vue',
                ),
            ],
        );
        
        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_ID = '".$post->get_ID()."'");
        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_TYPE = ".json_encode($post->get_type()));
        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_CATEGORIES = ".json_encode(array_map(function($cat) {
            return (string)$cat->cat_ID;
        }, $post->get_categories())));

        $settings = array_merge($this->get_post_settings($post->get_ID()), Settings::many([
            'open_internal_link_in_new_tab',
            'add_destination_post_title_to_links' ,
            'type_to_skip',
            'no_of_type_to_skip',
            'suggestions_ignored_posts',
        ]));
        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_SETTINGS = ".json_encode($settings)."");

        $phrases = $this->get_post_phrases($post->get_ID(), $settings);
        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_PHRASES = ".json_encode($phrases)."");

        $types = Settings::get('suggestions_post_types', ['post', 'page']);
        wp_add_inline_script( $this->plugin_name, "var LINKSY_POSTS_TYPES = ".json_encode($types));
        wp_add_inline_script( $this->plugin_name, "var LINKSY_POSTS_CATEGORIES = ".json_encode(array_map(function($cat) {
            return [
                'id' => $cat->cat_ID,
                'name' => $cat->cat_name,
            ];
        }, get_categories())));

        foreach ($scripts as $script) {
            wp_enqueue_script( $script[0], $script[1], $script[2], $this->plugin_version, true );
        }
    }

    public function enqueue_styles(){
        wp_enqueue_style(
            $this->page.'-post',
            plugin_dir_url( __FILE__ )."_build/css/app.css",
            array(),
            $this->plugin_version,
            'all'
        );
    }

    public function display_page(){
        $this->render_view('post', [
            'post_id' => $this->postID
        ]);
    }

    public function add_phrase_container() {
        if($this->is_current_page()){
            add_meta_box(
                'linksy-phrase-container',
                'Linksy',
                [ $this, 'add_phrase_container_template' ],
                Settings::get('suggestions_post_types', ['post', 'page']),
                'side',
                'high'            
            );
        }
    }

    public function add_phrase_container_template( $data ) {
        include plugin_dir_path( __FILE__ ).'_build'.'/templates/phrase_container.php';
    }

    public function before_insert_post_data( $new, $old ) {
        if (isset($old['post_modified'])) {
            $new['post_modified'] = $old['post_modified'];
        }
        
        if (isset($old['post_modified_gmt'])) {
            $new['post_modified_gmt'] = $old['post_modified_gmt'];
        }
        return $new;
    }
}