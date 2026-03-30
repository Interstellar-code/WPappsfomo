<?php

namespace Linksy\Inc\Admin\Partials\Reports;

use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Admin\Partials\Base;
use Linksy\Inc\Helpers\Linksy\Settings;

/**
 * Reports class.
 */
class Reports extends Base {

    use AjaxActions;

    public function admin_init() {
        $this->page = $this->plugin_name.'-reports';

        $this->action( Ajax::prefix('reports_get_domains'), 'linksy_reports_get_domains');
        $this->action( Ajax::prefix('reports_get_internal_links'), 'linksy_reports_get_internal_links');
    }

    public function register_admin_page(){
    	add_submenu_page(
            $this->plugin_name,
            'Reports', 
			'Reports',
			'manage_options', 
			$this->page, 
			array($this, 'display_page')
        );

        $this->enqueue_files();
    }

	public function enqueue_scripts(){
        parent::enqueue_scripts();

        $types = Settings::get('suggestions_post_types', ['post', 'page']);

        $categories = array_map(function($cat) {
            return [
                'value' => $cat->cat_ID,
                'label' => $cat->cat_name,
            ];
        }, get_categories());

        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_TYPES = ".json_encode($types));
        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_CATEGORIES = ".json_encode($categories));

        wp_enqueue_script(
            $this->page.'-export',
            LINKSY_PLUGIN_ADMIN_URL."/assets/js/export.vue.js",
            array(  $this->plugin_name.'-vue' ),
            $this->plugin_version,
            true
        );
        
        wp_enqueue_script(
            $this->page.'-datatable',
            LINKSY_PLUGIN_ADMIN_URL."/assets/js/datatable.vue.js",
            array(  $this->plugin_name.'-vue' ),
            $this->plugin_version,
            true
        );

        wp_enqueue_script(
            $this->page.'-pagination',
            LINKSY_PLUGIN_ADMIN_URL."/assets/js/pagination.vue.js",
            array(  $this->plugin_name.'-vue' ),
            $this->plugin_version,
            true
        );

        wp_enqueue_script(
            $this->page.'-internal-links-report',
            plugin_dir_url( __FILE__ )."_build/js/tabs/internal-links-report.vue.js",
            array( ),
            $this->plugin_version,
            true
        );

        wp_enqueue_script(
            $this->page.'-domain-report',
            plugin_dir_url( __FILE__ )."_build/js/tabs/domain-report.vue.js",
            array( ),
            $this->plugin_version,
            true
        );
        
        wp_enqueue_script(
            $this->page,
            plugin_dir_url( __FILE__ )."_build/js/app.js",
            array(
                $this->page.'-export',
                $this->page.'-datatable',
                $this->page.'-internal-links-report',
                $this->page.'-domain-report'
            ),
            $this->plugin_version,
            true
        );
    }
    
    public function enqueue_styles(){
        parent::enqueue_styles();

        wp_enqueue_style(
            $this->page.'-datatable',
            LINKSY_PLUGIN_ADMIN_URL."/assets/css/datatable.vue.css",
            array( ),
            $this->plugin_version,
            'all'
        );
        
        wp_enqueue_style(
            $this->page,
            plugin_dir_url( __FILE__ )."_build/css/app.css",
            array(),
            $this->plugin_version,
            'all'
        );
    }

    public function display_page(){
        $this->render_view('reports');
    }
}