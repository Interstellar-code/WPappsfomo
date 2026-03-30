<?php
namespace Linksy\Inc\Admin\Partials\Keywords_Rating;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Request;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Linksy\Posts;
use Linksy\Inc\Helpers\Linksy\Keywords;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Linksy\Semantic;
use Linksy\Inc\Helpers\Database\Database;

trait AjaxActions {
    public function linksy_keywords_rating_get_posts() {
		try {
            $posts = [];

            $page = Request::get('page', 0);
            $limit = Request::get('limit', 10);
            $search = Request::get('search', '');
            $order = Request::get('order', null, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $filters = Request::get('filter', null, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

            $total = 0;
            $offset = $page * $limit;

            $has_custom_order = false;
            $has_custom_filter = false;

            $opts = [
                'numberposts' => $limit,
                'sort_order'  => 'asc',
                'post_status' => 'publish',
                'offset'      => $offset,
                'category'    => 0,
                'post_type'   => Settings::get('suggestions_post_types', ['post', 'page']),
                'date_query'  => [],
                'orderby'     => [],
            ];

            if (!empty($search)) {
                $opts['post__in'] = array_map(function($item){
                    return $item->ID;
                }, Database::table("posts")->select('ID')->where('post_status', 'publish')->whereLike('post_title', $search)->get());
            }

            if (!empty($order)) {
                $title_order = array_search('0', array_column($order, 'column'))?? false;
                if ($title_order !== false) {
                    $opts['orderby']['title'] = $order[$title_order]['dir'] ?? 'desc';
                }

                $score_order = array_search('3', array_column($order, 'column'))?? false;
                if ($score_order !== false) {
                    $has_custom_order = true;
                }
            }

            if (!empty($filters)) {
                if (!empty($filters['type'])) {
                    $opts['post_type'] = $filters['type'];
                }

                if (!empty($filters['category'])) {
                    $opts['category'] = $filters['category'];
                }

                if (!empty($filters['rating'])) {
                    $rating = Semantic::tagToScore($filters['rating']);

                    if (!empty($rating)) {
                        $min_rating = $rating[0]/100;
                        $max_rating = $rating[1]/100;

                        $active_providers = Database::table("linksy_keywords")->whereIn('provider', array_map(function($provider) {
                            return $provider['key'];
                        }, Keywords::supported_providers(['is_active' => true]) ));

                        $filter_ratings = array_map(function($item){
                            return $item->post_id;
                        }, $active_providers->select(['post_id'])->where('score', '>=', $min_rating)->where('score', '<=', $max_rating)->get());

                        $opts['post__in'] =  isset($opts['post__in'])? array_merge($filter_ratings,  $opts['post__in'] ) : $filter_ratings;
                    } else {
                        $filter_keywords = Keywords::get();

                        $filter_ratings = array_map(function($item){
                            return $item['post_id'];
                        }, $filter_keywords);

                        $opts['post__not_in'] =  isset($opts['post__not_in'])? array_merge($filter_ratings,  $opts['post__not_in'] ) : $filter_ratings;
                    }
                }

                if (!empty($filters['keyword'])) {
                    $filter_keywords = Keywords::get([
                        'q' => $filters['keyword']
                    ]);

                    $filter_keywords = array_map(function($item){
                        return $item['post_id'];
                    }, $filter_keywords);

                    $opts['post__in'] =  isset($opts['post__in'])? array_merge($filter_keywords,  $opts['post__in'] ) : $filter_keywords;   
                }

                if (!empty($filters['date'])) {
                    $filter_dates = explode(" - ", $filters['date']);// get_option('date_format')

                    $filter_dates[0] = date('Y-m-d', strtotime($filter_dates[0] . ' -1 day'));
                    $filter_dates[1] = date('Y-m-d', strtotime($filter_dates[1] . ' +1 day'));

                    $opts['date_query'][] = [
                        'after'   =>  $filter_dates[0],
                        'before'  => $filter_dates[1],
                    ];
                }
            }

            if ($has_custom_filter || $has_custom_order) { 
                $custom_posts = Posts::get(array_merge($opts, ['offset' => 0, 'numberposts' => - 1]));

                if ($has_custom_order) {
                    // todo:
                    $posts = [];
                }

                $total = count($custom_posts);

            } else {
                $posts = Posts::get( $opts );
                $posts_keywords = Keywords::get([
                    'groupby' => 'post',
                    'post__in' => array_map(function ($post) {
                        return $post->get_ID();
                    }, $posts),
                ]);
                
                $posts = array_map(function ($post) use ($posts_keywords) {
                    return [
                        'post_id'      => $post->get_ID(),
                        'title'        => $post->get_title(),
                        'date'         => $post->get_date(),
                        'type'         => $post->get_type(),
                        'link'         => $post->get_link(),
                        'edit_link'    => $post->get_edit_link(),

                        'categories'   => array_map( function($cat) { return $cat->name;}, $post->get_categories()),
                        'inbound_link' => admin_url('admin.php').'?page=Linksy-inbound-links&post_id='.$post->get_ID(),

                        'keywords' => isset($posts_keywords[$post->get_ID()])? $posts_keywords[$post->get_ID()] : []
                    ];
                }, $posts);
                $total = $limit > count($posts)?  $offset + count($posts) : Posts::total( array_merge($opts, ['numberposts' => - 1]) );
            }

            Ajax::success([
                "data" => $posts,
                "per_page" => $limit,
                "current_page" => $page,
                "last_page" => count($posts) < 1? 0 :ceil($total / $limit) - 1,
                "total" => $total,
                "show_rating_filter" => Config::get(LINKSY_OPTION_KEYWORDS_SETUP_COMPLETE, false)
            ]);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_keywords_rating_get_scores() {
		try {
            $keywords =  json_decode(Request::post('keywords', '[]', FILTER_DEFAULT));
            $saved_keywords = Database::table("linksy_keywords")->select(['id', 'post_id', 'keyword', 'provider', 'score'])->whereIN('(post_id, keyword)', array_map(function($item) {
                return [$item->post_id, $item->keyword];
            }, $keywords))->get();

            $data = [];

            $to_send = [];
            $to_create = [];
            $to_update = [];

            foreach ($saved_keywords as $keyword) {
                if (!is_null($keyword->score)) {
                    $data[] = [
                        'keyword' => $keyword->keyword,
                        'post_id' => $keyword->post_id,
                        'score' => $keyword->score,
                        'provider' => $keyword->provider,
                    ];
                } else {
                    $to_update[] = [
                        'id'      => $keyword->id,
                        'keyword' => $keyword->keyword,
                        'post_id' => $keyword->post_id,
                        'provider' => $keyword->provider,
                    ];
                }
            }


            if (count($data) >= count($keywords)) {
                Ajax::success($data);
            }
            
            $data_values = array_column($data, 'keyword');

            foreach ($keywords as $item) {
                if (array_search($item->keyword, $data_values) === false) {
                    $to_send[] = [
                        'phrase'      => $item->keyword,
                        'provider'    => $item->provider,
                        'occurrences' => [$item->post_id],
                    ];
                }
            }

            if (!count($to_send)) {
                Ajax::success($data);
            }

            $api = new Api( Config::get(LINKSY_OPTION_API_KEY) );
            $scores = $api->post(LINKSY_API_URL.'posts/similarities/', [
                'anchors' => $to_send,
            ]);

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1); 
            }

            foreach ($to_send as $keyword) {
                $key = $keyword['phrase'];
                $provider = $keyword['provider'];
                $post_id = $keyword['occurrences'][0];

                $score = $this->get_keyword_score($key, $scores);
                $post_score = $this->get_destination_score($post_id, $score);

                $data[] = [
                    'new'     => true,
                    'keyword'   => $key,
                    'post_id' => $post_id,
                    'score'   => $post_score,
                    'provider' => $provider,
                ];

                // if is to update
                $to_update_index = $this->get_keyword_by_post_and_value($to_update, $post_id, $key);
                if ($to_update_index != -1) {
                    $to_update[$to_update_index]['score'] = $post_score;
                } else {
                    $to_create[] = [
                        'score'   => $post_score,
                        'keyword' => $key,
                        'post_id' => $post_id,
                        'provider' => $provider,
                    ];
                }
                
            }

            if (count($to_update)) {
                Keywords::update($to_update);
            }

            if (count($to_create)) {
                Keywords::insert($to_create);
            }

            Ajax::success($data);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_keywords_rating_add_custom_keywords() {
		try {
            $post_id = Request::postOrFail('post_id');
            $provider = 'linksy_focus_keyword';
            $keywords =  json_decode(Request::post('keywords', '[]', FILTER_DEFAULT));

            if (count($keywords) < 1) {
                throw new Exception("No keywords to process", 1);
            }

            $api = new Api( Config::get(LINKSY_OPTION_API_KEY) );
            $scores = $api->post(LINKSY_API_URL.'posts/search/', [
                'limit' => -1,
                'q' => $keywords,
            ]);

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1); 
            }

            $data = [];
            foreach ($keywords as $key) {
                $score = $this->get_keyword_score($key, $scores);
                $post_score = $this->get_destination_score($post_id, $score);
                $saved = add_post_meta( $post_id, $provider, $key);

                if ($saved) {
                    $data[] = [
                        'keyword' => $key,
                        'post_id' => $post_id,
                        'score' => $post_score,
                        'provider' => $provider,
                    ];
                }
            }

            Keywords::insert($data);

            Ajax::success($data);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_keywords_rating_remove_custom_keyword() {
		try {
            $post_id = Request::postOrFail('post_id');
            $keyword =  Request::postOrFail('keyword');

            $deleted = delete_post_meta( $post_id, 'linksy_focus_keyword', $keyword);

            if (!$deleted) {
                throw new Exception("Failed to delete key", 1);
            }

            // todo: remove from linksy_keywords
            Ajax::success('done');
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_keywords_rating_reset_keywords() {
		try {
            $post_links = [];
            $posts_with_links = [];
            $post_ids =  json_decode(Request::post('posts', '[]', FILTER_DEFAULT));

            $keywords = Keywords::get([
                'post__in' => $post_ids,
                'groupby'  => 'post'
            ]);

            foreach ($keywords as $k => $links) {
                $posts_with_links[] = [
                    'post_id'      => $k,
                    'keywords'     => $links,
                ];
                
                foreach ($links as $l) {
                    $post_links[] = [
                        'post_id'  => $k,
                        'keyword'  => $l['keyword'],
                        'provider' => $l['provider'],
                    ];
                }
            }

            // clear keywords 
            Database::table("linksy_keywords")->whereIn('post_id', $post_ids)->delete();

            Keywords::insert($post_links);
            
            Ajax::success($posts_with_links);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    private function get_keyword_score($keyword, $scores) {
        foreach ($scores as $score) {
            if ($score['phrase'] == $keyword) {
                return $score;
            }
        }
	}

    private function get_destination_score($destination, $score) {
        foreach ($score['documents'] as $document) {
            if ($destination == $document['post_id']) {
                return $document['score'];
            }
        }

        return 0;
	}

    private function get_keyword_by_post_and_value($array, $post_id, $value) {
        foreach ($array as $k => $v) {
            if ($v['post_id'] == $post_id && $v['keyword'] == $value) {
                return $k;
            }
        }

        return -1;
	}
}