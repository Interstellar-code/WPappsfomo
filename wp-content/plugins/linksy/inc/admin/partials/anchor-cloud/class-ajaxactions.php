<?php

namespace Linksy\Inc\Admin\Partials\Anchor_Cloud;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Request;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Database\Database;

trait AjaxActions {
    public function linksy_anchor_cloud_get_links() {
		try {
            $search = Request::get('search', '');
            $filter = Request::get('filter', null, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $show_rating_filter = Config::get(LINKSY_OPTION_ANCHORS_SETUP_COMPLETE, false);

            $links = Database::table("linksy_links")->where('is_internal', '1')->where('to_post_id', '>','0')->where('is_broken', '0')->get();

            $anchors = [];

            foreach ($links as $k => $v) {
                $anchor_key = strtolower($v->anchor);

                if (!isset($anchors[$anchor_key])) {
                    $anchors[$anchor_key][] = [
                        'destination' => [
                            'id' => $v->to_post_id,
                            'score' => $v->score,
                        ],
                        'sources' => [ $v->post_id ]
                    ];

                    continue;
                }

                $anchor_index = null;
                foreach ($anchors[$anchor_key] as $i => $e) {
                    if ($e['destination']['id'] === $v->to_post_id) {
                        $anchor_index = $i;
                        break;
                    }
                }

                if (is_null($anchor_index)) {
                    $anchors[$anchor_key][] = [
                        'destination' => [
                            'id' => $v->to_post_id,
                            'score' => $v->score,
                        ],
                        'sources' => [ $v->post_id ]
                    ];
                } else {
                    if (!in_array($v->post_id, $anchors[$anchor_key][$anchor_index]['sources']))
                        $anchors[$anchor_key][$anchor_index]['sources'][] = $v->post_id;
                }
            }

            $data = [];

            foreach ($anchors as $k => $anchor) {
                $occurrences = [];

                foreach ($anchor as $v) {
                    $destination_post = new Post($v['destination']['id']);

                    if (!empty($destination_post->to_post())) {
                        $occurrences[] = [
                            'destination' => [
                                'id' => $destination_post->get_ID(),
                                'title' => $destination_post->get_title(),
                                'url' => $destination_post->get_link(),
                                'edit_url' => $destination_post->get_edit_link(),
                                'type' => $destination_post->get_type(),
                                'date' => $destination_post->get_date(),
                                'categories' => []
                                
                            ],
                            'score' => $v['destination']['score'],
                            'sources'     => array_map(function($source) {
                                $post = new Post($source);

                                return [
                                    'id' => $post->get_ID(),
                                    'title' => $post->get_title(),
                                    'url' => $post->get_link(),
                                    'edit_url' => $post->get_edit_link(),
                                ];
                            }, $v['sources'])
                        ];
                    }
                }

                if (count($occurrences) > 0) {
                    $data[] = [
                        'anchor' => $k,
                        'occurrences' => $occurrences
                    ];
                }
            }

            Ajax::success([
                "data" => $data,
                "show_rating_filter" => $show_rating_filter
            ]);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_anchor_cloud_get_categories() {
		try {
            $anchors =  json_decode(Request::post('ids', '[]', FILTER_DEFAULT));

            if (count($anchors) < 1) {
                throw new Exception("No ids to process", 1);
            }
            
            $categories = [];

            foreach ($anchors as $anchor) {
                if (!isset($categories[$anchor])) {
                    $categories[$anchor] = array_map(function($cat) {
                        return $cat->cat_ID;
                    }, get_the_category( $anchor ));
                }     
            }

            Ajax::success($categories);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_anchor_cloud_get_keywords() {
		try {
            $anchors =  json_decode(Request::post('anchors', '[]', FILTER_DEFAULT));
            if (count($anchors) < 1) {
                throw new Exception("No anchors to process", 1);
            }

            $api = new Api( Config::get(LINKSY_OPTION_API_KEY) );
            $scores = $api->post(LINKSY_API_URL.'posts/similarities/', [
                'anchors' => $anchors,
            ]);

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1); 
            }

            $data = [];
            $column_data = [];
            foreach ($anchors as $anchor) {
                $occurrences = [];
                $key = $anchor->phrase;
                $score = $this->get_anchor_score($key, $scores);

                foreach ($anchor->occurrences as $v) {
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

            Ajax::success($data);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}
}