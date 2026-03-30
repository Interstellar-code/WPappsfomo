<?php

namespace Linksy\Inc\Admin\Partials\Dashboard;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Admin\Partials\Base;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Database\Database;

/**
 * Dashboard class.
 */
class Dashboard extends Base {

    use Utils;
    use AjaxActions;

    public function admin_init() {
        $this->page = $this->plugin_name;

        $post_types = Settings::get('suggestions_post_types', ['post', 'page']);
        foreach ($post_types as $type) {
            $this->action( 'trash_'.$type, 'on_post_deleted' );
            $this->action( 'publish_'.$type, 'on_post_created' );
        }
        
        $this->action(Ajax::prefix('dashboard_sync_posts'), 'linksy_dashboard_sync_posts');
        $this->action(Ajax::prefix('dashboard_get_post_summary'), 'linksy_dashboard_get_post_summary');
        $this->action(Ajax::prefix('dashboard_get_links_summary'), 'linksy_dashboard_get_links_summary');
        $this->action(Ajax::prefix('dashboard_get_keywords_rating'), 'linksy_dashboard_get_keywords_rating');
    }

    public function register_admin_page(){
    	add_submenu_page(
            $this->plugin_name, 
            'Dashboard', 
            'Dashboard', 
            'manage_options', 
            $this->plugin_name,
            array($this, 'display_page')
        );

        $this->enqueue_files();
    }

	public function enqueue_scripts(){
        parent::enqueue_scripts();

        $scripts = array(
            [
                $this->page.'-dashboard-post-stats',
                plugin_dir_url( __FILE__ )."_build/js/post-stats.js",
                array(),
            ],
            [
                $this->page.'-dashboard-link-stats',
                plugin_dir_url( __FILE__ )."_build/js/link-stats.js",
                array(),
            ],
            [
                $this->page.'-dashboard-anchor-cloud',
                plugin_dir_url( __FILE__ )."_build/js/anchor-cloud.js",
                array(),
            ],
            [
                $this->page.'-dashboard-keyword-rating',
                plugin_dir_url( __FILE__ )."_build/js/keyword-rating.js",
                array(),
            ],
            [
                $this->page.'-dashboard-domains',
                plugin_dir_url( __FILE__ )."_build/js/domains.js",
                array(),
            ],
            [
                $this->page.'-dashboard',
                plugin_dir_url( __FILE__ )."_build/js/app.js",
                array(
                    $this->page.'-dashboard-post-stats',
                    $this->page.'-dashboard-link-stats',
                    $this->page.'-dashboard-anchor-cloud',
                    $this->page.'-dashboard-keyword-rating',
                    $this->page.'-dashboard-domains',
                    $this->plugin_name.'-vue',
                ),
            ],
        );

        foreach ($scripts as $script) {
            wp_enqueue_script( $script[0], $script[1], $script[2], $this->plugin_version, true );
        }
    }

    public function enqueue_styles(){
        wp_enqueue_style(
            $this->page.'-dashboard',
            plugin_dir_url( __FILE__ )."_build/css/app.css",
            array(),
            $this->plugin_version,
            'all'
        );
    }

    public function display_page(){
        $this->render_view('dashboard');
    }

    public function on_post_created($post_id) {
        $post = new Post($post_id);

        try {
            $this->refresh_links($post);

            $api = new Api(Config::get(LINKSY_OPTION_API_KEY));
            $res = $api->post(LINKSY_API_URL.'posts/', [
                'post_id' => $post->get_ID(),
                'title' => $post->get_title(),
                'date' => $post->get_date('Y-m-d H:i:s'),
                'type' => $post->get_type(),
                'content' => $post->get_content(true)
            ]);

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1);
            }

            Database::table("linksy_posts_migrations_failed")->where('post_id', $post->get_ID())->delete();

            // todo: create batch for this
        } catch (Exception $e) {
            Config::set(LINKSY_ERROR_POST_ADD, $e->getMessage());
            Database::table("linksy_posts_migrations_failed")->insert([
                'batch' => 0,
                'post_id' => $post->get_ID(),
                'last_error' => $e->getMessage(),
            ]);
        }
    }

    public function on_post_deleted($post_id) {
        try {
            $api = new Api(Config::get(LINKSY_OPTION_API_KEY));
            $res = $api->delete(LINKSY_API_URL.'posts/'.$post_id);
            
            Database::table("linksy_links")->where('post_id', $post_id)->delete();
        } catch (Exception $e) {
            error_log($e->getMessage());
            Config::set(LINKSY_ERROR_POST_DELETE, $e->getMessage());
        }
    }
}