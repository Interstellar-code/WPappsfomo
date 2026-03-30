<?php

namespace Linksy\Inc\Admin\Partials\Inbound_Links;

use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Request;

use Linksy\Inc\Admin\Partials\Base;

/**
 * Dashboard class.
 */
class InboundLinks extends Base {

    use Utils;
    use AjaxActions;

    public $postID;

    public function admin_init() {
        $this->postID = Request::get('post_id');
        $this->page = $this->plugin_name.'-inbound-links';

        $this->action( Ajax::prefix('inbound_links_get_posts'), 'linksy_inbound_links_get_posts');
        $this->action( Ajax::prefix('inbound_links_get_summary'), 'linksy_inbound_links_get_summary');
        $this->action( Ajax::prefix('inbound_links_get_orphans'), 'linksy_inbound_links_get_orphans');
        $this->action( Ajax::prefix('inbound_links_get_suggestions'), 'linksy_inbound_links_get_suggestions');
        $this->action( Ajax::prefix('inbound_links_apply_suggestions'), 'linksy_inbound_links_apply_suggestions');
    }

    public function register_admin_page(){
        add_submenu_page(
            $this->plugin_name, // 'options.php',
			'Add Inbound Links',
            'Add Inbound Links',
			'manage_options', 
			$this->page, 
			array($this, 'display_page')
        );

        $this->enqueue_files();
    }

	public function enqueue_scripts(){
        parent::enqueue_scripts();
        
        if ($this->postID) {
            wp_enqueue_script(
                $this->page.'-keywords',
                LINKSY_PLUGIN_ADMIN_URL."/assets/js/keywords.js",
                array( ),
                $this->plugin_version,
                true
            );

            wp_enqueue_script(
                $this->page,
                plugin_dir_url( __FILE__ )."_build/js/app.js",
                array( $this->page.'-keywords' ),
                $this->plugin_version,
                true
            );
        } else {
            wp_enqueue_script(
                $this->page,
                plugin_dir_url( __FILE__ )."_build/js/empty.js",
                array( ),
                $this->plugin_version,
                true
            );
        }
    }
    
    public function enqueue_styles(){
        parent::enqueue_styles();

        if ($this->postID) {
            wp_enqueue_style(
                $this->page,
                plugin_dir_url( __FILE__ )."_build/css/app.css",
                array(),
                $this->plugin_version,
                'all'
            );
        } else {
            wp_enqueue_style(
                $this->page,
                plugin_dir_url( __FILE__ )."_build/css/empty.css",
                array(),
                $this->plugin_version,
                'all'
            );
        }
    }

    public function display_page(){
        $data = [];

        if (!empty($this->postID)) {
            $data = $this->get_links_summary($this->postID);
        }
        
        $this->render_view('inbound-links', $data);
    }
}