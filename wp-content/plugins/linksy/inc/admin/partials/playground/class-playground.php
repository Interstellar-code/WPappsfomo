<?php

namespace Linksy\Inc\Admin\Partials\Playground;

use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Admin\Partials\Base;

/**
 * Playground class.
 */
class Playground extends Base {

    use AjaxActions;

    public function admin_init() {
        $this->page = $this->plugin_name.'-playground';

        $this->action( Ajax::prefix('playground_search'), 'linksy_playground_search');
        $this->action( Ajax::prefix('playground_get_phrases'), 'linksy_playground_get_phrases');
    }

    public function register_admin_page(){
    	add_submenu_page(
            $this->plugin_name, 
            'Playground', 
            'Playground', 
            'manage_options', 
			$this->page, 
			array($this, 'display_page')
        );

        $this->enqueue_files();
    }

	public function enqueue_scripts(){
        parent::enqueue_scripts();

         wp_enqueue_script(
            $this->page.'-copy',
            LINKSY_PLUGIN_ADMIN_URL."/assets/js/copy.vue.js",
            array( ),
            $this->plugin_version,
            true
        );

        wp_enqueue_script(
            $this->page.'-keyword-search',
            plugin_dir_url( __FILE__ )."_build/tabs/search.js",
            array( ),
            $this->plugin_version,
            true
        );
        
        wp_enqueue_script(
            $this->page,
            plugin_dir_url( __FILE__ )."_build/app.js",
            array(
                $this->plugin_name.'-vue',
                $this->page.'-keyword-search',
            ),
            $this->plugin_version,
            true
        );
    }

    public function enqueue_styles(){
        wp_enqueue_style(
            $this->page,
            plugin_dir_url( __FILE__ )."_build/app.css",
            array(),
            $this->plugin_version,
            'all'
        );
    }

    public function display_page(){
        $this->render_view('playground');
    }
}