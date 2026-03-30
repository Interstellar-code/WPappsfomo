<?php

namespace Linksy\Inc\Admin\Partials\Keywords_Rating;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Admin\Partials\Base;
use Linksy\Inc\Helpers\Linksy\Keywords;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Database\Database;

/**
 * Dashboard class.
 */
class KeywordsRating extends Base {

    use AjaxActions;

    public function admin_init() {
        $this->page = $this->plugin_name.'-keywords-rating';

        $this->action( Ajax::prefix('keywords_rating_get_posts'), 'linksy_keywords_rating_get_posts');
        $this->action( Ajax::prefix('keywords_rating_get_scores'), 'linksy_keywords_rating_get_scores');
        $this->action( Ajax::prefix('keywords_rating_add_custom_keywords'), 'linksy_keywords_rating_add_custom_keywords');
        $this->action( Ajax::prefix('keywords_rating_remove_custom_keyword'), 'linksy_keywords_rating_remove_custom_keyword');
        $this->action( Ajax::prefix('keywords_rating_reset_keywords'), 'linksy_keywords_rating_reset_keywords');
        $this->action( Ajax::prefix('keywords_rating_get_keywords_score_cron'), 'get_keywords_score_cron');
        
        $this->action( 'linksy_keywords_rating_get_keywords_cron', 'get_keywords_cron' );
        $this->action( 'linksy_keywords_rating_get_keywords_score_cron', 'get_keywords_score_cron' );

        // schedulers
        if(!wp_get_schedule('linksy_keywords_rating_get_keywords_cron')){
            wp_schedule_event(time(), 'hourly', 'linksy_keywords_rating_get_keywords_cron');
        }

        if(!wp_get_schedule('linksy_keywords_rating_get_keywords_score_cron')){
            wp_schedule_event(time(), 'linksy_keywords_rating_interval', 'linksy_keywords_rating_get_keywords_score_cron');
        }
    }

    public function register_admin_page(){
    	add_submenu_page(
            $this->plugin_name,
			'Keywords Rating',
            'Keywords Rating',
			'manage_options', 
			$this->page, 
			array($this, 'display_page')
        );

        $this->enqueue_files();
    }
	
	public function enqueue_scripts(){
        $types = Settings::get('suggestions_post_types', ['post', 'page']);

        $categories = array_map(function($cat) {
            return [
                'value' => $cat->cat_ID,
                'label' => $cat->cat_name,
            ];
        }, get_categories());

        $scripts = array(
            [
                $this->page.'-export',
                LINKSY_PLUGIN_ADMIN_URL."/assets/js/export.vue.js",
                array( ),
            ],
            [
                $this->page.'-datatable',
                LINKSY_PLUGIN_ADMIN_URL."/assets/js/datatable.vue.js",
                array( ),
            ],
            [
                $this->page.'-pagination',
                LINKSY_PLUGIN_ADMIN_URL."/assets/js/pagination.vue.js",
                array( ),
            ],
            [
                $this->page.'',
                plugin_dir_url( __FILE__ )."_build/app.js",
                array(
                    $this->page.'-export',
                    $this->page.'-datatable',
                    $this->page.'-pagination',
                    $this->plugin_name.'-vue'
                ),
            ],
        );

        parent::enqueue_scripts();

        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_TYPES = ".json_encode($types));
        wp_add_inline_script( $this->plugin_name, "var LINKSY_POST_CATEGORIES = ".json_encode($categories));

        foreach ($scripts as $script) {
            wp_enqueue_script( $script[0], $script[1], $script[2], $this->plugin_version, true );
        }
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
        $this->render_view('keywords-rating', []);
    }

    public function get_keywords_cron() {
        try {
            error_log('starting: get_keywords_cron');
            $inserted_cnt = Keywords::insert(Keywords::get([
                'new' => true
            ]));
        } catch (Exception $e) {
            error_log($e->getMessage());
        } finally {
            error_log('finished: get_keywords_cron');
        }
    }

    public function get_keywords_score_cron() {
        try {
            error_log("starting: get_keywords_score_cron");
            $keywords = Database::table("linksy_keywords")->select(['id', 'post_id', 'keyword'])->whereNull('score')->limit(200)->get();

            if (count($keywords) > 0) {
                $anchors = [];
                foreach ($keywords as $keyword) {
                    $anchors[] = ['id' => $keyword->id, 'phrase' => $keyword->keyword, 'occurrences'=> [$keyword->post_id]];
                }

                $api = new Api( Config::get(LINKSY_OPTION_API_KEY) );
                $scores = $api->post(LINKSY_API_URL.'posts/similarities/', [
                    'anchors' => $anchors,
                ]);

                if (!$api->is_success()) {
                    throw new Exception($api->get_error(), 1); 
                }

                $data = [];
                foreach ($anchors as $anchor) {
                    $occurrences = [];
                    $key = $anchor['phrase'];
                    $score = $this->get_keyword_score($key, $scores);

                    $data[] = [
                        'id'      => $anchor['id'],
                        'keyword' => $key,
                        'post_id' => $anchor['occurrences'][0],
                        'score'   => $this->get_destination_score($anchor['occurrences'][0], $score)
                    ];
                }

                if (count($data) > 0) {
                    Keywords::update($data);
                }
            } else {
                Config::set(LINKSY_OPTION_KEYWORDS_SETUP_COMPLETE, true, true);
            }

            error_log("finished: get_keywords_score_cron => ". count($keywords));

        } catch (Exception $e) {
            error_log('error: get_keywords_score_cron => '. $e->getMessage());
        }
    }
}