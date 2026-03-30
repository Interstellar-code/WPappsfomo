<?php

namespace Linksy\Inc\Admin\Partials\Settings;

use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Database\Database;
use Linksy\Inc\Helpers\Linksy\Settings as SettingsHelper;

use Linksy\Inc\Admin\Partials\Base;

class Settings extends Base {

    use AjaxActions;

    public function admin_init() {
        $this->page = $this->plugin_name.'-settings';

        $this->action( Ajax::prefix('settings_save'), 'linksy_settings_save');
        $this->action( Ajax::prefix('settings_all_posts'), 'linksy_settings_all_posts');
        $this->action( Ajax::prefix('settings_load_posts'), 'linksy_settings_load_posts');
        $this->action( Ajax::prefix('settings_save_posts'), 'linksy_settings_save_posts');
        $this->action( Ajax::prefix('settings_verify_plugin'), 'linksy_settings_verify_plugin');
    }

    public function register_admin_page(){
    	add_submenu_page(
            $this->plugin_name,
            'Settings', 
			'Settings',
			'manage_options', 
			$this->page, 
			array($this, 'display_page')
        );

        $this->enqueue_files();
    }

	public function enqueue_scripts(){
        parent::enqueue_scripts();
        
        $scripts = array(
            [
                $this->page.'-common',
                plugin_dir_url( __FILE__ )."_build/tabs/common.vue.js",
                array( ),
            ],
            [
                $this->page.'-licensing-settings',
                plugin_dir_url( __FILE__ )."_build/tabs/licensing.vue.js",
                array( ),
            ],
            [
                $this->page.'-posts-settings',
                plugin_dir_url( __FILE__ )."_build/tabs/posts.vue.js",
                array( ),
            ],
            [
                $this->page.'-general-settings',
                plugin_dir_url( __FILE__ )."_build/tabs/general.vue.js",
                array( ),
            ],
            [
                $this->page.'',
                plugin_dir_url( __FILE__ )."_build/app.js",
                array(
                    $this->plugin_name.'-vue'
                ),
            ],
        );

        $settings = [];
        $settings_from_db = Database::table("linksy_settings")->get();
        foreach ($settings_from_db as $key => $value) {
            $settings[$value->setting_key] = SettingsHelper::value($value->setting_value, $value->setting_value_type);
        }

        // Filter out the internally used post types such as revisions and wp_templates
        $types = array_keys(get_post_types([ 'public' => true ]));

        $categories = array_map(function($cat) {
            return [
                'id' => $cat->cat_ID,
                'name' => $cat->cat_name,
            ];
        }, get_categories());

        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_TYPES = ".json_encode($types));
        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_CATEGORIES = ".json_encode($categories));
        wp_add_inline_script( $this->plugin_name, "var LINKSY_SETTINGS = ".json_encode($settings));

        foreach ($scripts as $script) {
            wp_enqueue_script( $script[0], $script[1], $script[2], $this->plugin_version, true );
        }
    }

    public function enqueue_styles(){
        parent::enqueue_styles();
        
        wp_enqueue_style(
            $this->page,
            plugin_dir_url( __FILE__ )."_build/app.css",
            array(),
            $this->plugin_version,
            'all'
        );
    }

    public function display_page(){
        $this->render_view('settings', []);
    }
}