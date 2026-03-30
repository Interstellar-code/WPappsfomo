<?php

namespace Linksy\Inc\Admin\Partials\Inbound_Links;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Request;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Linksy\Posts;
use Linksy\Inc\Helpers\Linksy\Utils;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Database\Database;
use Linksy\Inc\Admin\Partials\Reports\Helpers\Internal_Links_Report;

use Linksy\Inc\Admin\Partials\Post\Editors\Elementor;
use Linksy\Inc\Admin\Partials\Post\Editors\Thrive;
use Linksy\Inc\Admin\Partials\Post\Editors\Beaver;
use Linksy\Inc\Admin\Partials\Post\Editors\Cornerstone;
use Linksy\Inc\Admin\Partials\Post\Editors\Oxygen;

trait AjaxActions {
    public function linksy_inbound_links_get_posts() {
        global $wpdb;
		try {
            $q = Request::getOrFail('q');

            $args = [
                'numberposts' => -1,
                'post_status' => 'publish',
                'category'    => 0,
                'post_type'   => Settings::get('suggestions_post_types', ['post', 'page']),
                'sort_order'  => 'desc',
                'suppress_filters' => false,
            ];

            if ($ignore_post_older_than = Settings::get('ignore_post_older_than', null)) {
                $args['date_query'] = array(
                    'after' => date('Y-m-d', strtotime('-'.$ignore_post_older_than.' months')) 
                );
            }

            add_filter( 'posts_where', function( $where ) use ( $q ) {
                global $wpdb;
                return $where . $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like($q) . '%' );
            });

            Ajax::success(array_map(function($post) {
                return [
                    'ID' => $post->get_ID(),
                    'post_title' => $post->get_title(),
                ];
            },Posts::get( $args )));
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_inbound_links_get_summary() {
		try {
            $post_id = Request::getOrFail('post_id');
            
            $summary = $this->get_links_summary($post_id);

            Ajax::success($summary);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_inbound_links_get_orphans() {
		try {
            $post = get_post(Request::get('post_id'));
            $posts_with_inbound_links = array_map(function($n) {
                return $n->to_post_id;
            },Database::table("linksy_links")->whereNotNull('to_post_id')->select('to_post_id')->get());
            
            $args = [
                'numberposts' => 10,
                'post_status' => 'publish',
                'category'    => 0,
                'post_type'   => Settings::get('suggestions_post_types', ['post', 'page']),
                'sort_order'  => 'desc',
                'suppress_filters' => false,
                'post__not_in' => $posts_with_inbound_links
            ];

            if ($ignore_post_older_than = Settings::get('ignore_post_older_than', null)) {
                $args['date_query'] = array(
                    'after' => date('Y-m-d', strtotime('-'.$ignore_post_older_than.' months')) 
                );
            }

            if ($post) {
                $args['numberposts'] = 1;
                add_filter( 'posts_where', function( $where ) use ( $post ) {
                    global $wpdb;
                    return $where . $wpdb->prepare( " AND {$wpdb->posts}.post_date < %s", $post->post_date );
                });
            }

            $posts = array_map(function($post) {
                return [
                    'ID' => $post->get_ID(),
                    'post_title' => $post->get_title(),
                ];
            }, Posts::get( $args ));

            if (count($posts) < 1) {
                throw new Exception("No opharned post found", 1);
            }

            Ajax::success($posts);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_inbound_links_get_suggestions() {
		try {
            $post = new Post(Request::getOrFail('post_id'));
            $limit = Request::get('limit', 35);

            $token =  Config::get(LINKSY_OPTION_API_KEY);

            $api = new Api($token);
            $res = $api->get(LINKSY_API_URL.'posts/inbound-links', [
                'limit' => $limit,
                'post_id' => $post->get_ID(),
                // todo: get post types
                'allow_single_word' => (bool)get_post_meta($post->get_ID(), 'linksy_show_single_words', false)
            ]);

            if (!$api->is_success()) {
                throw new Exception($api->get_error()); 
            }

            $contents = [];
            $suggestions = [];
            $used_phrases = [];

            $anchors = $post->get_linksy_links();
            $inbound_links = array_map(function($n) {
                return $n->post_id;
            }, $anchors['inbound_links']);
            $outbound_links = array_map(function($n) {
                return $n->to_post_id;
            }, $anchors['outbound_links']);

            $disabled_two_way_linking = Settings::get('disable_two_way_linking', false);
            $disable_link_resuggestion = Settings::get('disable_link_resuggestion', true);
            $ignored_posts = Settings::get('suggestions_ignored_posts', []);
            $ignored_categories = Settings::get('suggestions_ignored_categories', []);
            $ignored_date_min = Settings::get('ignore_post_older_than', 0);
            $ignored_date_max = Settings::get('ignore_post_younger_than', 0);

            foreach ($res as $k => $v) {
                $suggestion_post_id = $v['post']['id'];

                if ($disable_link_resuggestion) {
                    if (in_array($suggestion_post_id, $inbound_links)) {
                        continue;
                    }
                }

                if ($disabled_two_way_linking) {
                    if (in_array($suggestion_post_id, $outbound_links)) {
                        continue;
                    }
                }

                $suggestion_post = new Post($suggestion_post_id);

                if (!$suggestion_post->is_published()) {
                    continue;
                }

                if (get_post_meta($suggestion_post_id, 'linksy_inbound_ignored_post', true) ) {
                    continue;
                }

                if (count($ignored_posts) > 0 ) {
                    if (in_array($suggestion_post->get_link(), $ignored_posts)) {
                        continue;
                    }
                }

                if (count($ignored_categories) > 0 ) {
                    $post_categories = array_map(function($cat) {return $cat->cat_ID;},$suggestion_post->get_categories());
                    if (count(array_intersect($post_categories, $ignored_categories)) === count($post_categories)) {
                        continue;
                    }
                }

                if ($ignored_date_min) {
                    if ( $suggestion_post->is_before(date('Y-m-d', strtotime('-'.$ignored_date_min.' months'))) ) {
                        continue;
                    }
                }

                if ($ignored_date_max) {
                    if ( $suggestion_post->is_after(date('Y-m-d', strtotime('-'.$ignored_date_max.' months'))) ) {
                        continue;
                    }
                }

                if (!isset($contents[$suggestion_post_id])) {
                    $contents[$suggestion_post_id] = Str::strip_shortcodes($suggestion_post->get_suggestable_content());
                }

                if (!isset($used_phrases[$suggestion_post_id])) {
                    $used_phrases[$suggestion_post_id] = array_values(array_map( function($anchor) {
                        return trim(strtolower($anchor['text']));
                    }, $suggestion_post->get_content_links()));
                }
                
                if (!isset($suggestions[$suggestion_post_id]['keywords']) || count($suggestions[$suggestion_post_id]['keywords']) < 5 ) {
                    $phrase = trim($v['post']['phrase']);

                    if (in_array(strtolower($phrase), $used_phrases[$suggestion_post_id])) {
                        continue;
                    }

                    $suggestion_keyword = [
                        'score' => $v['score'],
                        'phrase' => $phrase,
                    ];
                    
                    if (isset($suggestions[$suggestion_post_id]) || array_key_exists($suggestion_post_id, $suggestions)) {
                        $suggestions[$suggestion_post_id]['keywords'][] = $suggestion_keyword;
                    } else {
                        $suggestions[$suggestion_post_id] = [
                            'id' => $suggestion_post_id,
                            'title' => $v['post']['title'],
                            'link' => $suggestion_post->get_link(),
                            'edit_link' => $suggestion_post->get_edit_link(),
                            'keywords' => [$suggestion_keyword],
                            'content' => $contents[$suggestion_post_id],
                            'used_phrases' => $used_phrases[$suggestion_post_id],
                        ];
                    }
                }
            }
            Ajax::success(array_values($suggestions));
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_inbound_links_apply_suggestions() {
		try {
            $the_post = new Post(Request::postOrFail('post_id'));

            $ids = explode(',', Request::post('suggestion_ids', ''));
            $phrases = explode(',', Request::post('suggestion_phrases', ''));
            $sources = explode(',', Request::post('suggestion_sources', ''));

            $failed = [];
            $processed = [];

            $post_link = $the_post->get_link();
            $post_inbound_links_cnt = count(Database::table("linksy_links")->select('id')->where('to_post_id', $the_post->get_ID())->get());

            // settings
            $disable_two_way_linking = Settings::get('disable_two_way_linking', false);
            $disable_link_resuggestion = Settings::get('disable_link_resuggestion', true);
            $max_post_inbound_links_cnt = Settings::get('max_inbound_links_per_post', -1);
            $max_post_outbound_links_cnt = Settings::get('max_outbound_links_per_post', -1);
            $open_internal_link_in_new_tab = Settings::get('open_internal_link_in_new_tab', false);
            $update_post_modified_date = Settings::get('update_post_modified_date', false);

            foreach ($ids as $k => $v) {
                $post = new Post($v);
                $post_content = $post->get_content();

                if ($max_post_inbound_links_cnt != -1) {
                    if ($post_inbound_links_cnt >= $max_post_inbound_links_cnt) {
                        $failed[] = [
                            'id' => $v,
                            'title' => $post->get_title(),
                            'errors' => [
                                "max inbound link value($max_post_inbound_links_cnt) reached"
                            ]
                        ];

                        continue;
                    }
                }

                if ($max_post_outbound_links_cnt != -1) {
                    $post_outbound_links_cnt = count(Database::table("linksy_links")->select('id')->where('post_id', $v)->where('is_internal', 1)->get());

                    if ($post_outbound_links_cnt >= $max_post_outbound_links_cnt) {
                        $failed[] = [
                            'id' => $v,
                            'title' => $post->get_title(),
                            'errors' => [
                                "max outbound link value($max_post_outbound_links_cnt) reached"
                            ]
                        ];
    
                        continue;
                    }
                }

                if ($disable_link_resuggestion) {
                    if (count(Database::table("linksy_links")->select('id')->where('to_post_id', $the_post->get_ID())->get()) > 0) {
                        $failed[] = [
                            'id' => $v,
                            'title' => $post->get_title(),
                            'errors' => [
                                "link already exists"
                            ]
                        ];
                    }
                }

                if ($disable_two_way_linking) {
                    if (count(Database::table("linksy_links")->select('id')->where('post_id', $the_post->get_ID())->where('to_post_id', $v)->get()) > 0) {
                        $failed[] = [
                            'id' => $v,
                            'title' => $post->get_title(),
                            'errors' => [
                                "two way linking  disabled"
                            ]
                        ];
    
                        continue;
                    }
                }

                $phrase_replace = '<a'.($open_internal_link_in_new_tab? ' target="_blank"' : '').' href="'.$post_link.'">'.$phrases[$k].'</a>';

                $post_content = Utils::insert_link_in_content($post_content, $sources[$k], $phrases[$k], $phrase_replace);
                if ($post_content === false) {
                    $failed[] = [
                        'id' => $v,
                        'title' => $post->get_title(),
                        'errors' => [
                            'can not find -'.$sources[$k].'- in post'
                        ]
                    ];

                    continue;
                }

                if (!$update_post_modified_date)
                    $this->filter('wp_insert_post_data', 'before_insert_post_data', 10, 2);

                wp_update_post([
                    'ID' => $post->get_ID(),
                    'post_content' => $post_content,
                ]);

                if (!$update_post_modified_date)
                    $this->remove_filter('wp_insert_post_data', 'before_insert_post_data');

                if (is_wp_error($post->get_ID())) {
                    $failed[] = [
                        'id' => $v,
                        'title' => $post->post_title,
                        'errors' => $post->get_ID()->get_error_messages()
                    ];

                    continue;
                }

                Elementor::update_post($post->get_ID(), $the_content, $sources[$k], $phrases[$k], $phrase_replace);
                Thrive::update_post($post->get_ID(), $the_content, $sources[$k], $phrases[$k], $phrase_replace);
                Beaver::update_post($the_post->get_ID(), $the_content, $sources[$k], $phrases[$k], $phrase_replace);
                Cornerstone::update_post($post->get_ID(), $the_content, $sources[$k], $phrases[$k], $phrase_replace);
                Oxygen::update_post($post->get_ID(), $the_content, $sources[$k], $phrases[$k], $phrase_replace);

                $processed[] = [
                    'id' => $v,
                ];

                $post_inbound_links_cnt++;
            }

            Ajax::success([
                'processed' => $processed,
                'failed' => $failed
            ]);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
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