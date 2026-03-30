<?php
namespace Linksy\Inc\Admin\Partials\Post;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Links;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Request;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Linksy\Utils;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Database\Database;

use Linksy\Inc\Admin\Partials\Post\Editors\Elementor;
use Linksy\Inc\Admin\Partials\Post\Editors\Thrive;
use Linksy\Inc\Admin\Partials\Post\Editors\Beaver;
use Linksy\Inc\Admin\Partials\Post\Editors\Cornerstone;
use Linksy\Inc\Admin\Partials\Post\Editors\Oxygen;

trait AjaxActions {
    public function linksy_post_get_phrases() {
        try {
            $postID = Request::postOrFail('post_id');

            $settings = array(
                'show_single_words' => (bool)get_post_meta($postID, 'linksy_show_single_words', true),
                'show_suggestions_used_phrases' => (bool)get_post_meta($postID, 'linksy_show_suggestions_used_phrases', true),
                'show_suggestions_ignored_phrases' => (bool)get_post_meta($postID, 'linksy_show_suggestions_ignored_phrases', true),
                'suggestions_ignored_post' => (bool)get_post_meta($postID, 'linksy_suggestions_ignored_post', true),
                'inbound_ignored_post' => (bool)get_post_meta($postID, 'linksy_inbound_ignored_post', true),
            );

            $phrases = $this->get_post_phrases($postID, $settings);
            
            if (count($phrases) < 1) {
                throw new Exception("nothing here"); 
            }

            Ajax::success($phrases);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_post_get_content() {
        try {
            $the_post = new Post(Request::postOrFail('post_id'));
            $the_content =  Str::strip_shortcodes($the_post->get_suggestable_content());

            Ajax::success($the_content);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_post_get_suggestions() {
        try {
            $postID = Request::post('post_id');
            $phrases = Request::post('phrases');
            $filters = Request::get('filters', null, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

            // settings: max outbout
            $max_post_outbound_links_cnt = Settings::get('max_outbound_links_per_post', -1);
            if ($max_post_outbound_links_cnt != -1) {
                $post_outbound_links_cnt = count(Database::table("linksy_links")->select('id')->where('post_id', $postID)->where('is_internal', 1)->get());
                if ($post_outbound_links_cnt >= $max_post_outbound_links_cnt) {
                    throw new Exception("max outbound link value($max_post_outbound_links_cnt) reached", 1);
                }
            }
            
            $phrases = array_filter( explode(',', $phrases), function($phrase) {
                return !empty($phrase);
            } );

            if (count($phrases) < 1) {
                return Ajax::success([]);
            }

            $api = new Api(Config::get(LINKSY_OPTION_API_KEY));
            $res = $api->post(LINKSY_API_URL.'posts/search/', [
                'q' => $phrases
            ]);

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1); 
            }

            $posts = [];

            $settings = Settings::many([
                'disable_two_way_linking',
                'disable_link_resuggestion',
                'ignore_post_older_than',
                'ignore_post_younger_than',
                'max_inbound_links_per_post',
                'suggestions_ignored_posts',
                'suggestions_ignored_categories'
            ]);

            foreach ($res as $i => $e) {
                $doc_count = 0;
                $documents = [];
                foreach($e['documents'] as $k => $v) {
                    if ($doc_count >= 5) {
                        break;
                    }

                    if ($v['post_id'] == $postID) {
                        continue;
                    }

                    $the_post = new Post($v['post_id']);

                    if (!empty($filters) && !empty($filters['types'])) {
                        if (!in_array($the_post->get_type(), $filters['types'])) {
                            continue;
                        }
                    }

                    if (!empty($filters) && !empty($filters['categories'])) {
                        if (!$the_post->has_category($filters['categories'])) {
                            continue;
                        }
                    }

                    if (!empty($filters) && !empty($filters['date'])) {
                        $daterange = explode(' - ', $filters['date']);

                        if ($the_post->is_before($daterange[0]) || $the_post->is_after($daterange[1])) {
                            continue;
                        }
                    }

                    if (!$this->is_post_suggestable($the_post, $settings)) {
                        continue;
                    }

                    // Add the condition to skip suggestions based on the 'disable_link_resuggestion' setting
                    if ($settings['disable_link_resuggestion']) {
                        // Check if the post has already been added to another post
                        if (count(Database::table("linksy_links")->select(['id', 'post_id'])->where('to_post_id', $the_post->get_ID())->get()) > 0) {
                            continue;
                        }
                    }

                    // disable two way linking for this post
                    if ($settings['disable_two_way_linking']) {
                        if (count(Database::table("linksy_links")->select(['id', 'post_id', 'to_post_id'])->where('post_id', $postID)->where('to_post_id', $the_post->get_ID())->get()) > 0) {
                            continue;
                        }
                    }

                    $documents[$k] = array_merge($v, [
                        'link' => $the_post->get_link()
                    ]);
                    $doc_count +=1;
                }

                $e['documents'] = array_values($documents);
                $posts[] = $e;
            }

            Ajax::success($posts);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_post_get_summary() {
        try {
            $post = new Post(Request::getorFail('post_id'));

            $links = $post->get_linksy_links();
            $keywords = $post->get_keywords(); //todo: usehelper

            $keyword_with_scores = [];

            if (count($keywords) > 0) {
                $api = new Api(Config::get(LINKSY_OPTION_API_KEY));
                $res = $api->post(LINKSY_API_URL.'posts/search/', [
                    'limit' => 0,
                    'q' => array_map(function($item) {
                            return $item['keyword'];
                    }, $keywords)
                ]);

                if (!$api->is_success()) {
                    throw new Exception($api->get_error(), 1); 
                }

                foreach ($res as $keyword) {
                    foreach ($keyword['documents'] as $document) {
                        $keyword_with_scores[] = [
                            'phrase' => $keyword['phrase'],
                            'score' => $document['score']
                        ];
                        break;
                    }
                }
            }

            Ajax::success([
                'links' => [
                    'inbound_links' => count($links['inbound_links']),
                    'outbound_links' => count($links['outbound_links']),
                    'external_links' => count($links['external_links']),
                ],
                'keywords' => $keyword_with_scores
            ]);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
    }

    public function linksy_post_add_meta() {
        try {
            $postID = Request::postorFail('post_id');
            $keys =  json_decode(Request::post('meta_keys', '[]', FILTER_DEFAULT));
            $values =  json_decode(Request::post('meta_values', '[]', FILTER_DEFAULT));

            if (count($keys) < 1) {
                throw new Exception("No keywords to process", 1);
            }
            
            for ($i=0; $i < count($keys); $i++) {
                update_post_meta($postID, 'linksy_'.$keys[$i], Settings::value($values[$i]) );
            }

            Ajax::success([]);

        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_post_add() {
        try {
            $link = Request::postorFail('link');
            $phrase = Request::postorFail('phrase');

            $postID = url_to_postid($link);
            if (!$postID) {
                throw new Exception("Post not found", 1); 
            }

            $api = new Api(Config::get(LINKSY_OPTION_API_KEY));
            $res = $api->post(LINKSY_API_URL.'posts/similarities/', [
                "anchors" => array(
                    [
                        "phrase" => $phrase,
                        "occurrences" => [$postID]
                    ],
                )
            ]);

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1); 
            }

            $score = 0;
            $post = new Post( $postID );

            foreach ($res as $v) {
                foreach ($v['documents'] as $document) {
                    if ($document['post_id'] == $post->get_ID()) {
                        $score = $document['score'] < 0? 0: $document['score'];
                        break;
                    }
                }
            }
            
            Ajax::success([
                'phrase' => $phrase,
                'document' => [
                    'link' => $link,
                    'score' => $score,
                    'custom' => true,
                    'post_id' => $post->get_ID(),
                    'post_title' => $post->get_title()
                ]
            ]);

        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_post_ignore() {
        try {
            $post = new Post(Request::postorFail('post_id'));
            
            $ignored_posts = Settings::get('suggestions_ignored_posts', []);

            array_push($ignored_posts, $post->get_link());

            Settings::set('suggestions_ignored_posts', $ignored_posts, true);

            Ajax::success([
                'post_id' => $post->get_ID(),
                'ignored_posts' => $ignored_posts
            ]);

        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_phrase_add() {
        try {
            Ajax::success('to do');
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_phrase_ignore() {
        try {
            $phrase = Request::postorFail('phrase');
            
            $ignored_phrases = Settings::get('suggestions_ignored_phrases', []);

            array_push($ignored_phrases, strtolower($phrase));

            Settings::set('suggestions_ignored_phrases', $ignored_phrases, true);

            Ajax::success([
                'phrase' => $phrase,
                'ignored_phrases' => $ignored_phrases
            ]);

        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_post_apply_suggestions() {
		try {
            $the_post = new Post(Request::postOrFail('post_id'));
            $the_content = $the_post->get_content();

            $ids = explode(',', Request::post('suggestion_ids', ''));
            $phrases = explode(',', Request::post('suggestion_phrases', ''));
            $sources = explode(',', Request::post('suggestion_sources', ''));

            $failed = [];
            $processed = [];

            // todo: settings

            $open_internal_link_in_new_tab = Settings::get('open_internal_link_in_new_tab', false);
            $add_destination_post_title_to_links = Settings::get('open_internal_link_in_new_tab', false);
            $update_post_modified_date = Settings::get('update_post_modified_date', false);


            foreach ($ids as $k => $v) {
                $post = new Post($v);

                $phrase_replace = '<a'.($open_internal_link_in_new_tab? ' target="_blank"' : '').($add_destination_post_title_to_links? ' title="'. $post->get_title() .'"' : '').' href="'.$post->get_link().'">'.$phrases[$k].'</a>';

                $the_content = Utils::insert_link_in_content($the_content, $sources[$k], $phrases[$k], $phrase_replace);

                if ($the_content === false) {
                    $failed[] = [
                        'id' => $v,
                        'phrase' => $phrases[$k],
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
                    'ID' => $the_post->get_ID(),
                    'post_content' => $the_content,
                ], true);

                if (!$update_post_modified_date)
                    $this->remove_filter('wp_insert_post_data', 'before_insert_post_data');

                if (is_wp_error($post->get_ID())) {
                    $failed[] = [
                        'id' => $v,
                        'phrase' => $phrases[$k],
                        'title' => $post->get_title(),
                        'errors' => $post->get_ID()->get_error_messages()
                    ];

                    continue;
                }

                Elementor::update_post($the_post->get_ID(), $the_content, $sources[$k], $phrases[$k], $phrase_replace);
                Thrive::update_post($the_post->get_ID(), $the_content, $sources[$k], $phrases[$k], $phrase_replace);
                Beaver::update_post($the_post->get_ID(), $the_content, $sources[$k], $phrases[$k], $phrase_replace);
                Cornerstone::update_post($the_post->get_ID(), $the_content, $sources[$k], $phrases[$k], $phrase_replace);
                Oxygen::update_post($post->get_ID(), $the_content, $sources[$k], $phrases[$k], $phrase_replace);

                $processed[] = [
                    'id' => $v,
                    'phrase' => $phrases[$k],
                ];
            }

            Ajax::success([
                'processed' => $processed,
                'failed' => $failed
            ]);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}
}