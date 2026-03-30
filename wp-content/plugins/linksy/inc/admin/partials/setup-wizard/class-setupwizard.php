<?php

namespace Linksy\Inc\Admin\Partials\Setup_Wizard;

use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Admin\Partials\Base;
use Linksy\Inc\Helpers\Linksy\Posts;
use Linksy\Inc\Helpers\Linksy\Settings;

/**
 * Dashboard class.
 */
class SetupWizard extends Base {

    use AjaxActions;

    public function admin_init() {
        $this->page = $this->plugin_name.'-setup';

        $this->action( Ajax::prefix('sync_posts'), 'linksy_setup_sync_posts');
        $this->action( Ajax::prefix('report_errors'), 'linksy_setup_report_errors');
        $this->action( Ajax::prefix('verify_plugin'), 'linksy_setup_verify_plugin');
        $this->action( Ajax::prefix('generate_links'), 'linksy_setup_generate_links');
        $this->action( Ajax::prefix('generate_keywords'), 'linksy_setup_generate_keywords');

        $this->action( Ajax::prefix('setup_init'), 'linksy_setup_init');
        $this->action( Ajax::prefix('setup_safe'), 'linksy_setup_safe');

        $this->action( Ajax::prefix('embbed_posts'), 'linksy_setup_embbed_posts');
    }

    public function register_admin_page(){
    	add_submenu_page(
            null, 
            'Setup', 
			'Setup',
			'manage_options', 
			$this->page, 
			array($this, 'display_page')
        );

        $this->enqueue_files();
    }

	public function enqueue_scripts(){
        parent::enqueue_scripts();
        
        wp_enqueue_script(
            $this->page,
            plugin_dir_url( __FILE__ )."_build/js/app.js",
            array(  $this->plugin_name.'-vue' ),
            $this->plugin_version,
            true
        );
    }
    
    public function enqueue_styles(){
        parent::enqueue_styles();
        
        wp_enqueue_style(
            $this->page,
            plugin_dir_url( __FILE__ )."_build/css/app.css",
            array(),
            $this->plugin_version,
            'all'
        );
    }

    public function display_page(){
        $published_posts = Posts::total([
            'numberposts' => -1,
            'post_status' => 'publish',
            'post_type'   => Settings::get('suggestions_post_types', ['post', 'page'])
        ]);

        $this->render_view('setup-wizard', [
            'token' => Config::get(LINKSY_OPTION_API_KEY),
            'posts_summary' =>  $published_posts,
        ]);
    }
}