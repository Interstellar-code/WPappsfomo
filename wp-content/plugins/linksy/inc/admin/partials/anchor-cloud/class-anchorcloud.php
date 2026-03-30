<?php

namespace Linksy\Inc\Admin\Partials\Anchor_Cloud;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Database\Database;

use Linksy\Inc\Admin\Partials\Base;

class AnchorCloud extends Base {

    use Utils;
    use AjaxActions;

    public function admin_init() {
        $this->page = $this->plugin_name.'-anchor-cloud';

        $this->action( Ajax::prefix('anchor_cloud_get_links'), 'linksy_anchor_cloud_get_links');
        $this->action( Ajax::prefix('anchor_cloud_get_keywords'), 'linksy_anchor_cloud_get_keywords');
        $this->action( Ajax::prefix('anchor_cloud_get_categories'), 'linksy_anchor_cloud_get_categories');

		$this->action( 'linksy_anchor_cloud_get_keywords_cron', 'get_keywords_cron' );
        $this->action( Ajax::prefix('anchor_cloud_get_keywords_cron'), 'get_keywords_cron');

        // todo: create schedule for updating linksy links
        
        // schedulers
        if(!wp_get_schedule('linksy_anchor_cloud_get_keywords_cron')){
            wp_schedule_event(time(), 'linksy_anchor_cloud_interval', 'linksy_anchor_cloud_get_keywords_cron');
        }
    }

    public function register_admin_page(){
    	add_submenu_page(
            $this->plugin_name,
            'Anchors Cloud', 
			'Anchors Cloud',
			'manage_options', 
			$this->page, 
			array($this, 'display_page')
        );

        $this->enqueue_files();
    }

	public function enqueue_scripts(){
        parent::enqueue_scripts();
        
        $types = ['post', 'page'];

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
            array( ),
            $this->plugin_version,
            true
        );

        wp_enqueue_script(
            $this->page.'-datatable',
            LINKSY_PLUGIN_ADMIN_URL."/assets/js/datatable.vue.js",
            array( ),
            $this->plugin_version,
            true
        );

        wp_enqueue_script(
            $this->page.'-pagination',
            LINKSY_PLUGIN_ADMIN_URL."/assets/js/pagination.vue.js",
            array( ),
            $this->plugin_version,
            true
        );

        wp_enqueue_script(
            $this->page,
            plugin_dir_url( __FILE__ )."_build/app.js",
            array( 
                'jquery',
                $this->plugin_name.'-vue',
                $this->page.'-export',
                $this->page.'-datatable',
                $this->page.'-pagination',
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
            plugin_dir_url( __FILE__ )."_build/app.css",
            array(),
            $this->plugin_version,
            'all'
        );
    }

    public function display_page(){
        $this->render_view('anchor-cloud', []);
    }

    public function get_keywords_cron() {
        try {
            error_log("starting: get_anchors_score_cron");
            $links = Database::table("linksy_links")->select([
                'anchor',
                'score',
                'to_post_id'
            ])->where('is_internal', '1')->where('to_post_id', '>','0')->whereNull('score')->limit(200)->get();

            if (count($links) > 0) {
                $anchors = [];
                foreach ($links as $k => $v) {
                    if (!isset($anchors[$v->anchor])) {
                        $anchors[$v->anchor][] = $v->to_post_id;
                        continue;
                    }

                    $anchor_index = null;
                    foreach ($anchors[$v->anchor] as $i => $e) {
                        if ($e === $v->to_post_id) {
                            $anchor_index = $i;
                            break;
                        }
                    }

                    if (is_null($anchor_index)) {
                        $anchors[$v->anchor][] = $v->to_post_id;
                    }
                }

                $anchors_data = [];
                foreach ($anchors as $phrase => $occurrences) {
                    $anchors_data[] = [
                        'phrase' => $phrase,
                        'occurrences' => $occurrences
                    ];
                }

                $api = new Api( Config::get(LINKSY_OPTION_API_KEY) );
                $scores = $api->post(LINKSY_API_URL.'posts/similarities/', [
                    'anchors' => $anchors_data
                ]);

                if (!$api->is_success()) {
                    throw new Exception($api->get_error(), 1); 
                }

                $data = [];
                $column_data = [];
                foreach ($anchors_data as $anchor) {
                    $occurrences = [];
                    $key = $anchor['phrase'];
                    $score = $this->get_anchor_score($key, $scores);

                    foreach ($anchor['occurrences'] as $v) {
                        $occurrence_data = [
                            'id' => $v,
                            'score' => $this->get_destination_score($v, $score),
                        ];

                        $occurrences[] = $occurrence_data;

                        $column_data[] = array_merge(['anchor' => $key], $occurrence_data);
                    }

                    $data[] = [
                        'anchor' => $key,
                        'occurrences' => $occurrences
                    ];
                }

                $this->save_scores($column_data);
            } else {
                Config::set(LINKSY_OPTION_ANCHORS_SETUP_COMPLETE, true, true);
            }

            error_log("finished: get_anchors_score_cron => ". count($links));
            
        } catch (Exception $e) {
            error_log('error: get_anchors_score_cron => '. $e->getMessage());
        } 
    }
}